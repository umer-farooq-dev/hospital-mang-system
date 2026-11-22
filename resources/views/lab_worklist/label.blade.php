<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $item->sample_number }}</title>
    <style>
        @page { size: 60mm 30mm; margin: 2mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 0; }
        .label { display: flex; gap: 4px; align-items: center; width: 56mm; height: 26mm; }
        .text b { font-size: 12px; display: block; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.ot.print') }}</button></div>
<div class="label">
    <div>{!! QrCode::size(80)->margin(0)->generate($item->sample_number) !!}</div>
    <div class="text">
        <b>{{ $item->sample_number }}</b>
        {{ $item->order?->patient?->patientUser?->full_name }}<br>
        {{ \Illuminate\Support\Str::limit($item->description, 40) }}<br>
        {{ $item->collected_at?->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
