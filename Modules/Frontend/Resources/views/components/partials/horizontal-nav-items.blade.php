<li class="nav-item">
    <a class="nav-link" href="{{ route('user.login') }}">
        <span class="item-name">{{ __('frontend.home') }}</span>
    </a>
</li>
@if (isenablemodule('movie'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('movies') }}">
            <span class="item-name">{{ __('frontend.movies') }}</span>
        </a>
    </li>
@endif
@if (isenablemodule('tvshow'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('tv-shows') }}">
            <span class="item-name">{{ __('frontend.tvshows') }}</span>
        </a>
    </li>
@endif
@if (isenablemodule('video'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('videos') }}">
            <span class="item-name">{{ __('frontend.video') }}</span>
        </a>
    </li>
@endif
<li class="nav-item d-none">
    <a class="nav-link" href="{{ route('comingsoon') }}">
        <span class="item-name">{{ __('frontend.coming_soon') }}</span>
    </a>
</li>
<li class="nav-item d-none">
    <a class="nav-link" href="{{ route('leavingsoon') }}">
        <span class="item-name">{{ __('frontend.leaving_soon') }}</span>
    </a>
</li>
@if (isenablemodule('livetv'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('livetv') }}">
            <span class="item-name">{{ __('frontend.livetv') }}</span>
        </a>
    </li>
@endif
