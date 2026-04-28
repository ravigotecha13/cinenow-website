{{-- Desktop primary nav (xl+); mobile uses offcanvas horizontal-nav --}}
<nav class="d-none d-xl-flex align-items-center header-inline-nav" aria-label="{{ __('frontend.home') }}">
    <ul class="navbar-nav iq-nav-menu list-unstyled d-flex flex-row align-items-center flex-wrap mb-0">
        @include('frontend::components.partials.horizontal-nav-items')
    </ul>
</nav>
