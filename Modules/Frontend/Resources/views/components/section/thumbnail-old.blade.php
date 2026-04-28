<style>
/* Ensure overlay scrollable on mobile if content long */
.movie-overlay {
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
</style>


<style>
.video-player-wrapper { position: relative; width: 100%; height: 100%; overflow: hidden; }
#videoPlayer { width: 100%; height: auto; object-fit: cover; pointer-events: none; }
.movie-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to top, rgba(0,0,0,0.75) 20%, rgba(0,0,0,0.1) 80%); display: flex; align-items: flex-end; justify-content: flex-start; padding: 60px; color: #fff; z-index: 5; transition: opacity 0.4s ease; overflow-y: auto; }
.movie-overlay-content { max-width: 600px; }
.movie-title { font-size: 40px; font-weight: 700; margin-bottom: 15px; }
.movie-description { font-size: 16px; opacity: 0.9; margin-bottom: 25px; }
.btn-watch-now { background: #e50914; color: white; border: none; padding: 12px 28px; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s ease; }
.btn-watch-now:hover { background: #b20710; }
@media (max-width: 768px) {
    .movie-overlay { padding: 25px; align-items: flex-end; }
    .movie-title { font-size: 26px; }
    .movie-description { font-size: 14px; }
}
</style>
<div class="detail-page-banner">
    <div class="video-player-wrapper">
        <!-- Video.js core -->
        <link rel="stylesheet" href="{{ asset('css/video-js.css') }}" />
        <script src="{{ asset('js/videojs/video.min.js') }}"></script>

        <!-- YouTube Support -->
        <script src="{{ asset('js/videojs/videojs-youtube.min.js') }}"></script>

        <!-- IMA SDK -->
        <script src="{{ asset('js/videojs/ima3.js') }}"></script>

        <!-- Video.js Ads & IMA plugins -->
        <script src="{{ asset('js/videojs/videojs-contrib-ads.min.js') }}"></script>
        <script src="{{ asset('js/videojs/videojs.ima.min.js') }}"></script>
        <link href="{{ asset('css/videojs.ima.css') }}" rel="stylesheet">

        <div class="video-player">
           <video id="videoPlayer" class="video-js vjs-default-skin vjs-ima" controls width="100%" height="315" muted
                data-setup='{"muted": true}' data-type="{{ $type }}"
                content-video-type="{{ $content_video_type }}"
                data-continue-watch="{{ isset($continue_watch) && $continue_watch ? 'true' : 'false' }}"
                @if ($type != 'Local') data-watch-time="{{ $watched_time ?? 0 }}"
                    data-movie-access="{{ $dataAccess ?? '' }}"
                    data-encrypted="{{ $video_url_input }}"
                @endif
                @if (isset($content_type) && isset($content_id)) data-contentType="{{ $content_type }}"
                    data-contentId="{{ $content_id }}"
                @endif
            >
                @if ($type == 'Local')
                    <source src="{{ $video_url_input }}" type="video/mp4" id="videoSource">
                @endif
            </video>

            <!-- Vimeo iframe for Vimeo videos -->
            <div id="vimeoContainer">
                <iframe id="vimeoIframe" frameborder="0"
                    allow="autoplay; fullscreen; picture-in-picture" allowfullscreen>
                </iframe>
            </div>



            <!-- Overlay with FULL movie details -->
            <div class="movie-overlay">
                <div class="movie-overlay-content">

                    <!-- Movie Title & Description -->
                    <h1 class="movie-title">{{ $data['name'] }}</h1>
                    <p class="movie-description">{!! $data['description'] !!}</p>

                    <!-- Genres -->
                    <ul class="genres-list ps-0 mb-2 d-flex flex-wrap align-items-center gap-2">
                        @if(isset($data['genres']) && $data['genres']->isNotEmpty())
                            @foreach($data['genres'] as $index => $genreResource)
                                <li class="position-relative fw-semibold d-flex align-items-center">
                                    {{ $genreResource->name ?? '--' }}
                                    @if(!$loop->last)
                                        <span class="mx-1">•</span>
                                    @endif
                                </li>
                            @endforeach
                        @else
                            <li>No genres found</li>
                        @endif
                    </ul>

                    <!-- Metadata (Year, Language, Duration, IMDb) -->
                    <ul class="list-inline mt-2 mb-3 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
                        <li><span class="fw-medium">{{ \Carbon\Carbon::parse($data['release_date'])->format('Y') }}</span></li>
                        <li>{{ ucfirst($data['language']) }}</li>
                        <li>{{ $data['duration'] ? formatDuration($data['duration']) : '--' }}</li>
                        <!--@if ($data['imdb_rating'])-->
                        <!--    <li>{{ $data['imdb_rating'] }} (IMDb)</li>-->
                        <!--@endif-->
                    </ul>

                    <!-- Actions: Rate, Watchlist, Share, Like, Cast -->
                    <div class="overlay-actions d-flex flex-wrap align-items-center gap-3 mb-3">
                        @if($data['your_review'] == null)
                            @if(Auth::check())
                                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#rattingModal" data-entertainment-id="{{ $data['id'] }}">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="text-warning"><i class="ph-fill ph-star"></i></span>
                                        <span>{{ __('frontend.rate_this') }}</span>
                                    </span>
                                </button>
                            @else
                                <a href="{{ url('/login') }}" class="btn btn-dark">
                                    <span class="d-flex align-items-center gap-2">
                                        <span class="text-warning"><i class="ph-fill ph-star"></i></span>
                                        <span>{{ __('frontend.rate_this') }}</span>
                                    </span>
                                </a>
                            @endif
                        @endif

                        <x-watchlist-button :entertainment-id="$data['id']" :in-watchlist="$data['is_watch_list'] ?? false" :entertainmentType="$data['type']" customClass="watch-list-btn" />

                        <x-like-button :entertainmentId="$data['id']" :isLiked="$data['is_likes']" :type="$data['type']"/>

                        <div class="share-button dropend dropdown">
                            <button type="button" class="action-btn btn btn-dark" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ph ph-share-network"></i>
                            </button>
                            <div class="share-wrapper">
                                <div class="share-box dropdown-menu">
                                    <svg width="15" height="40" viewBox="0 0 15 40" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.8842 40C6.82983 37.2868 1 29.3582 1 20C1 10.6418 6.82983 2.71323 14.8842 0H0V40H14.8842Z" fill="currentColor"/>
                                    </svg>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <a href="https://www.facebook.com/sharer?u={{ urlencode(Request::url()) }}" target="_blank" class="share-ico"><i class="ph ph-facebook-logo"></i></a>
                                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($data['name']) }}&url={{ urlencode(Request::url()) }}" target="_blank" class="share-ico"><i class="ph ph-x-logo"></i></a>
                                        <a href="#" data-link="{{ Request::url() }}" class="share-ico iq-copy-link"><i class="ph ph-link"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cast button -->
                        @php
                        $video_upload_type = $data['video_upload_type'];
                        $plan_type = getActionPlan('video-cast');
                        $video_url11 = ($video_upload_type == "URL") ? Crypt::decryptString($video_url_input) : $video_url_input;
                        @endphp
                        @if(!empty($plan_type) && ($video_upload_type == "Local" || $video_upload_type == "URL"))
                            <button class="action-btn btn btn-dark" data-name="{{ $video_url11 }}" id="castme">
                                <i class="ph ph-screencast"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Watch Now button -->
                    <button class="btn-watch-now" id="watchNowButton"
                        data-video-url="{{ $video_url_input }}"
                        data-type="{{ $type }}"
                    >
                        Watch Now
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Video.js script if not already -->
<script src="{{ asset('js/videoplayer.min.js') }}"></script>
<script>
    var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    var loginUrl = "{{ route('login') }}";  // Update with your actual login route
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const player = videojs('videoPlayer', {
        autoplay: true,
        muted: true,
        loop: true,
        controls: false,
        preload: 'auto',
        techOrder: ['youtube', 'vimeo', 'html5'],
        youtube: {
            autoplay: 1,
            controls: 0,
            disablekb: 1,
            modestbranding: 1,
            rel: 0,
            fs: 0,
            iv_load_policy: 3,
            showinfo: 0
        },
        vimeo: {
            autoplay: true,
            controls: false,
            muted: true,
            loop: true,
            title: false,
            byline: false,
            portrait: false
        }
    });

    // Prevent click/pause on background trailer
    player.ready(() => {
        player.play().catch(() => {});
        player.el().style.pointerEvents = 'none';
    });

    const overlay = document.querySelector('.movie-overlay');
    const watchBtn = document.getElementById('watchNowButton');

    watchBtn.addEventListener('click', function () {
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        setTimeout(() => overlay.style.display = 'none', 500);

        const videoUrl = this.getAttribute('data-video-url');
        const type = this.getAttribute('data-type');

        // Enable player interactions
        player.el().style.pointerEvents = 'auto';
        player.controls(true);
        player.muted(false);
        player.loop(false);

        // Set new source based on type
        if (type === 'Local') {
            player.src({ type: 'video/mp4', src: videoUrl });
        } 
        else if (type === 'YouTube') {
            // Handle full YouTube URL or just video ID
            const youtubeId = videoUrl.includes('youtube.com') 
                ? new URL(videoUrl).searchParams.get('v') 
                : videoUrl;
            player.src({ type: 'video/youtube', src: youtubeId });
        } 
        else if (type === 'Vimeo') {
            const vimeoId = videoUrl.split('/').pop();
            player.src({ type: 'video/vimeo', src: vimeoId });
        }

        player.ready(() => {
            player.play().catch(err => console.log('Autoplay blocked:', err));
        });
    });
});
</script>

