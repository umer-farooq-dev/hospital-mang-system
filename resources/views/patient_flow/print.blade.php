<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.patient_flow.title') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; text-align: left; }
        .text-end { text-align: right; } .text-nowrap { white-space: nowrap; } .fw-bold { font-weight: bold; }
        .text-danger { color: #b91c1c; } .text-decoration-line-through { text-decoration: line-through; }
        .d-flex { display: flex; gap: 20px; justify-content: flex-end; margin-top: 8px; font-size: 11px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.cashier.print') }}</button></div>
<h2 style="margin:0">{{ $settings['company_name']->value ?? getAppName() }}</h2>
<div>{{ __('messages.patient_flow.title') }} · {{ __('messages.patient_flow.tabs.' . $filters['tab']) }} · {{ $filters['from']->format('d/m/Y') }} – {{ $filters['to']->format('d/m/Y') }}
    @if ($filters['department']) · {{ $departments[$filters['department']] ?? '' }} @endif</div>
@include('patient_flow.table')
</body>
</html>
