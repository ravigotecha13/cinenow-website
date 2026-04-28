/**
 * OTT Mid-roll ads — YouTube-style breaks that play INSIDE the existing Artplayer instance.
 *
 * On a cue crossing: remember resumeAt, pause, fetch ads, play them via OTTPlayerAds.runInPlayer,
 * then switch Artplayer back to the main URL and seek to resumeAt. No modal, no re-init.
 */
(function (global) {
  'use strict';

  var DEFAULT_SEEK_JUMP = 2.5;
  var DEFAULT_POST_RESUME_MS = 600;
  var CROSS_EPS = 0.05;

  function normalizeCuePoints(raw) {
    var list = [];
    if (Array.isArray(raw)) {
      list = raw;
    } else if (raw != null && typeof raw === 'object' && typeof raw.length === 'number') {
      for (var i = 0; i < raw.length; i++) list.push(raw[i]);
    }
    var out = [];
    var seen = {};
    for (var j = 0; j < list.length; j++) {
      var n = Number(list[j]);
      if (!Number.isFinite(n) || n <= 0) continue;
      var key = Math.round(n * 100) / 100;
      if (seen[key]) continue;
      seen[key] = true;
      out.push(key);
    }
    out.sort(function (a, b) {
      return a - b;
    });
    return out;
  }

  function cueKey(t) {
    return Math.round(Number(t) * 100) / 100;
  }

  /**
   * @param {object} options
   * @param {object} options.art Artplayer instance
   * @param {number[]} options.cuePoints
   * @param {string} options.mainVideoUrl URL to switch back to after each mid-roll
   * @param {object} [options.playerAds] window.OTTPlayerAds (required for in-player mode)
   * @param {object} [options.ottPreRoll] window.OTTPreRoll (used for fetchAds + selectAdsForPreRoll)
   * @param {string} options.apiBaseUrl
   * @param {string} options.csrfToken
   * @param {(string|number)} options.contentId
   * @param {string} options.contentType
   * @param {(string|number)} [options.categoryIdValue]
   * @param {number} [options.initialContentTime] Resume position — cues before this are marked as consumed.
   * @param {HTMLElement} [options.container] Toggled with .ott-inplayer-ad-phase during breaks.
   * @param {HTMLElement} [options.playerSlot] Used to detect fullscreen state.
   * @param {HTMLElement} [options.overlayHost] For corner overlay helper.
   */
  function OTTMidRollManager(options) {
    if (!options || !options.art) {
      throw new Error('OTTMidRollManager: options.art is required');
    }
    this.art = options.art;
    this.playerAds = options.playerAds || global.OTTPlayerAds;
    this.ottPreRoll = options.ottPreRoll || global.OTTPreRoll;
    this.mainVideoUrl = options.mainVideoUrl || '';
    this.apiBaseUrl = options.apiBaseUrl || '';
    this.csrfToken = options.csrfToken || '';
    this.contentId = options.contentId;
    this.contentType = options.contentType;
    this.categoryIdValue = options.categoryIdValue;
    this.cuePoints = normalizeCuePoints(options.cuePoints);
    this.container = options.container || null;
    this.playerSlot = options.playerSlot || null;
    this.overlayHost = options.overlayHost || options.container || null;
    this.seekJumpSec =
      options.seekJumpSec != null ? Number(options.seekJumpSec) : DEFAULT_SEEK_JUMP;
    this.postResumeCooldownMs =
      options.postResumeCooldownMs != null
        ? Number(options.postResumeCooldownMs)
        : DEFAULT_POST_RESUME_MS;
    this.initialContentTime =
      options.initialContentTime != null && Number.isFinite(Number(options.initialContentTime))
        ? Number(options.initialContentTime)
        : null;
    this.placementPrefs = options.placementPrefs || ['player', 'movie_detail', 'movie_detail_page'];

    this.played = new global.Set();
    this.prevTime = 0;
    this.inAdBreak = false;
    this.resumeCooldownUntil = 0;
    this._pendingBreak = null;
    this._detached = false;

    this._onTimeUpdate = this._onTimeUpdate.bind(this);
    this._onEnded = this._onEnded.bind(this);
    this._onDestroy = this._onDestroy.bind(this);
  }

  OTTMidRollManager.prototype._emit = function (detail) {
    try {
      global.dispatchEvent(new CustomEvent('ott:midroll', { detail: detail }));
    } catch (e) {}
  };

  OTTMidRollManager.prototype._readCurrentTime = function () {
    try {
      if (this.art && this.art.video && Number.isFinite(this.art.video.currentTime)) {
        return this.art.video.currentTime;
      }
      if (this.art && Number.isFinite(this.art.currentTime)) {
        return this.art.currentTime;
      }
    } catch (e) {}
    return 0;
  };

  OTTMidRollManager.prototype._seekTo = function (t) {
    try {
      if (typeof this.art.seek === 'function') {
        this.art.seek(t);
        return;
      }
    } catch (e) {}
    try {
      this.art.currentTime = t;
    } catch (e2) {}
    try {
      if (this.art.video) this.art.video.currentTime = t;
    } catch (e3) {}
  };

  OTTMidRollManager.prototype._exitFullscreenIfInPlayer = function () {
    try {
      if (
        document.fullscreenElement &&
        this.playerSlot &&
        (document.fullscreenElement === this.playerSlot ||
          document.fullscreenElement.contains(this.playerSlot))
      ) {
        document.exitFullscreen();
      }
    } catch (e) {}
  };

  OTTMidRollManager.prototype._fetchQueueForCue = function () {
    var self = this;
    if (!self.ottPreRoll || typeof self.ottPreRoll.fetchAds !== 'function') {
      return Promise.resolve([]);
    }
    return self.ottPreRoll
      .fetchAds({
        contentId: self.contentId,
        contentType: self.contentType,
        categoryId: self.categoryIdValue,
        baseUrl: self.apiBaseUrl,
        csrfToken: self.csrfToken,
        timeoutMs: 12000,
      })
      .then(function (rows) {
        var selected = self.ottPreRoll.selectAdsForPreRoll(
          rows,
          self.placementPrefs,
          self.contentId,
          self.categoryIdValue,
          8
        );
        if (self.playerAds && typeof self.playerAds.filterPlayableVideoAds === 'function') {
          return self.playerAds.filterPlayableVideoAds(selected);
        }
        return selected;
      })
      .catch(function () {
        return [];
      });
  };

  OTTMidRollManager.prototype._runBreakAt = function (cueTime) {
    var self = this;
    if (this.inAdBreak || this._detached) return Promise.resolve();
    if (!this.playerAds || typeof this.playerAds.runInPlayer !== 'function') {
      self._emit({ event: 'midroll_error', reason: 'OTTPlayerAds missing', cueTime: cueTime });
      return Promise.resolve();
    }

    this.inAdBreak = true;
    this.played.add(cueKey(cueTime));

    var resumeAt = this._readCurrentTime();
    this._emit({ event: 'midroll_start', cueTime: cueTime, resumeAt: resumeAt });

    self._exitFullscreenIfInPlayer();
    try {
      self.art.pause();
    } catch (e) {}

    return self
      ._fetchQueueForCue()
      .then(function (queue) {
        if (!queue || !queue.length) {
          self._emit({ event: 'midroll_empty', cueTime: cueTime });
          return { played: 0, total: 0 };
        }
        return self.playerAds.runInPlayer(self.art, queue, {
          baseUrl: self.apiBaseUrl,
          maxAdDurationMs: 4 * 60 * 1000,
          container: self.container,
          onAnalytics: function (evt) {
            try {
              global.dispatchEvent(
                new CustomEvent('ott:preroll', {
                  detail: Object.assign({}, evt, { midrollCue: cueTime }),
                })
              );
            } catch (err) {}
          },
        });
      })
      .catch(function () {
        self._emit({ event: 'midroll_fetch_error', cueTime: cueTime });
      })
      .finally(function () {
        if (self._detached) {
          self.inAdBreak = false;
          return;
        }
        /* Switch back to the movie and seek to the captured position. */
        try {
          if (typeof self.art.switchUrl === 'function') {
            self.art.switchUrl(self.mainVideoUrl);
          } else {
            self.art.url = self.mainVideoUrl;
          }
        } catch (eSwap) {}
        setTimeout(function () {
          if (self._detached) return;
          self._seekTo(resumeAt);
          try {
            var p = self.art.play && self.art.play();
            if (p && typeof p.catch === 'function') p.catch(function () {});
          } catch (ePlay) {}
          self.prevTime = resumeAt;
          self.inAdBreak = false;
          self.resumeCooldownUntil = Date.now() + self.postResumeCooldownMs;
          self._emit({ event: 'midroll_complete', cueTime: cueTime, resumeAt: resumeAt });
        }, 120);
      });
  };

  OTTMidRollManager.prototype._burnCuesInInterval = function (lo, hi) {
    for (var i = 0; i < this.cuePoints.length; i++) {
      var t = this.cuePoints[i];
      if (t > lo && t <= hi && !this.played.has(cueKey(t))) {
        this.played.add(cueKey(t));
        this._emit({ event: 'midroll_skipped_seek', cueTime: t, from: lo, to: hi });
      }
    }
  };

  OTTMidRollManager.prototype._onTimeUpdate = function () {
    if (this._detached || !this.cuePoints.length) return;
    if (this.inAdBreak) {
      this.prevTime = this._readCurrentTime();
      return;
    }
    if (Date.now() < this.resumeCooldownUntil) {
      this.prevTime = this._readCurrentTime();
      return;
    }

    var ct = this._readCurrentTime();
    var prev = this.prevTime;
    var dt = ct - prev;

    /*
     | NOTE: Do NOT auto-clear consumed cues on a currentTime → 0 snap.
     | Artplayer's switchQuality / subtitle switch briefly resets the <video> before
     | restoring position, which looks identical to a "restart from zero". Clearing cues
     | there would replay every ad after a setting change. Only `video:ended` clears.
     */

    if (dt > this.seekJumpSec) {
      this._burnCuesInInterval(prev, ct);
    } else if (dt < -this.seekJumpSec) {
      /* backward seek: do not replay consumed cues */
    } else {
      for (var i = 0; i < this.cuePoints.length; i++) {
        var t = this.cuePoints[i];
        var ck = cueKey(t);
        if (this.played.has(ck)) continue;
        if (prev < t - CROSS_EPS && ct >= t - CROSS_EPS) {
          if (this._pendingBreak) break;
          var pending = this._runBreakAt(t);
          this._pendingBreak = pending;
          var self = this;
          pending.finally(function () {
            if (self._pendingBreak === pending) {
              self._pendingBreak = null;
            }
          });
          break;
        }
      }
    }

    this.prevTime = ct;
  };

  OTTMidRollManager.prototype._onEnded = function () {
    this.played.clear();
    this.prevTime = 0;
  };

  OTTMidRollManager.prototype._onDestroy = function () {
    this.detach();
  };

  OTTMidRollManager.prototype.attach = function () {
    if (this._detached) return;
    if (!this.cuePoints.length) return;
    var read = this._readCurrentTime();
    var start =
      this.initialContentTime != null
        ? Math.max(read, this.initialContentTime)
        : read;
    for (var k = 0; k < this.cuePoints.length; k++) {
      var c = this.cuePoints[k];
      if (c < start - CROSS_EPS) {
        this.played.add(cueKey(c));
      }
    }
    this.prevTime = read;
    this.art.on('video:timeupdate', this._onTimeUpdate);
    this.art.on('video:ended', this._onEnded);
    try {
      this.art.on('destroy', this._onDestroy);
    } catch (e) {}
  };

  OTTMidRollManager.prototype.detach = function () {
    if (this._detached) return;
    this._detached = true;
    try {
      if (this.container) {
        this.container.classList.remove('ott-inplayer-ad-phase');
      }
    } catch (e) {}
    if (this.art && typeof this.art.off === 'function') {
      try {
        this.art.off('video:timeupdate', this._onTimeUpdate);
      } catch (e) {}
      try {
        this.art.off('video:ended', this._onEnded);
      } catch (e2) {}
      try {
        this.art.off('destroy', this._onDestroy);
      } catch (e3) {}
    }
  };

  /** Non-blocking corner overlay (unchanged API). */
  OTTMidRollManager.prototype.showCornerOverlay = function (opts) {
    opts = opts || {};
    var host = this.overlayHost;
    if (!host || !opts.html) return null;
    var ms = opts.durationMs != null ? Number(opts.durationMs) : 15000;
    var el = document.createElement('div');
    el.className = opts.className || 'ott-midroll-corner-overlay';
    el.setAttribute('role', 'complementary');
    el.style.cssText = [
      'position:absolute',
      'right:12px',
      'bottom:52px',
      'max-width:min(280px,42vw)',
      'z-index:90',
      'pointer-events:auto',
      'box-sizing:border-box',
    ].join(';');
    el.innerHTML = opts.html;

    var close = document.createElement('button');
    close.type = 'button';
    close.textContent = '\u00d7';
    close.setAttribute('aria-label', 'Close');
    close.style.cssText =
      'position:absolute;top:4px;right:4px;border:0;background:rgba(0,0,0,.5);color:#fff;width:28px;height:28px;border-radius:4px;cursor:pointer;line-height:1';
    el.appendChild(close);

    host.appendChild(el);
    var removed = false;
    function remove() {
      if (removed) return;
      removed = true;
      try {
        el.remove();
      } catch (e) {}
    }
    close.addEventListener('click', remove);
    var timer = ms > 0 ? setTimeout(remove, ms) : null;
    return { el: el, close: remove, timer: timer };
  };

  global.OTTMidRollManager = OTTMidRollManager;
})(typeof window !== 'undefined' ? window : typeof globalThis !== 'undefined' ? globalThis : this);
