{{-- Font Awesome + Slick CSS are already loaded globally (layouts/header.blade.php
     and components/partials/head/plugins.blade.php). Loading them again here
     was 3 extra render-blocking requests on every page that uses the banner.  --}}


<style>
/* Edge-to-edge hero: break out of any centered/max-width ancestor */
#banner-section,
#movies-page-banner {
    width: 100vw;
    max-width: 100vw;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    padding-left: 0;
    padding-right: 0;
    box-sizing: border-box;
}

#banner-section .js-hero-banner,
#movies-page-banner .js-hero-banner {
    display: block;
    width: 100%;
    max-width: none;
}

/* Never force width on .slick-track / .slick-slide — Slick sets pixel widths; overriding breaks the carousel */

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
.unmute-btn,
.slick-prev,
.slick-next {
    pointer-events: auto !important;
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

@php
    /* Preload the very first hero poster: gives a big LCP win without
       needing a @push('head') (Blade stacks rendered in <head> don't see
       pushes from content sections). This <link> MUST live outside the
       .slick-banner container — Slick treats every direct child as a
       slide, so an extra <link> there produces a phantom blank slide. */
    $__firstHeroPoster = null;
    foreach (($data ?? []) as $__sliderForHint) {
        if (empty($__sliderForHint['data'])) continue;
        $__itemForHint = $__sliderForHint['data']->toArray(request());
        $__firstHeroPoster = $__itemForHint['thumbnail_image']
            ?? $__itemForHint['thumbnail_url']
            ?? $__itemForHint['poster_image']
            ?? $__itemForHint['poster_url']
            ?? null;
        if (!empty($__firstHeroPoster)) break;
    }
    $__heroIndex = 0;
@endphp
@if(!empty($__firstHeroPoster))
    <link rel="preload" as="image" href="{{ setBaseUrlWithFileName($__firstHeroPoster) }}" fetchpriority="high">
@endif
<div class="slick-banner main-banner js-hero-banner"
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
                $isFirstHero = ($__heroIndex === 0);
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

                    {{-- Poster Image (initially visible).
                         All posters are eagerly loaded because Slick positions
                         non-active slides via CSS transforms; browsers do NOT
                         load `loading="lazy"` images on transform-offscreen
                         slides, so lazy here produces blank slides 2+. The
                         first poster still gets fetchpriority=high for LCP. --}}
                    @if(!empty($poster))
                        <img class="w-100 h-100 position-absolute top-0 start-0 banner-poster"
                             style="object-fit: cover; z-index:1;"
                             src="{{ setBaseUrlWithFileName($poster) }}"
                             alt="Poster Image"
                             decoding="async"
                             @if($isFirstHero) fetchpriority="high" @endif>
                    @endif

                    {{-- 🎥 Trailer Video: preload='none' — JS swaps it in after the poster delay.
                         preload='metadata' was eagerly pulling the first bytes of every trailer
                         on page load, hurting Speed Index. --}}
                    @if(!empty($trailer))
                        <video class="w-100 h-100 position-absolute top-0 start-0 banner-trailer-video"
                               style="object-fit: cover; display: none;"
                               muted
                               playsinline
                               preload="none"
                               data-src="{{ setBaseUrlWithFileName($trailer) }}">
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
                                                    @php
                                                        $tagName = app()->getLocale() === 'ar'
                                                            ? ($genre['name_ar'] ?? $genre['name_en'] ?? $genre['name'] ?? '')
                                                            : ($genre['name_en'] ?? $genre['name'] ?? '');
                                                    @endphp
                                                    <li><a href="#" class="tag">{{ $tagName }}</a></li>
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
                                                        <span class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                                            <span dir="auto">{{ __('frontend.get_ticket') }}</span>
                                                            <bdi dir="ltr" class="text-nowrap">{{ Currency::format($price - $price * (($item['discount'] ?? 0) / 100), 2) }}</bdi>
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
                @php $__heroIndex++; @endphp
            @endif
        @endif
    @endforeach

</div>

@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const slickBanner = $('.js-hero-banner');
    if (!slickBanner.length) return;
    if (slickBanner.data('heroBannerInit') === true) return;
    slickBanner.data('heroBannerInit', true);

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

                // Lazy-inject the <source> only now, so we skip initial-page network work.
                if (!video.dataset.srcInjected && video.dataset.src) {
                    const source = document.createElement('source');
                    source.src = video.dataset.src;
                    source.type = 'video/mp4';
                    video.appendChild(source);
                    video.dataset.srcInjected = '1';
                    try { video.load(); } catch (e) {}
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
            startPosterThenTrailer(slide);
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


