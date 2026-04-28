@php
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$userId          = auth()->id();
$entertainmentId = $data['id'] ?? null;
$contentType     = $content_type ?? $data['type'] ?? 'movie';
$profileId       = ($userId ? resolveContinueWatchProfileId((int) $userId, request()) : null);

$locale          = app()->getLocale();
$isRtl           = $locale === 'ar' || (session()->has('dir') && session('dir') === 'rtl');

$displayName = $locale === 'ar'
    ? ($data['name_ar'] ?? $data['name'] ?? '')
    : ($data['name'] ?? '');
$displayDescription = $locale === 'ar'
    ? ($data['description_ar'] ?? $data['description'] ?? '')
    : ($data['description'] ?? '');

/* RELEASE CHECK */
$isReleased = true;
if (!empty($data['start_date'])) {
    try {
        $isReleased = Carbon::parse($data['start_date'])->lte(now());
    } catch (\Exception $e) {
        $isReleased = true;
    }
}

/* TRAILER + MAIN VIDEO */
$useEntertainmentTrailerOnly = $use_entertainment_trailer_only ?? false;
$bannerTrailer = $data['banner_trailer_url'] ?? '';
$entTrailer    = $data['trailer_url'] ?? '';

if ($useEntertainmentTrailerOnly) {
    $trailerUrl  = $entTrailer;
    $trailerType = $data['trailer_url_type'] ?? 'URL';
} else {
    $trailerUrl  = !empty($bannerTrailer) ? $bannerTrailer : $entTrailer;
    $trailerType = !empty($bannerTrailer) ? 'URL' : ($data['trailer_url_type'] ?? 'URL');
}
if (!empty($trailerUrl) && str_contains($trailerUrl, '.m3u8')) {
    $trailerType = 'HLS';
}

$video_url       = $data['video_url_input'] ?? '';
$videoUploadType = $data['video_upload_type'] ?? 'URL';

/* PPV + PRICE */
$isMoviePPV = (($data['movie_access'] ?? $data['access'] ?? '') === 'pay-per-view');
$finalPrice = isset($data['price'])
    ? $data['price'] - ($data['price'] * ($data['discount'] ?? 0) / 100)
    : 0;

/* QUALITY + SUBTITLES */
$qualityPlaylist = video_quality_playlist_from_links(
    $data['video_links'] ?? null,
    $video_url,
    $videoUploadType
);
$qualityOptions = [];
foreach ($qualityPlaylist as $row) {
    $qualityOptions[$row['label']] = [
        'value' => $row['url'],
        'type'  => $row['type'],
    ];
}
$qualityOptionsJson = json_encode($qualityOptions);

$subtitleInfo = !empty($data['subtitle_info'])
    ? $data['subtitle_info']->toArray(request())
    : [];

/* PPV STATE */
$activeTicket = null;
$watchedPercent = 0;
if ($userId && $entertainmentId && $isMoviePPV) {
    $activeTicket = DB::table('ppv_tickets')
        ->where('user_id', $userId)
        ->where('entertainment_id', $entertainmentId)
        ->where('status', 'active')
        ->latest('id')
        ->first();

    if ($activeTicket) {
        $progress = DB::table('watch_progress')
            ->where('ticket_id', $activeTicket->id)
            ->first();
        $watchedPercent = (int) ($progress->watched_percentage ?? 0);
    }
}

$artPlayerUiRtl = $isRtl;
$ottTimerDir    = $isRtl ? 'rtl' : 'ltr';

/* Mid-roll cue points (seconds). Empty = pre-roll only. */
$midrollCues = [];

/* Attributes shared by every Watch-Now button variant. */
$watchBtnData = [
    'data-entertainment-id'   => $entertainmentId,
    'data-entertainment-type' => $contentType,
    'data-category-id'        => $data['category_id'] ?? '',
    'data-profile-id'         => $profileId,
    'data-video-url'          => $video_url,
    'data-quality-options'    => $qualityOptionsJson,
    'data-quality-playlist'   => json_encode($qualityPlaylist),
    'data-subtitle-info'      => json_encode($subtitleInfo),
    'data-midroll-cues'       => json_encode($midrollCues),
    'data-video-type'         => $videoUploadType,
];
@endphp

@once
    @push('after-styles')
        <link rel="stylesheet" href="{{ mix('css/hero-thumbnail.css') }}">
    @endpush
    @push('after-scripts')
        {{-- Pinned builds; order: Hls → Artplayer (customType) → OTT pre-roll; defer runs before DOMContentLoaded --}}
        <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js" defer crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/artplayer@5.4.0/dist/artplayer.js" defer crossorigin="anonymous"></script>
        <script src="{{ mix('js/ott-preroll-ads.js') }}" defer></script>
        <script src="{{ mix('js/ott-player-ads.js') }}" defer></script>
        <script src="{{ mix('js/ott-midroll-ads.js') }}" defer></script>
    @endpush
@endonce

