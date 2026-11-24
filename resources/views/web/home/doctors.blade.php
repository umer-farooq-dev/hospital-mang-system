@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.doctors') }}
@endsection
@section('content')
    <section class="vs-doctors style3 doctors-page space">
        <div class="container">
            @if ($doctors->isEmpty())
                <p class="text-center">{{ __('messages.doctor.no_doctor_available') }}</p>
            @endif
            <div class="vs-doctors__wrap">
                @foreach ($doctors as $i => $doctor)
                    <div class="vs-doctors__wrap-content wow animate__fadeInUp" data-wow-delay="0.{{ $i % 4 + 2 }}s">
                        <div class="doctor_img">
                            <div class="shape-circle"></div>
                            <div class="imgBox">
                                <a href="{{ route('doctor-details', $doctor->id) }}"><img src="{{ $doctor->doctorUser->getFirstMediaUrl(\App\Models\User::COLLECTION_PROFILE_PICTURES) ?: asset('web_front/kliniko/img/doctors/dr-h3-img'.($i % 4 + 1).'.jpg') }}" alt="{{ $doctor->doctorUser->full_name }}" loading="lazy"></a>
                            </div>
                        </div>
                        <div class="doctor_txt">
                            <a href="{{ route('doctor-details', $doctor->id) }}"><h3>{{ $doctor->doctorUser->full_name }}</h3></a>
                            <div class="dr-txt"><p>{{ $doctor->specialist ?: optional($doctor->department)->title }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-5">{{ $doctors->links() }}</div>
        </div>
    </section>
@endsection
