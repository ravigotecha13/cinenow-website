@php
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$userId = auth()->id();
$entertainmentId = $data['id'] ?? null;
$contentType = $content_type ?? $data['type'] ?? 'movie';

/* -------------------------------------------------
| RELEASE CHECK
------------------------------------------------- */
$isReleased = true;
if (!empty($data['start_date'])) {
    try {
        $isReleased = Carbon::parse($data['start_date'])->lte(now());
    } catch (\Exception $e) {
        $isReleased = true;
    }
}

/* -------------------------------------------------
| TRAILER + VIDEO
------------------------------------------------- */
$trailerUrl  = $data['trailer_url'] ?? '';
$trailerType = $data['trailer_url_type'] ?? 'URL';
$video_url   = $data['video_url_input'] ?? '';

/* -------------------------------------------------
| PPV + PRICE
------------------------------------------------- */
$isMoviePPV = (($data['movie_access'] ?? $data['access'] ?? '') === 'pay-per-view');
$finalPrice = isset($data['price'])
    ? $data['price'] - ($data['price'] * ($data['discount'] ?? 0) / 100)
    : 0;

/* -------------------------------------------------
| QUALITY + SUBTITLES
------------------------------------------------- */
$qualityOptions = [];
if (!empty($data['video_links'])) {
    foreach ($data['video_links'] as $link) {
        $qualityOptions[$link->quality] = [
            'value' => $link->type === 'Local'
                ? setBaseUrlWithFileName($link->url)
                : $link->url,
            'type' => $link->type,
        ];
    }
}

$subtitleInfo = [];
if (!empty($data['subtitle_info'])) {
    $subtitleInfo = $data['subtitle_info']->toArray(request());
}

$qualityOptionsJson = json_encode($qualityOptions);
$subtitleInfoJson   = json_encode($subtitleInfo);

/* -------------------------------------------------
| PPV STATE
------------------------------------------------- */
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

        $watchedPercent = (int)($progress->watched_percentage ?? 0);
    }
}

$canContinue = $activeTicket !== null;
$showRepay   = $activeTicket && $watchedPercent >= 25 && $watchedPercent < 98;
@endphp

@php
    $displayName = app()->getLocale() === 'ar'
        ? ($data['name_ar'] ?? $data['name'] ?? '')
        : ($data['name'] ?? '');
    $displayDescription = app()->getLocale() === 'ar'
        ? ($data['description_ar'] ?? $data['description'] ?? '')
        : ($data['description'] ?? '');
@endphp

@php
$profileId = getCurrentProfile($userId, request());
@endphp

<div id="video-section" class="video-player-wrapper position-relative">

    <div id="videoContainer" class="position-relative w-100">

        {{-- 📺 CUSTOM AD OVERLAY --}}
        <div id="customAdModal" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:black; z-index:20; align-items:center; justify-content:center;">
            <div id="customAdContent" style="width:100%; height:100%; position:relative; display:flex; align-items:center; justify-content:center;">
                {{-- Ad content injected here --}}
            </div>
            <button id="customAdCloseBtn" style="position:absolute; top:20px; right:20px; z-index:21; background:rgba(0,0,0,0.5); color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; display:none;">
                Skip Ad
            </button>
            <div id="adTimer" style="position:absolute; bottom:20px; right:20px; z-index:21; color:white; font-size:14px; background:rgba(0,0,0,0.5); padding:4px 8px; border-radius:4px; display:none;">
                Ad: <span id="adTimeRemaining"></span>
            </div>
        </div>

        {{-- 🎞️ TRAILER --}}
        <video id="trailerPlayer"
               class="w-100"
               preload="metadata"
               muted
               playsinline
               poster="{{ $data['thumbnail_image'] ?? '' }}">
            @if ($trailerUrl)
                <source src="{{ $trailerUrl }}"
                        type="{{ $trailerType === 'HLS' ? 'application/x-mpegURL' : 'video/mp4' }}">
            @endif
        </video>

        {{-- 🔊 MUTE --}}
        <button id="muteToggleBtn" class="mute-btn">
            <i class="fa-solid fa-volume-mute"></i>
        </button>

        {{-- 🎭 OVERLAY --}}
        <div class="movie-overlay">
            <div class="movie-overlay-content">

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
                                {{ __('frontend.get_ticket') }}
                                <span class="ms-2">{{ Currency::format($finalPrice,2) }}</span>
                            </a>
                
                        {{-- PURCHASED --}}
                        @else
                            <button id="watchNowBtn"
                                class="btn btn-primary btn-watch-now me-2"
                                data-entertainment-id="{{ $entertainmentId }}"
                                data-entertainment-type="{{ $contentType }}"
                                data-profile-id="{{ $profileId }}"
                                data-is-ppv="true"
                                data-payment-url="{{ route('pay-per-view.paymentform',['id'=>$entertainmentId,'type'=>$contentType]) }}"
                                data-video-url="{{ $video_url }}"
                                data-quality-options='{{ $qualityOptionsJson }}'
                                data-subtitle-info='{{ $subtitleInfoJson }}'>
                                {{ $watchedPercent > 0 ? __('frontend.continue_watching') : __('frontend.watch_now') }}
                            </button>

                            @if ($watchedPercent >= 25)
                                <a href="{{ route('pay-per-view.paymentform', ['id'=>$entertainmentId,'type'=>$contentType]) }}"
                                   class="btn btn-primary">
                                    <i class="fa-solid fa-ticket me-2"></i> {{ __('frontend.get_ticket_again') }}
                                </a>
                            @endif

                        @endif
                
                    {{-- FREE MOVIE --}}
                    @else
                       <button id="watchNowBtn"
        class="btn btn-primary btn-watch-now"
        data-entertainment-id="{{ $entertainmentId }}"
        data-entertainment-type="{{ $contentType }}"
        data-profile-id="{{ $profileId }}"
        data-is-ppv="false"
        data-video-url="{{ $video_url }}"
        data-quality-options='{{ $qualityOptionsJson }}'
        data-subtitle-info='{{ $subtitleInfoJson }}'>
    {{ __('frontend.watch_now') }}
