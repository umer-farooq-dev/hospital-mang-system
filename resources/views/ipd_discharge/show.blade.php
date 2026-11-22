@extends('layouts.app')
@section('title')
    {{ __('messages.discharge.sheet') }} · {{ $ipd->ipd_number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('ipd.discharge.print', $ipd->id) }}" target="_blank" class="btn btn-primary"><i class="fas fa-print me-1"></i>{{ __('messages.discharge.print') }}</a>
                <a href="{{ route('ipd.patient.show', $ipd->id) }}" class="btn btn-outline-primary">{{ __('messages.ot.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php($canEdit = canAccessRoute('ipd.discharge.update'))
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="row">
            <div class="col-xl-4 mb-7">
                <div class="card mb-7">
                    <div class="card-body">
                        <h3 class="mb-4">{{ $ipd->patient?->patientUser?->full_name }}</h3>
                        <div class="text-muted mb-1">{{ __('messages.ipd_patient.admission_date') }}: {{ \Carbon\Carbon::parse($ipd->admission_date)->format('d/m/Y H:i') }}</div>
                        <div class="text-muted mb-1">{{ __('messages.ipd_consultant_doctor') }}: {{ $ipd->doctor?->doctorUser?->full_name }}</div>
                        <div class="mt-4">
                            @if ($ipd->discharge)
                                <span class="badge bg-light-success fs-6">{{ __('messages.discharge.discharged_on', ['date' => $ipd->discharge_date ? \Carbon\Carbon::parse($ipd->discharge_date)->format('d/m/Y H:i') : '']) }}</span>
                            @else
                                <span class="badge bg-light-warning fs-6">{{ __('messages.discharge.in_hospital') }}</span>
                            @endif
                        </div>
                        <div class="mt-4 fs-5">{{ __('messages.discharge.balance') }}: <strong class="{{ $balance > 0.009 ? 'text-danger' : 'text-success' }}">{{ formatMoney(max(0, $balance)) }}</strong></div>
                        @if (! $ipd->discharge)
                            <div class="form-text mt-3">{{ __('messages.discharge.how_to') }}</div>
                            @if (($settings['discharge_block_unpaid'] ?? '0') === '1')
                                <div class="alert alert-info alert-important py-2 fs-7 mt-3 mb-0">{{ __('messages.discharge.block_unpaid_hint') }}</div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-8 mb-7">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['ipd.discharge.update', $ipd->id], 'method' => 'post']) }}
                        <div class="row">
                            <div class="col-md-6 mb-5">
                                {{ Form::label('discharge_date', __('messages.discharge.date') . ':', ['class' => 'form-label']) }}
                                <input type="datetime-local" name="discharge_date" class="form-control" value="{{ old('discharge_date', $ipd->discharge_date ? \Carbon\Carbon::parse($ipd->discharge_date)->format('Y-m-d\TH:i') : '') }}" {{ $canEdit ? '' : 'disabled' }}>
                            </div>
                            <div class="col-md-6 mb-5">
                                {{ Form::label('discharge_outcome', __('messages.discharge.outcome') . ':', ['class' => 'form-label']) }}
                                {{ Form::select('discharge_outcome', $outcomes, old('discharge_outcome', $ipd->discharge_outcome), ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.discharge.outcome')] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                            </div>
                            <div class="col-12 mb-5">
                                <label class="form-label">{{ __('messages.discharge.checklist') }}{{ ($settings['discharge_require_checklist'] ?? '0') === '1' ? ' *' : '' }}:</label>
                                <div class="d-flex flex-wrap gap-5">
                                    @forelse ($checks as $code => $label)
                                        <label class="form-check form-check-sm form-check-custom">
                                            <input class="form-check-input" type="checkbox" name="discharge_checks[]" value="{{ $code }}" {{ in_array($code, $ipd->discharge_checks, true) ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @empty
                                        <span class="text-muted fs-7">{{ __('messages.discharge.no_checks') }}</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-12 mb-5">
                                {{ Form::label('discharge_summary', __('messages.discharge.summary') . ':', ['class' => 'form-label']) }}
                                {{ Form::textarea('discharge_summary', $summary, ['class' => 'form-control', 'rows' => 18, 'maxlength' => 20000] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                <div class="form-text">{{ __('messages.discharge.summary_hint') }}</div>
                            </div>
                        </div>
                        @if ($canEdit)
                            <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                        @endif
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
