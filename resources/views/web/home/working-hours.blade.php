@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_menu.working_hours') }}
@endsection
@section('content')
    <section class="space">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-6">
                    <div class="vs-title animation-style2">
                        <p class="vs-title__sub"><i class="fa-solid fa-staff-snake title-icon"></i> {{ __('messages.web_menu.working_hours') }}</p>
                        <h2 class="vs-title__main title-anime">{{ frontSetting('appointment_title') }}</h2>
                    </div>
                    <ul class="hours-list">
                        @forelse ($hospitalSchedules as $hospitalSchedule)
                            <li>
                                <span>{{ localization('schedule_weekday', $weekDay[$hospitalSchedule->day_of_week]) }}</span>
                                <strong>{{ $hospitalSchedule->start_time . ' - ' . $hospitalSchedule->end_time }}</strong>
                            </li>
                        @empty
                            <li><span>{{ getSettingValue()['hospital_from_day']['value'] ?? '' }}</span><strong>{{ getSettingValue()['hospital_from_time']['value'] ?? '' }}</strong></li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="vs-appoint__form hours-form">
                        <h3 class="mb-3">{{ __('messages.web_home.book_an_appointment') }}</h3>
                        <form action="{{ route('appointment.post') }}" method="POST">
                            @csrf
                            <div class="appoint_form">
                                <select name="doctorId" aria-label="{{ __('messages.web_home.select_doctor') }}">
                                    <option value="">{{ __('messages.web_home.select_doctor') }}</option>
                                    @foreach ($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->doctorUser->full_name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="appointmentDate" autocomplete="off" class="datepicker" placeholder="{{ __('messages.web_appointment.select_time') }}">
                                <div class="text-center"><button type="submit" class="appoint_btn">{{ __('messages.web_home.book_now') }}</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
