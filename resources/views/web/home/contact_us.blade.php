@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.contact') }}
@endsection
@section('content')
    @php
        $settingValue = getSettingValue();
        $value = fn ($key) => $settingValue[$key]['value'] ?? '';
        $mapUrl = frontSetting('location_map');
    @endphp
    <section class="space vs-contact space-extra-bottom overflow-hidden">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 contactP">
                    <div class="contact-info mb-20 wow animate__fadeInUp" data-wow-delay="0.2s">
                        <div class="d-flex align-items-center justify-content-between">
                            <h2>{{ __('messages.web_front.address') }}</h2>
                            <div class="con-icon"><i class="fa-solid fa-location-dot"></i></div>
                        </div>
                        <p>{{ $value('hospital_address') }}</p>
                        <div class="bg-icon"><i class="fa-solid fa-location-dot"></i></div>
                    </div>
                    <div class="contact-info mb-20 wow animate__fadeInUp" data-wow-delay="0.3s">
                        <div class="d-flex align-items-center justify-content-between">
                            <h2>{{ __('messages.web_front.call_us') }}</h2>
                            <div class="con-icon"><i class="fa-solid fa-phone-volume"></i></div>
                        </div>
                        <ul><li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $value('hospital_phone')) }}">{{ $value('hospital_phone') }}</a></li></ul>
                        <div class="bg-icon"><i class="fa-solid fa-phone-volume"></i></div>
                    </div>
                    <div class="contact-info mb-20 wow animate__fadeInUp" data-wow-delay="0.4s">
                        <div class="d-flex align-items-center justify-content-between">
                            <h2>{{ __('messages.web_front.email_us') }}</h2>
                            <div class="con-icon"><i class="fa-solid fa-envelope"></i></div>
                        </div>
                        <ul><li><a href="mailto:{{ $value('hospital_email') }}">{{ $value('hospital_email') }}</a></li></ul>
                        <div class="bg-icon"><i class="fa-solid fa-envelope"></i></div>
                    </div>
                    <div class="contact-info mb-20 wow animate__fadeInUp" data-wow-delay="0.5s">
                        <div class="d-flex align-items-center justify-content-between">
                            <h2>{{ __('messages.web_menu.working_hours') }}</h2>
                            <div class="con-icon"><i class="fa-solid fa-clock"></i></div>
                        </div>
                        <p>{{ $value('hospital_from_day') }} · {{ $value('hospital_from_time') }}</p>
                        <div class="bg-icon"><i class="fa-solid fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="contact-box">
                        <div class="vs-title text-center animation-style2">
                            <p class="vs-title__sub mb-15"><i class="fa-solid fa-staff-snake title-icon"></i> {{ __('messages.web_home.contact') }}</p>
                            <h2 class="vs-title__main title-anime">{{ __('messages.web_front.get_in_touch') }}</h2>
                        </div>
                        <div class="form-style">
                            @include('web.partial.contact_form_partial')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @if (!empty($mapUrl))
        <div class="vs-map contact-map">{!! $mapUrl !!}</div>
    @endif
@endsection
@section('page_scripts')
    @if (config('app.recaptcha.sitekey'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endsection
