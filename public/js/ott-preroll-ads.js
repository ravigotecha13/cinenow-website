/**
 * OTT Pre-Roll Ads — vanilla JS module for Laravel/Blade + Artplayer flows.
 *
 * Flow: fetchAds → selectAdsForPreRoll → playAdQueue → resolve → startMainVideo()
 *
 * @version 1.0.0
 */
(function (global) {
  'use strict';

  var DEFAULT_FETCH_TIMEOUT_MS = 12000;
  var DEFAULT_MAX_AD_MS = 4 * 60 * 1000;
  var DEFAULT_MAX_QUEUE = 8;
  /** When `skip_after` is not set on the ad row, show Skip after this many seconds. */
  var DEFAULT_SKIP_AFTER_SECONDS = 10;

  function resolveMediaUrl(media, baseUrl) {
    var m = media == null ? '' : String(media).trim();
    if (!m) return '';
    if (/^https?:\/\//i.test(m) || m.indexOf('data:') === 0 || m.indexOf('blob:') === 0) return m;
    if (m.indexOf('//') === 0) return (global.location && global.location.protocol ? global.location.protocol : 'https:') + m;
    var b = (baseUrl || '').replace(/\/$/, '');
    return m.indexOf('/') === 0 ? b + m : b + '/' + m.replace(/^\/+/, '');
  }

  function parseTargetIds(raw) {
    if (Array.isArray(raw)) {
      return raw.map(function (v) { return Number(v); }).filter(Number.isFinite);
    }
    if (typeof raw === 'string') {
      try {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          return parsed.map(function (v) { return Number(v); }).filter(Number.isFinite);
        }
      } catch (e) {
        return [];
      }
    }
    return [];
  }

  /**
   * GET /api/custom-ads/get-active — returns raw rows array (normalized).
   */
  function fetchAds(options) {
    var contentId = options.contentId;
    var contentType = options.contentType;
    var categoryId = options.categoryId;
    var baseUrl = options.baseUrl || (global.location && global.location.origin) || '';
    var csrfToken = options.csrfToken || '';
    var timeoutMs = options.timeoutMs || DEFAULT_FETCH_TIMEOUT_MS;

    if (!contentId || !contentType) {
      return Promise.resolve([]);
    }

    var params = new URLSearchParams();
    params.set('content_id', String(contentId));
    params.set('type', String(contentType));
    if (categoryId !== undefined && categoryId !== null && String(categoryId) !== '') {
      params.set('category_id', String(categoryId));
    }

    var url = baseUrl.replace(/\/$/, '') + '/api/custom-ads/get-active?' + params.toString();
    var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timer = null;

    var fetchOpts = {
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      credentials: 'include'
    };
    if (controller) {
      fetchOpts.signal = controller.signal;
      timer = setTimeout(function () {
        try {
          controller.abort();
        } catch (e) {}
      }, timeoutMs);
    }

    return fetch(url, fetchOpts)
      .then(function (res) {
        if (timer) clearTimeout(timer);
        return res.ok ? res.json() : null;
      })
      .then(function (json) {
        if (!json || json.success === false) return [];
        var rows = Array.isArray(json.data)
          ? json.data
          : json.data && Array.isArray(json.data.data)
            ? json.data.data
            : [];
        return rows;
      })
      .catch(function () {
        if (timer) clearTimeout(timer);
        return [];
      });
  }

  /**
   * Priority: placement order → category/content targeting → newest id.
   * Returns ordered playlist (deduped by id).
   */
  function selectAdsForPreRoll(rows, placementPrefs, contentId, categoryId, maxAds) {
    var prefs = (placementPrefs || []).map(function (p) {
      return String(p || '').toLowerCase();
    });
    var limit = maxAds == null ? DEFAULT_MAX_QUEUE : Math.min(Math.max(1, maxAds), DEFAULT_MAX_QUEUE);
    var cid = Number(contentId);
    var catId = categoryId !== undefined && categoryId !== null && String(categoryId) !== '' ? Number(categoryId) : NaN;

    var list = (rows || []).filter(function (item) {
      if (!item || item.status != 1) return false;
      if (!item.media) return false;
      var pl = String(item.placement || '').toLowerCase();
      return prefs.indexOf(pl) !== -1;
    });

    function rank(ad) {
      var pl = String(ad.placement || '').toLowerCase();
      var pIdx = prefs.indexOf(pl);
      if (pIdx === -1) pIdx = 999;
      var targets = parseTargetIds(ad.target_categories);
      var contentMatch = Number.isFinite(cid) && targets.indexOf(cid) !== -1;
      var catMatch = Number.isFinite(catId) && targets.indexOf(catId) !== -1;
      var targetBoost = contentMatch ? 2 : catMatch ? 1 : 0;
      return { pIdx: pIdx, targetBoost: targetBoost, id: Number(ad.id) || 0 };
    }

    list.sort(function (a, b) {
      var ra = rank(a);
      var rb = rank(b);
      if (ra.pIdx !== rb.pIdx) return ra.pIdx - rb.pIdx;
      if (ra.targetBoost !== rb.targetBoost) return rb.targetBoost - ra.targetBoost;
      return rb.id - ra.id;
    });

    var seen = {};
    var out = [];
    for (var i = 0; i < list.length; i++) {
      var id = list[i].id;
      if (seen[id]) continue;
      seen[id] = true;
      out.push(list[i]);
      if (out.length >= limit) break;
    }
    return out;
  }

  function emitAnalytics(callback, payload) {
    if (typeof callback !== 'function') return;
    try {
      callback(payload);
    } catch (e) {}
  }

  /**
   * Plays one ad inside the given DOM shell. Resolves when finished/skipped/error.
   */
  function playAd(ad, ctx) {
    return new Promise(function (resolve) {
      var baseUrl = ctx.baseUrl || '';
      var modal = ctx.modalEl;
      var content = ctx.contentEl;
      var closeBtn = ctx.skipBtnEl;
      var timerDiv = ctx.timerEl;
      var timeSpan = ctx.timerSpanEl;
      var onAnalytics = ctx.onAnalytics;
      var index = ctx.adIndex;
      var total = ctx.adTotal;
      var maxMs = ctx.maxAdDurationMs || DEFAULT_MAX_AD_MS;

      if (!modal || !content) {
        resolve({ status: 'skipped', reason: 'no_dom' });
        return;
      }

      var finished = false;
      var timers = [];
      var hlsInstance = null;

      function cleanupTimers() {
        timers.forEach(function (t) {
          clearInterval(t);
          clearTimeout(t);
        });
        timers = [];
      }

      function done(status, reason) {
        if (finished) return;
        finished = true;
        cleanupTimers();
        if (hlsInstance) {
          try {
            hlsInstance.destroy();
          } catch (e) {}
          hlsInstance = null;
        }
        content.innerHTML = '';
        resolve({ status: status, reason: reason || '' });
      }

      function finishAd() {
        emitAnalytics(onAnalytics, {
          event: 'ad_completed',
          ad: ad,
          index: index,
          total: total
        });
        done('completed', 'ended');
      }

      function skipAd() {
        emitAnalytics(onAnalytics, {
          event: 'ad_skipped',
          ad: ad,
          index: index,
          total: total
        });
        done('skipped', 'user_skip');
      }

      emitAnalytics(onAnalytics, {
        event: 'ad_started',
        ad: ad,
        index: index,
        total: total
      });

      modal.style.display = 'flex';
      modal.classList.add('ott-preroll-active');
      content.innerHTML = '';
      if (closeBtn) closeBtn.style.display = 'none';
      if (timerDiv) timerDiv.style.display = 'none';
      if (ctx.labelEl) {
        ctx.labelEl.textContent =
          total > 1 ? 'Ad ' + (index + 1) + ' of ' + total : '';
        ctx.labelEl.style.display = total > 1 ? 'block' : 'none';
      }

      var safety = setTimeout(function () {
        emitAnalytics(onAnalytics, {
          event: 'ad_error',
          ad: ad,
          index: index,
          total: total,
          reason: 'safety_timeout'
        });
        done('error', 'safety_timeout');
      }, maxMs);
      timers.push(safety);

      var skipTimer = null;
      if (closeBtn) {
        // Always offer Skip after a delay. DB `skip_enabled` defaults to 0 — do not treat that as "no skip"
        // or the button never appears. Optional hard opt-out: skip_enabled === 2 (reserved).
        var skipHardOff = ad.skip_enabled === 2 || ad.skip_enabled === '2';
        if (!skipHardOff) {
          closeBtn.onclick = function () {
            skipAd();
          };
          var skipAfter = ad.skip_after;
          var delaySec;
          if (skipAfter === 0 || skipAfter === '0') {
            delaySec = 0;
          } else if (Number(skipAfter) > 0) {
            delaySec = Number(skipAfter);
          } else {
            delaySec = DEFAULT_SKIP_AFTER_SECONDS;
          }
          if (delaySec === 0) {
            closeBtn.style.display = 'block';
          } else {
            skipTimer = setTimeout(function () {
              if (!finished) closeBtn.style.display = 'block';
            }, delaySec * 1000);
            timers.push(skipTimer);
          }
        }
      }

      var adType = String(ad.type || '').toLowerCase();

      if (adType === 'image') {
        var duration = Number(ad.duration) > 0 ? Number(ad.duration) : 10;
        var imgSrc = resolveMediaUrl(ad.media, baseUrl);
        var imgStyle =
          'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;';
        var imgHtml =
          '<img src="' +
          imgSrc.replace(/"/g, '&quot;') +
          '" alt="" style="' +
          imgStyle +
          '">';
        if (ad.redirect_url) {
          imgHtml =
            '<a href="' +
            String(ad.redirect_url).replace(/"/g, '&quot;') +
            '" target="_blank" rel="noopener" style="position:absolute;inset:0;display:block">' +
            imgHtml +
            '</a>';
        }
        content.innerHTML = imgHtml;
        if (timerDiv && timeSpan) {
          timerDiv.style.display = 'block';
          var left = duration;
          timeSpan.textContent = String(left);
          var tick = setInterval(function () {
            if (finished) return;
            left -= 1;
            timeSpan.textContent = String(Math.max(0, left));
            if (left <= 0) {
              clearInterval(tick);
              finishAd();
            }
          }, 1000);
          timers.push(tick);
        } else {
          setTimeout(finishAd, duration * 1000);
        }
        return;
      }

      if (adType === 'video') {
        var mediaRaw = ad.media || '';
        var isYouTube = /youtu\.?be/i.test(mediaRaw);

        if (isYouTube) {
          var vid = '';
          var ytMatch = String(mediaRaw).match(
            /(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/
          );
          if (ytMatch && ytMatch[1]) vid = ytMatch[1];
          if (!vid) {
            emitAnalytics(onAnalytics, {
              event: 'ad_error',
              ad: ad,
              index: index,
              total: total,
              reason: 'youtube_parse'
            });
            done('error', 'youtube_parse');
            return;
          }
          var pageOrigin =
            global.location && global.location.origin ? String(global.location.origin) : '';
          var ytParams =
            'autoplay=1&mute=1&controls=0&rel=0&playsinline=1&enablejsapi=1' +
            (pageOrigin ? '&origin=' + encodeURIComponent(pageOrigin) : '');
          content.innerHTML =
            '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' +
            vid +
            '?' +
            ytParams +
            '" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;min-width:100%;min-height:100%;border:0;"></iframe>';
          var yDur = Number(ad.duration) > 0 ? Number(ad.duration) : 30;
          var yLeft = yDur;
          if (timerDiv && timeSpan) {
            timerDiv.style.display = 'block';
            timeSpan.textContent = String(yLeft);
            var yTick = setInterval(function () {
              if (finished) return;
              yLeft -= 1;
              timeSpan.textContent = String(Math.max(0, yLeft));
              if (yLeft <= 0) {
                clearInterval(yTick);
                finishAd();
              }
            }, 1000);
            timers.push(yTick);
          } else {
            var yEnd = setTimeout(finishAd, yDur * 1000);
            timers.push(yEnd);
          }
          return;
        }

        var videoUrl = resolveMediaUrl(mediaRaw, baseUrl);
        var isHls = videoUrl.indexOf('.m3u8') !== -1;
        var videoEl = document.createElement('video');
        videoEl.setAttribute('playsinline', '');
        videoEl.setAttribute('webkit-playsinline', '');
        videoEl.style.cssText =
          'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#000;';
        videoEl.autoplay = true;
        videoEl.muted = true;
        videoEl.controls = false;
        if (ad.redirect_url) {
          videoEl.style.cursor = 'pointer';
          videoEl.addEventListener('click', function () {
            global.open(ad.redirect_url, '_blank', 'noopener');
          });
        }
        content.appendChild(videoEl);

        function attachHls() {
          if (isHls && global.Hls && global.Hls.isSupported()) {
            hlsInstance = new global.Hls({ enableWorker: true });
            hlsInstance.loadSource(videoUrl);
            hlsInstance.attachMedia(videoEl);
            hlsInstance.on(global.Hls.Events.ERROR, function () {
              emitAnalytics(onAnalytics, {
                event: 'ad_error',
                ad: ad,
                index: index,
                total: total,
                reason: 'hls_error'
              });
              done('error', 'hls_error');
            });
          } else {
            videoEl.src = videoUrl;
          }
        }

        attachHls();

        videoEl.addEventListener(
          'ended',
          function () {
            finishAd();
          },
          { once: true }
        );
        videoEl.addEventListener(
          'error',
          function () {
            emitAnalytics(onAnalytics, {
              event: 'ad_error',
              ad: ad,
              index: index,
              total: total,
              reason: 'video_error'
            });
            done('error', 'video_error');
          },
          { once: true }
        );

        var playAttempt = videoEl.play();
        if (playAttempt && typeof playAttempt.catch === 'function') {
          playAttempt.catch(function () {
            emitAnalytics(onAnalytics, {
              event: 'ad_error',
              ad: ad,
              index: index,
              total: total,
              reason: 'autoplay_blocked'
            });
            done('error', 'autoplay_blocked');
          });
        }

        videoEl.addEventListener('timeupdate', function () {
          if (finished || !videoEl.duration) return;
          if (timerDiv && timeSpan) {
            timerDiv.style.display = 'block';
            timeSpan.textContent = String(Math.ceil(videoEl.duration - videoEl.currentTime));
          }
        });
        return;
      }

      emitAnalytics(onAnalytics, {
        event: 'ad_error',
        ad: ad,
        index: index,
        total: total,
        reason: 'unknown_type'
      });
      done('error', 'unknown_type');
    });
  }

  /**
   * Sequentially plays each ad; continues on single-ad failure (skip to next / main).
   */
  function playAdQueue(ads, ctx) {
    if (!ads || !ads.length) {
      return Promise.resolve();
    }
    var i = 0;
    var onAnalytics = ctx.onAnalytics;

    function next() {
      if (i >= ads.length) return Promise.resolve();
      var ad = ads[i];
      var idx = i;
      i += 1;
      var sub = Object.assign({}, ctx, {
        adIndex: idx,
        adTotal: ads.length
      });
      return playAd(ad, sub).then(function (result) {
        if (result && result.status === 'error') {
          emitAnalytics(onAnalytics, {
            event: 'ad_failed',
            ad: ad,
            index: idx,
            total: ads.length,
            reason: result.reason || ''
          });
        }
        return next();
      });
    }

    return next();
  }

  /**
   * High-level: fetch → select → optional loader → queue → hide modal.
   */
  function runPreRoll(options) {
    var placementPrefs = options.placementPrefs || ['player'];
    var contentId = options.contentId;
    var contentType = options.contentType;
    var categoryId = options.categoryId;
    var baseUrl =
      options.baseUrl ||
      (document.querySelector('meta[name="baseUrl"]') &&
        document.querySelector('meta[name="baseUrl"]').getAttribute('content')) ||
      (global.location && global.location.origin) ||
      '';
    var csrfToken =
      options.csrfToken ||
      (document.querySelector('meta[name="csrf-token"]') &&
        document.querySelector('meta[name="csrf-token"]').getAttribute('content')) ||
      '';

    var modalEl = options.modalEl || document.getElementById('customAdModal');
    var contentEl = options.contentEl || document.getElementById('customAdContent');
    var skipBtnEl = options.skipBtnEl || document.getElementById('customAdCloseBtn');
    var timerEl = options.timerEl || document.getElementById('adTimer');
    var timerSpanEl = options.timerSpanEl || document.getElementById('adTimeRemaining');
    var loaderEl = options.loaderEl || document.getElementById('ottPrerollLoader');
    var labelEl = options.labelEl || document.getElementById('ottPrerollAdLabel');

    var onAnalytics = options.onAnalytics;
    var maxAds = options.maxAds;

    /** Optional: pause hero / background video (call from Blade before runPreRoll, or pass here). */
    if (typeof options.pauseBackgroundMedia === 'function') {
      try {
        options.pauseBackgroundMedia();
      } catch (e) {}
    }

    function showLoader(show) {
      if (loaderEl) {
        loaderEl.style.display = show ? 'flex' : 'none';
      }
      if (modalEl) {
        if (show) {
          modalEl.style.display = 'flex';
          modalEl.classList.add('ott-preroll-loading');
        } else {
          modalEl.classList.remove('ott-preroll-loading');
        }
      }
    }

    showLoader(true);

    return fetchAds({
      contentId: contentId,
      contentType: contentType,
      categoryId: categoryId,
      baseUrl: baseUrl,
      csrfToken: csrfToken,
      timeoutMs: options.fetchTimeoutMs
    })
      .then(function (rows) {
        var queue = selectAdsForPreRoll(
          rows,
          placementPrefs,
          contentId,
          categoryId,
          maxAds
        );
        showLoader(false);

        if (!queue.length) {
          if (modalEl) {
            modalEl.style.display = 'none';
            modalEl.classList.remove('ott-preroll-active');
          }
          emitAnalytics(onAnalytics, { event: 'ad_break_empty', contentId: contentId });
          return;
        }

        var ctx = {
          modalEl: modalEl,
          contentEl: contentEl,
          skipBtnEl: skipBtnEl,
          timerEl: timerEl,
          timerSpanEl: timerSpanEl,
          labelEl: labelEl,
          baseUrl: baseUrl,
          onAnalytics: onAnalytics,
          maxAdDurationMs: options.maxAdDurationMs
        };

        return playAdQueue(queue, ctx).then(function () {
          if (modalEl) {
            modalEl.style.display = 'none';
            modalEl.classList.remove('ott-preroll-active');
          }
          if (contentEl) contentEl.innerHTML = '';
        });
      })
      .catch(function () {
        showLoader(false);
        if (modalEl) {
          modalEl.style.display = 'none';
          modalEl.classList.remove('ott-preroll-active', 'ott-preroll-loading');
        }
      });
  }

  /** Future: mid-roll markers (no-op hooks). */
  var midRoll = {
    register: function () {
      /* reserved for Artplayer / HLS cue integration */
    }
  };

  var OTTPreRoll = {
    resolveMediaUrl: resolveMediaUrl,
    parseTargetIds: parseTargetIds,
    fetchAds: fetchAds,
    selectAdsForPreRoll: selectAdsForPreRoll,
    playAd: playAd,
    playAdQueue: playAdQueue,
    runPreRoll: runPreRoll,
    midRoll: midRoll,
    _defaults: {
      fetchTimeoutMs: DEFAULT_FETCH_TIMEOUT_MS,
      maxAdDurationMs: DEFAULT_MAX_AD_MS,
      maxQueue: DEFAULT_MAX_QUEUE,
      skipAfterDefaultSeconds: DEFAULT_SKIP_AFTER_SECONDS
    }
  };

  global.OTTPreRoll = OTTPreRoll;
})(typeof window !== 'undefined' ? window : typeof globalThis !== 'undefined' ? globalThis : this);
