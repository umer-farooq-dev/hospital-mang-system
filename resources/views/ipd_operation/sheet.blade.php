@extends('layouts.app')
@section('title')
    {{ __('messages.ot.sheet') }} {{ $operation->ref_no }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('operation.print', $operation->id) }}" target="_blank" class="btn btn-primary"><i class="fas fa-print me-1"></i>{{ __('messages.ot.print') }}</a>
                <a href="{{ route('ipd.patient.show', $operation->ipd_patient_department_id) }}" class="btn btn-outline-primary">{{ __('messages.ot.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php
        $ipd = $operation->ipd_patient_department;
        $done = $operation->preop_checks ?? [];
        $name = fn ($doctor) => $doctor?->doctorUser?->full_name;
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="row">
            <div class="col-xl-6 mb-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="mb-5">{{ $operation->operations->name ?? '' }}</h3>
                        <table class="table table-row-dashed align-middle mb-0">
                            <tr><td class="text-muted w-40">{{ __('messages.ot.patient') }}</td><td>{{ $ipd?->patient?->patientUser?->full_name }} · {{ $ipd?->ipd_number }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.operation_category.operation_category') }}</td><td>{{ $operation->operations->operation_category->name ?? '' }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.operation.operation_date') }}</td><td>{{ \Carbon\Carbon::parse($operation->operation_date)->format('d/m/Y') }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.room') }}</td><td>{{ \App\Models\MasterListItem::label('ot_room', $operation->ot_room, __('messages.common.n/a')) }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.schedule') }}</td><td>{{ $operation->scheduled_start ? $operation->scheduled_start->format('d/m/Y H:i') . ' – ' . $operation->scheduled_end->format('H:i') : __('messages.ot.not_scheduled') }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.status') }}</td><td>{{ \App\Models\MasterListItem::label('operation_status', $operation->status, __('messages.common.n/a')) }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.intervention_type') }}</td><td>{{ \App\Models\MasterListItem::label('intervention_type', $operation->intervention_type, __('messages.common.n/a')) }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.anaesthesia_type') }}</td><td>{{ \App\Models\MasterListItem::label('anaesthesia_type', $operation->anesthesia_type, $operation->anesthesia_type ?: __('messages.common.n/a')) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="mb-5">{{ __('messages.ot.team') }}</h3>
                        <table class="table table-row-dashed align-middle mb-5">
                            <tr><td class="text-muted w-40">{{ __('messages.ot.surgeon') }}</td><td>{{ $name($operation->surgeon) }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.assistant_doctor') }}</td><td>{{ $name($operation->assistantDoctor) ?? __('messages.common.n/a') }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.anaesthetist_doctor') }}</td><td>{{ $name($operation->anaesthetistDoctor) ?? __('messages.common.n/a') }}</td></tr>
                            <tr><td class="text-muted">{{ __('messages.ot.other_team') }}</td><td>{{ collect([$operation->assistant_consultant_1, $operation->assistant_consultant_2, $operation->anesthetist, $operation->ot_technician, $operation->ot_assistant])->filter()->implode(', ') ?: __('messages.common.n/a') }}</td></tr>
                        </table>
                        <h3 class="mb-4">{{ __('messages.ot.preop_checklist') }}</h3>
                        @forelse ($checklist as $code => $label)
                            @continue(! in_array($code, $done, true) && ! array_key_exists($code, $activeChecks))
                            <div class="mb-2">
                                @if (in_array($code, $done, true))
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                @else
                                    <i class="far fa-circle text-danger me-2"></i>
                                @endif
                                {{ $label }}
                            </div>
                        @empty
                            <div class="text-muted fs-7">{{ __('messages.ot.no_preop_items') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="mb-5">{{ __('messages.ot.fees') }}</h3>
                        @if (! $operation->k_value)
                            <div class="alert alert-warning alert-important py-2 fs-7">{{ __('messages.ot.k_not_set') }}</div>
                        @endif
                        <table class="table table-row-dashed align-middle mb-0">
                            <thead><tr class="text-muted fs-7 text-uppercase"><th></th><th class="text-end">{{ __('messages.ot.coefficient') }}</th><th class="text-end">{{ __('messages.ot.amount') }}</th></tr></thead>
                            @foreach (['surgeon' => 'k_surgeon', 'assistant' => 'k_assistant', 'anaesthetist' => 'k_anaesthetist'] as $role => $column)
                                <tr><td>{{ __('messages.ot.' . $column) }}</td><td class="text-end">{{ ($operation->operations->$column ?? 0) + 0 }}</td><td class="text-end">{{ formatMoney($operation->{$role . '_fee'}) }}</td></tr>
                            @endforeach
                            <tr class="fw-bold"><td>{{ __('messages.ot.total') }} (K = {{ formatAmount($operation->k_value ?? 0) }})</td><td></td><td class="text-end">{{ formatMoney($operation->total_fee) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-7">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="mb-5">{{ __('messages.common.result') }}</h3>
                        <p class="mb-3">{!! nl2br(e($operation->result ?: __('messages.common.n/a'))) !!}</p>
                        <div class="text-muted fs-7">{{ __('messages.blood_issue.remarks') }}</div>
                        <p class="mb-0">{!! nl2br(e($operation->remark ?: __('messages.common.n/a'))) !!}</p>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-7">
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5">{{ __('messages.ot.followups') }}</h3>
                        @forelse ($operation->followups as $followup)
                            <div class="border-start border-3 border-primary ps-4 mb-5">
                                <div class="text-muted fs-7">{{ $followup->noted_at->format('d/m/Y H:i') }} · {{ $followup->author?->full_name }}</div>
                                <div>{!! nl2br(e($followup->note)) !!}</div>
                            </div>
                        @empty
                            <div class="text-muted mb-5">{{ __('messages.ot.no_followups') }}</div>
                        @endforelse
                        @if (canAccessRoute('operation.followups.store'))
                            {{ Form::open(['route' => ['operation.followups.store', $operation->id], 'method' => 'post', 'class' => 'row g-4 align-items-end']) }}
                            <div class="col-md-3">
                                {{ Form::label('noted_at', __('messages.ot.noted_at') . ':', ['class' => 'form-label']) }}
                                <input type="datetime-local" name="noted_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                            </div>
                            <div class="col-md-7">
                                {{ Form::label('note', __('messages.ot.note') . ':', ['class' => 'form-label']) }}
                                {{ Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 5000, 'required']) }}
                            </div>
                            <div class="col-md-2">{{ Form::submit(__('messages.ot.add_followup'), ['class' => 'btn btn-primary w-100']) }}</div>
                            {{ Form::close() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
