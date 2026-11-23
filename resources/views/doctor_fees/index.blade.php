@extends('layouts.app')
@section('title')
    {{ __('messages.doctor_fees.statements') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('layouts.export_button')
            @canroute('doctor-fees.plans')<a href="{{ route('doctor-fees.plans') }}" class="btn btn-outline-primary">{{ __('messages.doctor_fees.plans') }}</a>@endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        @canroute('doctor-fees.store')
            <div class="card mb-7">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.doctor_fees.new') }}</h5>
                    {{ Form::open(['route' => 'doctor-fees.store', 'method' => 'post', 'class' => 'row g-3 align-items-end']) }}
                    <div class="col-md-3"><label class="form-label required">{{ __('messages.doctor_fees.doctor') }}</label>{{ Form::select('doctor_id', $doctors, old('doctor_id'), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}</div>
                    <div class="col-md-2"><label class="form-label required">{{ __('messages.stock_report.from') }}</label><input type="date" name="from" class="form-control" required value="{{ old('from', now()->subMonthNoOverflow()->startOfMonth()->toDateString()) }}"></div>
                    <div class="col-md-2"><label class="form-label required">{{ __('messages.stock_report.to') }}</label><input type="date" name="to" class="form-control" required value="{{ old('to', now()->subMonthNoOverflow()->endOfMonth()->toDateString()) }}"></div>
                    <div class="col-md-1"><label class="form-label">{{ __('messages.doctor_fees.hours') }}</label><input type="number" name="hours" min="0" step="0.25" class="form-control" value="{{ old('hours') }}"></div>
                    <div class="col-md-2"><label class="form-label">{{ __('messages.doctor_fees.other') }}</label><input type="number" name="other" min="0" step="0.01" class="form-control" value="{{ old('other') }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.doctor_fees.make') }}</button></div>
                    <div class="col-md-10"><input type="text" name="note" maxlength="255" class="form-control" placeholder="{{ __('messages.doctor_fees.other_note') }}" value="{{ old('note') }}"></div>
                    <div class="col-12 form-text">{{ __('messages.doctor_fees.new_hint') }}</div>
                    {{ Form::close() }}
                </div>
            </div>
        @endcanroute
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('doctor_id', $doctors, request('doctor_id'), ['class' => 'form-select', 'placeholder' => __('messages.doctor_fees.all_doctors')]) }}</div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">{{ __('messages.stock_transfer.all_statuses') }}</option>
                            @foreach (\App\Models\DoctorStatement::STATUSES as $option)
                                <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ __('messages.doctor_fees.statuses.' . $option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.claims.number') }}</th>
                            <th>{{ __('messages.doctor_fees.doctor') }}</th>
                            <th>{{ __('messages.claims.period') }}</th>
                            <th class="text-end">{{ __('messages.doctor_fees.gross') }}</th>
                            <th class="text-end">{{ __('messages.doctor_fees.withholding') }}</th>
                            <th class="text-end">{{ __('messages.doctor_fees.net') }}</th>
                            <th>{{ __('messages.stock_transfer.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($statements as $s)
                            <tr>
                                <td><a href="{{ route('doctor-fees.show', $s->id) }}">{{ $s->number }}</a></td>
                                <td>{{ $s->doctor->doctorUser->full_name ?? '' }}</td>
                                <td>{{ $s->period_from->format('d/m/Y') }} – {{ $s->period_to->format('d/m/Y') }}</td>
                                <td class="text-end">{{ formatMoney($s->gross) }}</td>
                                <td class="text-end">{{ formatMoney($s->withholding) }}</td>
                                <td class="text-end fw-bold">{{ formatMoney($s->net) }}</td>
                                <td>@include('doctor_fees.status', ['status' => $s->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('messages.doctor_fees.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $statements->links() }}
            </div>
        </div>
    </div>
@endsection
