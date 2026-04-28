/**
 * OTT in-player ads — plays ad queue INSIDE the existing Artplayer instance.
 *
 * Pattern: switchUrl → play ad → on ended/skip → next ad → restore main URL + resume time.
 *
 * Plays MP4 / HLS video ads only. Image / YouTube ads are skipped
 * (they cannot render inside a <video> element).
 */
(function (global) {
  'use strict';

  var DEFAULT_SKIP_AFTER_SECONDS = 10;
  var DEFAULT_MAX_AD_MS = 4 * 60 * 1000;

  function resolveMediaUrl(media, baseUrl) {
    var m = media == null ? '' : String(media).trim();
    if (!m) return '';
    if (/^https?:\/\//i.test(m) || m.indexOf('data:') === 0 || m.indexOf('blob:') === 0) return m;
    if (m.indexOf('//') === 0) {
      return (global.location && global.location.protocol ? global.location.protocol : 'https:') + m;
    }
    var b = (baseUrl || '').replace(/\/$/, '');
    return m.indexOf('/') === 0 ? b + m : b + '/' + m.replace(/^\/+/, '');
  }

  function isPlayableVideoAd(ad) {
    if (!ad || ad.type !== 'video' || !ad.media) return false;
    if (/youtu\.?be/i.test(ad.media)) return false;
    return true;
  }

  function filterPlayableVideoAds(queue) {
    return (queue || []).filter(isPlayableVideoAd);
  }

  function getPlayerRoot(art) {
    try {
      if (art && art.template && art.template.$player) return art.template.$player;
    } catch (e) {}
    try {
      if (art && art.container) return art.container;
    } catch (e2) {}
    return null;
  }

  function createAdUi(hostEl, strings) {
    strings = strings || {};
    var skipLabel = strings.skipLabel || 'Skip Ad';
    var adPrefix = strings.adPrefix || 'Ad';
    var adsSeparator = strings.adsSeparator || 'of';
    var timerUnit = strings.timerUnit || 's';

    var ui = document.createElement('div');
    ui.className = 'ott-inplayer-ad-ui';
    ui.style.cssText = [
      'position:absolute',
      'inset:0',
      'z-index:60',
      'pointer-events:none',
      'display:none'
    ].join(';');

    ui.innerHTML = [
      '<a class="ott-inplayer-ad-click" target="_blank" rel="noopener"',
      ' style="position:absolute;inset:0;pointer-events:auto;background:transparent;display:none;"></a>',
      '<div class="ott-inplayer-ad-label"',
      ' style="position:absolute;top:14px;inset-inline-start:18px;color:#fff;',
      'font:500 13px/1.3 system-ui,Arial,sans-serif;text-shadow:0 1px 4px rgba(0,0,0,.8);',
      'display:none;pointer-events:none;"></div>',
      '<div class="ott-inplayer-ad-timer"',
      ' style="position:absolute;bottom:72px;inset-inline-end:20px;color:#fff;',
      'background:rgba(0,0,0,.65);padding:5px 10px;border-radius:4px;',
      'font:500 13px/1.3 system-ui,Arial,sans-serif;display:none;pointer-events:none;"></div>',
      '<button type="button" class="ott-inplayer-ad-skip"',
      ' style="position:absolute;bottom:72px;inset-inline-end:20px;color:#fff;',
      'background:rgba(0,0,0,.85);border:0;padding:8px 16px;border-radius:4px;',
      'cursor:pointer;font:600 13px/1.3 system-ui,Arial,sans-serif;',
      'pointer-events:auto;display:none;">' + skipLabel + '</button>'
    ].join('');

    hostEl.appendChild(ui);
    return {
      root: ui,
      click: ui.querySelector('.ott-inplayer-ad-click'),
      label: ui.querySelector('.ott-inplayer-ad-label'),
      timer: ui.querySelector('.ott-inplayer-ad-timer'),
      skip: ui.querySelector('.ott-inplayer-ad-skip'),
      strings: {
        adPrefix: adPrefix,
        adsSeparator: adsSeparator,
        timerUnit: timerUnit,
      },
    };
  }

  /** Lock seek/controls UI so the user cannot scrub/skip ahead during an ad. */
  function lockPlayerChrome(art) {
    var root = getPlayerRoot(art);
    if (!root) return function () {};
    root.classList.add('ott-inplayer-ad-active');
    var cls = 'ott-inplayer-ad-locked';
    return function unlock() {
      root.classList.remove('ott-inplayer-ad-active');
      root.classList.remove(cls);
    };
  }

  function setUrlOnArt(art, url) {
    if (art && typeof art.switchUrl === 'function') {
      try {
        art.switchUrl(url);
        return;
      } catch (e) {}
    }
    try {
      art.url = url;
      return;
    } catch (e2) {}
    try {
      if (art && art.video) art.video.src = url;
    } catch (e3) {}
  }

  function emitAnalytics(callback, payload) {
    if (typeof callback !== 'function') return;
    try {
      callback(payload);
    } catch (e) {}
  }

  function playOneAdInPlayer(art, ad, ctx) {
    return new Promise(function (resolve) {
      var mediaUrl = resolveMediaUrl(ad.media, ctx.baseUrl || '');
      if (!mediaUrl) {
        resolve({ status: 'skipped', reason: 'no_media' });
        return;
      }

      var ui = ctx.ui;
      var maxMs = ctx.maxAdDurationMs || DEFAULT_MAX_AD_MS;
      var onAnalytics = ctx.onAnalytics;

      var finished = false;
      var timers = [];

      function cleanupTimers() {
        timers.forEach(function (t) {
          clearInterval(t);
          clearTimeout(t);
        });
        timers = [];
      }

      function detachPlayerEvents() {
        try {
          art.off('video:ended', onEnded);
        } catch (e) {}
        try {
          art.off('video:error', onError);
        } catch (e2) {}
      }

      function done(status, reason) {
        if (finished) return;
        finished = true;
        cleanupTimers();
        detachPlayerEvents();
        if (ui) {
          ui.skip.onclick = null;
          ui.skip.style.display = 'none';
          ui.timer.style.display = 'none';
          ui.click.removeAttribute('href');
          ui.click.style.display = 'none';
        }
        resolve({ status: status, reason: reason || '' });
      }

      function onEnded() {
        emitAnalytics(onAnalytics, {
          event: 'ad_completed',
          ad: ad,
          index: ctx.index,
          total: ctx.total,
        });
        done('completed', 'ended');
      }
      function onError() {
        emitAnalytics(onAnalytics, {
          event: 'ad_error',
          ad: ad,
          index: ctx.index,
          total: ctx.total,
          reason: 'video_error',
        });
        done('error', 'video_error');
      }

      ui.root.style.display = 'block';
      if (ctx.total > 1) {
        ui.label.style.display = 'block';
        ui.label.textContent =
          ui.strings.adPrefix + ' ' + (ctx.index + 1) + ' ' + ui.strings.adsSeparator + ' ' + ctx.total;
      } else {
        ui.label.style.display = 'none';
      }
      if (ad.redirect_url) {
        ui.click.href = ad.redirect_url;
        ui.click.style.display = 'block';
      } else {
        ui.click.removeAttribute('href');
        ui.click.style.display = 'none';
      }
      ui.skip.style.display = 'none';
      ui.timer.style.display = 'none';

      emitAnalytics(onAnalytics, {
        event: 'ad_started',
        ad: ad,
        index: ctx.index,
        total: ctx.total,
      });

      art.on('video:ended', onEnded);
      art.on('video:error', onError);

      setUrlOnArt(art, mediaUrl);

      timers.push(
        setTimeout(function () {
          try {
            if (art.video) art.video.currentTime = 0;
          } catch (e) {}
          try {
            var p = art.play && art.play();
            if (p && typeof p.catch === 'function') p.catch(function () {});
          } catch (e2) {}
        }, 80)
      );

      var skipHardOff = ad.skip_enabled === 2 || ad.skip_enabled === '2';
      if (!skipHardOff) {
        var skipAfter = ad.skip_after;
        var delaySec;
        if (skipAfter === 0 || skipAfter === '0') {
          delaySec = 0;
        } else if (Number(skipAfter) > 0) {
          delaySec = Number(skipAfter);
        } else {
          delaySec = DEFAULT_SKIP_AFTER_SECONDS;
        }

        function showSkip() {
          ui.timer.style.display = 'none';
          ui.skip.style.display = 'inline-flex';
          ui.skip.onclick = function () {
            emitAnalytics(onAnalytics, {
              event: 'ad_skipped',
              ad: ad,
              index: ctx.index,
              total: ctx.total,
            });
            done('skipped', 'user_skip');
          };
        }

        if (delaySec === 0) {
          showSkip();
        } else {
          var remain = delaySec;
          ui.timer.style.display = 'block';
          ui.timer.textContent = remain + ui.strings.timerUnit;
          var iv = setInterval(function () {
            remain--;
            if (remain <= 0) {
              clearInterval(iv);
              showSkip();
            } else {
              ui.timer.textContent = remain + ui.strings.timerUnit;
            }
          }, 1000);
          timers.push(iv);
        }
      }

      timers.push(
        setTimeout(function () {
          emitAnalytics(onAnalytics, {
            event: 'ad_error',
            ad: ad,
            index: ctx.index,
            total: ctx.total,
            reason: 'safety_timeout',
          });
          done('error', 'safety_timeout');
        }, maxMs)
      );
    });
  }

  /**
   * @param {object} art Artplayer instance (NOT destroyed between ads)
   * @param {object[]} queue Raw ads from backend (will be filtered to playable video)
   * @param {object} [opts]
   *   baseUrl, maxAdDurationMs, onAnalytics,
   *   container (HTMLElement — toggled .ott-inplayer-ad-phase),
   *   strings ({ skipLabel, adPrefix, adsSeparator, timerUnit }).
   * @returns {Promise<{ played:number, total:number }>}
   */
  function runInPlayer(art, queue, opts) {
    opts = opts || {};
    var playable = filterPlayableVideoAds(queue);
    if (!art || !playable.length) {
      return Promise.resolve({ played: 0, total: 0 });
    }

    var host = getPlayerRoot(art);
    if (!host) return Promise.resolve({ played: 0, total: 0 });

    var ui = createAdUi(host, opts.strings);
    var unlock = lockPlayerChrome(art);

    try {
      if (opts.container) opts.container.classList.add('ott-inplayer-ad-phase');
    } catch (e) {}

    emitAnalytics(opts.onAnalytics, {
      event: 'ad_break_start',
      total: playable.length,
    });

    var idx = 0;
    function next() {
      if (idx >= playable.length) {
        return Promise.resolve({ played: idx, total: playable.length });
      }
      return playOneAdInPlayer(art, playable[idx], {
        baseUrl: opts.baseUrl,
        maxAdDurationMs: opts.maxAdDurationMs,
        onAnalytics: opts.onAnalytics,
        ui: ui,
        index: idx,
        total: playable.length,
      }).then(function () {
        idx++;
        return next();
      });
    }

    return next().finally(function () {
      try {
        unlock();
      } catch (e) {}
      try {
        ui.root.remove();
      } catch (e2) {}
      try {
        if (opts.container) opts.container.classList.remove('ott-inplayer-ad-phase');
      } catch (e3) {}
      emitAnalytics(opts.onAnalytics, {
        event: 'ad_break_end',
        played: idx,
        total: playable.length,
      });
    });
  }

  global.OTTPlayerAds = {
    runInPlayer: runInPlayer,
    filterPlayableVideoAds: filterPlayableVideoAds,
    isPlayableVideoAd: isPlayableVideoAd,
    resolveMediaUrl: resolveMediaUrl,
  };
})(typeof window !== 'undefined' ? window : typeof globalThis !== 'undefined' ? globalThis : this);
