@php 
    $imgUrl = $movie->poster_url ?? $movie->thumbnail_url ?? asset('default-image/Default-Image.jpg');
@endphp

<div class="modern-movie-card" style="margin-top: -13px !important;">

    <!-- Fully clickable -->
    <a href="{{ route('movie-details', ['id' => $movie->id]) }}"
       class="position-absolute top-0 start-0 end-0 bottom-0 w-100 h-100"
       style="z-index:5;">
    </a>

    <!-- Poster -->
    <div class="mm-image">
        <img src="{{ $imgUrl }}" alt="{{ $movie->name }}">
    </div>

    <!-- Age Restriction (same style as premium) -->
    @if((int) ($movie->is_restricted ?? 0) === 1)
        <span class="mm-premium"><i class="ph ph-shield"></i></span>
    @endif

    <!-- Info Panel -->
    <div class="mm-info">

        <!-- Genres -->
        <ul class="mm-genres">
            @foreach(($movie['genres'] ?? []) as $gener)
                @php
                    $genreName = app()->getLocale() === 'ar'
                        ? ($gener['name_ar'] ?? $gener['name_en'] ?? $gener['name'] ?? '--')
                        : ($gener['name_en'] ?? $gener['name'] ?? '--');
                @endphp
                <li>{{ $genreName }}</li>
            @endforeach
        </ul>

        <!-- Title -->
        <div class="mm-title">{{ $movie->name ?? '--' }}</div>

        <!-- Meta (same layout as reference) -->
        <div class="mm-meta mb-2" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
            <i class="ph ph-calendar"></i>
            {{ $movie->release_date ? \Carbon\Carbon::parse($movie->release_date)->format('d M, Y') : '--' }}

            <span dir="ltr"> | </span>

            <i class="ph ph-translate"></i>
            {{ ucfirst($movie->language ?? 'English') }}
        </div>

        <!-- Buttons (same layout as reference card buttons) -->
        <div class="d-flex align-items-center gap-2">

            <!-- Remind Button styled like watchlist button -->
            <x-watchlist-button
                :entertainment-id="$movie->id"
                :in-watchlist="$movie->is_watch_list"
                :entertainmentType="$movie->type"
                customClass="mm-watchlist" />

            <!-- Coming Soon Button (same UI as Watch Now) -->
            <a href="{{ route('movie-details', ['id' => $movie->id]) }}"
               class="mm-watchnow text-center">
               @if($templateName == 'ComingSoon')
                    {{ __('frontend.coming_soon') }}
                @else 
                    {{ __('frontend.watch_now') }}
                @endif
            </a>

        </div>

    </div>

</div>
