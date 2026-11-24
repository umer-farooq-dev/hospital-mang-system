@php
    $settingValue = getSettingValue();
    $value = fn ($key) => $settingValue[$key]['value'] ?? '';
    $social = collect(\App\Models\Setting::SOCIAL_ICONS)->filter(fn ($icon, $key) => $value($key) !== '');
    $footerServices = \App\Models\FrontService::ofType(\App\Models\FrontService::SERVICE)->take(5)->get(['id', 'name']);
@endphp
<footer class="vs-footer bg-title" data-bg-src="{{ asset('web_front/kliniko/img/bg/footer-bg-h1.png') }}">
    <div class="vs-footer__top z-index-common space space-extra-bottom">
        <div class="container">
            <div class="vs-footer__contact">
                <div class="contact_box">
                    <div class="con-icon"><i class="fa-solid fa-map-location"></i></div>
                    <div class="con-txt">
                        <p>{{ __('messages.web_front.address') }}</p>
                        <h3>{{ $value('hospital_address') }}</h3>
                    </div>
                </div>
                <div class="contact_box">
                    <div class="con-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="con-txt">
                        <p>{{ __('messages.web_front.email_us') }}</p>
                        <h3><a href="mailto:{{ $value('hospital_email') }}">{{ $value('hospital_email') }}</a></h3>
                    </div>
                </div>
                <div class="contact_box">
                    <div class="con-icon"><i class="fa-solid fa-headphones"></i></div>
                    <div class="con-txt">
                        <p>{{ __('messages.web_front.call_us') }}</p>
                        <h3><a href="tel:{{ preg_replace('/[^0-9+]/', '', $value('hospital_phone')) }}">{{ $value('hospital_phone') }}</a></h3>
                    </div>
                </div>
            </div>
            <div class="vs-footer__wrapper wow animate__fadeInUp" data-wow-delay="0.25s">
                <div class="vs-footer__wrapper-widget">
                    <div class="vs-footer__logo mb-35">
                        <a href="{{ route('front') }}"><img src="{{ asset(getLogoUrl()) }}" alt="{{ getAppName() }}"></a>
                    </div>
                    <div class="vs-footer__wrapper-content">
                        <p>{{ strip_tags($value('about_us')) }}</p>
                        @if ($social->isNotEmpty())
                            <div class="social-style mt-3">
                                @foreach ($social as $key => $icon)
                                    <a href="{{ $value($key) }}" target="_blank" rel="noopener" aria-label="{{ $key }}"><i class="fa-brands {{ $icon }}"></i></a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="vs-footer__wrapper-widget">
                    <h3 class="vs-footer__wrapper-title">{{ __('messages.web_home.services') }}</h3>
                    <div class="vs-footer__wrapper-content">
                        <ul>
                            @foreach ($footerServices as $service)
                                <li><a href="{{ route('service-details', $service->id) }}">{{ $service->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="vs-footer__wrapper-widget">
                    <h3 class="vs-footer__wrapper-title">{{ __('messages.web_menu.useful_link') }}</h3>
                    <div class="vs-footer__wrapper-content">
                        <ul>
                            <li><a href="{{ route('aboutUs') }}">{{ __('messages.web_menu.about') }}</a></li>
                            <li><a href="{{ route('our-doctors') }}">{{ __('messages.web_home.doctors') }}</a></li>
                            <li><a href="{{ route('news') }}">{{ __('messages.front_web.type_3') }}</a></li>
                            <li><a href="{{ route('appointment') }}">{{ __('messages.web_menu.appointment') }}</a></li>
                            <li><a href="{{ route('contact') }}">{{ __('messages.web_home.contact') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="vs-footer__wrapper-widget">
                    <h3 class="vs-footer__wrapper-title">{{ __('messages.web_menu.working_hours') }}</h3>
                    <div class="vs-footer__wrapper-content">
                        <p class="mb-2"><i class="fa-solid fa-calendar me-2"></i>{{ $value('hospital_from_day') }}</p>
                        <p class="mb-3"><i class="fa-solid fa-clock me-2"></i>{{ $value('hospital_from_time') }}</p>
                        <a href="{{ route('working-hours') }}" class="vsBtn">{{ __('messages.web_front.see_hours') }}<span></span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="vs-footer__bottom">
        <div class="container">
            <div class="row justify-content-lg-between justify-content-md-center align-items-center gy-3">
                <div class="col-md-auto">
                    <p class="vs-footer__copyright m-0 pt-2">© {{ date('Y') }} <a href="{{ route('front') }}">{{ getAppName() }}</a>. {{ __('messages.web_front.rights') }}</p>
                </div>
                <div class="col-md-auto">
                    <ul class="vs-footer__bottom--menu">
                        <li><a href="{{ route('terms-of-service') }}">{{ __('messages.web_home.terms_of_service') }}</a></li>
                        <li><a href="{{ route('privacy-policy') }}">{{ __('messages.web_home.privacy_policy') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
