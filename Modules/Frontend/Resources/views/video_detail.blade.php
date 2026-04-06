@extends('frontend::layouts.master')

@section('content')

<div id="thumbnail-section">
    @include('frontend::components.section.thumbnail',  ['data' => $data, 'content_type' => 'video', 'video_type' => $data['video_upload_type']])
</div>

<div id="detail-section">
    @include('frontend::components.section.video_data',  ['data' => $data,'subtitle_info' => $data['subtitle_info']])
</div>

<div class="container-fluid">
    <div class="overflow-hidden">
        @include('frontend::components.section.custom_ad_banner', [
            'placement' => 'video_detail',
            'content_id' => $data['id'] ?? '',
            'content_type' => $data['type'] ?? '',
            'category_id' => $data['category_id'] ?? ''
        ])
        <div id="more-like-this">
            @include('frontend::components.section.video',  ['data' => $data['more_items'], 'title'=>__('frontend.more_like_this')])
        </div>
    </div>
</div>

<div class="modal fade" id="DeviceSupport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content position-relative">
            <div class="modal-body user-login-card m-0 p-4 position-relative">
                <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                    <i class="ph ph-x text-white fw-bold align-middle"></i>
                </button>

                <div class="modal-body">
                    {{__('frontend.device_not_support')}}
                  </div>

                <div class="d-flex align-items-center justify-content-center">
                    <a href="{{ Auth::check() ? route('subscriptionPlan') : route('login') }}" class="btn btn-primary mt-5" >{{__('frontend.upgrade')}}</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchCustomVideoDetailAd() {
        const baseUrl = window.location.origin || document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || window.envURL || '';
        fetch(`${baseUrl}/api/custom-ads/get-active`)
            .then(response => response.json())
            .then(data => {
                const rows = Array.isArray(data.data) ? data.data : (data.data && Array.isArray(data.data.data) ? data.data.data : []);
                if (data.success && Array.isArray(rows) && rows.length > 0) {
                    const ads = rows.filter(item => (item.placement || '').toString().toLowerCase() === 'video_detail_page');
                    if (ads.length > 0) {
                        let adHtml = `
                            <div class="custom-ad-slider">
                                ${ads.map(ad => {
                                    let content = '';
                                    if ((ad.type || '').toString().toLowerCase() === 'image') {
                                        let imgSrc = ad.media;
                                        content = `
                                            <div class="custom-ad-content">
                                                ${ad.redirect_url ? `
                                                    <a href="${ad.redirect_url}" class="ad-link" target="_blank" rel="noopener noreferrer">
                                                        <img src="${imgSrc}" alt="${ad.name}" class="ad-image">
                                                        <div class="ad-overlay"></div>
                                                    </a>
                                                ` : `
                                                    <img src="${imgSrc}" alt="${ad.name}" class="ad-image">
                                                    <div class="ad-overlay"></div>
                                                `}
                                            </div>
                                        `;
                                    }
                                    return `<div class="custom-ad-wrapper">${content}</div>`;
                                }).join('')}
                            </div>
                        `;
                        const adSection = document.getElementById('custom-video-detail-ad-section');
                        if (adSection) {
                            adSection.innerHTML = adHtml;
                            adSection.classList.remove('section-hidden');
                            adSection.classList.add('section-visible');
                            // Initialize Slick slider if available
                            if (window.$ && typeof $.fn.slick === 'function') {
                                $('.custom-ad-slider').slick({
                                    dots: true,
                                    arrows: false,
                                    infinite: ads.length > 1,
                                    slidesToShow: 1,
                                    slidesToScroll: 1,
                                    adaptiveHeight: true,
                                    autoplay: true,
                                    autoplaySpeed: 5000
                                });
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching custom video detail ad:', error);
            });
    }
    fetchCustomVideoDetailAd();
});
</script>
@endpush
