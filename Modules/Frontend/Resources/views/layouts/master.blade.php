<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark" dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}" data-bs-theme-color={{ getCustomizationSetting('theme_color') }}>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{url('/')}}" />
    <link rel="icon" type="image/png" href="{{ GetSettingValue('favicon') ?? asset('img/logo/favicon.png')   }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ GetSettingValue('favicon') ?? asset('img/logo/favicon.png')  }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    


    @include('frontend::layouts.head')

    {{-- Speed: preconnect to 3rd-party origins the page hits --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://www.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://www.google.com">

    {{-- Fonts (async, non-blocking). Fallback for clients without JS. --}}
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap"
          media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap"></noscript>

    {{-- Flatpickr is not needed above-the-fold on most pages: load it async. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"></noscript>

    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    {{-- Filled icon set is rarely above-the-fold – async load it. --}}
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}"></noscript>

    {{-- SweetAlert2 is only used for session popups + PPV confirmations: defer it. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (document.documentElement.dir === 'rtl') {
                document.body.classList.add('translation-loaded');
            }
        });
    </script>

    @include('frontend::components.partials.head.plugins')
    @stack('after-styles')
    {{-- Vite CSS --}}
    {{-- {{ module_vite('build-frontend', 'resources/assets/sass/app.scss') }} --}}
    <style>
        /* Base styles */
        html {
            background-color: #000000 !important; /* Prevent white flash */
        }
        
        /* Arabic pages should render immediately (no blocking hide) */
        html[dir="rtl"] body {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        
        /* Show state */
        html[dir="rtl"] body.translation-loaded {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transition: opacity 0.8s ease-in-out !important;
        }

        /* Cookie fallback should not block rendering */
        html.force-hide-rtl body {
            opacity: 1 !important;
            visibility: visible !important;
        }

        header {
            position: fixed !important;
            top: 0;
            inset-inline-start: 0;
            width: 100%;
            z-index: 1000;
            transition: background-color 0.3s ease, backdrop-filter 0.3s ease !important;
            background: transparent !important;
            backdrop-filter: none !important;
        }
    
        /* When scrolled */
        header.scrolled {
            /*background: rgb(0 0 0 / 40%) !important;*/
            background-color: #000000 !important;
            backdrop-filter: blur(6px) !important;
        }
        .iq-nav-menu > li > a {
            font-weight: 500;
            font-size: 1rem;
            color: #dedede !important;
        }
        .short-menu {
            position: relative !important;
        }
        .position-absolute.top-0.start-0.m-2.badge.bg-success.d-flex.align-items-center.gap-1.px-2.py-1.fs-6 {
            display: none !important;
        }
        .footer-top {
            background-color: #000000 !important;
        }
        .footer-bottom {
            background-color: #000000 !important;
        }

    </style>
    
    <style>
        /* Hide top Google bar */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }
        
        /* Hide body translate margin that Google adds */
        body {
            top: 0 !important;
        }
        
        /* Hide small Google widget at bottom-left */
        .goog-te-gadget {
            display: none !important;
        }
        
        .goog-te-menu-value {
            display: none !important;
        }
        
        #google_translate_element {
            display: none !important;
        }
        .skiptranslate {
            display: none;
        }
 
        .notranslate {
            unicode-bidi: plaintext !important;
        }
        
        /* Hide Google Translate popup */
        .goog-tooltip,
        .goog-tooltip:hover,
        .goog-te-balloon-frame,
        .goog-te-balloon-frame * {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        /* Hide the small blue icons and suggestion box */
        .goog-te-spinner-pos,
        .goog-te-menu-value,
        .goog-te-gadget-simple,
        .goog-te-banner-frame,
        #goog-gt-tt,
        .goog-te-gadget-icon {
            display: none !important;
        }
        
        /* Fix Google Translate auto-selection in Arabic */
        .goog-text-highlight {
            background: transparent !important;
            box-shadow: none !important;
            color: inherit !important;
            user-select: none !important;
        }
        
        html[dir="rtl"] span.goog-text-highlight {
            pointer-events: none !important;
            user-select: none !important;
        }

        #goog-gt-tt,
        .goog-tooltip,
        .goog-tooltip:hover,
        .goog-te-balloon-frame,
        .goog-te-menu-frame {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Remove Google Translate automatic text highlight */
        .goog-text-highlight,
        .goog-texthighlight {
            background: transparent !important;
            color: inherit !important;
            box-shadow: none !important;
            transition: none !important;
            animation: none !important;
            border: none !important;
            outline: none !important;
            cursor: default !important;
        }
        
        /* Prevent Google Translate from auto-selecting text */
        .goog-text-highlight * {
            user-select: none !important;
        }
        
        /* Remove Google Translate highlight */
        .gt-cc,
        span[style*="background-color"],
        font[style*="background-color"],
        span[data-language-for-alternatives],
        span[data-gt-bubble] {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* Do NOT use global *[style*="background"] / box-shadow — it strips inline styles
           across the whole page (header nav, gradients, payment UI). Scoped rules above
           are enough for Google Translate highlights. */

        /* Disable Google Translate hover blue highlight */
        .VIpgJd-yAWNEb-VIpgJd-fmcmS-sn54Q,
        .VIpgJd-yAWNEb-VIpgJd-fmcmS-sn54Q * {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            position: static !important;
            color: inherit !important;
        }
        
        .VIpgJd-ZVi9od-aZ2wEe-wOHMyf.VIpgJd-ZVi9od-aZ2wEe-wOHMyf-ti6hGc {
            display: none !important;
        }


    </style>


</head>

@php
    $flushMainTopRoutes = ['user.login', 'movies', 'movies.language', 'movies.genre', 'movie-details'];
@endphp
<body class="min-vh-100 @if(in_array(Route::currentRouteName(), $flushMainTopRoutes, true)) page-main-flush-top @endif {{ Route::currentRouteName() == 'search' ? 'search-page' : '' }}" style="background-color: #000000 !important;">
    @include('frontend::layouts.header')

    <main class="flex-fill">
        @yield('content')
    </main>

    @include('frontend::layouts.footer')

    @include('frontend::components.partials.back-to-top')
    @include('frontend::components.partials.scripts.plugins')
    


    @if(session('success'))
    <script>
document.addEventListener('DOMContentLoaded', function() {
     document.body.setAttribute('data-swal2-theme', 'dark');
    Swal.fire({
        icon: 'success',
        title: "{{ session('success.title') }}",
        html: `
            <div class="text-center">
                <p>{{ session('success.message') }}</p>
                <div class="mt-3">
                    <p><strong>Plan:</strong> {{ session('success.plan_name') }}</p>
                    <p><strong>Amount:</strong> {{ session('success.amount') }}</p>
                    <p><strong>Valid Until:</strong> {{ session('success.valid_until') }}</p>
                </div>
            </div>
        `,
        showConfirmButton: true,
        confirmButtonText: 'Continue',
        confirmButtonColor: '#e50914', // Changed to Bootstrap's danger red
        iconColor: '#e50914', // Added to make the success icon red
        customClass: {
            icon: 'swal2-icon-red' // Added custom class for icon color
        }
    });
});
</script>

<style>
.swal2-icon-red {
    border-color: #e50914 !important;
    color: #e50914 !important;
}
</style>
    @endif

    @if(session('error'))
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "{{ session('error') }}",
            confirmButtonColor: '#dc3545'
        });
    });
    </script>
    @endif

    @if(session('purchase_success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.setAttribute('data-swal2-theme', 'dark');
        Swal.fire({
            icon: 'success',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px;"></div>
                    <h2 class=="text-heading" style="margin: 15px 0 10px; font-size: 21px;">Purchase Successful!</h2>
                    <p class="text-body" style="font-size: 16px;">You have successfully purchased access to this content.</p>
                    <p class="text-body" style="font-size: 14px;">Enjoy until {{ session('view_expiry') }}.</p>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Begin Watching',
            confirmButtonColor: '#e50914',
            iconColor: '#e50914', // Added to make the success icon red
            customClass: {
                icon: 'swal2-icon-red' // Added custom class for icon color
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('unlock.videos') }}";
            }
        });
    });
