<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') | {{ getAppName() }}</title>
    <meta name="description" content="{{ getAppName() }}">
    <link rel="canonical" href="{{ route('front') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/third-party.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}">
    <link href="{{ mix('/assets/css/custom-auth.css') }}" rel="stylesheet" type="text/css" />
    @include('layouts.brand_head')
    {{--    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pages.css') }}"> --}}
    <!-- CSS Libraries -->
    @yield('css')
    @livewireStyles
    <!-- Scripts -->
    @livewireScripts
    <script src="{{ asset('assets/js/third-party.js') }}"></script>
    <script src="{{ asset('messages.js') }}"></script>
    {{-- <script src="{{ asset('js/pages.js') }}"></script> --}}
    @yield('scripts')
    <script>
        $(document).ready(function() {
            $('.alert').delay(5000).slideUp(300)
        });

        $(document).on('click', '.language-select', function() {
            let languageName = $(this).data('id');
            $.ajax({
                type: 'get',
                url: 'set-language',
                data: {
                    languageName: languageName
                },
                success: function() {
                    location.reload();
                },
            });
        });
    </script>
</head>

<body
    class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed">
    <style>
        .app-back-button { position: fixed; top: 16px; left: 16px; z-index: 1050; width: 42px; height: 42px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--bs-primary, #009e49);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .12); font-size: 18px; text-decoration: none; }
        .app-back-button:hover { background: var(--bs-primary, #009e49); color: #fff; }
    </style>
    @include('layouts.back_button', ['fallback' => request()->routeIs('login') ? route('front') : route('login')])
    <div class="d-flex flex-column flex-root">
        <div
            class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed authImage">
            @yield('content')
        </div>
    </div>
    {{ Form::hidden('userCurrentLanguage', checkLanguageSession(), ['class' => 'userCurrentLanguage']) }}
    {{ Form::hidden('invalidNumber', __('messages.common.invalid_number'), ['class' => 'invalidNumber']) }}
    {{ Form::hidden('invalidCountryNumber', __('messages.common.invalid_country_code'), ['class' => 'invalidCountryNumber']) }}
    {{ Form::hidden('tooShort', __('messages.common.too_short'), ['class' => 'tooShort']) }}
    {{ Form::hidden('tooLong', __('messages.common.too_long'), ['class' => 'tooLong']) }}
    {{ Form::hidden('invalidNumber', __('messages.common.invalid_number'), ['class' => 'invalidNumber']) }}
    {{ Form::hidden('invalidNumber', __('messages.common.invalid_number'), ['class' => 'invalidNumber']) }}
</body>

</html>
