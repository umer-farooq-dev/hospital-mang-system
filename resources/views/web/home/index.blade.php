@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.home') }}
@endsection
@section('content')
    @php
        $f = fn ($key, $default = '') => frontSetting($key, $default);
        $settingValue = getSettingValue();
        $phone = $settingValue['hospital_phone']['value'] ?? '';
        $tel = preg_replace('/[^0-9+]/', '', $phone);
        $video = $f('home_page_video_url');
        $serviceImages = ['service/ser-img-h3.jpg', 'service/ser-img1-h3.jpg', 'service/ser-img2-h3.jpg'];
        $serviceIcons = ['fa-stethoscope', 'fa-truck-medical', 'fa-person-pregnant', 'fa-flask-vial', 'fa-x-ray', 'fa-syringe'];
        $hasContent = ['services' => $frontServices->isNotEmpty(), 'doctors' => $doctors->isNotEmpty(),
            'cases' => $cases->isNotEmpty(), 'testimonials' => $testimonials->isNotEmpty(), 'blog' => $posts->isNotEmpty()];
        $stepIcons = ['fa-calendar-check', 'fa-user-doctor', 'fa-microscope', 'fa-heart-pulse'];
        $title = fn ($sub, $main, $center = false) => '<div class="vs-title animation-style2 '.($center ? 'text-center' : '').'"><p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> '.e($sub).'</p><h2 class="vs-title__main title-anime '.($center ? 'm-auto' : '').'">'.e($main).'</h2></div>';
    @endphp

    {{-- Hero --}}
    <section class="vs-hero overflow-hidden">
        <div class="vs-hero__bg" data-bg-src="{{ frontImage('home_page_image', 'hero/banner-BG-h3.jpg') }}"></div>
        <div class="container">
            <div class="vs-hero__wrap">
                <div class="row">
                    <div class="col-lg-7 col-md-12">
                        <div class="vs-hero__content">
                            <span class="vs-hero__title--sub title-sub text-theme-color1 fw-medium">{{ $f('home_page_subtitle') }}</span>
                            <h1 class="vs-hero__title--main title-main">{{ $f('home_page_title') }}</h1>
                            <p class="vs-hero__title--para title-para">{{ $f('home_page_description') }}</p>
                            <div class="vs-hero__btn heroBtn">
                                <a class="vsBtn" href="{{ route('appointment') }}"><span></span>{{ __('messages.web_home.book_appointment') }}</a>
                                @if ($video)
                                    <a href="{{ $video }}" class="vs-video__button play-btn popup-video" aria-label="video"><i class="fa-solid fa-play"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @foreach ($sections as $section)
        @continue(! $section['on'] || ! ($hasContent[$section['key']] ?? true))
        @switch($section['key'])
            @case('about')
                <section class="vs-about style2 space">
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-6 col-lg-12">
                                <div class="abImg style2 wow animate__fadeInUp" data-wow-delay="0.2s">
                                    <img src="{{ frontImage('home_page_certified_doctor_image', 'about/about-img-h3.jpg') }}" alt="{{ $f('home_page_certified_doctor_title') }}" loading="lazy">
                                    @if ($f('home_page_experience'))
                                        <div class="vs-about__wrap">
                                            <div class="ab-experience">
                                                <h1>{{ $f('home_page_experience') }}</h1>
                                                <p>{{ $f('home_page_experience_label') }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($f('home_page_circle_text'))
                                        <div class="circle">
                                            <div class="circle-logo"><img src="{{ asset(getLogoUrl()) }}" alt="{{ getAppName() }}"></div>
                                            <svg viewBox="0 0 100 100" class="rotating-svg">
                                                <path id="circlePath" d="M 50,50 m -40,0 a 40,40 0 1,1 80,0 a 40,40 0 1,1 -80,0" fill="transparent" />
                                                <text><textPath href="#circlePath" startOffset="0%">{{ $f('home_page_circle_text') }}</textPath></text>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-12 ps-xl-5">
                                <div class="vs-title home3 mb-35 mt-5 animation-style2">
                                    <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ $f('home_page_certified_doctor_text') }}</p>
                                    <h2 class="vs-title__main title-anime">{{ $f('home_page_certified_doctor_title') }}</h2>
                                </div>
                                <div class="vs-about__right wow animate__fadeInUp" data-wow-delay="0.2s">
                                    <p>{{ $f('home_page_certified_doctor_description') }}</p>
                                    <div class="ab-Box">
                                        <div class="question">
                                            <h3>{{ $f('home_page_box_title') }}</h3>
                                            <p>{{ $f('home_page_box_description') }}</p>
                                            @if ($phone)
                                                <div class="d-inline-flex align-items-center">
                                                    <div class="call-icon">
                                                        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M0 30C0 13.4315 13.4315 0 30 0H50C55.5228 0 60 4.47715 60 10V30C60 46.5685 46.5685 60 30 60C13.4315 60 0 46.5685 0 30Z" fill="url(#callGradient)" />
                                                            <defs>
                                                                <linearGradient id="callGradient" x1="60" y1="30" x2="0" y2="30" gradientUnits="userSpaceOnUse">
                                                                    <stop offset="0" style="stop-color: var(--vs-theme-color1)" />
                                                                    <stop offset="1" style="stop-color: var(--vs-theme-color5)" />
                                                                </linearGradient>
                                                            </defs>
                                                        </svg>
                                                        <i class="fa-solid fa-headphones"></i>
                                                    </div>
                                                    <p class="e-call"><span>{{ __('messages.web_front.emergency_call') }}</span> <a href="tel:{{ $tel }}">{{ $phone }}</a></p>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <ul>
                                                @foreach (array_filter(array_map('trim', preg_split('/\R/', $f('home_page_about_points')))) as $point)
                                                    <li><i class="fa-solid fa-badge-check"></i> {{ $point }}</li>
                                                @endforeach
                                            </ul>
                                            <a href="{{ route('aboutUs') }}" class="vsBtn">{{ __('messages.web_front.read_more') }}<span></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('services')
                <section class="vs-service style3 z-index-common space-top bg-img-color">
                    <div class="container">
                        <div class="d-md-flex align-items-center justify-content-between d-sm-block">
                            {!! $title($f('section_services_sub'), $f('section_services_title')) !!}
                            <div class="vs-navigation style2">
                                <div id="style5_prev" class="swiper-prevBtn"><i class="fa-solid fa-arrow-left"></i></div>
                                <div id="style5_next" class="swiper-nextBtn"><i class="fa-solid fa-arrow-right"></i></div>
                            </div>
                        </div>
                        <div class="swiper" data-swiper data-xl="3" data-gap-xl="30" data-lg="2" data-nav-next="#style5_next" data-nav-prev="#style5_prev">
                            <div class="swiper-wrapper">
                                @foreach ($frontServices as $i => $service)
                                    <div class="swiper-slide">
                                        <div class="vs-service__wrap wow animate__fadeInUp" data-wow-delay="0.2s">
                                            <div class="vs-service__img">
                                                <img src="{{ $service->image_url ?: asset('web_front/kliniko/img/'.$serviceImages[$i % 3]) }}" alt="{{ $service->name }}" class="serImg" loading="lazy">
                                                <div class="ser_icon">
                                                    @if ($service->icon_url)
                                                        <img src="{{ $service->icon_url }}" alt="">
                                                    @else
                                                        <i class="fa-solid {{ $serviceIcons[$i % 6] }}"></i>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="vs-service__txt">
                                                <a href="{{ route('service-details', $service->id) }}"><h3>{{ $service->name }}</h3></a>
                                                <a href="{{ route('service-details', $service->id) }}" class="ser_btn">{{ __('messages.web_front.read_more') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('video')
                @php($videoFile = frontFile(\App\Models\FrontSetting::VIDEO_KEY))
                <div class="vs-video vs-video--bg space" @unless ($videoFile) data-bg-src="{{ frontImage('home_page_video_image', 'bg/video-h3-bg.jpg') }}" @endunless>
                    @if ($videoFile)
                        <video class="vs-video__media" muted loop playsinline preload="none" data-lazy-video poster="{{ frontImage('home_page_video_image', 'bg/video-h3-bg.jpg') }}" aria-hidden="true">
                            <source src="{{ $videoFile }}" type="{{ str_ends_with($videoFile, '.webm') ? 'video/webm' : 'video/mp4' }}">
                        </video>
                    @endif
                    <div class="container">
                        <div class="video_btn text-center">
                            @if ($video)
                                <a href="{{ $video }}" class="play-btn popup-video" aria-label="video"><i class="fa-solid fa-play"></i></a>
                            @endif
                            <h2 class="video-banner-title">{{ $f('home_page_subtitle') }}</h2>
                            <a href="{{ route('appointment') }}" class="vsBtn">{{ __('messages.web_home.book_appointment') }}<span></span></a>
                        </div>
                    </div>
                </div>
                @break

            @case('process')
                <section class="vs-process space bg-img-color" data-bg-src="{{ asset('web_front/kliniko/img/service/proces-bg-h2.png') }}">
                    <div class="container">
                        <div class="d-flex align-items-center justify-content-lg-between flex-wrap gap-3">
                            {!! $title($f('section_process_sub'), $f('section_process_title')) !!}
                            <p>{{ $f('section_process_text') }}</p>
                        </div>
                        <div class="vs-process_navigation">
                            <div class="swiper processNav">
                                <div class="swiper-wrapper">
                                    @for ($n = 1; $n <= 4; $n++)
                                        <div class="swiper-slide">{{ __('messages.web_front.step') }} {{ $n }}.</div>
                                    @endfor
                                </div>
                            </div>
                            <div class="vs-navigation style2">
                                <div class="swiper-prevBtn process-prev"><i class="fa-solid fa-arrow-left"></i></div>
                                <div class="swiper-nextBtn process-next"><i class="fa-solid fa-arrow-right"></i></div>
                            </div>
                        </div>
                        <div class="vs-process__wrap">
                            <div class="swiper processSlider">
                                <div class="swiper-wrapper">
                                    @for ($n = 1; $n <= 4; $n++)
                                        <div class="swiper-slide">
                                            <div class="process-box">
                                                <div class="process-img">
                                                    <img src="{{ frontImage('home_page_step_'.$n.'_image', 'service/proces-h3-img'.(($n - 1) % 2 + 1).'.jpg') }}" alt="{{ $f('home_page_step_'.$n.'_title') }}" loading="lazy">
                                                </div>
                                                <div class="vs-service">
                                                    <div class="vs-service__wrap">
                                                        <div class="vs-service__icon">
                                                            <div class="ser_icon"><i class="fa-solid {{ $stepIcons[$n - 1] }}"></i></div>
                                                            <span class="ser_number">{{ sprintf('%02d', $n) }}</span>
                                                        </div>
                                                        <div class="vs-service__txt">
                                                            <h3>{{ $f('home_page_step_'.$n.'_title') }}</h3>
                                                            <p>{{ $f('home_page_step_'.$n.'_description') }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('appointment')
                <section class="vs-appoint style3 space" data-bg-src="{{ asset('web_front/kliniko/img/bg/appoint-bg-h3.jpg') }}">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-9">
                                <div class="d-flex align-items-center justify-content-lg-between flex-wrap gap-3">
                                    <div class="vs-title mb-0 animation-style2">
                                        <p class="vs-title__sub text-white"><i class="fa-solid fa-staff-snake"></i> {{ $f('section_appointment_sub') }}</p>
                                        <h2 class="vs-title__main title-anime text-white">{{ $f('appointment_title') }}</h2>
                                    </div>
                                    @if ($phone)
                                        <div class="vs-appoint__box">
                                            <div class="appoint-content">
                                                <div class="appoint-icon"><i class="fa-solid fa-headphones"></i></div>
                                                <div class="appoint-txt"><p><span>{{ __('messages.web_front.call_us_24') }}</span><a href="tel:{{ $tel }}">{{ $phone }}</a></p></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="vs-appoint__form wow animate__fadeInUp" data-wow-delay="0.4s">
                                    <form action="{{ route('appointment.post') }}" method="POST">
                                        @csrf
                                        <div class="appoint_form">
                                            <select name="departmentId" aria-label="{{ __('messages.appointment.doctor_department') }}">
                                                <option value="">{{ __('messages.appointment.doctor_department') }}</option>
                                                @foreach ($departments as $id => $department)
                                                    <option value="{{ $id }}">{{ $department }}</option>
                                                @endforeach
                                            </select>
                                            <select name="doctorId" aria-label="{{ __('messages.web_home.select_doctor') }}">
                                                <option value="">{{ __('messages.web_home.select_doctor') }}</option>
                                                @foreach ($doctors as $doctor)
                                                    <option value="{{ $doctor->id }}">{{ $doctor->doctorUser->full_name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="appointmentDate" autocomplete="off" class="datepicker" placeholder="{{ __('messages.web_appointment.select_time') }}">
                                            <div class="text-center">
                                                <button type="submit" class="appoint_btn">{{ __('messages.web_home.book_appointment') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                    <p class="appoint-note">{{ $f('appointment_description') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case('doctors')
                <section class="vs-doctors style3 space">
                    <div class="container">
                        {!! $title($f('section_doctors_sub'), $f('section_doctors_title'), true) !!}
                        <div class="vs-doctors__wrap">
                            @foreach ($doctors->take(4) as $i => $doctor)
                                <div class="vs-doctors__wrap-content wow animate__fadeInUp" data-wow-delay="0.{{ $i + 2 }}s">
                                    <div class="doctor_img">
                                        <div class="shape-circle"></div>
                                        <div class="imgBox">
                                            <a href="{{ route('doctor-details', $doctor->id) }}">
                                                <img src="{{ $doctor->doctorUser->getFirstMediaUrl(\App\Models\User::COLLECTION_PROFILE_PICTURES) ?: asset('web_front/kliniko/img/doctors/dr-h3-img'.($i % 4 + 1).'.jpg') }}" alt="{{ $doctor->doctorUser->full_name }}" loading="lazy">
                                            </a>
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
                @break

            @case('cases')
                <section class="vs-case style3 z-index-common space-top bg-img-color">
                    <div class="container">
                        <div class="d-md-flex align-items-center justify-content-lg-between d-sm-block">
                            {!! $title($f('section_cases_sub'), $f('section_cases_title')) !!}
                            <a href="{{ route('case-studies') }}" class="vsBtn">{{ __('messages.web_front.view_all') }}<span></span></a>
                        </div>
                        <div class="scroll-case">
                            @foreach ($cases as $i => $case)
                                <div class="slide layer-{{ $i + 1 }}">
                                    <div class="vs-case__wrap wow animate__fadeInUp" data-wow-delay="0.{{ $i + 2 }}s">
                                        <div class="vs-case__img">
                                            <div class="case_img">
                                                <a href="{{ route('case-studies', $case->id) }}"><img src="{{ $case->image_url ?: asset('web_front/kliniko/img/doctors/case-h3-img'.($i % 3 + 1).'.jpg') }}" alt="{{ $case->name }}" loading="lazy"></a>
                                            </div>
                                        </div>
                                        <div class="case_txt">
                                            @if ($case->category)<span class="label">{{ $case->category }}</span>@endif
                                            <a href="{{ route('case-studies', $case->id) }}"><h3>{{ $case->name }}</h3></a>
                                            <p>{{ $case->short_description }}</p>
                                            <div class="readMore"><a href="{{ route('case-studies', $case->id) }}">{{ __('messages.web_front.read_more') }}</a></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('testimonials')
                <section class="vs-testimoni z-index-common space space-extra-bottom">
                    <div class="container">
                        {!! $title($f('section_testimonials_sub'), $f('section_testimonials_title'), true) !!}
                        <div class="vs-testimoni--style3">
                            @foreach ($testimonials->take(4) as $i => $testimonial)
                                <div class="vs-testimoni__wrap wow animate__fadeInUp" data-wow-delay="0.2s">
                                    <div class="vs-testimoni__img">
                                        <div class="test_img">
                                            <img src="{{ optional($testimonial->media->first())->getFullUrl() ?: asset('web_front/kliniko/img/testimoni/testi-h3-img'.($i % 2 + 1).'.jpg') }}" alt="{{ $testimonial->name }}" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="test_txt">
                                        <span class="star"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span>
                                        <p>“{{ $testimonial->description }}”</p>
                                        <div class="test-admin"><h3>{{ $testimonial->name }}</h3></div>
                                        <div class="test-icon">
                                            <img src="{{ asset('web_front/kliniko/img/icons/comma.svg') }}" alt="" class="comma1">
                                            <img src="{{ asset('web_front/kliniko/img/icons/comma2.svg') }}" alt="" class="comma2">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case('blog')
                <section class="vs-blog style2 z-index-common space bg-img-color">
                    <div class="container">
                        <div class="d-flex align-items-center justify-content-lg-between">
                            {!! $title($f('section_blog_sub'), $f('section_blog_title')) !!}
                            <div class="vs-navigation style2">
                                <div id="style6_prev" class="swiper-prevBtn"><i class="fa-solid fa-arrow-left"></i></div>
                                <div id="style6_next" class="swiper-nextBtn"><i class="fa-solid fa-arrow-right"></i></div>
                            </div>
                        </div>
                        <div class="swiper blogSlider" data-swiper data-xl="3" data-md="2" data-sm="1" data-nav-next="#style6_next" data-nav-prev="#style6_prev">
                            <div class="swiper-wrapper">
                                @foreach ($posts as $i => $post)
                                    <div class="swiper-slide">
                                        <div class="vs-blog__wrap wow animate__fadeInUp" data-wow-delay="0.2s">
                                            <div class="blog_img">
                                                <a href="{{ route('news', $post->id) }}"><img src="{{ $post->image_url ?: asset('web_front/kliniko/img/blog/blog-h1-img'.($i % 3 + 1).'.jpg') }}" alt="{{ $post->name }}" loading="lazy"></a>
                                                @if ($post->published_at)
                                                    <p class="bdate style2"><span>{{ $post->published_at->format('d') }}</span> {{ $post->published_at->translatedFormat('M') }}</p>
                                                @endif
                                            </div>
                                            <div class="blog_content">
                                                <div class="blog-meta">
                                                    <span><i class="fa-solid fa-hospital"></i> {{ getAppName() }}</span>
                                                    @if ($post->category)<span><i class="fa-solid fa-tag"></i> {{ $post->category }}</span>@endif
                                                </div>
                                                <div class="blog-txt">
                                                    <a href="{{ route('news', $post->id) }}"><h3>{{ $post->name }}</h3></a>
                                                    <div class="blog-btn">
                                                        <a class="readMore" href="{{ route('news', $post->id) }}">{{ __('messages.web_front.read_more') }}</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach
@endsection
@section('scripts')
    <script>
        // start the background video only when it is about to be seen (saves the download for visitors who never scroll there)
        document.querySelectorAll('video[data-lazy-video]').forEach(function (video) {
            if (!('IntersectionObserver' in window)) { video.preload = 'auto'; video.play().catch(function () {}); return; }
            new IntersectionObserver(function (entries, observer) {
                if (!entries[0].isIntersecting) return;
                observer.disconnect();
                video.preload = 'auto';
                video.load();
                video.play().catch(function () {});
            }, {rootMargin: '300px'}).observe(video);
        });
    </script>
@endsection