</script>
@endif

    <script src="{{ mix('modules/frontend/script.js') }}" defer></script>
    <script src="{{ mix('js/backend-custom.js') }}" defer></script>

    <!--- chrome cast (only needed on pages that actually cast) --->
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1" defer></script>
    <script src="{{ asset('js/script.js') }}" defer></script>
    {{-- Vite JS --}}
    {{-- {{ module_vite('build-frontend', 'resources/assets/js/app.js') }} --}}
    @stack('after-scripts')
    
<div id="google_translate_element"></div>

    <script>

    const currencyFormat = (amount) => {
        const DEFAULT_CURRENCY = JSON.parse(@json(json_encode(Currency::getDefaultCurrency(true))))
         const noOfDecimal = DEFAULT_CURRENCY.no_of_decimal
         const decimalSeparator = DEFAULT_CURRENCY.decimal_separator
         const thousandSeparator = DEFAULT_CURRENCY.thousand_separator
         const currencyPosition = DEFAULT_CURRENCY.currency_position
         const currencySymbol = DEFAULT_CURRENCY.currency_symbol
        return formatCurrency(amount, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition, currencySymbol)
     }

    window.currencyFormat = currencyFormat
    window.defaultCurrencySymbol = @json(Currency::defaultSymbol())

    window.translations = {
        otp_send_success: @json(__('frontend.otp_send_success')),
        otp_send_error: @json(__('frontend.otp_send_error')),
        send_otp: @json(__('Send OTP')),
        sending: @json(__('frontend.sending')),
         send_otp: @json(__('frontend.send_otp')),
    }