</button>

                    @endif
                
                </div>


            </div>
        </div>

    </div>

  
</div>

<script src="https://cdn.jsdelivr.net/npm/artplayer/dist/artplayer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const watchBtn  = document.getElementById('watchNowBtn');
    const overlay   = document.querySelector('.movie-overlay');
    const container = document.getElementById('videoContainer');

    if (!watchBtn) return;

    watchBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        const entertainmentId   = this.dataset.entertainmentId;
        const entertainmentType = this.dataset.entertainmentType;
        const profileId         = this.dataset.profileId;
        const isPPV             = this.dataset.isPpv === 'true';
        const paymentUrl        = this.dataset.paymentUrl || '';
        const qualities         = JSON.parse(this.dataset.qualityOptions || '{}');
        const videoUrl          = Object.values(qualities)[0]?.value || this.dataset.videoUrl;

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
                        'Content-Type': 'application/json'
                    },
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
        | 📺 FETCH & PLAY CUSTOM ADS (PRE-ROLL)
        ------------------------------------------------- */
        async function playPreRollAd() {
            return new Promise(async (resolve) => {
                try {
                    const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
                    const url = `${baseUrl}/api/custom-ads/get-active?content_id=${entertainmentId}&type=${entertainmentType}`;
            
            const res = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                credentials: 'include'
            });
            const json = await res.json();
                    
                    if (!json.success || !Array.isArray(json.data)) {
                        resolve(); // No ads or error
                        return;
                    }

                    // Find ad for player placement
                    const ad = json.data.find(item => item.placement === 'player' && item.status == 1);
                    
                    if (!ad) {
                        resolve(); // No player ad
                        return;
                    }

                    // Show Ad Modal
                    const modal = document.getElementById('customAdModal');
                    const content = document.getElementById('customAdContent');
                    const closeBtn = document.getElementById('customAdCloseBtn');
                    const timerDiv = document.getElementById('adTimer');
                    const timeSpan = document.getElementById('adTimeRemaining');

                    if (!modal || !content) {
                        resolve();
                        return;
                    }

                    // Hide overlay elements
                    overlay.style.display = 'none';
                    modal.style.display = 'flex';
                    content.innerHTML = '';
                    closeBtn.style.display = 'none';
                    timerDiv.style.display = 'none';

                    let adFinished = false;
                    let skipTimer = null;

                    const finishAd = () => {
                        if (adFinished) return;
                        adFinished = true;
                        if (skipTimer) clearTimeout(skipTimer);
                        modal.style.display = 'none';
                        content.innerHTML = ''; // Cleanup
                        resolve();
                    };

                    closeBtn.onclick = finishAd;

                    // Handle Skip Button
                    if (ad.skip_after > 0) {
                        setTimeout(() => {
                            if (!adFinished) {
                                closeBtn.style.display = 'block';
                            }
                        }, ad.skip_after * 1000);
                    } else if (ad.skip_after === 0) {
                         closeBtn.style.display = 'block';
                    }

                    // Render Ad Content
                    if (ad.type === 'image') {
                        const duration = ad.duration || 10; // Default 10s for images
                        const imgSrc = ad.url_type === 'url' ? ad.media : `${baseUrl}${ad.media}`;
                        
                        let imgHtml = `<img src="${imgSrc}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
                        if (ad.redirect_url) {
                            imgHtml = `<a href="${ad.redirect_url}" target="_blank">${imgHtml}</a>`;
                        }
                        content.innerHTML = imgHtml;

                        // Timer for image
                        let timeLeft = duration;
                        timerDiv.style.display = 'block';
                        timeSpan.innerText = timeLeft + 's';

                        const tick = setInterval(() => {
                            if (adFinished) {
                                clearInterval(tick);
                                return;
                            }
                            timeLeft--;
                            timeSpan.innerText = timeLeft + 's';
                            if (timeLeft <= 0) {
                                clearInterval(tick);
                                finishAd();
                            }
                        }, 1000);

                    } else if (ad.type === 'video') {
                         // Check for YouTube
                         const isYouTube = /youtu\.?be/.test(ad.media);
                         
                         if (isYouTube) {
                             let videoId = '';
                             const ytMatch = ad.media.match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
                             if (ytMatch && ytMatch[1]) videoId = ytMatch[1];
                             
                             if (videoId) {
                                 content.innerHTML = `<iframe id="adYtFrame" width="100%" height="100%" src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&controls=0&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
                                 
                                 // YouTube duration is hard to get without API, use ad duration or default
                                 const duration = ad.duration || 30; 
                                 let timeLeft = duration;
                                 timerDiv.style.display = 'block';
                                 timeSpan.innerText = timeLeft + 's';

                                 const tick = setInterval(() => {
                                    if (adFinished) {
                                        clearInterval(tick);
                                        return;
                                    }
                                    timeLeft--;
                                    timeSpan.innerText = timeLeft + 's';
                                    if (timeLeft <= 0) {
                                        clearInterval(tick);
                                        finishAd();
                                    }
                                 }, 1000);
                             } else {
                                 finishAd(); // Invalid YT
                             }

                         } else {
                             // Regular Video (MP4/HLS)
                             const videoUrl = ad.url_type === 'url' ? ad.media : `${baseUrl}${ad.media}`;
                             const isHls = videoUrl.includes('.m3u8');
                             
                             const videoEl = document.createElement('video');
                             videoEl.style.width = '100%';
                             videoEl.style.height = '100%';
                             videoEl.autoplay = true;
                             videoEl.controls = false;
                             videoEl.playsInline = true;
                             // videoEl.muted = true; // Start muted to allow autoplay
                             
                             if (ad.redirect_url) {
                                 videoEl.style.cursor = 'pointer';
                                 videoEl.onclick = () => window.open(ad.redirect_url, '_blank');
                             }

                             content.appendChild(videoEl);

                             if (isHls && Hls.isSupported()) {
                                 const hls = new Hls();
                                 hls.loadSource(videoUrl);
                                 hls.attachMedia(videoEl);
                             } else {
                                 videoEl.src = videoUrl;
                             }
                             
                             videoEl.onended = finishAd;
                             videoEl.onerror = (e) => {
                                 console.error('Ad Playback Error', e);
                                 finishAd();
                             };
                             
                             // Timer based on actual video time
                             videoEl.ontimeupdate = () => {
                                 if (videoEl.duration) {
                                     timerDiv.style.display = 'block';
                                     const remaining = Math.ceil(videoEl.duration - videoEl.currentTime);
                                     timeSpan.innerText = remaining + 's';
                                 }
                             };
                         }
                    }

                } catch (e) {
                    console.error('Error fetching ads:', e);
                    resolve();
                }
            });
        }

        await playPreRollAd();

        /* -------------------------------------------------
        | 🎬 LOAD PLAYER
        ------------------------------------------------- */
        overlay.style.display = 'none';
        container.innerHTML = `<div id="artplayer" style="width:100%;height:100%"></div>`;

        const art = new Artplayer({
            container: '#artplayer',
            url: videoUrl,
            autoplay: true,
            currentTime: resumeTime, // Resume from last watched time
            fullscreen: true,
            hotkey: true,
            pip: true,
            customType: {
                m3u8(video, url) {
                    if (window.Hls && Hls.isSupported()) {
                        const hls = new Hls();
                        hls.loadSource(url);
                        hls.attachMedia(video);

                        if (resumeTime > 0) {
                             hls.on(Hls.Events.MANIFEST_PARSED, function() {
                                 video.currentTime = resumeTime;
                             });
                        }
                    }
                }
            }
        });

        art.on('ready', () => {
            if (resumeTime > 0 && art.currentTime < 1) {
                art.currentTime = resumeTime;
            }
        });

        /* -------------------------------------------------
        | ▶ LOGGING RESUME
        ------------------------------------------------- */
        console.log('Player initialized with resumeTime:', resumeTime);

        /* -------------------------------------------------
        | 💾 SAVE CONTINUE WATCH (EVERY 15s)
        ------------------------------------------------- */
        let lastSaved = 0;

        art.on('video:timeupdate', () => {
            if (art.video.currentTime - lastSaved < 15) return;

            lastSaved = Math.floor(art.video.currentTime);
            console.log('Saving progress:', lastSaved);

            fetch('{{ route("frontend.continueWatch.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    entertainment_id: entertainmentId,
                    entertainment_type: entertainmentType,
                    profile_id: profileId,
                    watched_time: Math.floor(art.video.currentTime),
                    total_time: Math.floor(art.video.duration)
                })
            });
        });
        
        /* -------------------------------------------------
        | 🎫 PPV 25% WATCH THRESHOLD (GET TICKET AGAIN)
        ------------------------------------------------- */
        let lastReportedPercent = 0;
        let ticketLocked = false;
        
        if (isPPV) {
            art.on('video:timeupdate', async () => {
        
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
        
                        art.pause();
                        art.destroy();
        
                        const wrapper = document.querySelector('.play-button-wrapper');
                        if (wrapper) {
                            wrapper.innerHTML = `
                                <a href="${paymentUrl}" class="btn btn-primary">
                                    <i class="fa-solid fa-ticket me-2"></i>
                                    Get Ticket Again
                                </a>
                            `;
                        }
        
                        document.querySelector('.movie-overlay').style.display = 'block';
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


<style>
    .art-control-progress,
    .art-control-progress-inner {
        display: none !important;
    }

    .mute-btn {
        position: absolute;
        bottom: 25px;
        right: 25px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        border-radius: 50%;
        width: 46px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 15;
        transition: background 0.3s ease;
    }

    .mute-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .mute-btn i {
        font-size: 18px;
    }

    .plyr--video {
        --plyr-color-main: #ffffff;
    }

    .plyr__controls {
        background: rgba(0, 0, 0, 0.35) !important;
        border-radius: 0 0 8px 8px;
    }

    .js-minimal-player {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .custom-controls {
        position: absolute;
        bottom: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .video-player-wrapper:hover .custom-controls {
        opacity: 1;
    }

    .custom-controls button,
    .custom-controls select {
        background: rgba(0, 0, 0, 0.7);
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 6px 10px;
        cursor: pointer;
        font-size: 14px;
    }

    .custom-controls button:hover,
    .custom-controls select:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .video-player-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    /* ✅ Prime/Netflix style overlay (left dark band + bottom black fade) */
    .movie-overlay{
      position:absolute;
      inset:0;
      display:flex;
      align-items:flex-end;
      justify-content:flex-start;
      padding:60px;
      color:#fff;
      z-index:5;
      overflow:hidden;
    
      /* IMPORTANT: remove old gradient */
      background: none !important;
    }
    
    /* Left-side darkness for title/description (Prime-like) */
    .movie-overlay::before{
      content:"";
      position:absolute;
      inset:0;
      z-index:0;
      pointer-events:none;
      background: linear-gradient(
        90deg,
        rgba(0,0,0,0.75) 0%,
        rgba(0,0,0,0.55) 28%,
        rgba(0,0,0,0.25) 48%,
        rgba(0,0,0,0.00) 65%
      );
    }
    
    /* Bottom black fade (strong at bottom, clear at top) */
    .movie-overlay::after{
      content:"";
      position:absolute;
      inset:0;
      z-index:0;
      pointer-events:none;
      background: linear-gradient(
        0deg,
        rgba(0,0,0,1.00) 0%,
        rgba(0,0,0,0.92) 18%,
        rgba(0,0,0,0.55) 40%,
        rgba(0,0,0,0.18) 58%,
        rgba(0,0,0,0.00) 72%
      );
    }
    
    /* Make sure text stays above gradients */
    .movie-overlay-content{
      position:relative;
      z-index:1;
    }
    
    /* Optional: improves readability without extra darkness */
    .movie-title,
    .movie-description{
      text-shadow: 0 2px 12px rgba(0,0,0,0.55);
    }


    .movie-overlay-content {
        max-width: 600px;
    }

    .movie-title {
        font-size: 40px;
        font-weight: 700;
        margin-bottom: 15px;
    }

    .movie-description {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 25px;
    }

    .btn-watch-now {
        background: #91969e;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-watch-now:hover {
        background: rgb(116 120 126);
    }

    @media (max-width: 768px) {
        .movie-overlay {
            padding: 25px;
            align-items: flex-end;
        }

        .movie-title {
            font-size: 26px;
        }

        .movie-description {
            font-size: 14px;
        }
    }

    .video-player-wrapper {
        position: relative;
        width: 100%;
        margin: 0 auto;
        height: 650px;
        overflow: hidden;
        max-height: 650px;
    }

    #videoContainer,
    #trailerPlayer,
    #mainPlayer,
    iframe {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    

</style>
