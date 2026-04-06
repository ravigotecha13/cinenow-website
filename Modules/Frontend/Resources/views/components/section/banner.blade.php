<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>


<style>
.unmute-btn {
    position: absolute;
    bottom: 25px;
    inset-inline-end: 25px;
    background: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border: none !important;
    border-radius: 50% !important;
    width: 46px !important;
    height: 60px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 15;
    transition: background 0.3s ease;
}

.unmute-btn:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}

.unmute-btn i {
    font-size: 18px !important;
} 
/* ✅ Bottom black gradient overlay for the slider */
/*.slick-item::after {*/
/*    content: "";*/
/*    position: absolute;*/
/*    bottom: 0;*/
/*    inset-inline-start: 0;*/
/*    width: 100%;*/
    /*height: 200px;*/
/*    background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, #000 100%) !important;*/
    /*z-index: 2; */
/*    pointer-events: none;*/
/*}*/

/* ✅ Slick arrows styling */
.slick-prev, .slick-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 30; /* keep above video */
    background: transparent !important;
    color: #fff !important;
    border: none;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    display: flex !important;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.3s ease;
}
.slick-prev:hover, .slick-next:hover {
    background: transparent !important;
}
.slick-prev { inset-inline-start: 25px; }  /* works for LTR + RTL */
.slick-next { inset-inline-end: 25px; }    /* works for LTR + RTL */

.slick-prev::before,
.slick-next::before {
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 22px;
}
.slick-prev::before {
    content: "\f104"; /* fa-chevron-left */
}
.slick-next::before {
    content: "\f105"; /* fa-chevron-right */
}

/* ✅ Make sure dots are visible too */
.slick-dots li button:before {
    color: white !important;
}
/* Ensure each slide is a proper positioning context */
.slick-item {
    position: relative;
    overflow: hidden;
}

/* ✅ Ensure content (text, buttons) are above the gradient */
.movie-content {
    position: relative;
    z-index: 3 !important;
    isolation: isolate;
    margin-inline-start: 15px !important;
    margin-inline-end: 15px !important;
}

/* Readable text band on the inline-start side (scaleX mirrors for RTL) */
.slick-item .movie-content::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background: linear-gradient(
        90deg,
        rgba(0, 0, 0, 0.88) 0%,
        rgba(0, 0, 0, 0.55) 32%,
        rgba(0, 0, 0, 0.22) 52%,
        transparent 70%
    );
}
html[dir="rtl"] .slick-item .movie-content::before {
    transform: scaleX(-1);
}

.slick-item .movie-content .container-fluid {
    position: relative;
    z-index: 1;
}

/*.banner-bottom-fade {*/
/*    position: absolute !important;*/
/*    bottom: 0 !important;*/
/*    left: 0 !important;*/
/*    width: 100% !important;*/
/*    height: 220px !important;*/
/*    background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, #030303 100%) !important;*/
/*    z-index: 5 !important;*/
/*    pointer-events: none !important;*/
/*}*/

/* ✅ Hide default "Previous" / "Next" text from Slick arrows */
.slick-prev,
.slick-next {
    font-size: 0 !important; /* hides text but keeps icons visible */
    line-height: 0 !important;
}

.slick-prev::before,
.slick-next::before {
    font-size: 22px !important; /* restore arrow icon size */
}

/* Make full slide clickable */
.slick-item {
    cursor: pointer !important;
}

/* Prevent overlays from blocking pointer events */
.slick-item video,
.slick-item img.banner-poster,
.slick-item::after,
.slick-item .bg-black {
    pointer-events: none !important;
}

/* Allow only buttons and controls to accept pointer events */
.slick-item .movie-content,
.slick-item .play-now-btn,
.slick-item .watch-list-btn,
.slick-item .banner-ad-overlay,
.unmute-btn,
.slick-prev,
.slick-next {
    pointer-events: auto !important;
}

.slick-item .banner-ad-overlay {
    z-index: 12;
    pointer-events: auto !important;
}

