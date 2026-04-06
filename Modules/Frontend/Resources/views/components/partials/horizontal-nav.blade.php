<!-- Horizontal Menu Start -->
<nav id="navbar_main" class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
  <div class="container-fluid p-lg-0">
    <div class="offcanvas-header">
      <div class="navbar-brand p-0">
        <!--Logo -->
        @include('frontend::components.partials.logo')

      </div>
      <button type="button" class="btn-close p-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <ul class="navbar-nav iq-nav-menu list-unstyled" id="header-menu">
      @include('frontend::components.partials.horizontal-nav-items')
    </ul>
  </div>
  <!-- container-fluid.// -->
</nav>
<!-- Horizontal Menu End -->