<div id="video-section" class="video-player-wrapper position-relative">

    <div id="videoContainer" class="position-relative w-100" data-art-rtl="{{ $artPlayerUiRtl ? '1' : '0' }}">

        {{-- 🎞️ TRAILER (src finalized in JS — HLS needs hls.js; avoids wrong cached stream) --}}
        <video id="heroTrailerVideo_{{ $entertainmentId }}"
               class="w-100 hero-trailer-video"
               data-entertainment-id="{{ $entertainmentId }}"
               preload="metadata"
               muted
               loop
               playsinline
               poster="{{ $data['thumbnail_image'] ?? '' }}">
            @if ($trailerUrl && ($trailerType ?? '') !== 'HLS')
                <source src="{{ $trailerUrl }}"
                        type="video/mp4">
            @endif
        </video>

        {{-- 🔊 MUTE --}}
        <button id="heroTrailerMute_{{ $entertainmentId }}" type="button" class="mute-btn">
            <i class="fa-solid fa-volume-mute"></i>
        </button>

        {{-- 🎭 OVERLAY --}}
        <div class="movie-overlay">
            <div class="movie-overlay-content text-start"
                 lang="{{ str_replace('_', '-', app()->getLocale()) }}"
                 dir="auto">

                <h1 class="movie-title">{{ $displayName }}</h1>
                <p class="movie-description">{!! $displayDescription !!}</p>

                <div class="play-button-wrapper">

                    {{-- COMING SOON --}}
                    @if (!$isReleased)
                        <button class="btn btn-primary" disabled>
                            <i class="fa-solid fa-clock me-2"></i> {{ __('frontend.coming_soon') }}
                        </button>
                
                    {{-- PAY PER VIEW MOVIE --}}
                    @elseif ($isMoviePPV)
                
                        {{-- NOT PURCHASED --}}
                        @if (!$activeTicket)
                            <a href="{{ route('pay-per-view.paymentform', ['id'=>$entertainmentId,'type'=>$contentType]) }}"
                               class="btn btn-primary">
                                <i class="fa-solid fa-ticket me-2"></i>
                                <span class="d-inline-flex align-items-center gap-2 flex-wrap">
                                    <span dir="auto">{{ __('frontend.get_ticket') }}</span>
                                    <bdi dir="ltr" class="text-nowrap">{{ Currency::format($finalPrice, 2) }}</bdi>
                                </span>
                            </a>
                
                        {{-- PURCHASED --}}
                        @else
                            <button id="watchNowBtn"
                                class="btn btn-primary btn-watch-now me-2"
                                data-is-ppv="true"
                                data-payment-url="{{ route('pay-per-view.paymentform', ['id' => $entertainmentId, 'type' => $contentType]) }}"
                                @foreach($watchBtnData as $attr => $val) {{ $attr }}='{{ $val }}' @endforeach>
                                {{ $watchedPercent > 0 ? __('frontend.continue_watching') : __('frontend.watch_now') }}
                            </button>

                            @if ($watchedPercent >= 25)
                                <a href="{{ route('pay-per-view.paymentform', ['id' => $entertainmentId, 'type' => $contentType]) }}"
                                   class="btn btn-primary">
                                    <i class="fa-solid fa-ticket me-2"></i> {{ __('frontend.get_ticket_again') }}
                                </a>
                            @endif
                        @endif

                    {{-- FREE MOVIE --}}
                    @else
                        <button id="watchNowBtn"
                            class="btn btn-primary btn-watch-now"
                            data-is-ppv="false"
                            @foreach($watchBtnData as $attr => $val) {{ $attr }}='{{ $val }}' @endforeach>
                            {{ __('frontend.watch_now') }}
                        </button>
                    @endif
                
                </div>


            </div>
        </div>

        {{-- Main feature player mounts here so #customAdModal is not destroyed by innerHTML --}}
        <div id="mainArtplayerSlot"
             class="position-absolute top-0 start-0 w-100 h-100"
             style="display:none; z-index: 12;"
             aria-hidden="true"></div>

        {{-- 📺 Interstitial ad shell AFTER player slot so mid-rolls paint above Artplayer (z-index + DOM order). --}}
        <div id="customAdModal"
             class="ott-preroll-modal"
             style="display:none; position:absolute; inset:0; width:100%; height:100%; background:#000; z-index:100;"
             aria-hidden="true">
            <div id="ottPrerollLoader" class="ott-preroll-loader" style="display:none;" aria-busy="true">
                <span class="ott-preroll-spinner" aria-hidden="true"></span>
                <span class="ott-preroll-loader-text">Loading…</span>
            </div>
            <div id="ottPrerollAdLabel" class="ott-preroll-ad-label" style="display:none;" dir="auto" aria-live="polite"></div>
            <div id="customAdContent" class="ott-preroll-content">
                {{-- Ad creatives injected by OTTPreRoll — full-bleed like hero trailer (object-fit: cover) --}}
            </div>
            <button type="button" id="customAdCloseBtn" class="ott-preroll-skip-btn" style="display:none;">
                <span class="ott-preroll-skip-btn-text" dir="{{ $ottTimerDir }}">{{ __('frontend.skip_ad') }}</span>
            </button>
            <div id="adTimer" class="ott-preroll-timer" style="display:none;">
                <span class="ott-preroll-timer-inner" dir="{{ $ottTimerDir }}">
                    <span class="ott-preroll-timer-prefix">{{ __('frontend.ad_timer_prefix') }}</span>
                    <span class="ott-preroll-timer-digits" dir="ltr"><span id="adTimeRemaining"></span></span>
                    <span class="ott-preroll-timer-unit">{{ __('frontend.ad_seconds_unit') }}</span>
                </span>
            </div>
        </div>

    </div>
</div>

@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const trailer   = document.getElementById('heroTrailerVideo_{{ $entertainmentId }}');
    const muteBtn   = document.getElementById('heroTrailerMute_{{ $entertainmentId }}');
    const container = document.getElementById('videoContainer');
    const overlay   = container ? container.querySelector('.movie-overlay') : null;

    const heroTrailerUrl = @json($trailerUrl ?? '');
    const heroTrailerIsHls = @json(!empty($trailerUrl) && (($trailerType ?? '') === 'HLS'));
    let heroTrailerHls = null;

    (async function runHeroTrailerWithOptionalAd() {
        if (!trailer) return;
        const url = heroTrailerUrl;
        if (!url) return;

        trailer.pause();
        try {
            trailer.querySelectorAll('source').forEach(function (el) {
                el.remove();
            });
        } catch (e) {}

        if (heroTrailerHls) {
            try {
                heroTrailerHls.destroy();
            } catch (e) {}
            heroTrailerHls = null;
        }

        if (heroTrailerIsHls && window.Hls && window.Hls.isSupported()) {
            heroTrailerHls = new window.Hls({ enableWorker: true });
            heroTrailerHls.loadSource(url);
            heroTrailerHls.attachMedia(trailer);
            heroTrailerHls.on(window.Hls.Events.ERROR, function () {
                try {
                    if (heroTrailerHls) heroTrailerHls.destroy();
                } catch (err) {}
                heroTrailerHls = null;
            });
        } else if (heroTrailerIsHls && trailer.canPlayType && trailer.canPlayType('application/vnd.apple.mpegurl')) {
            trailer.src = url;
            trailer.load();
        } else if (!heroTrailerIsHls) {
            trailer.src = url;
            trailer.load();
        } else {
            return;
        }

        try {
            trailer.currentTime = 0;
        } catch (e) {}
        if (overlay) overlay.style.display = '';
        trailer.muted = true;
        trailer.play().catch(function () {});
    })();

    function updateHeroMuteIcon(isMuted) {
        if (!muteBtn) return;
        const icon = muteBtn.querySelector('i');
        if (icon) {
            icon.className = isMuted ? 'fa-solid fa-volume-mute' : 'fa-solid fa-volume-high';
        }
    }

    if (muteBtn && trailer) {
        muteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const modal = document.getElementById('customAdModal');
            const prerollUi =
                modal &&
                (modal.classList.contains('ott-preroll-active') ||
                    modal.classList.contains('ott-preroll-loading'));

            if (prerollUi) {
                const adVideo = document.querySelector('#customAdContent video');
                if (adVideo) {
                    adVideo.muted = !adVideo.muted;
                    updateHeroMuteIcon(!!adVideo.muted);
                    return;
                }
                const ytIframe = document.querySelector(
                    '#customAdContent iframe[src*="youtube.com/embed"]'
                );
                if (ytIframe && ytIframe.contentWindow) {
                    const icon = muteBtn.querySelector('i');
                    const currentlyMuted = icon && icon.classList.contains('fa-solid fa-volume-mute');
                    const func = currentlyMuted ? 'unMute' : 'mute';
                    try {
                        ytIframe.contentWindow.postMessage(
                            JSON.stringify({ event: 'command', func: func, args: '' }),
                            'https://www.youtube.com'
                        );
                    } catch (err) {}
                    updateHeroMuteIcon(func === 'mute');
                    return;
                }
                return;
            }

            trailer.muted = !trailer.muted;
            updateHeroMuteIcon(!!trailer.muted);
        });
    }

    const watchBtn  = document.getElementById('watchNowBtn');
    if (!watchBtn) return;

    watchBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        const entertainmentId   = this.dataset.entertainmentId;
        const entertainmentType = this.dataset.entertainmentType;
        const profileId         = this.dataset.profileId;
        const categoryId        = this.dataset.categoryId || '';
        let midrollCues = [];
        try {
            midrollCues = JSON.parse(this.dataset.midrollCues || '[]');
        } catch (eMid) {
            midrollCues = [];
        }
        if (!Array.isArray(midrollCues)) {
            midrollCues = [];
        }
        const isPPV             = this.dataset.isPpv === 'true';
        const paymentUrl        = this.dataset.paymentUrl || '';
        const contentVideoType  = (this.dataset.videoType || 'URL').toLowerCase();

        function detectUrlKind(u) {
            var url = String(u || '').trim();
            if (!url) return 'native';
            if (/youtube\.com|youtu\.be/i.test(url)) return 'youtube';
            if (/vimeo\.com/i.test(url)) return 'vimeo';
            if (/^<iframe\s|\bembed\b/i.test(url) && /src=/.test(url)) return 'embedded';
            return 'native';
        }

        const qualities = JSON.parse(this.dataset.qualityOptions || '{}');
        let qualityPlaylist = [];
        try {
            qualityPlaylist = JSON.parse(this.dataset.qualityPlaylist || '[]');
        } catch (err) {
            qualityPlaylist = [];
        }
        if (!qualityPlaylist.length && qualities && typeof qualities === 'object') {
            Object.keys(qualities).forEach(function (label) {
                var q = qualities[label];
                if (q && q.value) {
                    qualityPlaylist.push({ label: label, url: q.value, type: q.type || 'URL' });
                }
            });
        }
        qualityPlaylist.forEach(function (q) {
            q._kind = detectUrlKind(q.url);
        });

        // Prefer native (HLS/MP4/local) over iframe for the initial playback, but keep
        // all qualities visible in the selector so the user can still pick any of them.
        var nativeEntries = qualityPlaylist.filter(function (q) { return q._kind === 'native'; });
        var preferredEntry = nativeEntries[0] || qualityPlaylist[0] || null;

        const videoUrl = (preferredEntry && preferredEntry.url) ||
                         Object.values(qualities)[0]?.value ||
                         this.dataset.videoUrl;

        const videoType = preferredEntry ? preferredEntry._kind : contentVideoType;
        const isIframeVideoType = (videoType === 'youtube' || videoType === 'vimeo' || videoType === 'embedded');

        let subtitleTracks = [];
        try {
            subtitleTracks = JSON.parse(this.dataset.subtitleInfo || '[]');
        } catch (err2) {
            subtitleTracks = [];
        }

        let resumeTime = 0;

        /* -------------------------------------------------
        | 🔐 PPV ACCESS CHECK (ONLY FOR PPV)
        ------------------------------------------------- */
        if (isPPV) {
            const access = await fetch('{{ route("ppv.checkAccess") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ entertainment_id: entertainmentId })
            }).then(r => r.json());

            if (access.status === 'purchase_required') {
                window.location.href = paymentUrl;
                return;
            }

            if (access.status === 'consumed_or_completed') {
                alert('Ticket already consumed. Please purchase again.');
                window.location.href = paymentUrl;
                return;
            }

            resumeTime = Number(access.resume_time || 0);
        } else {
            /* -------------------------------------------------
            | ▶ FETCH RESUME TIME (FREE MOVIES)
            ------------------------------------------------- */
            try {
                const cw = await fetch('{{ route("frontend.continueWatch.resume") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        entertainment_id: entertainmentId,
                        entertainment_type: entertainmentType,
                        profile_id: profileId
                    })
                }).then(r => r.json());
                resumeTime = Number(cw.resume_time || 0);
            } catch (e) {
                console.error('Error fetching resume time:', e);
            }
        }

        /* -------------------------------------------------
        | 📺 Stop hero trailer — Artplayer is about to take over the wrapper.
        ------------------------------------------------- */
        if (heroTrailerHls) {
            try {
                heroTrailerHls.destroy();
            } catch (e) {}
            heroTrailerHls = null;
        }
        if (trailer) {
            try {
                trailer.pause();
                trailer.muted = true;
            } catch (e) {}
            if (muteBtn) {
                const icon = muteBtn.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-volume-mute';
            }
        }

        const apiBaseUrl = (
            (document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '')
                .replace(/\/$/, '') || window.location.origin
        );

        if (overlay) {
            overlay.style.display = 'none';
        }

        /* -------------------------------------------------
        | 📺 PRE-ROLL queue — played INSIDE Artplayer via OTTPlayerAds.
        | (Fetch first, then boot Artplayer with first ad URL so there is ONE player, one DOM.)
        ------------------------------------------------- */
        let prerollQueue = [];
        if (typeof window.OTTPreRoll !== 'undefined' && typeof window.OTTPlayerAds !== 'undefined') {
            try {
                const adRows = await window.OTTPreRoll.fetchAds({
                    contentId: entertainmentId,
                    contentType: entertainmentType,
                    categoryId: categoryId,
                    baseUrl: apiBaseUrl,
                    csrfToken: '{{ csrf_token() }}',
                    timeoutMs: 12000,
                });
                const selected = window.OTTPreRoll.selectAdsForPreRoll(
                    adRows,
                    ['player', 'movie_detail', 'movie_detail_page'],
                    entertainmentId,
                    categoryId,
                    8
                );
                prerollQueue = window.OTTPlayerAds.filterPlayableVideoAds(selected);
            } catch (eAds) {
                console.error('Pre-roll fetch failed', eAds);
                prerollQueue = [];
            }
        }

        /* -------------------------------------------------
        | 🎬 LOAD PLAYER (mount in slot — single Artplayer instance for ads + movie)
        ------------------------------------------------- */
        if (trailer) {
            trailer.style.display = 'none';
        }
        if (muteBtn) {
            muteBtn.style.display = 'none';
        }

        const playerSlot = document.getElementById('mainArtplayerSlot');
        if (!playerSlot) {
            console.error('mainArtplayerSlot missing');
            return;
        }
        playerSlot.style.display = 'block';
        playerSlot.setAttribute('aria-hidden', 'false');

        function buildIframeSrc(kind, rawUrl, resumeSec) {
            var raw = String(rawUrl || '').trim();
            if (kind === 'youtube') {
                var m = raw.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/))([a-zA-Z0-9_-]{11})/);
                var id = m ? m[1] : raw;
                var p  = 'autoplay=1&rel=0&modestbranding=1&playsinline=1';
                if (resumeSec > 0) p += '&start=' + Math.floor(resumeSec);
                return 'https://www.youtube.com/embed/' + id + '?' + p;
            }
            if (kind === 'vimeo') {
                var v   = raw.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                var vid = v ? v[1] : raw;
                return 'https://player.vimeo.com/video/' + vid + '?autoplay=1&playsinline=1' +
                       (resumeSec > 0 ? '#t=' + Math.floor(resumeSec) + 's' : '');
            }
            var emb = raw.match(/src=["']([^"']+)["']/i);
            return emb ? emb[1] : raw;
        }

        function mountIframeInSlot(src) {
            const slot = document.getElementById('mainArtplayerSlot');
            if (!slot) return;
            slot.style.display = 'block';
            slot.setAttribute('aria-hidden', 'false');
            slot.innerHTML =
                '<iframe src="' + src + '" ' +
                'style="position:absolute;inset:0;width:100%;height:100%;border:0;" ' +
                'allow="autoplay; fullscreen; picture-in-picture; encrypted-media" ' +
                'allowfullscreen loading="eager"></iframe>';
        }

        /* YouTube / Vimeo / Embedded without pre-roll: skip Artplayer entirely and mount iframe. */
        if (isIframeVideoType && prerollQueue.length === 0) {
            mountIframeInSlot(buildIframeSrc(videoType, videoUrl, resumeTime));
            return;
        }

        playerSlot.innerHTML = `<div id="artplayer" style="width:100%;height:100%"></div>`;

        const subtitleStyle = {
            color: '#ffffff',
            'font-size': 'max(14px, 1.05em)',
            'font-weight': '500',
            'line-height': 1.45,
            background: 'transparent',
            padding: '4px 12px',
            'text-shadow': '0 2px 4px rgba(0,0,0,0.95), 0 0 6px rgba(0,0,0,0.75)',
        };

        function inferSubtitleFormat(url) {
            if (!url) return 'srt';
            var path = String(url).split('?')[0];
            var ext = path.split('.').pop().toLowerCase();
            if (ext === 'vtt') return 'vtt';
            if (ext === 'ass' || ext === 'ssa') return 'ass';
            return 'srt';
        }

        function isSubtitleDefaultFlag(v) {
            return v === true || v === 1 || v === '1';
        }

        let artRef = null;
        let midRollMgr = null;
        /*
         * Build the quality selector list for Artplayer.
         *
         * IMPORTANT: exactly ONE entry must be marked as `default: true`.
         * Previously we did `default: q.url === videoUrl`, which marked every
         * entry with the same URL as the active one — a very common case when
         * multiple quality labels point at the same HLS master playlist or the
         * same MP4. That produced a checkmark next to every quality option.
         *
         * We resolve the default like this:
         *   1. First entry whose URL matches the currently-playing URL.
         *   2. Otherwise, the first entry in the playlist.
         */
        const qualityForArt = (function () {
            if (!qualityPlaylist.length) return [];

            var defaultIdx = qualityPlaylist.findIndex(function (q) {
                return q && q.url === videoUrl;
            });
            if (defaultIdx < 0) defaultIdx = 0;

            return qualityPlaylist.map(function (q, idx) {
                return {
                    html: q.label,
                    url: q.url,
                    default: idx === defaultIdx,
                    _kind: q._kind || 'native',
                };
            });
        })();

        const hasDefaultSubtitle = subtitleTracks.some(function (t) {
            return isSubtitleDefaultFlag(t.is_default);
        });
        const subtitleSettings = [];
        var subSelector = [];
        const playerUiStrings = {
            quality: @json(__('frontend.player_quality')),
            subtitles: @json(__('frontend.player_subtitles')),
            subtitlesOff: @json(__('frontend.player_subtitles_off')),
        };

        if (subtitleTracks.length > 0) {
            subSelector = [{ html: playerUiStrings.subtitlesOff, url: '', format: 'srt', default: false }];
            var autoDefaultIdx = -1;
            subtitleTracks.forEach(function (t) {
                if (!t.subtitle_file) return;
                subSelector.push({
                    html: t.language || t.language_code || 'Subtitle',
                    url: t.subtitle_file,
                    format: t.format || inferSubtitleFormat(t.subtitle_file),
                    default: isSubtitleDefaultFlag(t.is_default),
                });
                if (autoDefaultIdx < 0) autoDefaultIdx = subSelector.length - 1;
            });
            if (!hasDefaultSubtitle && autoDefaultIdx > 0) {
                subSelector[autoDefaultIdx].default = true;
            }
        }
        function applySubtitle(a, url, format) {
            if (!a || !a.subtitle) return Promise.resolve(false);
            if (!url) {
                try { a.subtitle.show = false; } catch (e) {}
                return Promise.resolve(true);
            }
            var fmt = format || inferSubtitleFormat(url);
            var p;
            try {
                if (typeof a.subtitle.switch === 'function') {
                    p = a.subtitle.switch(url, {
                        type: fmt,
                        style: subtitleStyle,
                        encoding: 'utf-8',
                    });
                } else {
                    a.subtitle.url = url;
                    p = Promise.resolve();
                }
            } catch (err) {
                console.warn('[Artplayer] subtitle.switch threw', err);
                return Promise.resolve(false);
            }
            if (!p || typeof p.then !== 'function') p = Promise.resolve();
            return p.then(function () {
                try { a.subtitle.show = true; } catch (e) {}
                return true;
            }).catch(function (err) {
                console.warn('[Artplayer] subtitle load failed', { url: url, type: fmt, error: err });
                return false;
            });
        }

        if (subSelector.length > 1) {
            subtitleSettings.push({
                html: playerUiStrings.subtitles,
                tooltip: playerUiStrings.subtitles,
                selector: subSelector,
                onSelect: function (item) {
                    applySubtitle(artRef, item.url, item.format);
                    return item.html;
                },
            });
        }

        const settingsForPlayer = [];
        if (qualityForArt.length > 1) {
            // Keep a single source of truth for the selector list so we can
            // flip the `default` flag when the user picks a new quality.
            var qualitySelectorList = qualityForArt.map(function (q) {
                return { html: q.html, url: q.url, default: !!q.default, _kind: q._kind || 'native' };
            });

            function markActiveQuality(selectedItem) {
                qualitySelectorList.forEach(function (row) {
                    row.default = row === selectedItem
                        || (row.html === selectedItem.html && row.url === selectedItem.url);
                });
            }

            settingsForPlayer.push({
                html: playerUiStrings.quality,
                tooltip: playerUiStrings.quality,
                selector: qualitySelectorList,
                onSelect: function (item) {
                    var a = artRef;

                    // Move the checkmark to the just-selected quality so that
                    // re-opening the menu shows only the active one ticked.
                    markActiveQuality(item);

                    // If the user picked an iframe quality, tear down Artplayer and mount an iframe.
                    if (item._kind && item._kind !== 'native') {
                        try { if (midRollMgr) midRollMgr.detach(); } catch (e) {}
                        try { a && a.destroy(); } catch (e) {}
                        artRef = null;
                        mountIframeInSlot(buildIframeSrc(item._kind, item.url, 0));
                        return item.html;
                    }

                    if (!a || typeof a.switchQuality !== 'function') return item.html;

                    var resumeAt = 0;
                    var wasPlaying = false;
                    try {
                        resumeAt = Math.max(0, Number(a.currentTime) || 0);
                        wasPlaying = !a.video || !a.video.paused;
                    } catch (e) {}

                    var restored = false;
                    function applySeek() {
                        if (restored) return;
                        var v = a && a.video;
                        if (!v) return;
                        if (isNaN(v.duration) || v.duration <= 0) return;

                        try {
                            if (resumeAt > 0) {
                                v.currentTime = Math.min(resumeAt, v.duration - 0.1);
                            }
                            if (wasPlaying) {
                                var p = v.play();
                                if (p && typeof p.catch === 'function') p.catch(function () {});
                            }
                            restored = true;
                            a.off('video:loadedmetadata', applySeek);
                            a.off('video:canplay', applySeek);
                            a.off('video:loadeddata', applySeek);
                        } catch (err) {}
                    }

                    try {
                        a.on('video:loadedmetadata', applySeek);
                        a.on('video:canplay', applySeek);
                        a.on('video:loadeddata', applySeek);
                    } catch (eBind) {}

                    // Fallback poll for HLS streams where events don't fire as expected.
                    var pollAttempts = 0;
                    var pollTimer = setInterval(function () {
                        pollAttempts++;
                        if (restored || pollAttempts > 30) {
                            clearInterval(pollTimer);
                            return;
                        }
                        applySeek();
                    }, 300);

                    try {
                        a.switchQuality(item.url);
                    } catch (eSwitch) {
                        console.warn('switchQuality failed', eSwitch);
                    }

                    return item.html;
                },
            });
        }
        subtitleSettings.forEach(function (row) {
            settingsForPlayer.push(row);
        });

        var defaultSubtitle = subtitleTracks.find(function (t) {
            return isSubtitleDefaultFlag(t.is_default);
        });
        var initialSubtitle = null;
        if (defaultSubtitle && defaultSubtitle.subtitle_file) {
            initialSubtitle = {
                url: defaultSubtitle.subtitle_file,
                type: defaultSubtitle.format || inferSubtitleFormat(defaultSubtitle.subtitle_file),
                encoding: 'utf-8',
                style: subtitleStyle,
            };
        } else if (!hasDefaultSubtitle && subSelector.length > 1) {
            var preload = subtitleTracks.find(function (t) {
                return t.subtitle_file;
            });
            if (preload && preload.subtitle_file) {
                initialSubtitle = {
                    url: preload.subtitle_file,
                    type: preload.format || inferSubtitleFormat(preload.subtitle_file),
                    encoding: 'utf-8',
                    style: subtitleStyle,
                };
            }
        }

        /* Phase tracks which URL is currently loaded in Artplayer. */
        let adPhase = prerollQueue.length > 0;
        const initialUrl = adPhase
            ? window.OTTPlayerAds.resolveMediaUrl(prerollQueue[0].media, apiBaseUrl)
            : videoUrl;
        /*
         | HLS re-attach happens on EVERY quality/subtitle change. Seeking to `resumeTime`
         | inside MANIFEST_PARSED must therefore be ONE-SHOT — otherwise selecting a quality
         | mid-movie yanks playback back to the old resume position.
         */
        let mainHlsInitialSeekApplied = resumeTime <= 0;

        const artOptions = {
            container: '#artplayer',
            url: initialUrl,
            lang: @json(str_replace('_', '-', app()->getLocale())),
            autoplay: true,
            currentTime: adPhase ? 0 : resumeTime,
            fullscreen: !adPhase,
            hotkey: !adPhase,
            pip: !adPhase,
            setting: settingsForPlayer.length > 0,
            settings: settingsForPlayer,
            customType: {
                m3u8(video, url) {
                    /* Tear down previous Hls on switchUrl so MP4 ad → HLS movie (or vice versa) works cleanly. */
                    if (art && art._ottHls) {
                        try { art._ottHls.destroy(); } catch (e) {}
                        art._ottHls = null;
                    }
                    if (window.Hls && Hls.isSupported()) {
                        const hls = new Hls();
                        hls.loadSource(url);
                        hls.attachMedia(video);
                        if (art) art._ottHls = hls;

                        if (!adPhase && !mainHlsInitialSeekApplied && url === videoUrl) {
                            mainHlsInitialSeekApplied = true;
                            hls.on(Hls.Events.MANIFEST_PARSED, function () {
                                try { video.currentTime = resumeTime; } catch (e) {}
                            });
                        }
                    }
                },
            },
        };
        if (!adPhase && initialSubtitle) {
            artOptions.subtitle = initialSubtitle;
        }

        const art = new Artplayer(artOptions);
        artRef = art;

        art.on('destroy', function () {
            if (art._ottHls) {
                try { art._ottHls.destroy(); } catch (e) {}
                art._ottHls = null;
            }
        });

        function describeMediaError(video) {
            try {
                var err = video && video.error;
                if (!err) return null;
                var codes = { 1: 'MEDIA_ERR_ABORTED', 2: 'MEDIA_ERR_NETWORK', 3: 'MEDIA_ERR_DECODE', 4: 'MEDIA_ERR_SRC_NOT_SUPPORTED' };
                return { code: err.code, name: codes[err.code] || 'UNKNOWN', message: err.message || '', src: video.currentSrc || '' };
            } catch (e) { return null; }
        }

        art.on('video:error', function (e) {
            var info = describeMediaError(art && art.video);
            console.warn('[Artplayer] video error', info || e);
            if (info && info.code === 2) {
                setTimeout(function () { try { art.video.load(); art.play(); } catch (_) {} }, 1000);
            }
        });

        art.on('error', function (err) {
            console.warn('[Artplayer] error', err);
        });

        (function suppressArtplayerPromiseRejections() {
            if (window.__artUnhandledHooked) return;
            window.__artUnhandledHooked = true;
            window.addEventListener('unhandledrejection', function (event) {
                var reason = event && event.reason;
                if (reason && reason.type === 'error' && reason.target && reason.target.tagName === 'VIDEO') {
                    var info = describeMediaError(reason.target);
                    console.warn('[Artplayer] unhandled video play() rejection', info || reason);
                    event.preventDefault();
                }
            });
        })();

        function attachMidRoll() {
            if (
                !midrollCues.length ||
                typeof window.OTTMidRollManager !== 'function' ||
                typeof window.OTTPlayerAds === 'undefined' ||
                typeof window.OTTPreRoll === 'undefined'
            ) {
                return;
            }
            midRollMgr = new window.OTTMidRollManager({
                art: art,
                cuePoints: midrollCues,
                initialContentTime: resumeTime,
                mode: 'in-player',
                playerAds: window.OTTPlayerAds,
                ottPreRoll: window.OTTPreRoll,
                apiBaseUrl: apiBaseUrl,
                csrfToken: '{{ csrf_token() }}',
                contentId: entertainmentId,
                contentType: entertainmentType,
                categoryIdValue: categoryId,
                mainVideoUrl: videoUrl,
                container: container,
                playerSlot: document.getElementById('mainArtplayerSlot'),
                overlayHost: container,
            });
            midRollMgr.attach();
        }

        /** Swap the ad that is currently loaded in Artplayer for the real movie. */
        function loadMainFeature() {
            adPhase = false;

            /* YouTube / Vimeo / Embedded: Artplayer cannot play these inside a <video>.
               Destroy Artplayer and mount an iframe. Mid-roll is skipped for iframe types
               (no reliable cross-origin way to pause & resume them). */
            if (isIframeVideoType) {
                if (midRollMgr && typeof midRollMgr.detach === 'function') {
                    midRollMgr.detach();
                    midRollMgr = null;
                }
                try { art.destroy(); } catch (e) {}
                artRef = null;
                mountIframeInSlot(buildIframeSrc(videoType, videoUrl, resumeTime));
                return;
            }

            try {
                if (typeof art.switchUrl === 'function') {
                    art.switchUrl(videoUrl);
                } else {
                    art.url = videoUrl;
                }
            } catch (eSwap) {
                console.error('switchUrl failed', eSwap);
            }
            /* Re-enable features disabled during the ad phase. */
            try { art.option.hotkey = true; } catch (e) {}
            try { art.option.pip = true; } catch (e) {}
            try { art.option.fullscreen = true; } catch (e) {}

            setTimeout(function () {
                try {
                    if (resumeTime > 0 && art.video && art.video.currentTime < 1) {
                        art.currentTime = resumeTime;
                    }
                } catch (e) {}
                if (initialSubtitle) {
                    applySubtitle(art, initialSubtitle.url, initialSubtitle.type);
                }
                try {
                    var p = art.play && art.play();
                    if (p && typeof p.catch === 'function') p.catch(function () {});
                } catch (ePlay) {}
                attachMidRoll();
            }, 120);
        }

        if (adPhase) {
            /* Run queue in-player, then swap to movie. Artplayer already loaded ad[0], so runInPlayer will
               replay it cleanly as ad[0] — we want the correct timer/skip/analytics UI for every entry. */
            window.OTTPlayerAds.runInPlayer(art, prerollQueue, {
                baseUrl: apiBaseUrl,
                maxAdDurationMs: 4 * 60 * 1000,
                container: container,
                strings: {
                    skipLabel: @json(__('frontend.skip_ad')),
                    adPrefix: @json(rtrim(__('frontend.ad_timer_prefix'), ':')),
                    adsSeparator: @json(__('frontend.ads_separator')),
                    timerUnit: @json(' ' . __('frontend.ad_seconds_unit')),
                },
                onAnalytics: function (evt) {
                    try {
                        window.dispatchEvent(new CustomEvent('ott:preroll', { detail: evt }));
                    } catch (e) {}
                },
            }).then(loadMainFeature, loadMainFeature);
        } else {
            attachMidRoll();
        }

        art.on('ready', () => {
            if (!adPhase && resumeTime > 0 && art.currentTime < 1) {
                art.currentTime = resumeTime;
            }
            if (!adPhase && initialSubtitle) {
                applySubtitle(art, initialSubtitle.url, initialSubtitle.type);
            }
        });

        /* -------------------------------------------------
        | 💾 SAVE CONTINUE WATCH (EVERY 15s)
        ------------------------------------------------- */
        let lastSaved = 0;

        let lastFlushedCw = 0;
        function flushContinueWatch() {
            if (adPhase || !art || !art.video) return;
            var ct = Math.floor(art.video.currentTime || 0);
            var dur = Math.floor(art.video.duration || 0);
            if (ct < 2 || !dur) return;
            if (ct === lastFlushedCw) return;
            lastFlushedCw = ct;
            fetch('{{ route("frontend.continueWatch.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    entertainment_id: entertainmentId,
                    entertainment_type: entertainmentType,
                    profile_id: profileId,
                    watched_time: ct,
                    total_time: dur
                })
            }).catch(function () {});
        }

        art.on('video:timeupdate', () => {
            if (adPhase) return;
            if (art.video.currentTime - lastSaved < 15) return;

            lastSaved = Math.floor(art.video.currentTime);

            fetch('{{ route("frontend.continueWatch.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    entertainment_id: entertainmentId,
                    entertainment_type: entertainmentType,
                    profile_id: profileId,
                    watched_time: Math.floor(art.video.currentTime),
                    total_time: Math.floor(art.video.duration)
                })
            }).catch(function () {});
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') flushContinueWatch();
        });
        window.addEventListener('pagehide', function () {
            flushContinueWatch();
        });
        
        /* -------------------------------------------------
        | 🎫 PPV 25% WATCH THRESHOLD (GET TICKET AGAIN)
        ------------------------------------------------- */
        let lastReportedPercent = 0;
        let ticketLocked = false;
        
        if (isPPV) {
            art.on('video:timeupdate', async () => {
                if (adPhase) return;
                if (!art.video.duration || ticketLocked) return;
        
                const percent = Math.floor(
                    (art.video.currentTime / art.video.duration) * 100
                );
        
                // Report only every 5%
                if (percent - lastReportedPercent < 5) return;
                lastReportedPercent = percent;
        
                try {
                    const res = await fetch('{{ route("frontend.updateWatchProgress") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            entertainment_id: entertainmentId,
                            entertainment_type: entertainmentType,
                            watched_percentage: percent,
                            last_time_seconds: Math.floor(art.video.currentTime)
                        })
                    });
        
                    const data = await res.json();
        
                    // 🔥 Ticket consumed at 25%
                    if (data.status === 'get_ticket') {
                        ticketLocked = true;
        
                        if (midRollMgr && typeof midRollMgr.detach === 'function') {
                            midRollMgr.detach();
                        }
                        art.pause();
                        art.destroy();
                        const lockedSlot = document.getElementById('mainArtplayerSlot');
                        if (lockedSlot) {
                            lockedSlot.innerHTML = '';
                            lockedSlot.style.display = 'none';
                            lockedSlot.setAttribute('aria-hidden', 'true');
                        }

                        const wrapper = document.querySelector('.play-button-wrapper');
                        if (wrapper) {
                            wrapper.innerHTML = `
                                <a href="${paymentUrl}" class="btn btn-primary">
                                    <i class="fa-solid fa-ticket me-2"></i>
                                    Get Ticket Again
                                </a>
                            `;
                        }
        
                        if (overlay) {
                            overlay.style.display = 'block';
                        }
                    }
        
                } catch (e) {
                    console.error('PPV progress update failed', e);
                }
            });
        }


        /* -------------------------------------------------
        | 🧾 PPV CONSUME AT END
        ------------------------------------------------- */
        if (isPPV) {
            art.on('video:ended', async () => {
                if (adPhase) return;
                await fetch('{{ route("ppv.consumeTicket") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ entertainment_id: entertainmentId })
                });

                window.location.href = paymentUrl;
            });
        }
    });
});
</script>
@endpush
