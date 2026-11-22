@extends('layouts.app')
@section('title')
    {{ __('messages.ot.calendar') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div style="min-width: 220px">
                {{ Form::select('room', ['' => __('messages.ot.all_rooms')] + $rooms, null, ['class' => 'form-select', 'id' => 'otRoomFilter']) }}
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card mb-7">
            <div class="card-body">
                <div id="otCalendar" data-events-url="{{ route('ot-calendar.events') }}" data-locale="{{ getLoggedInUser()->language ?? app()->getLocale() }}"
                     data-today="{{ __('messages.appointment.today') }}" data-month="{{ __('messages.appointment.month') }}"
                     data-week="{{ __('messages.appointment.week') }}" data-day="{{ __('messages.appointment.day') }}"></div>
            </div>
        </div>
        @if (canAccessRoute('ot-calendar.update'))
            <div class="card">
                <div class="card-body">
                    <h3 class="mb-5">{{ __('messages.ot.settings') }}</h3>
                    {{ Form::open(['route' => 'ot-calendar.update', 'method' => 'post']) }}
                    <label class="form-label">{{ __('messages.ot.excluded_statuses') }}:</label>
                    <div class="d-flex flex-wrap gap-5 mb-5">
                        @foreach ($statuses as $code => $label)
                            <label class="form-check form-check-sm form-check-custom">
                                <input class="form-check-input" type="checkbox" name="excluded_statuses[]" value="{{ $code }}" {{ in_array($code, $excluded, true) ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="text-muted fs-7 mb-5">{{ __('messages.ot.settings_hint') }}</div>
                    {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                    {{ Form::close() }}
                </div>
            </div>
        @endif
    </div>
@endsection
