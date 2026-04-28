<style>
.ltr-sep {
    direction: ltr;
    unicode-bidi: embed;
}

/* ===== CARD WRAPPER ===== */
.modern-movie-card {
  position: relative !important;
  width: 230px !important;
  border-radius: 18px !important;
  overflow: hidden !important;
  background: #0c0c0c !important;
  box-shadow: 0 8px 28px rgba(0,0,0,0.55) !important;
  transition: transform .35s ease, box-shadow .35s ease !important;
}
.modern-movie-card:hover {
  transform: translateY(-10px) !important;
  box-shadow: 0 14px 40px rgba(0,0,0,0.7) !important;
}

/* ===== IMAGE ===== */
.mm-image {
  position: relative !important;
  height: 320px !important;
  overflow: hidden !important;
}
.mm-image img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  transition: transform .6s ease, filter .5s ease !important;
}
.modern-movie-card:hover .mm-image img {
  transform: scale(1.12) !important;
  filter: brightness(.55) !important;
}

/* ===== PREMIUM LOCK ===== */
.mm-premium {
  position: absolute !important;
  top: 10px !important;
  right: 10px !important;
  z-index: 20 !important;
  background: rgba(255, 215, 0, 0.25) !important;
  backdrop-filter: blur(6px);
  border-radius: 50px !important;
  padding: 6px 10px !important;
  color: gold !important;
  font-size: 14px !important;
}

/* ===== DESCRIPTION PANEL ===== */
.mm-info {
  position: absolute !important;
  bottom: 0 !important;
  width: 100% !important;
  padding: 18px !important;
  background: rgba(0,0,0,0.3) !important;
  backdrop-filter: blur(12px) !important;
  color: #fff !important;
  transform: translateY(100%) !important;
  opacity: 0 !important;
  transition: all .45s ease-in-out !important;
}
.modern-movie-card:hover .mm-info {
  transform: translateY(0) !important;
  opacity: 1 !important;
  background: rgba(0,0,0,0.78) !important;
}

/* ===== TEXT ===== */
.mm-genres {
  display: flex !important;
  gap: 6px !important;
  flex-wrap: wrap !important;
  margin: 0 0 6px 0 !important;
  padding: 0 !important;
  list-style: none !important;
}
.mm-genres li {
  font-size: 12px !important;
  color: #c7c7c7 !important;
}

.mm-title {
  font-size: 17px !important;
  font-weight: 600 !important;
  margin-bottom: 4px !important;
}

.mm-desc {
  font-size: 12px !important;
  color: #d4d4d4 !important;
  margin-bottom: 10px !important;
  line-height: 1.4 !important;
  max-height: 38px !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* ===== META ===== */
.mm-meta {
  font-size: 13px !important;
  color: #bdbdbd !important;
}

/* ===== BUTTONS ===== */
.mm-watchlist {
  background: rgba(255,255,255,.15) !important;
  padding: 6px 10px !important;
  color: #fff !important;
  border: none !important;
  border-radius: 50px !important;
  font-size: 13px !important;
  transition: background .3s ease !important;
  z-index: 99 !important;
}
.mm-watchlist:hover {
  background: rgba(255,255,255,.32) !important;
}

.mm-watchnow {
  background: #a5a8ae !important;
  padding: 8px 12px !important;
  border-radius: 22px !important;
  font-size: 13px !important;
  color: #fff !important;
  font-weight: 600 !important;
  border: none !important;
  width: 100% !important;
  z-index: 99 !important;
}
.mm-watchnow:hover {
  background: #93969d !important;
}

/* Responsive */
@media (max-width: 768px) {
  .modern-movie-card {
    width: 190px !important;
  }
  .mm-image {
    height: 260px !important;
  }
}
</style>
<div class="modern-movie-card mt-3">

    <!-- Fully clickable -->
    <a href="{{ ($value['type'] ?? 'movie') == 'tvshow' ? route('tvshow-details',['id'=>$value['id']]) : route('movie-details',['id'=>$value['id']]) }}"
       class="position-absolute top-0 start-0 end-0 bottom-0 w-100 h-100"
       style="z-index:5;">
    </a>

    <!-- Poster (APIs sometimes expose poster_url / thumbnail only) -->
    <div class="mm-image">
        <img src="{{ setBaseUrlWithFileName($value['poster_image'] ?? $value['poster_url'] ?? $value['thumbnail_image'] ?? $value['thumbnail_url'] ?? null) }}" alt="{{ $value['name'] ?? '' }}">
    </div>

    <!-- PREMIUM LOCK -->
    @php
        $user_plan = auth()->user()?->subscriptionPackage;
        $user_level = $user_plan->level ?? 0;
    @endphp

    @if(($value['movie_access'] ?? '') == 'paid' && ($value['plan_level'] ?? 0) > $user_level)
        <span class="mm-premium"><i class="ph ph-crown-simple"></i></span>
    @endif


    <!-- Info Panel -->
    <div class="mm-info">

        <!-- Genres -->
        <ul class="mm-genres">
            @foreach(collect($value['genres'] ?? [])->slice(0,2) as $gener)
                @php
                    if (is_array($gener)) {
                        $genreName = app()->getLocale() === 'ar'
                            ? ($gener['name_ar'] ?? $gener['name_en'] ?? $gener['name'] ?? '--')
                            : ($gener['name_en'] ?? $gener['name'] ?? '--');
                    } else {
                        $genreName = \Modules\Genres\Support\GenreLocale::name(optional($gener->resource)->genre) ?? '--';
                    }
                @endphp
                <li>{{ $genreName }}</li>
            @endforeach
        </ul>

        <!-- Title -->
        <div class="mm-title">{{ $value['name'] ?? '' }}</div>

        <!-- Meta -->
       <div class="mm-meta mb-2" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
            <i class="ph ph-clock"></i>
        
            {{-- Duration --}}
            @if(app()->getLocale() == 'ar')
                {{ $value['duration'] ?? '--' }}
            @else
                {{ $value['duration'] ? formatDuration($value['duration']) : '--' }}
            @endif
        
            {{-- Separator (always LTR so it looks correct) --}}
            <span dir="ltr"> | </span>
        
            {{-- Language --}}
            <i class="ph ph-translate"></i>
            {{ $value['language'] ?? '--' }}
        </div>


        <!-- Buttons -->
        <div class="d-flex align-items-center gap-2">

            <x-watchlist-button
                :entertainment-id="$value['id']"
                :in-watchlist="$value['is_watch_list'] ?? false"
                :entertainmentType="$value['type'] ?? 'movie'"
                customClass="mm-watchlist" />

            <a href="{{ ($value['type'] ?? 'movie') == 'tvshow' ? route('tvshow-details',['id'=>$value['id']]) : route('movie-details',['id'=>$value['id']]) }}"
               class="mm-watchnow text-center">
               {{ __('frontend.watch_now') }}
            </a>
        </div>

    </div>
</div>