</script>
<script>
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (!header) {
            return;
        }
        if (window.scrollY > 80) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
 
</script>


<script>
// function forceArabicTranslate() {
//     console.log('Called POut!');
//     const selectFrame = document.querySelector("iframe.goog-te-menu-frame");

//     if (!selectFrame) {
//         return setTimeout(forceArabicTranslate, 300);
//     }

//     const inner = selectFrame.contentDocument || selectFrame.contentWindow.document;
//     const items = inner.querySelectorAll(".goog-te-menu2-item span.text");

//     items.forEach(el => {
//         if (el.innerText.trim().toLowerCase() === "arabic") {
//             el.click();
//         }
//     });
// }
</script>
<script>
// Observe DOM changes & re-apply Arabic translation automatically
// const observer = new MutationObserver(() => {
//     if (document.documentElement.getAttribute("dir") === "rtl") {
//         forceArabicTranslate();
//     }
// });

// observer.observe(document.body, { childList: true, subtree: true });
</script>
<script>
// Detect if a string contains Arabic characters
function containsArabic(text) {
    const arabicRegex = /[\u0600-\u06FF\u0750-\u077F]/;
    return arabicRegex.test(text);
}

// Mark existing Arabic text as NOTRANSLATE before Google Translate runs
function protectArabicText() {
    document.querySelectorAll('body *:not(script):not(style)').forEach(el => {
        if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
            const txt = el.innerText.trim();
            if (txt !== "" && containsArabic(txt)) {
                el.classList.add("notranslate");
            }
        }
    });
}

protectArabicText();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.google && google.translate) {
        google.translate.TranslateElement.prototype.showTooltip_ = function() {};
    }
});
</script>
<script>
function disableGoogleTooltip() {
    // Disable tooltip function if Google Translate is loaded
    if (window.google && google.translate && google.translate.TranslateElement) {
        google.translate.TranslateElement.prototype.showTooltip_ = function() {};
        google.translate.TranslateElement.prototype.getTooltipInstance_ = function() { return { show: function(){}, hide: function(){} }; };
        console.log("Google Tooltip Disabled!");
        return;
    }
    // Retry until available
    setTimeout(disableGoogleTooltip, 300);
}

// Start checking after Translator loads
window.addEventListener("load", function () {
    setTimeout(disableGoogleTooltip, 300);
});
</script>
<script>
// Clean Google Translate leftovers on DOM mutations instead of a 300ms polling
// loop. The old setInterval kept running forever and tanked Speed Index.
(function () {
    const GOOG_HIGHLIGHT_RE = /\b(goog-text-highlight|goog-texthighlight)\b/;
    const GOOG_EXTRA_SEL = '.gt-cc, [data-gt-bubble], [data-language-for-alternatives]';

    function cleanup(root) {
        if (!root || root.nodeType !== 1) return;
        if (GOOG_HIGHLIGHT_RE.test(root.className || '')) {
            root.classList.remove('goog-text-highlight', 'goog-texthighlight');
        }
        root.querySelectorAll && root.querySelectorAll('.goog-text-highlight, .goog-texthighlight')
            .forEach(el => el.classList.remove('goog-text-highlight', 'goog-texthighlight'));
        root.querySelectorAll && root.querySelectorAll(GOOG_EXTRA_SEL).forEach(el => el.remove());
    }

    const start = () => {
        cleanup(document.body);
        const mo = new MutationObserver(muts => {
            for (const m of muts) {
                m.addedNodes && m.addedNodes.forEach(cleanup);
                if (m.type === 'attributes' && m.target) cleanup(m.target);
            }
        });
        mo.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();

document.addEventListener("mouseup", (e) => {
    if (window.getSelection().toString().length > 0) {
        window.getSelection().removeAllRanges();
    }
});

</script>


</body>
</html>
