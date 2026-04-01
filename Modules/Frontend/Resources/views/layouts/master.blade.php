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

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Google Translate: allow translating DB content when Arabic fields are missing.
         We still protect any already-Arabic text by adding the `notranslate` class via JS (see protectArabicText()). --}}
    <script>
        function setArabicCookie() {
            document.cookie = "googtrans=/en/ar; path=/;";
            document.cookie = "googtrans=/en/ar; path=/; domain=." + window.location.hostname + ";";
        }
        function clearTranslateCookie() {
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=." + window.location.hostname + ";";
        }
    </script>

    @php
        $shouldTranslateToArabic = (app()->getLocale() === 'ar') || (session()->get('dir') == 'rtl');
    @endphp

    @if($shouldTranslateToArabic)
        <script>setArabicCookie();</script>
    @else
        <script>clearTranslateCookie();</script>
    @endif

    @if($shouldTranslateToArabic)
        <script>
            (function() {
                if (document.cookie.indexOf('googtrans=/en/ar') > -1) {
                    document.documentElement.classList.add('force-hide-rtl');
                    document.documentElement.setAttribute('dir', 'rtl');
                }
            })();
        </script>
        <script>
            function googleTranslateElementInit() {
                new google.translate.TranslateElement({
                    pageLanguage: 'en',
                    includedLanguages: 'ar',
                    autoDisplay: false
                }, 'google_translate_element');
            }

            document.addEventListener("DOMContentLoaded", function() {
                if (document.documentElement.dir === 'rtl' || document.documentElement.classList.contains('force-hide-rtl')) {
                    const showContent = () => {
                        document.body.classList.add('translation-loaded');
                        document.documentElement.classList.remove('force-hide-rtl');
                    };
                    showContent();
                }
            });
        </script>
        <script async src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    @else
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (document.documentElement.dir === 'rtl') {
                    document.body.classList.add('translation-loaded');
                }
            });
        </script>
    @endif

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
            left: 0;
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
        
        *[style*="box-shadow"] {
            box-shadow: none !important;
        }
        *[style*="background"] {
            background: transparent !important;
        }
        
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

<body class="d-flex flex-column min-vh-100 {{ Route::currentRouteName() == 'search' ? 'search-page' : '' }}" style="background-color: #000000 !important;">
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

    <script src="{{ mix('modules/frontend/script.js') }}"></script>
    <script src="{{ mix('js/backend-custom.js') }}"></script>

    <!--- chrome cast  --->
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js"></script>
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
// Remove highlight Google adds later
function removeGoogleHighlights() {
    document.querySelectorAll('.goog-text-highlight, .goog-texthighlight')
        .forEach(el => {
            el.classList.remove('goog-text-highlight', 'goog-texthighlight');
        });
}

// Keep removing every 500ms because Google keeps adding it
setInterval(removeGoogleHighlights, 300);

setInterval(() => {
    document.querySelectorAll('.gt-cc, [data-gt-bubble], [data-language-for-alternatives]').forEach(el => {
        el.remove();
    });
}, 300);


document.addEventListener("mouseup", (e) => {
    if (window.getSelection().toString().length > 0) {
        window.getSelection().removeAllRanges();
    }
});

</script>


</body>
</html>
