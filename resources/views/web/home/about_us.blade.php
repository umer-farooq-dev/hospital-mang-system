@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_menu.about') }}
@endsection
@section('content')
    @php
        $f = fn ($key, $default = '') => frontSetting($key, $default);
        $counters = [
            [(int) $f('home_page_experience'), '+', $f('home_page_experience_label')],
            [$totalDoctorNurses, '', __('messages.web_front.doctors_nurses')],
            [$totalbeds, '', __('messages.web_front.beds')],
            [$totalPatient, '+', __('messages.web_front.patients')],
        ];
    @endphp

    <section class="vs-about style2 space">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-6 col-lg-12">
                    <div class="abImg style2 wow animate__fadeInUp" data-wow-delay="0.2s">
                        <img src="{{ frontImage('about_us_image', 'about/about-img-h3.jpg') }}" alt="{{ $f('about_us_title') }}" loading="lazy">
                        @if ($f('home_page_experience'))
                            <div class="vs-about__wrap">
                                <div class="ab-experience">
                                    <h1>{{ $f('home_page_experience') }}</h1>
                                    <p>{{ $f('home_page_experience_label') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-xl-6 col-lg-12 ps-xl-5">
                    <div class="vs-title home3 mb-35 mt-5 animation-style2">
                        <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ __('messages.web_menu.about') }}</p>
                        <h2 class="vs-title__main title-anime">{{ $f('about_us_title') }}</h2>
                    </div>
                    <div class="vs-about__right wow animate__fadeInUp" data-wow-delay="0.2s">
                        <p>{!! nl2br(e($f('about_us_description'))) !!}</p>
                        <div class="ab-Box">
                            <div class="question">
                                <h3>{{ __('messages.web_front.our_mission') }}</h3>
                                <p>{{ $f('about_us_mission') }}</p>
                            </div>
                            <div>
                                <ul>
                                    @foreach (array_filter(array_map('trim', preg_split('/\R/', $f('home_page_about_points')))) as $point)
                                        <li><i class="fa-solid fa-badge-check"></i> {{ $point }}</li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('appointment') }}" class="vsBtn">{{ __('messages.web_home.book_appointment') }}<span></span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="space-bottom">
        <div class="container">
            <div class="vs-counter about-counter" data-bg-src="{{ asset('web_front/kliniko/img/bg/count-h2-bg.jpg') }}">
                @foreach ($counters as $i => [$number, $suffix, $label])
                    @continue($number <= 0)
                    <div class="counter-body wow animate__fadeInUp" data-wow-delay="0.{{ $i + 2 }}s">
                        <div class="counter-txt">
                            <h2><span class="counter-number" data-counter="{{ $number }}">{{ $number }}</span>{{ $suffix }}</h2>
                            <h3>{{ $label }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="vs-choose style2 space-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5">
                    <div class="chooseImg">
                        <img src="{{ frontImage('home_page_certified_doctor_image', 'bg/choose-img-h2.jpg') }}" alt="" loading="lazy">
                    </div>
                </div>
                <div class="col-lg-7 ps-lg-5">
                    <div class="vs-title animation-style2">
                        <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ $f('section_process_sub') }}</p>
                        <h2 class="vs-title__main title-anime">{{ $f('home_page_certified_box_title') }}</h2>
                    </div>
                    <p>{{ $f('home_page_certified_box_description') }}</p>
                    <div class="row g-4 mt-2">
                        @for ($n = 1; $n <= 4; $n++)
                            <div class="col-sm-6">
                                <div class="about-step">
                                    <span class="ser_number">{{ sprintf('%02d', $n) }}</span>
                                    <h3>{{ $f('home_page_step_'.$n.'_title') }}</h3>
                                    <p>{{ $f('home_page_step_'.$n.'_description') }}</p>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($doctors->isNotEmpty())
        <section class="vs-doctors style3 space-bottom">
            <div class="container">
                <div class="vs-title text-center animation-style2">
                    <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ $f('section_doctors_sub') }}</p>
                    <h2 class="vs-title__main title-anime">{{ $f('section_doctors_title') }}</h2>
                </div>
                <div class="vs-doctors__wrap">
                    @foreach ($doctors as $i => $doctor)
                        <div class="vs-doctors__wrap-content wow animate__fadeInUp" data-wow-delay="0.{{ $i + 2 }}s">
                            <div class="doctor_img">
                                <div class="shape-circle"></div>
                                <div class="imgBox">
                                    <a href="{{ route('doctor-details', $doctor->id) }}"><img src="{{ $doctor->doctorUser->getFirstMediaUrl(\App\Models\User::COLLECTION_PROFILE_PICTURES) ?: asset('web_front/kliniko/img/doctors/dr-h3-img'.($i % 4 + 1).'.jpg') }}" alt="{{ $doctor->doctorUser->full_name }}" loading="lazy"></a>
                                </div>
                            </div>
                            <div class="doctor_txt">
                                <a href="{{ route('doctor-details', $doctor->id) }}"><h3>{{ $doctor->doctorUser->full_name }}</h3></a>
                                <div class="dr-txt"><p>{{ $doctor->specialist }}</p></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
