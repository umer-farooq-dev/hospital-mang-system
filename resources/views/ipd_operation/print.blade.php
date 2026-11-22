<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.ot.sheet') }} {{ $operation->ref_no }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 14px; margin: 14px 0 6px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .num { text-align: right; white-space: nowrap; }
        .signatures td { border: 0; width: 33%; padding-top: 40px; }
        .signatures .line { border-top: 1px solid #111; padding-top: 4px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
@php
    $ipd = $operation->ipd_patient_department;
    $done = $operation->preop_checks ?? [];
    $name = fn ($doctor) => $doctor?->doctorUser?->full_name;
    $label = fn ($type, $code) => \App\Models\MasterListItem::label($type, $code, '');
@endphp
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.ot.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<h2>{{ __('messages.ot.sheet') }} {{ $operation->ref_no }}</h2>
<table>
    <tr><th>{{ __('messages.ot.patient') }}</th><td>{{ $ipd?->patient?->patientUser?->full_name }} · {{ $ipd?->ipd_number }}</td>
        <th>{{ __('messages.ot.operation') }}</th><td>{{ $operation->operations->name ?? '' }}</td></tr>
    <tr><th>{{ __('messages.operation.operation_date') }}</th><td>{{ \Carbon\Carbon::parse($operation->operation_date)->format('d/m/Y') }}</td>
        <th>{{ __('messages.ot.room') }}</th><td>{{ $label('ot_room', $operation->ot_room) }}</td></tr>
    <tr><th>{{ __('messages.ot.schedule') }}</th><td>{{ $operation->scheduled_start ? $operation->scheduled_start->format('d/m/Y H:i') . ' – ' . $operation->scheduled_end->format('H:i') : '' }}</td>
        <th>{{ __('messages.ot.status') }}</th><td>{{ $label('operation_status', $operation->status) }}</td></tr>
    <tr><th>{{ __('messages.ot.intervention_type') }}</th><td>{{ $label('intervention_type', $operation->intervention_type) }}</td>
        <th>{{ __('messages.ot.anaesthesia_type') }}</th><td>{{ \App\Models\MasterListItem::label('anaesthesia_type', $operation->anesthesia_type, $operation->anesthesia_type) }}</td></tr>
</table>
<h2>{{ __('messages.ot.team') }}</h2>
<table>
    <tr><th>{{ __('messages.ot.surgeon') }}</th><td>{{ $name($operation->surgeon) }}</td><th>{{ __('messages.ot.assistant_doctor') }}</th><td>{{ $name($operation->assistantDoctor) }}</td></tr>
    <tr><th>{{ __('messages.ot.anaesthetist_doctor') }}</th><td>{{ $name($operation->anaesthetistDoctor) }}</td>
        <th>{{ __('messages.ot.other_team') }}</th><td>{{ collect([$operation->assistant_consultant_1, $operation->assistant_consultant_2, $operation->anesthetist, $operation->ot_technician, $operation->ot_assistant])->filter()->implode(', ') }}</td></tr>
</table>
<h2>{{ __('messages.ot.preop_checklist') }}</h2>
<table>
    @foreach ($checklist as $code => $text)
        @continue(! in_array($code, $done, true) && ! array_key_exists($code, $activeChecks))
        <tr><td>{{ $text }}</td><td class="num">{{ in_array($code, $done, true) ? __('messages.ot.checked') : __('messages.ot.not_checked') }}</td></tr>
    @endforeach
</table>
<h2>{{ __('messages.common.result') }}</h2>
<p>{!! nl2br(e($operation->result)) !!}</p>
@if ($operation->followups->isNotEmpty())
    <h2>{{ __('messages.ot.followups') }}</h2>
    <table>
        @foreach ($operation->followups as $followup)
            <tr><td style="width:120px">{{ $followup->noted_at->format('d/m/Y H:i') }}</td><td>{!! nl2br(e($followup->note)) !!}</td></tr>
        @endforeach
    </table>
@endif
<table class="signatures">
    <tr>
        <td><div class="line">{{ __('messages.ot.surgeon') }}</div></td>
        <td><div class="line">{{ __('messages.ot.anaesthetist_doctor') }}</div></td>
        <td><div class="line">{{ __('messages.ot.nurse_signature') }}</div></td>
    </tr>
</table>
</body>
</html>