/* Keep title/content visible, but hide slide media while pre-roll ad is active */
.slick-item.ad-playing .banner-trailer-video,
.slick-item.ad-playing img.banner-poster {
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Keep movie meta/title above ad overlay while ad plays */
.slick-item.ad-playing .movie-content {
    z-index: 13 !important;
}

/* Keep hero text area same compact width as original design */
.slick-item .movie-info {
    width: min(100%, 540px);
    max-width: 540px;
}
.slick-item .movie-info .line-count-3 {
    display: -webkit-box !important;
    -webkit-box-orient: vertical !important;
    -webkit-line-clamp: 3 !important;
    white-space: normal !important;
    overflow: hidden !important;
    word-break: break-word;
    max-width: 100%;
}


.slick-item {
    height: 58vh !important; /* Prime style */
    max-height: 750px;
}
.slick-item video,
.slick-item img.banner-poster {
    height: 100% !important;
    object-fit: cover !important;
}


.slick-item {
    height: 58vh !important;        /* Prime height */
    max-height: 750px;
    position: relative;
    overflow: hidden;
}

.slick-item video,
.slick-item img.banner-poster {
    height: 100% !important;
    object-fit: cover !important;
    width: 100%;
}

/* Deep, Dark, Smooth Bottom Overlay (No line effect) */
/* Perfect Amazon Prime Bottom Fade */
.banner-bottom-fade {
    position: absolute;
    inset-inline-start: 0;
    bottom: 0;
    width: 100%;
    height: 100%; /* Only bottom 1/3 fades */
    pointer-events: none;
    z-index: 2 !important;
    background: linear-gradient(
        to bottom,
        rgba(0,0,0,0) 20%,
        rgba(0,0,0,0.15) 40%,
        rgba(0,0,0,0.45) 60%,
        rgba(0,0,0,0.75) 80%,
        rgba(0,0,0,1) 90%,
        rgba(0,0,0,1.25) 100%
    );
}



</style>

<div class="slick-banner main-banner js-banner-custom-ads"
     data-speed="100"
     data-autoplay="true"
     data-center="false"
     data-infinite="false"
     data-navigation="true"
     data-pagination="true"
     data-spacing="0">

    @foreach($data as $slider)
        @if(!empty($slider['data']))
            @php
                $item = $slider['data']->toArray(request());
                $trailer = $slider['video_trailer_url'] ?? $item['video_trailer_url'] ?? null;
                $poster = $item['thumbnail_image'] ?? $item['thumbnail_url'] ?? $item['poster_image'] ?? $item['poster_url'] ?? null;
                $price = $item['price'] ?? null;
                $displayName = app()->getLocale() === 'ar' ? ($item['name_ar'] ?? $item['name'] ?? '') : ($item['name'] ?? '');
                $displayDescription = app()->getLocale() === 'ar'
                    ? ($item['description_ar'] ?? $item['description'] ?? '')
                    : ($item['description'] ?? '');
            @endphp

            @if(isenablemodule($slider['type']) == 1)
                <div class="slick-item position-relative overflow-hidden"
                     data-content-id="{{ $item['id'] }}"
                     data-content-type="{{ $slider['type'] }}"
                     data-url="{{
                        $slider['type'] == 'livetv' ? route('livettv-details', ['id' => $item['id']]) :
                        ($slider['type'] == 'video' ? route('video-details', ['id' => $item['id']]) :
                        (($item['type'] ?? '') == 'tvshow' ? route('tvshow-details', ['id' => $item['id']]) :
                        route('movie-details', ['id' => $item['id']])))
                     }}">
                    
                    {{-- Poster Image (initially visible) --}}
                    @if(!empty($poster))
                        <img class="w-100 h-100 position-absolute top-0 start-0 banner-poster"
                             style="object-fit: cover; z-index:1;"
                             src="{{ setBaseUrlWithFileName($poster) }}"
                             alt="Poster Image">
                    @endif

                    {{-- 🎥 Trailer Video (playback controlled by JS: banner ad → poster → trailer) --}}
                    @if(!empty($trailer))
                        <video class="w-100 h-100 position-absolute top-0 start-0 banner-trailer-video"
                               style="object-fit: cover; display: none;"
                               muted
                               playsinline
                               preload="metadata">
                            <source src="{{ setBaseUrlWithFileName($trailer) }}" type="video/mp4">
                        </video>
                    @endif

                    {{-- Overlay --}}
                    <div class="movie-content h-100 position-relative" style="z-index: 3;">
                        <div class="container-fluid h-100">
                            <div class="row align-items-center h-100">
                                <div class="col-xxl-4 col-lg-6">
                                    <div class="movie-info text-white text-start"
                                         lang="{{ str_replace('_', '-', app()->getLocale()) }}"
                                         dir="auto"
                                         style="position: relative; z-index: 2;">

                                        <div class="movie-tag mb-3">
                                            <ul class="list-inline m-0 p-0 d-flex align-items-center flex-wrap movie-tag-list">
                                                @foreach($item['genres'] ?? [] as $genre)
                                                    <li><a href="#" class="tag">{{ $genre['name'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <h4 class="mb-2">{{ $displayName }}</h4>
                                        <p class="mb-0 font-size-14 line-count-3">{{ strip_tags($displayDescription) }}</p>

                                        <ul class="list-inline mt-4 mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3">
                                            @if(!empty($item['release_date']))
                                                <li><span class="fw-medium">{{ date('Y', strtotime($item['release_date'])) }}</span></li>
                                            @endif
                                            @if(!empty($item['language']))
                                                <li><span class="d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-language"></i>
                                                    <span class="fw-medium">{{ ucfirst($item['language']) }}</span>
                                                </span></li>
                                            @endif
                                           @if(!empty($item['duration']))
                                            <li>
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-clock"></i>
                                        
                                                    {{-- Arabic (RTL): show 02:26 --}}
                                                    @if(app()->getLocale() == 'ar')
                                                        <span class="fw-medium">{{ $item['duration'] }}</span>
                                        
                                                    {{-- Other languages: show 2h 26m --}}
                                                    @else
                                                        @php
                                                            $parts = explode(':', $item['duration']);
                                                            $hours = $parts[0] ?? 0;
                                                            $minutes = $parts[1] ?? 0;
                                                        @endphp
                                                        <span class="fw-medium">{{ $hours }}h {{ $minutes }}m</span>
                                                    @endif
                                        
                                                </span>
                                            </li>
                                        @endif
                                            
                                        </ul>

                                        <div class="mt-5">
                                            <div class="d-flex align-items-center gap-3">
                                                @if($slider['type'] != 'livetv')
                                                    <x-watchlist-button 
                                                        :entertainment-id="$item['id']"
                                                        :in-watchlist="$item['is_watch_list'] ?? false"
                                                        :entertainmentType="$slider['type']"
                                                        customClass="watch-list-btn" />
                                                @endif

                                                <div class="flex-grow-1">
                                                    <a href="{{
                                                        $slider['type'] == 'livetv' ? route('livetv-details', ['id' => $item['id']]) :
                                                        ($slider['type'] == 'video' ? route('video-details', ['id' => $item['id']]) :
                                                        (($item['type'] ?? '') == 'tvshow' ? route('tvshow-details', ['id' => $item['id']]) :
                                                        route('movie-details', ['id' => $item['id']])))
                                                    }}" class="btn btn-primary play-now-btn">
                                                        <span class="d-flex align-items-center justify-content-center gap-2">
                                                            {{ __('frontend.get_ticket') }}
                                                            {{ Currency::format($price - $price * (($item['discount'] ?? 0) / 100), 2) }}
                                                        </span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xxl-4 col-lg-6 d-lg-block d-none"></div>
                                <div class="col-xxl-4 d-xxl-block d-none"></div>
                            </div>
                        </div>
                    </div>
                <div class="banner-bottom-fade"></div>
                </div>
            @endif
        @endif
    @endforeach

</div>

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const slickBanner = $('.js-banner-custom-ads');
    if (!slickBanner.length) return;
    if (slickBanner.data('customAdsInit') === true) return;
    slickBanner.data('customAdsInit', true);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const FETCH_TIMEOUT_MS = 10000;

    /** API returns full URL for local media (setBaseUrlWithFileName); do not prefix baseUrl again */
    function resolveCustomAdMediaUrl(media, baseUrl) {
        var m = (media == null) ? '' : String(media).trim();
        if (!m) return '';
        if (/^https?:\/\//i.test(m) || m.startsWith('data:') || m.startsWith('blob:')) return m;
        if (m.startsWith('//')) return (window.location && window.location.protocol ? window.location.protocol : 'https:') + m;
        var b = (baseUrl || '').replace(/\/$/, '');
        return m.startsWith('/') ? b + m : b + '/' + m.replace(/^\/+/, '');
    }

    function normalizeContentType(rawType) {
        var t = (rawType || '').toString().trim().toLowerCase();
        if (t === 'tv_show') return 'tvshow';
        return t;
    }

    async function fetchJsonWithTimeout(url, options, timeoutMs) {
        if (!window.fetch) return null;
        var controller = new AbortController();
        var timer = setTimeout(function () { controller.abort(); }, timeoutMs);
        try {
            var res = await fetch(url, Object.assign({}, options || {}, { signal: controller.signal }));
            if (!res.ok) return null;
            return await res.json();
        } catch (e) {
            return null;
        } finally {
            clearTimeout(timer);
        }
    }

    function clearSlideBannerAd(slide) {
        if (!slide) return;
        if (typeof slide._clearBannerAd === 'function') {
            slide._clearBannerAd();
            slide._clearBannerAd = null;
        }
        slide.querySelectorAll('.banner-ad-overlay').forEach(function (n) { n.remove(); });
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

    function pickPrerollAd(rows, placementPrefs, contentId) {
        var prefs = (placementPrefs || []).map(function (p) { return (p || '').toString().toLowerCase(); });
        var cid = Number(contentId);
        var list = (rows || []).filter(function (item) {
            if (!item || item.status != 1) return false;
            if (!item.media) return false;
            var pl = (item.placement || '').toString().toLowerCase();
            return prefs.indexOf(pl) !== -1;
        });

        if (!list.length) return null;

        list.sort(function (a, b) {
            var pA = prefs.indexOf((a.placement || '').toString().toLowerCase());
            var pB = prefs.indexOf((b.placement || '').toString().toLowerCase());
            if (pA !== pB) return pA - pB;

            var idsA = parseTargetIds(a.target_categories);
            var idsB = parseTargetIds(b.target_categories);
            var exactA = Number.isFinite(cid) ? idsA.indexOf(cid) !== -1 : false;
            var exactB = Number.isFinite(cid) ? idsB.indexOf(cid) !== -1 : false;
            if (exactA !== exactB) return exactB - exactA;

            return Number(b.id || 0) - Number(a.id || 0);
        });

        return list[0] || null;
    }

    /**
     * Custom ad with placement "banner" for this slide’s content — plays before poster + trailer.
     */
    function playBannerSlideAd(slide) {
        return new Promise(function (resolve) {
            const contentId = slide?.dataset?.contentId;
            const contentType = normalizeContentType(slide?.dataset?.contentType);
            if (!contentId || !contentType) {
                resolve();
                return;
            }

            const baseUrl = window.location.origin || document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
            const url = baseUrl + '/api/custom-ads/get-active?content_id=' + encodeURIComponent(contentId) + '&type=' + encodeURIComponent(contentType);

            fetchJsonWithTimeout(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            }, FETCH_TIMEOUT_MS)
                .then(function (json) {
                    if (!json || json.success === false) {
                        resolve();
                        return;
                    }
                    var rows = Array.isArray(json.data) ? json.data : (json.data && Array.isArray(json.data.data) ? json.data.data : []);
                    if (!Array.isArray(rows) || rows.length === 0) {
                        resolve();
                        return;
                    }

                    // Hero slider priority from admin placements
                    var ad = pickPrerollAd(rows, ['banner', 'player', 'home_page'], contentId);
                    if (!ad) {
                        resolve();
                        return;
                    }

                    var overlay = document.createElement('div');
                    overlay.className = 'banner-ad-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
                    // Hide poster/video while ad is active, keep text content visible
                    overlay.style.background = 'rgba(0,0,0,0.88)';

                    var contentEl = document.createElement('div');
                    contentEl.style.cssText = 'width:100%;height:100%;position:relative;display:flex;align-items:center;justify-content:center;';

                    var closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.textContent = 'Skip Ad';
                    closeBtn.style.cssText = 'position:absolute;top:20px;right:20px;z-index:21;background:rgba(0,0,0,0.5);color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;display:none;';

                    var timerDiv = document.createElement('div');
                    timerDiv.style.cssText = 'position:absolute;bottom:20px;right:20px;z-index:21;color:white;font-size:14px;background:rgba(0,0,0,0.5);padding:4px 8px;border-radius:4px;display:none;';
                    var timeSpan = document.createElement('span');
                    timerDiv.appendChild(document.createTextNode('Ad: '));
                    timerDiv.appendChild(timeSpan);

                    overlay.appendChild(contentEl);
                    overlay.appendChild(closeBtn);
                    overlay.appendChild(timerDiv);
                    slide.classList.add('ad-playing');
                    slide.appendChild(overlay);

                    var adFinished = false;
                    var timers = [];
                    slide._bannerAdSafetyTimer = setTimeout(function () { finishAd(); }, 4 * 60 * 1000);

                    function cleanup() {
                        timers.forEach(function (t) { clearInterval(t); });
                        timers = [];
                        clearTimeout(slide._bannerAdSkipTimer);
                        slide._bannerAdSkipTimer = null;
                        clearTimeout(slide._bannerAdSafetyTimer);
                        slide._bannerAdSafetyTimer = null;
                    }

                    function finishAd() {
                        if (adFinished) return;
                        adFinished = true;
                        cleanup();
                        slide._clearBannerAd = null;
                        slide.classList.remove('ad-playing');
                        overlay.remove();
                        resolve();
                    }

                    slide._clearBannerAd = function () {
                        if (adFinished) return;
                        adFinished = true;
                        cleanup();
                        slide.classList.remove('ad-playing');
                        overlay.remove();
                        resolve();
                    };

                    closeBtn.onclick = finishAd;

                    var skipAfter = Number(ad.skip_after);
                    if (skipAfter > 0) {
                        slide._bannerAdSkipTimer = setTimeout(function () {
                            if (!adFinished) closeBtn.style.display = 'block';
                        }, skipAfter * 1000);
                    } else if (skipAfter === 0) {
                        closeBtn.style.display = 'block';
                    }

                    var adType = (ad.type || '').toString().toLowerCase();
                    if (adType === 'image') {
                        var duration = Number(ad.duration) || 10;
                        var imgSrc = resolveCustomAdMediaUrl(ad.media, baseUrl);
                        var imgHtml = '<img src="' + imgSrc.replace(/"/g, '&quot;') + '" alt="" style="max-width:100%;max-height:100%;object-fit:contain;">';
                        if (ad.redirect_url) {
                            imgHtml = '<a href="' + String(ad.redirect_url).replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">' + imgHtml + '</a>';
                        }
                        contentEl.innerHTML = imgHtml;

                        var timeLeft = duration;
                        timerDiv.style.display = 'block';
                        timeSpan.textContent = timeLeft + 's';
                        var tick = setInterval(function () {
                            if (adFinished) {
                                clearInterval(tick);
                                return;
                            }
                            timeLeft--;
                            timeSpan.textContent = timeLeft + 's';
                            if (timeLeft <= 0) {
                                clearInterval(tick);
                                finishAd();
                            }
                        }, 1000);
                        timers.push(tick);
                    } else if (adType === 'video') {
                        var isYouTube = /youtu\.?be/.test(ad.media || '');
                        if (isYouTube) {
                            var videoId = '';
                            var ytMatch = String(ad.media).match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
                            if (ytMatch && ytMatch[1]) videoId = ytMatch[1];
                            if (videoId) {
                                contentEl.innerHTML = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&mute=0&controls=0&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
                                var yDur = Number(ad.duration) || 30;
                                var yLeft = yDur;
                                timerDiv.style.display = 'block';
                                timeSpan.textContent = yLeft + 's';
                                var yTick = setInterval(function () {
                                    if (adFinished) {
                                        clearInterval(yTick);
                                        return;
                                    }
                                    yLeft--;
                                    timeSpan.textContent = yLeft + 's';
                                    if (yLeft <= 0) {
                                        clearInterval(yTick);
                                        finishAd();
                                    }
                                }, 1000);
                                timers.push(yTick);
                            } else {
                                finishAd();
                            }
                        } else {
                            var videoUrl = resolveCustomAdMediaUrl(ad.media, baseUrl);
                            var isHls = videoUrl.indexOf('.m3u8') !== -1;
                            var videoEl = document.createElement('video');
                            videoEl.style.width = '100%';
                            videoEl.style.height = '100%';
                            videoEl.autoplay = true;
                            videoEl.muted = true;
                            videoEl.controls = false;
                            videoEl.playsInline = true;
                            if (ad.redirect_url) {
                                videoEl.style.cursor = 'pointer';
                                videoEl.onclick = function () { window.open(ad.redirect_url, '_blank'); };
                            }
                            contentEl.appendChild(videoEl);

                            var hls = null;
                            if (isHls && window.Hls && window.Hls.isSupported()) {
                                var hls = new window.Hls();
                                hls.loadSource(videoUrl);
                                hls.attachMedia(videoEl);
                            } else {
                                videoEl.src = videoUrl;
                            }
                            videoEl.play().catch(function () { finishAd(); });
                            videoEl.onended = finishAd;
                            videoEl.onerror = function () { finishAd(); };
                            if (hls) {
                                var prevCleanup = cleanup;
                                cleanup = function () {
                                    prevCleanup();
                                    try { hls.destroy(); } catch (e) {}
                                };
                            }
                            videoEl.ontimeupdate = function () {
                                if (videoEl.duration) {
                                    timerDiv.style.display = 'block';
                                    timeSpan.textContent = Math.ceil(videoEl.duration - videoEl.currentTime) + 's';
                                }
                            };
                        }
                    } else {
                        finishAd();
                    }
                })
                .catch(function () {
                    resolve();
                });
        });
    }

    slickBanner.on('init', function (event, slick) {

        if (!slick || !slick.$slides) return;

        const slides = slick.$slides.get(); // SAFE
        const videos = slick.$slides.find('video').get();

        let currentSlide = slick.currentSlide || 0;
        let isMuted = true;
        let posterTimer = null;
        let sequenceSlide = null;

        /* ===========================
           GLOBAL MUTE / UNMUTE BUTTON
        ============================ */
        const globalBtn = document.createElement('button');
        globalBtn.className = 'unmute-btn';
        globalBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
        slickBanner.css('position', 'relative').append(globalBtn);

        globalBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            isMuted = !isMuted;
            updateMuteState();
        });

        function updateMuteState() {
            videos.forEach((video, index) => {
                if (!video) return;
                video.muted = isMuted || index !== currentSlide;
            });

            globalBtn.innerHTML = isMuted
                ? '<i class="fa-solid fa-volume-xmark"></i>'
                : '<i class="fa-solid fa-volume-high"></i>';
        }

        /* ===========================
           SLIDE CONTROL
        ============================ */
        function goNext() {
            if (slick.currentSlide < slick.slideCount - 1) {
                slickBanner.slick('slickNext');
            }
        }

        /* ===========================
           POSTER → VIDEO → NEXT SLIDE (after optional banner ad)
        ============================ */
        function startPosterThenTrailer(slide) {
            clearTimeout(posterTimer);

            const poster = slide.querySelector('.banner-poster');
            const video  = slide.querySelector('video');

            if (!video) {
                posterTimer = setTimeout(goNext, 4000);
                return;
            }

            video.loop = false;
            video.pause();
            video.currentTime = 0;
            video.style.display = 'none';

            if (poster) {
                poster.style.display = 'block';
                poster.style.opacity = '1';
                poster.style.transition = 'opacity 1s ease';
            }

            posterTimer = setTimeout(() => {

                if (poster) {
                    poster.style.opacity = '0';
                    setTimeout(() => { poster.style.display = 'none'; }, 1000);
                }

                video.style.display = 'block';
                video.muted = isMuted;
                video.play().catch(() => {});

            }, 3000);

            video.onended = function () {
                goNext();
            };
        }

        function showPosterThenVideo(slide) {
            clearTimeout(posterTimer);
            sequenceSlide = slide;

            slides.forEach(function (s) { clearSlideBannerAd(s); });

            playBannerSlideAd(slide).then(function () {
                if (sequenceSlide !== slide) return;
                startPosterThenTrailer(slide);
            });
        }

        /* ===========================
           INITIAL SLIDE
        ============================ */
        showPosterThenVideo(slides[currentSlide]);
        updateMuteState();

        slickBanner.on('beforeChange', function (e, slick, current) {
            clearTimeout(posterTimer);
            sequenceSlide = null;
            slides.forEach(function (s) { clearSlideBannerAd(s); });
            const v = slides[current]?.querySelector('video');
            if (v) {
                v.pause();
                v.currentTime = 0;
            }
        });

        slickBanner.on('afterChange', function (e, slick, index) {
            currentSlide = index;
            showPosterThenVideo(slides[currentSlide]);
            updateMuteState();
        });
    });

    /* ===========================
       SLICK INIT (NO AUTOPLAY)
    ============================ */
    slickBanner.slick({
        rtl: $('html').attr('dir') === 'rtl',
        autoplay: false,
        speed: 1000,
        dots: true,
        arrows: true,
        infinite: false,
        slidesToShow: 1,
        slidesToScroll: 1,
    });
});
</script>


@endpush


