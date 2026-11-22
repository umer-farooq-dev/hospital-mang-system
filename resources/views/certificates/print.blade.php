<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $certificate->title }} {{ $certificate->number }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 13px; color: #111; line-height: 1.7; }
        .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 30px; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 18px; margin: 10px 0 4px; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
        .number { text-align: center; color: #555; margin-bottom: 30px; }
        .muted { color: #555; }
        .body { min-height: 300px; }
        .signature { margin-top: 50px; text-align: right; }
        .signature .line { display: inline-block; min-width: 240px; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.ot.print') }}</button></div>
<div class="header">
    <h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
    <div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
</div>
<h2>{{ $certificate->title }}</h2>
<div class="number">N° {{ $certificate->number }}</div>
<div class="body">{!! nl2br(e($certificate->body)) !!}</div>
<div class="signature">
    <div class="line">
        {{ __('messages.certificate.done_on', ['date' => $certificate->issued_at->format('d/m/Y')]) }}<br><br><br>
        {{ $certificate->doctor?->doctorUser?->full_name }}<br><span class="muted">{{ __('messages.certificate.signature') }}</span>
    </div>
</div>
</body>
</html>
