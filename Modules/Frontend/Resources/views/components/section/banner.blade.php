<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>


<style>
.unmute-btn {
    position: absolute;
    bottom: 25px;
    right: 25px;
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
}
.movie-content {
    margin-inline-start: 15px !important;
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
.unmute-btn,
.slick-prev,
.slick-next {
    pointer-events: auto !important;
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
    left: 0;
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

<div class="slick-banner main-banner"
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
                $trailer = $item['video_trailer_url'] ?? null;
                $poster = $item['thumbnail_url'] ?? $item['poster_url'] ?? null;
                $price = $item['price'] ?? null;
                $displayName = app()->getLocale() === 'ar' ? ($item['name_ar'] ?? $item['name'] ?? '') : ($item['name'] ?? '');
                $displayDescription = app()->getLocale() === 'ar'
                    ? ($item['description_ar'] ?? $item['description'] ?? '')
                    : ($item['description'] ?? '');
            @endphp

            @if(isenablemodule($slider['type']) == 1)
                <div class="slick-item position-relative overflow-hidden"
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

                    {{-- 🎥 Trailer Video --}}
                    @if(!empty($trailer))
                        <video class="w-100 h-100 position-absolute top-0 start-0"
                               style="object-fit: cover;"
                               autoplay muted loop playsinline>
                            <source src="{{ setBaseUrlWithFileName($trailer) }}" type="video/mp4">
                        </video>
                    @endif

                    {{-- Overlay --}}
                    <div class="movie-content h-100 position-relative" style="z-index: 3;">
                        <div class="container-fluid h-100">
                            <div class="row align-items-center h-100">
                                <div class="col-xxl-4 col-lg-6">
                                    <div class="movie-info text-white">

                                        <div class="movie-tag mb-3">
                                            <ul class="list-inline m-0 p-0 d-flex align-items-center flex-wrap movie-tag-list">
                                                @foreach($item['genres'] ?? [] as $genre)
                                                    <li><a href="#" class="tag">{{ $genre['name'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <h4 class="mb-2">{{ $displayName }}</h4>
                                        <p class="mb-0 font-size-14 line-count-3">{!! $displayDescription !!}</p>

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
<script>
document.addEventListener('DOMContentLoaded', function () {

    const slickBanner = $('.slick-banner');

    slickBanner.on('init', function (event, slick) {

        if (!slick || !slick.$slides) return;

        const slides = slick.$slides.get(); // SAFE
        const videos = slick.$slides.find('video').get();

        let currentSlide = slick.currentSlide || 0;
        let isMuted = true;
        let posterTimer = null;

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
           POSTER → VIDEO → NEXT SLIDE
        ============================ */
        function showPosterThenVideo(slide) {

            clearTimeout(posterTimer);

            const poster = slide.querySelector('.banner-poster');
            const video  = slide.querySelector('video');

            // NO VIDEO → AUTO MOVE
            if (!video) {
                posterTimer = setTimeout(goNext, 4000);
                return;
            }

            /* 🔴 CRITICAL FIX */
            video.loop = false; // <— REQUIRED
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
                    setTimeout(() => poster.style.display = 'none', 1000);
                }

                video.style.display = 'block';
                video.muted = isMuted;
                video.play().catch(() => {});

            }, 3000);

            /* ✅ THIS WILL NOW FIRE */
            video.onended = function () {
                goNext();
            };
        }

        /* ===========================
           INITIAL SLIDE
        ============================ */
        showPosterThenVideo(slides[currentSlide]);
        updateMuteState();

        slickBanner.on('beforeChange', function (e, slick, current) {
            clearTimeout(posterTimer);
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


