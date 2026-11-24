@php
    $settingValue = getSettingValue();
    $menu = [
        ['url' => route('front'), 'label' => __('messages.web_home.home'), 'active' => Request::is('/')],
        ['url' => route('aboutUs'), 'label' => __('messages.web_menu.about'), 'active' => Request::is('about-us')],
        ['url' => route('our-services'), 'label' => __('messages.web_home.services'), 'active' => Request::is('our-services*')],
        ['url' => route('our-doctors'), 'label' => __('messages.web_home.doctors'), 'active' => Request::is('our-doctors', 'doctor-details*')],
        ['url' => 'javascript:void(0)', 'label' => __('messages.web_front.pages'), 'active' => Request::is('case-studies*', 'testimonial', 'working-hours', 'terms-of-service', 'privacy-policy', 'appointment'),
            'children' => [
                ['url' => route('case-studies'), 'label' => __('messages.front_web.type_2')],
                ['url' => route('appointment'), 'label' => __('messages.web_menu.appointment')],
                ['url' => route('working-hours'), 'label' => __('messages.web_menu.working_hours')],
                ['url' => route('testimonials'), 'label' => __('messages.web_home.testimonials')],
                ['url' => route('terms-of-service'), 'label' => __('messages.web_home.terms_of_service')],
                ['url' => route('privacy-policy'), 'label' => __('messages.web_home.privacy_policy')],
            ]],
        ['url' => route('news'), 'label' => __('messages.front_web.type_3'), 'active' => Request::is('news*')],
        ['url' => route('contact'), 'label' => __('messages.web_home.contact'), 'active' => Request::is('contact-us')],
    ];
    $languages = [];
    foreach (websiteLanguages() as $key) {
        $languages[] = ['key' => $key, 'label' => getLanguages()[$key], 'flag' => \App\Models\User::LANGUAGES_IMAGE[$key] ?? null];
    }
    $social = collect(\App\Models\Setting::SOCIAL_ICONS)->filter(fn ($icon, $key) => ! empty($settingValue[$key]['value'] ?? null));
@endphp

<div class="vs-menu-wrapper">
    <div class="vs-menu-area text-center">
        <div class="vs-header__right pt-4">
            <button class="vs-menu-toggle" aria-label="{{ __('messages.common.close') }}"><i class="fa-solid fa-xmark"></i></button>
            <div class="mobile-logo">
                <a href="{{ route('front') }}"><img src="{{ asset(getLogoUrl()) }}" alt="{{ getAppName() }}"></a>
            </div>
        </div>
        <div class="vs-mobile-menu">
            <ul>
                @foreach ($menu as $item)
                    <li class="{{ isset($item['children']) ? 'menu-item-has-children' : '' }} {{ $item['active'] ? 'active' : '' }}">
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                        @isset($item['children'])
                            <ul class="sub-menu">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ $child['url'] }}">{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endisset
                    </li>
                @endforeach
                @if (count($languages) > 1)
                <li class="menu-item-has-children">
                    <a href="javascript:void(0)">{{ getLanguages()[App::getLocale()] ?? strtoupper(App::getLocale()) }}</a>
                    <ul class="sub-menu">
                        @foreach ($languages as $language)
                            <li class="languageSelection" data-prefix-value="{{ $language['key'] }}"><a href="javascript:void(0)">{{ $language['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
                @endif
            </ul>
        </div>
        <div class="mt-50 pt-50">
            <div class="social-style mobileMenu mb-30">
                @foreach ($social as $key => $icon)
                    <a href="{{ $settingValue[$key]['value'] }}" target="_blank" rel="noopener" aria-label="{{ $key }}"><i class="fa-brands {{ $icon }}"></i></a>
                @endforeach
            </div>
            <p class="sidemenu-text sidemenu-text--footer text-center mb-0">© {{ date('Y') }} <a class="vs-theme-color" href="{{ route('front') }}">{{ getAppName() }}</a>.</p>
        </div>
    </div>
</div>

<header class="vs-header home-three bg-fixed {{ Request::is('/') ? '' : 'inner-page' }}">
    <div class="sticky-wrapper">
        <div class="sticky-active">
            <div class="container">
                <div class="row justify-content-between align-items-center">
                    <div class="col">
                        <div class="d-flex align-items-center justify-content-start position-relative">
                            <button class="vs-menu-toggle d-inline-block d-xl-none" aria-label="Menu"><i class="fal fa-bars"></i></button>
                            <div class="vs-header__logo">
                                <a href="{{ route('front') }}"><img src="{{ asset(getLogoUrl()) }}" alt="{{ getAppName() }}" class="logo"></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <nav class="main-menu d-none d-xl-block">
                            <ul>
                                @foreach ($menu as $item)
                                    <li class="{{ isset($item['children']) ? 'menu-item-has-children' : '' }} {{ $item['active'] ? 'active' : '' }}">
                                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                                        @isset($item['children'])
                                            <ul class="sub-menu">
                                                @foreach ($item['children'] as $child)
                                                    <li><a href="{{ $child['url'] }}">{{ $child['label'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endisset
                                    </li>
                                @endforeach
                                @if (count($languages) > 1)
                                <li class="menu-item-has-children">
                                    <a href="javascript:void(0)"><i class="fa-solid fa-globe"></i> {{ strtoupper(App::getLocale()) }}</a>
                                    <ul class="sub-menu">
                                        @foreach ($languages as $language)
                                            <li class="languageSelection" data-prefix-value="{{ $language['key'] }}">
                                                <a href="javascript:void(0)">
                                                    @if ($language['flag'])<img src="{{ asset($language['flag']) }}" alt="" class="lang-flag">@endif
                                                    {{ $language['label'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                    <div class="col-auto">
                        <div class="vs-header__action">
                            <div class="d-none d-sm-inline-flex">
                                <a href="{{ route('appointment') }}" class="vsBtn">{{ __('messages.web_home.book_appointment') }}<span></span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
