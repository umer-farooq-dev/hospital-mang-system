@extends('web.layouts.front')
@section('title')
    {{ $doctorDetails->doctorUser->full_name }}
@endsection
@section('content')
    @php($photo = $doctorDetails->doctorUser->getFirstMediaUrl(\App\Models\User::COLLECTION_PROFILE_PICTURES))
    <section class="space doctor-profile">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="doctor-card wow animate__fadeInUp" data-wow-delay="0.2s">
                        <img src="{{ $photo ?: asset('web_front/kliniko/img/doctors/dr-h3-img1.jpg') }}" alt="{{ $doctorDetails->doctorUser->full_name }}">
                        <div class="doctor-card__body">
                            <h2>{{ $doctorDetails->doctorUser->full_name }}</h2>
                            <p>{{ $doctorDetails->specialist }}</p>
                            <a href="{{ route('appointment') }}" class="vsBtn w-100 justify-content-center">{{ __('messages.web_home.book_appointment') }}<span></span></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="vs-title animation-style2 mb-4">
                        <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ optional($doctorDetails->department)->title }}</p>
                        <h2 class="vs-title__main title-anime">{{ $doctorDetails->doctorUser->full_name }}</h2>
                    </div>
                    <ul class="doctor-facts">
                        <li><span>{{ __('messages.doctor.specialist') }}</span>{{ $doctorDetails->specialist ?: __('messages.common.n/a') }}</li>
                        <li><span>{{ __('messages.user.qualification') }}</span>{{ $doctorDetails->doctorUser->qualification ?: __('messages.common.n/a') }}</li>
                    </ul>
                    @if (!empty($doctorDetails->description))
                        <p class="mt-4">{!! nl2br(e($doctorDetails->description)) !!}</p>
                    @endif
                    @if ($doctorSchedule->isNotEmpty())
                        <h3 class="mt-5 mb-3">{{ __('messages.schedules') }}</h3>
                        <div class="table-responsive">
                            <table class="table schedule-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.schedule.available_on') }}</th>
                                        <th>{{ __('messages.schedule.available_from') }}</th>
                                        <th>{{ __('messages.schedule.available_to') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($doctorSchedule as $schedule)
                                        <tr>
                                            <td>{{ __('messages.schedule_weekday.'.$schedule->available_on) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->available_from)->format('H:i') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->available_to)->format('H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
