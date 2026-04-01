<div class="detail-page-info section-spacing">
    @php
        $displayName = app()->getLocale() === 'ar' ? ($data['name_ar'] ?? $data['name'] ?? '') : ($data['name'] ?? '');
    @endphp
    <div class="container-fluid">

        <!-- Episode Name Display -->
        <div id="episodeNameDisplay" class="episode-name-display mb-3" style="display: none;">
            <p class="episode-title mb-0 text-white fw-bold fs-5">
                <span id="currentEpisodeName">Episode Name</span>
            </p>
        </div>
        
  

        <div class="row">
            <div class="col-md-12">
                <div class="movie-detail-content">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-7">
                            <div class="d-flex align-items-center">
                                <ul class="actions-list list-inline m-0 p-0 d-flex align-items-center flex-wrap gap-3">
                                    <li>
                                        <x-watchlist-button :entertainment-id="$data['id']" :in-watchlist="$data['is_watch_list']" :entertainmentType="$data['type']"
                                            customClass="watch-list-btn" />
                                    </li>
                                    <li class="position-relative share-button dropend dropdown">
                                        <button type="button" class="action-btn btn btn-dark" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ph ph-share-network"></i>
                                        </button>
                                        <div class="share-wrapper">
                                            <div class="share-box dropdown-menu">
                                                <svg width="15" height="40" viewBox="0 0 15 40"
                                                    class="share-shape" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M14.8842 40C6.82983 37.2868 1 29.3582 1 20C1 10.6418 6.82983 2.71323 14.8842 0H0V40H14.8842Z"
                                                        fill="currentColor"></path>
                                                </svg>
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <a href="https://www.facebook.com/sharer?u={{ urlencode(Request::url()) }}"
                                                        target="_blank" rel="noopener noreferrer" class="share-ico"><i
                                                            class="ph ph-facebook-logo"></i></a>
                                                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($displayName) }}&url={{ urlencode(Request::url()) }}"
                                                        target="_blank" rel="noopener noreferrer" class="share-ico"><i
                                                            class="ph ph-x-logo"></i></a>
                                                    <a href="#" data-link="{{ Request::url() }}"
                                                        class="share-ico iq-copy-link" id="copyLink"><i
                                                            class="ph ph-link"></i></a>

                                                    <span id="copyFeedback"
                                                        style="display: none; margin-left: 10px;">{{ __('frontend.copied') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- <li>
                                        <button class="action-btn btn btn-dark">
                                            <i class="ph ph-download-simple"></i>
                                        </button>
                                    </li> -->
                                    <li>
                                        <x-like-button :entertainmentId="$data['id']" :isLiked="$data['is_likes']" :type="$data['type']" />
                                    </li>

                                    <!--- Cast button -->
                                    @php
                                        $video_upload_type = $data['video_upload_type'];
                                        $plan_type = getActionPlan('video-cast');
                                    @endphp
                                    @if (!empty($plan_type) && ($video_upload_type == 'Local' || $video_upload_type == 'URL'))
                                        @php
                                            $video_url11 =
                                                $video_upload_type == 'URL'
                                                    ? Crypt::decryptString($video_url)
                                                    : $video_url;
                                        @endphp
                                        <li>
                                            <button class="action-btn btn btn-dark" data-name="{{ $video_url11 }}"
                                                id="castme">
                                                <i class="ph ph-screencast"></i>
                                            </button>
                                        </li>
                                    @endif
                                    <!--- End cast button -->
                                </ul>
                            </div>
                        </div>

                        @if ($data['your_review'] == null)
                            <div class="col-md-5 mt-md-0 mt-4 text-md-end d-none" id="addratingbtn">
                                @if (Auth::check())
                                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#rattingModal"
                                        data-entertainment-id="{{ $data['id'] }}">
                                        <span class="d-flex align-items-center justify-content-center gap-2">
                                            <span class="text-warning"><i class="ph-fill ph-star"></i></span>
                                            <span>{{ __('frontend.rate_this') }}</span>
                                        </span>
                                    </button>
                                @else
                                    <a href="{{ url('/login') }}" class="btn btn-dark">
                                        <span class="d-flex align-items-center justify-content-center gap-2">
                                            <span class="text-warning"><i class="ph-fill ph-star"></i></span>
                                            <span>{{ __('frontend.rate_this') }}</span>
                                        </span>
                                    </a>
                                @endif
                            </div>
                            {{-- @else
                            @if (Auth::check())
                            <div class="col-md-5 mt-md-0 mt-4 text-md-end d-none"  id="addratingbtn">
                                <button
                                class="btn btn-dark"
                                data-bs-toggle="modal"
                                data-bs-target="#rattingModal"
                                data-entertainment-id="{{ $data['id'] }}">
                                    <span class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="text-warning"><i class="ph-fill ph-star"></i></span>
                                        <span>{{ __('frontend.rate_this') }}</span>
                                    </span>
                                </button>
                            </div>
                            @endif --}}
                        @endif
                    </div>
                    
                    @php
                        use Carbon\Carbon;
                        $today = Carbon::today();
                        $startDate = !empty($data['start_date']) ? Carbon::parse($data['start_date']) : null;
                     
                    @endphp
                    
                    @if (
                        $data['movie_access'] == 'pay-per-view' &&
                        !\Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], $data['type']) &&
                        (
                            !$startDate || $startDate->lte($today)
                        )
                    )
                        <div class="bg-dark text-white p-3 mb-2 d-flex justify-content-between align-items-center d-none"
                            style="border-width: 2px;">
                            <div>
                                @if ($data['purchase_type'] === 'rental')
                                    <span>
                                        {!! __('messages.rental_info', [
                                            'days' => $data['available_for'],
                                            'hours' => $data['access_duration'],
                                        ]) !!}
                                        <button class="btn btn-link p-0" data-bs-toggle="modal"
                                            data-bs-target="#rentalPurchaseModal">
                                            <i class="ph ph-info">i</i>
                                        </button>
                                    </span>
                                @else
                                    <span>
                                        {!! __('messages.purchase_info', [
                                            'days' => $data['available_for'],
                                        ]) !!}
                                        <button class="btn btn-link p-0" data-bs-toggle="modal"
                                            data-bs-target="#onetimePurchaseModal">
                                            <i class="ph ph-info">i</i>
                                        </button>
                                    </span>
                                @endif
                            </div>

                            <div>
                                <div>
                                    @if ($data['purchase_type'] === 'rental')
                                        <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id']]) }}"
                                            class="btn btn-success d-flex align-items-center">
                                            <i class="bi bi-lock-fill me-1"></i>
                                            @if ($data['discount'] > 0)
                                                <span class="me-2">
                                                    {{ __('messages.rent_button', ['price' => Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2)]) }}
                                                </span>
                                                <span class="text-decoration-line-through text-white-50">
                                                    {{ Currency::format($data['price'], 2) }}
                                                </span>
                                            @else
                                                {{ __('messages.rent_button', ['price' => Currency::format($data['price'], 2)]) }}
                                            @endif
                                        </a>
                                    @else
                                        <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id']]) }}"
                                            class="btn btn-success d-flex align-items-center">
                                            <i class="bi bi-unlock-fill me-1"></i>
                                            @if ($data['discount'] > 0)
                                                <span class="me-2">
                                                    {{ __('messages.one_time_button', ['price' => Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2)]) }}
                                                </span>
                                                <span class="text-decoration-line-through text-white-50">
                                                    {{ Currency::format($data['price'], 2) }}
                                                </span>
                                            @else
                                                {{ __('messages.one_time_button', ['price' => Currency::format($data['price'], 2)]) }}
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- One-time Purchase Modal -->
<div class="modal fade" id="onetimePurchaseModal" tabindex="-1" aria-labelledby="onetimePurchaseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:500px;">
        <div class="modal-content section-bg text-white rounded shadow border-0 p-4">

            <!-- Header Info -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    @if (isset($data['is_restricted']) && $data['is_restricted'] == 1)
                        <span
                            class="badge bg-light text-dark fw-bold px-2 py-1 me-2">{{ __('messages.lbl_age_restriction') }}</span>
                    @endif
                    @if (isset($data['genres']) && count($data['genres']) > 0)
                        <span class="text-white-50 small">
                            @foreach ($data['genres'] as $key => $genre)
                                {{ is_array($genre) ? (!empty($genre['name']) ? $genre['name'] : '--') : (isset($genre) && isset($genre->name) ? $genre->name : '--') }}
                                @if (!$loop->last)
                                    &bull;
                                @endif
                            @endforeach
                        </span>
                    @endif
                </div>
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <!-- Movie Title -->
            <h4 class="fw-bold mb-2">{{ $displayName }}</h4>

            <!-- Movie Metadata -->
            <ul class="list-inline mb-4 d-flex flex-wrap gap-4">
                {{-- <li class="d-flex align-items-center gap-1"><span>{{ \Carbon\Carbon::parse($data['release_date'])->format('Y') }}</span></li> --}}
                <li class="d-flex align-items-center gap-1"><i
                        class="ph ph-translate me-1"></i><span>{{ $data['language'] }}</span></li>
                <li class="d-flex align-items-center gap-1"><i class="ph ph-clock me-1"></i><span>
                        {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}</span></li>
                @if ($data['imdb_rating'])
                    <li class="d-flex align-items-center gap-1"><i
                            class="ph-fill ph-star text-warning"></i><span>{{ $data['imdb_rating'] }}
                            ({{ __('messages.lbl_IMDb') }})</span></li>
                @endif
            </ul>

            <!-- Validity & Watch Time -->
            <div class="rounded p-5 mb-4 bg-dark">
                <div class="">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_validity') }}</p>
                        <h6 class="fw-semibold m-0">{{ __('messages.lbl_watch_time') }}</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_unlimited') }}</p>
                        @php
                            $availableDays = is_numeric($data['available_for']) ? (int) $data['available_for'] : 0;
                        @endphp

                        <h6 class="fw-semibold m-0">
                            {{ \Carbon\Carbon::now()->format('d-m-Y') }} to
                            {{ \Carbon\Carbon::now()->addDays($availableDays)->format('d-m-Y') }}
                        </h6>
                    </div>
                </div>
                {{-- <hr class="font-size-14 text-body"> --}}
                <ul class="font-size-14 text-body">
                    <li>{!! __('messages.info_start_days', ['days' => $data['available_for']]) !!}</li>
                    <li>{{ __('messages.info_multiple_times') }}</li>
                    <li>{!! __('messages.info_non_refundable') !!}</li>
                    <li>{{ __('messages.info_not_premium') }}</li>
                    <li>{{ __('messages.info_supported_devices') }}</li>
                </ul>
                <!-- Agreement Checkbox -->
                <div class="form-check mb-4 d-flex align-items-center gap-3 p-0">
                    <input class="form-check-input m-0" type="checkbox" checked id="agreeCheckbox">
                    <label class="form-check-label small text-white-50" for="rentalAgreeCheckbox">
                        {{ __('messages.lbl_agree_term') }}
                        <a href="{{ route('page.show', ['slug' => 'terms-conditions']) }}"
                            class="text-decoration-underline text-white">{{ __('messages.terms_use') }}</a>.
                    </label>
                </div>

                <!-- Rent Button -->
                <div class="text-center">
                    <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id']]) }}" id="onetimeSubmitButton"
                        class="btn btn-success fw-semibold d-inline-flex justify-content-center align-items-center gap-2">
                        <i class="ph ph-lock-key"></i>

                        @if ($data['discount'] > 0)
                            {{ __('messages.btn_onetime_payment', [
                                'price' => Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2),
                            ]) }}
                            <span class="text-decoration-line-through small text-white-50 ms-2">
                                {{ Currency::format($data['price'], 2) }}
                            </span>
                        @else
                            {{ __('messages.btn_onetime_payment', [
                                'price' => Currency::format($data['price'], 2),
                            ]) }}
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Rental Purchase Modal -->
<div class="modal fade" id="rentalPurchaseModal" tabindex="-1" aria-labelledby="rentalPurchaseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:500px;">
        <div class="modal-content section-bg text-white rounded shadow-lg border-0 p-4">

            <!-- Header Info -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    @if (isset($data['is_restricted']) && $data['is_restricted'] == 1)
                        <span
                            class="badge bg-light text-dark fw-bold px-2 py-1 me-2">{{ __('messages.lbl_age_restriction') }}</span>
                    @endif
                    @if (isset($data['genres']) && count($data['genres']) > 0)
                        <span class="text-white-50 small">
                            @foreach ($data['genres'] as $key => $genre)
                                {{ is_array($genre) ? (!empty($genre['name']) ? $genre['name'] : '') : (isset($genre) && isset($genre->name) ? $genre->name : '--') }}
                                @if (!$loop->last)
                                    &bull;
                                @endif
                            @endforeach
                        </span>
                    @endif
                </div>
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <!-- Movie Title -->
            <h4 class="fw-bold mb-2">{{ $displayName }}</h4>

            <!-- Movie Metadata -->
            <ul class="list-inline mb-4 d-flex flex-wrap gap-4">
                {{-- <li class="d-flex align-items-center gap-1"><span>{{ \Carbon\Carbon::parse($data['release_date'])->format('Y') }}</span></li> --}}
                <li class="d-flex align-items-center gap-1"><i
                        class="ph ph-translate me-1"></i><span>{{ $data['language'] }}</span></li>
                <li class="d-flex align-items-center gap-1"><i class="ph ph-clock me-1"></i><span>
                        {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}</span></li>
                @if ($data['imdb_rating'])
                    <li class="d-flex align-items-center gap-1"><i
                            class="ph-fill ph-star text-warning"></i><span>{{ $data['imdb_rating'] }}
                            ({{ __('messages.lbl_IMDb') }})</span></li>
                @endif
            </ul>

            <!-- Validity & Duration -->
            <div class="rounded p-5 mb-4 bg-dark">
                <div class="">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_validity') }}</p>
                        <h6 class="fw-semibold m-0">{{ __('messages.lbl_days', ['days' => $data['available_for']]) }}
                        </h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_watch_duration') }}</p>
                        <h6 class="fw-semibold m-0">
                            {{ __('messages.lbl_days', ['days' => $data['access_duration']]) }}</h6>
                    </div>
                </div>
                <ul class="font-size-14 text-body ">
                    <li>{!! __('messages.rental_info_start', ['days' => $data['available_for']]) !!}</li>
                    <li>{!! __('messages.rental_info_duration', ['hours' => $data['access_duration']]) !!}</li>
                    <li>{!! __('messages.info_non_refundable') !!}</li>
                    <li>{{ __('messages.info_not_premium') }}</li>
                    <li>{{ __('messages.info_supported_devices') }}</li>
                </ul>


                <!-- Terms Checkbox -->
                <div class="form-check mb-4 d-flex align-items-center gap-3 p-0">
                    <input class="form-check-input m-0" type="checkbox" checked id="rentalAgreeCheckbox">
                    <label class="form-check-label small text-white-50" for="rentalAgreeCheckbox">
                        {{ __('messages.lbl_agree_term') }}
                        <a href="{{ route('page.show', ['slug' => 'terms-conditions']) }}"
                            class="text-decoration-underline text-white">{{ __('messages.terms_use') }}</a>.
                    </label>
                </div>

                <!-- Rent Button -->
                <div class="">
                    <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id']]) }}" id="rentalSubmitButton"
                        class="btn btn-success fw-semibold d-inline-flex justify-content-center align-items-center gap-2 w-100">
                        <i class="ph ph-film-reel"></i>

                        @if ($data['discount'] > 0)
                            {{ __('messages.btn_rent_payment', [
                                'price' => Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2),
                            ]) }}
                            <span class="text-decoration-line-through small text-white-50 ms-2">
                                {{ Currency::format($data['price'], 2) }}
                            </span>
                        @else
                            {{ __('messages.btn_rent_payment', [
                                'price' => Currency::format($data['price'], 2),
                            ]) }}
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const oneTimeCheckbox = document.getElementById('agreeCheckbox');
        const oneTimeButton = document.getElementById('onetimeSubmitButton');
        oneTimeCheckbox.addEventListener('change', function() {
            if (this.checked) {
                oneTimeButton.classList.remove('disabled-link');
                oneTimeButton.style.pointerEvents = 'auto';
                oneTimeButton.style.opacity = '1';
            } else {
                oneTimeButton.classList.add('disabled-link');
                oneTimeButton.style.pointerEvents = 'none';
                oneTimeButton.style.opacity = '0.5';
            }
        });

        const rentalCheckbox = document.getElementById('rentalAgreeCheckbox');
        const rentalButton = document.getElementById('rentalSubmitButton');
        rentalCheckbox.addEventListener('change', function() {
            if (this.checked) {
                rentalButton.classList.remove('disabled-link');
                rentalButton.style.pointerEvents = 'auto';
                rentalButton.style.opacity = '1';
            } else {
                rentalButton.classList.add('disabled-link');
                rentalButton.style.pointerEvents = 'none';
                rentalButton.style.opacity = '0.5';
            }
        });
    });
