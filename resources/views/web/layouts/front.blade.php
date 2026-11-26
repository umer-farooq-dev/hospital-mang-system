<!doctype html>
<html lang="{{ App::getLocale() }}"
    @if (App::getLocale() == 'ar') dir="rtl" @endif>

<head>
    @php($settingValue = getSettingValue())
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($settingValue['about_us']['value'] ?? getAppName()), 160) }}">
    <meta name="author" content="{{ getAppName() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ getAppName() }}</title>
    @include('layouts.brand_head', ['website' => true])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/magnific-popup.min.css') }}">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/swiper-bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('web_front/kliniko/css/style.css') }}">
    <style>{!! frontThemeCss() !!}</style>
    <link rel="stylesheet" href="{{ mix('css/front-third-party.css') }}">
    <link rel="stylesheet" href="{{ mix('css/front-pages.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    @yield('page_css')
    @yield('css')
</head>

<body>
    @include('web.layouts.header')
    {{ Form::hidden('userCurrentLanguage', App::getLocale(), ['class' => 'userCurrentLanguage']) }}
    {{ Form::hidden('invalidNumber', __('messages.common.invalid_number'), ['class' => 'invalidNumber']) }}
    {{ Form::hidden('invalidCountryNumber', __('messages.common.invalid_country_code'), ['class' => 'invalidCountryNumber']) }}
    {{ Form::hidden('tooShort', __('messages.common.too_short'), ['class' => 'tooShort']) }}
    {{ Form::hidden('tooLong', __('messages.common.too_long'), ['class' => 'tooLong']) }}
    {{ Form::hidden('curruntLanguage', App::getLocale(), ['class' => 'curruntLanguage']) }}
    {{ Form::hidden('getISOCode', getISOCode(), ['class' => 'getISOCode']) }}

    <main class="vs-main">
        @unless (Request::is('/'))
            <div class="vs-breadcrumb overflow-hidden">
                <div class="vs-breadcrumb__bg">
                    <img src="{{ frontImage('page_banner_image', 'bg/breadcrumb-bg.jpg') }}" alt="">
                </div>
                <div class="container">
                    <div class="vs-breadcrumb__content wow animate__fadeInUp" data-wow-delay="0.2s">
                        <h1>@yield('title')</h1>
                        <div class="vs-breadcrumb__menu">
                            <ul>
                                <li><a href="{{ route('front') }}">{{ __('messages.web_home.home') }}</a></li>
                                <li>@yield('title')</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endunless
        @yield('content')
    </main>

    @include('web.layouts.footer')

    <button class="back-to-top" id="backToTop" aria-label="{{ __('messages.web_front.back_to_top') }}">
        <span class="progress-circle">
            <svg viewBox="0 0 100 100">
                <circle class="bg" cx="50" cy="50" r="40"></circle>
                <circle class="progress" cx="50" cy="50" r="40"></circle>
            </svg>
            <span class="progress-percentage" id="progressPercentage">0%</span>
        </span>
    </button>

    <script src="{{ mix('js/front-third-party.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/bootstrap.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
    <script src="{{ asset('messages.js') }}"></script>
    @routes('website')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="{{ mix('js/front-pages.js') }}"></script>
    {{-- theme effects (Kliniko) --}}
    <script src="{{ asset('web_front/kliniko/js/wow.min.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/gsap.min.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/ScrollTrigger.min.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/gsap-scroll-to-plugin.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js"></script>
    <script src="{{ asset('web_front/kliniko/js/lenis.min.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/swiper-bundle.js') }}"></script>
    <script src="{{ asset('web_front/kliniko/js/main.js') }}"></script>
    @yield('page_scripts')
    <script>
        $(document).on('click', '.languageSelection', function () {
            $.post('/change-language', {languageName: $(this).data('prefix-value')}, () => location.reload())
        })
    </script>
    @yield('scripts')
</body>

</html>
