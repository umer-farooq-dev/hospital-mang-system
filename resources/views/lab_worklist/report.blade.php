<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.lab.report') }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 14px; margin: 16px 0 6px; border-bottom: 1px solid #999; padding-bottom: 3px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #eee; }
        .abnormal { font-weight: bold; color: #b91c1c; }
        .sign { margin-top: 6px; text-align: right; font-size: 11px; color: #333; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.ot.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<table style="margin-top:12px">
    <tr><th>{{ __('messages.cashier.patient') }}</th><td>{{ $order->patient?->patientUser?->full_name }} · {{ $order->patient?->patient_unique_id }}</td>
        <th>{{ __('messages.certificate.doctor') }}</th><td>{{ $order->doctor?->doctorUser?->full_name }}</td></tr>
    <tr><th>{{ __('messages.cashier.date') }}</th><td>{{ $order->ordered_at?->format('d/m/Y H:i') }}</td><th>N°</th><td>{{ $order->id }}</td></tr>
</table>
@forelse ($items as $item)
    <h2>{{ $item->description }} <span class="muted" style="font-weight:normal">{{ $item->sample_number }}</span></h2>
    @if ($item->results->isNotEmpty())
        <table>
            <tr><th>{{ __('messages.lab.parameter') }}</th><th>{{ __('messages.lab.value') }}</th><th>{{ __('messages.nursing.unit') }}</th><th>{{ __('messages.lab.range') }}</th></tr>
            @foreach ($item->results as $result)
                <tr class="{{ $result->is_abnormal ? 'abnormal' : '' }}"><td>{{ $result->label }}</td><td>{{ $result->value }}{{ $result->is_abnormal ? ' *' : '' }}</td><td>{{ $result->unit }}</td><td>{{ $result->reference_range }}</td></tr>
            @endforeach
        </table>
    @endif
    @if ($item->report_text)<div style="margin-top:6px">{!! nl2br(e($item->report_text)) !!}</div>@endif
    @if ($item->result_note)<div class="muted" style="margin-top:6px">{!! nl2br(e($item->result_note)) !!}</div>@endif
    @php($attached = $item->getMedia(\App\Models\ExamOrderItem::FILES)->count())
    @if ($attached)<div class="muted" style="margin-top:6px">{{ __('messages.imaging.attached', ['count' => $attached]) }}</div>@endif
    <div class="sign">{{ __($item->is_lab ? 'messages.lab.validated_by' : 'messages.imaging.signed_by', ['name' => $item->stepUser('validated')?->full_name, 'date' => $item->validated_at?->format('d/m/Y H:i')]) }}</div>
@empty
    <p class="muted">{{ __('messages.lab.nothing_validated') }}</p>
@endforelse
<p class="muted" style="margin-top:20px">* {{ __('messages.lab.abnormal_hint') }}</p>
</body>
</html>