</script>

<script>
    document.getElementById('copyLink').addEventListener('click', function(e) {
        e.preventDefault();

        var url = this.getAttribute('data-link');

        var tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        window.successSnackbar('Link copied.');

        document.execCommand("copy");

        document.body.removeChild(tempInput);

        this.style.display = 'none';

        var feedback = document.getElementById('copyFeedback');
        feedback.style.display = 'inline';

        setTimeout(() => {
            feedback.style.display = 'none';
            this.style.display = 'inline';
        }, 1000);
    });
</script>

<script>
    $(document).ready(function() {
        $('#watchNowButton').on('click', function() {
            const button = $(this);
            const movie_access = button.data('movie-access');
            const puchase_type = button.data('purchase-type');
            const data = {
                user_id: button.data('user-id'),
                entertainment_id: button.data('entertainment-id'),
                entertainment_type: button.data('entertainment-type'),
                _token: '{{ csrf_token() }}'
            };
            if (movie_access == 'pay-per-view' && puchase_type == 'rental') {
                $.ajax({
                    url: '{{ route('pay-per-view.start-date') }}', // or '/pay-per-view/start-date'
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        // console.log('Start date set:', response);
                        // You can now proceed with video playback or other logic
                    },
                    error: function(xhr) {
                        console.error('Failed to set start date:', xhr.responseText);
                    }
                });
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update episode name display
        function updateEpisodeNameDisplay(episodeName) {
            const episodeNameDisplay = document.getElementById('episodeNameDisplay');
            const currentEpisodeName = document.getElementById('currentEpisodeName');
            if (episodeNameDisplay && currentEpisodeName) {
                if (episodeName) {
                    currentEpisodeName.textContent = episodeName;
                    episodeNameDisplay.style.display = 'block';
                    // Add smooth animation
                    setTimeout(() => {
                        episodeNameDisplay.classList.add('show');
                    }, 10);
                } else {
                    // Remove show class first for smooth hide animation
                    episodeNameDisplay.classList.remove('show');
                    // Hide after animation completes
                    setTimeout(() => {
                        episodeNameDisplay.style.display = 'none';
                    }, 300);
                }
            }
        }
        // Function to hide episode name display
        function hideEpisodeNameDisplay() {
            updateEpisodeNameDisplay(null);
        }
        // Make functions globally available
        window.updateEpisodeNameDisplay = updateEpisodeNameDisplay;
        window.hideEpisodeNameDisplay = hideEpisodeNameDisplay;
        // Listen for custom events from the video player
        document.addEventListener('episodeChanged', function(e) {
            if (e.detail && e.detail.episodeName) {
                updateEpisodeNameDisplay(e.detail.episodeName);
            }
        });
        // Initialize - hide episode name display initially
        hideEpisodeNameDisplay();
    });
</script>
