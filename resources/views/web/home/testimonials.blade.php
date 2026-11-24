@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.testimonials') }}
@endsection
@section('content')
    <section class="vs-testimoni z-index-common space">
        <div class="container">
            @if ($testimonials->isEmpty())
                <p class="text-center">{{ __('messages.common.no_data_available') }}</p>
            @endif
            <div class="vs-testimoni--style3">
                @foreach ($testimonials as $i => $testimonial)
                    <div class="vs-testimoni__wrap wow animate__fadeInUp" data-wow-delay="0.2s">
                        <div class="vs-testimoni__img">
                            <div class="test_img">
                                <img src="{{ optional($testimonial->media->first())->getFullUrl() ?: asset('web_front/kliniko/img/testimoni/testi-h3-img'.($i % 2 + 1).'.jpg') }}" alt="{{ $testimonial->name }}" loading="lazy">
                            </div>
                        </div>
                        <div class="test_txt">
                            <p>“{{ $testimonial->description }}”</p>
                            <div class="test-admin"><h3>{{ \Illuminate\Support\Str::limit($testimonial->name, 46) }}</h3></div>
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
@endsection
