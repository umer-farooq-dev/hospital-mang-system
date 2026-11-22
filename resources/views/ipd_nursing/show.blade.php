@extends('layouts.app')
@section('title')
    {{ __('messages.nursing.chart') }} · {{ $ipd->ipd_number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <div>
                <h1 class="mb-1">@yield('title')</h1>
                <div class="text-muted">{{ $ipd->patient?->patientUser?->full_name }} · {{ $ipd->bed?->name }}</div>
            </div>
            <a href="{{ route('ipd.patient.show', $ipd->id) }}" class="btn btn-outline-primary">{{ __('messages.ot.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $now = now()->format('Y-m-d\TH:i');
        $canWrite = canAccessRoute('ipd.nursing.vitals');
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" id="nursingTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#vitals">{{ __('messages.nursing.vital_signs') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#notes">{{ __('messages.nursing.notes') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#flows">{{ __('messages.nursing.intake_output') }}</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#mar">{{ __('messages.nursing.mar') }}</a></li>
        </ul>
        <div class="tab-content">
            {{-- Vital signs --}}
            <div class="tab-pane fade show active" id="vitals" role="tabpanel">
                @if ($canWrite)
                    <div class="card mb-7">
                        <div class="card-body">
                            {{ Form::open(['route' => ['ipd.nursing.vitals', $ipd->id], 'method' => 'post']) }}
                            <div class="row g-4 align-items-end">
                                <div class="col-md-3">
                                    {{ Form::label('measured_at', __('messages.ot.noted_at') . ':', ['class' => 'form-label']) }}
                                    <input type="datetime-local" name="measured_at" value="{{ $now }}" class="form-control" required>
                                </div>
                                @foreach ($types->where('is_active', true) as $type)
                                    <div class="col-md-2">
                                        <label class="form-label" title="{{ $type->range }}">{{ $type->label }}</label>
                                        <input type="number" step="any" name="values[{{ $type->id }}]" class="form-control" placeholder="{{ $type->unit }}">
                                        @if ($type->range)<div class="form-text">{{ $type->range }}</div>@endif
                                    </div>
                                @endforeach
                                <div class="col-md-6">
                                    {{ Form::label('note', __('messages.ot.note') . ':', ['class' => 'form-label']) }}
                                    <input type="text" name="note" class="form-control" maxlength="500">
                                </div>
                                <div class="col-md-2">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary w-100']) }}</div>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                @endif
                @if ($chart->isNotEmpty())
                    <div class="card mb-7">
                        <div class="card-body">
                            <canvas id="vitalsChart" height="110" data-series='@json($chart)'></canvas>
                        </div>
                    </div>
                @endif
                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th>{{ __('messages.ot.noted_at') }}</th>
                                    @foreach ($types as $type)<th class="text-end">{{ $type->label }}<div class="fw-normal text-lowercase">{{ $type->unit }}</div></th>@endforeach
                                    <th>{{ __('messages.ot.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rounds as $moment => $round)
                                    <tr>
                                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($moment)->format('d/m/Y H:i') }}<div class="text-muted fs-7">{{ $round->first()->author?->full_name }}</div></td>
                                        @foreach ($types as $type)
                                            @php($reading = $round->firstWhere('vital_type_id', $type->id))
                                            <td class="text-end {{ $reading && $type->isAbnormal($reading->value) ? 'text-danger fw-bold' : '' }}">{{ $reading ? $reading->value + 0 : '' }}</td>
                                        @endforeach
                                        <td class="fs-7">{{ $round->pluck('note')->filter()->unique()->implode(' · ') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $types->count() + 2 }}" class="text-center text-muted">{{ __('messages.nursing.nothing_yet') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Nurse notes per shift --}}
            <div class="tab-pane fade" id="notes" role="tabpanel">
                @if ($canWrite)
                    <div class="card mb-7">
                        <div class="card-body">
                            {{ Form::open(['route' => ['ipd.nursing.notes', $ipd->id], 'method' => 'post', 'class' => 'row g-4 align-items-end']) }}
                            <div class="col-md-3">
                                {{ Form::label('shift', __('messages.nursing.shift') . ':', ['class' => 'form-label']) }}
                                {{ Form::select('shift', $shifts, null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose') . ' ' . __('messages.nursing.shift')]) }}
                            </div>
                            <div class="col-md-3">
                                {{ Form::label('noted_at', __('messages.ot.noted_at') . ':', ['class' => 'form-label']) }}
                                <input type="datetime-local" name="noted_at" value="{{ $now }}" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                {{ Form::label('note', __('messages.ot.note') . ':', ['class' => 'form-label']) }}
                                {{ Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3, 'maxlength' => 5000, 'required']) }}
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                            {{ Form::close() }}
                        </div>
                    </div>
                @endif
                <div class="card">
                    <div class="card-body">
                        @forelse ($notes as $note)
                            <div class="border-start border-3 border-info ps-4 mb-5">
                                <div class="text-muted fs-7">{{ $note->noted_at->format('d/m/Y H:i') }} · {{ \App\Models\MasterListItem::label('nursing_shift', $note->shift, '') }} · {{ $note->author?->full_name }}</div>
                                <div>{!! nl2br(e($note->note)) !!}</div>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('messages.nursing.nothing_yet') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Intake and output --}}
            <div class="tab-pane fade" id="flows" role="tabpanel">
                @if ($canWrite)
                    <div class="card mb-7">
                        <div class="card-body">
                            {{ Form::open(['route' => ['ipd.nursing.flows', $ipd->id], 'method' => 'post', 'class' => 'row g-4 align-items-end', 'id' => 'flowForm']) }}
                            <div class="col-md-3">
                                {{ Form::label('recorded_at', __('messages.ot.noted_at') . ':', ['class' => 'form-label']) }}
                                <input type="datetime-local" name="recorded_at" value="{{ $now }}" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                {{ Form::label('kind', __('messages.nursing.direction') . ':', ['class' => 'form-label']) }}
                                {{ Form::select('kind', ['intake' => __('messages.nursing.intake'), 'output' => __('messages.nursing.output')], 'intake', ['class' => 'form-select', 'id' => 'flowKind']) }}
                            </div>
                            <div class="col-md-3">
                                {{ Form::label('category', __('messages.nursing.type') . ':', ['class' => 'form-label']) }}
                                <select name="category" class="form-select" id="flowCategory" required>
                                    @foreach ($intakeTypes as $code => $label)<option value="{{ $code }}" data-kind="intake">{{ $label }}</option>@endforeach
                                    @foreach ($outputTypes as $code => $label)<option value="{{ $code }}" data-kind="output" hidden disabled>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                {{ Form::label('volume_ml', __('messages.nursing.volume') . ' (ml):', ['class' => 'form-label']) }}
                                <input type="number" name="volume_ml" class="form-control" min="0" max="20000" step="any" required>
                            </div>
                            <div class="col-md-2">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary w-100']) }}</div>
                            <div class="col-md-12">
                                <input type="text" name="note" class="form-control" maxlength="500" placeholder="{{ __('messages.ot.note') }}">
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                @endif
                <div class="row">
                    <div class="col-xl-5 mb-7">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="mb-4">{{ __('messages.nursing.daily_balance') }}</h3>
                                <table class="table table-row-dashed align-middle">
                                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.cashier.date') }}</th><th class="text-end">{{ __('messages.nursing.intake') }}</th><th class="text-end">{{ __('messages.nursing.output') }}</th><th class="text-end">{{ __('messages.nursing.balance') }}</th></tr></thead>
                                    @forelse ($balance as $day => $totals)
                                        <tr><td>{{ \Carbon\Carbon::parse($day)->format('d/m/Y') }}</td><td class="text-end">{{ $totals['intake'] + 0 }} ml</td><td class="text-end">{{ $totals['output'] + 0 }} ml</td>
                                            <td class="text-end fw-bold {{ $totals['balance'] < 0 ? 'text-danger' : 'text-success' }}">{{ $totals['balance'] > 0 ? '+' : '' }}{{ $totals['balance'] + 0 }} ml</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">{{ __('messages.nursing.nothing_yet') }}</td></tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-7 mb-7">
                        <div class="card h-100">
                            <div class="card-body table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    @foreach ($flows as $flow)
                                        <tr>
                                            <td class="text-nowrap">{{ $flow->recorded_at->format('d/m/Y H:i') }}</td>
                                            <td><span class="badge {{ $flow->is_intake ? 'bg-light-primary' : 'bg-light-warning' }}">{{ $flow->is_intake ? __('messages.nursing.intake') : __('messages.nursing.output') }}</span>
                                                {{ $flow->is_intake ? \App\Models\MasterListItem::label('intake_type', $flow->intake_type, '') : \App\Models\MasterListItem::label('output_type', $flow->output_type, '') }}</td>
                                            <td class="text-end">{{ $flow->volume_ml + 0 }} ml</td>
                                            <td class="fs-7 text-muted">{{ $flow->note }} {{ $flow->author?->full_name }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Medication administration record --}}
            <div class="tab-pane fade" id="mar" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="form-text mb-5">{{ __('messages.nursing.mar_hint') }}</div>
                        @forelse ($doses as $day => $dayDoses)
                            <h4 class="mt-5 mb-3">{{ \Carbon\Carbon::parse($day)->translatedFormat('l d/m/Y') }}</h4>
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    @foreach ($dayDoses as $dose)
                                        <tr class="{{ ! $dose->status && $dose->scheduled_at->isPast() ? 'bg-light-danger' : '' }}">
                                            <td style="width:70px" class="fw-bold">{{ $dose->scheduled_at->format('H:i') }}</td>
                                            <td>{{ $dose->item?->medicine?->name }} <span class="text-muted">{{ $dose->item?->dosage }}</span>
                                                @if ($dose->item?->instruction)<div class="text-muted fs-7">{{ $dose->item->instruction }}</div>@endif</td>
                                            <td style="width:45%">
                                                @if ($dose->status)
                                                    <span class="badge bg-light-success">{{ \App\Models\MasterListItem::label('mar_status', $dose->status, '') }}</span>
                                                    <span class="text-muted fs-7">{{ $dose->administered_at?->format('d/m H:i') }} · {{ $dose->author?->full_name }} {{ $dose->note ? '· ' . $dose->note : '' }}</span>
                                                @elseif (canAccessRoute('ipd.nursing.dose'))
                                                    {{ Form::open(['route' => ['ipd.nursing.dose', $dose->id], 'method' => 'post', 'class' => 'd-flex gap-2']) }}
                                                    {{ Form::select('status', $marStatuses, null, ['class' => 'form-select form-select-sm', 'required', 'placeholder' => __('messages.ot.status')]) }}
                                                    <input type="text" name="note" class="form-control form-control-sm" maxlength="500" placeholder="{{ __('messages.ot.note') }}">
                                                    {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-sm btn-primary']) }}
                                                    {{ Form::close() }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('messages.nursing.no_doses') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
