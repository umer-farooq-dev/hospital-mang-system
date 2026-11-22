<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.discharge.summary') }} {{ $ipd->ipd_number }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12.5px; color: #111; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 16px; margin: 18px 0 10px; text-align: center; text-transform: uppercase; }
        .muted { color: #555; }
        .box { border: 1px solid #999; padding: 8px 10px; }
        .signature { margin-top: 50px; text-align: right; }
        .signature .line { display: inline-block; border-top: 1px solid #111; padding-top: 4px; min-width: 220px; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.discharge.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<h2>{{ __('messages.discharge.summary') }}</h2>
<div class="box">{!! nl2br(e($summary)) !!}</div>
<div class="signature">
    <div class="line">{{ $ipd->doctor?->doctorUser?->full_name }}<br><span class="muted">{{ __('messages.discharge.doctor_signature') }}</span></div>
</div>
</body>
</html>
