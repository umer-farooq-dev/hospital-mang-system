<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.cash_journal.title') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 17px; margin: 0; }
        h2 { font-size: 14px; margin: 10px 0 6px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; text-align: left; }
        .text-end { text-align: right; } .text-danger { color: #b91c1c; } .fw-bold { font-weight: bold; } .text-nowrap { white-space: nowrap; }
        .d-flex { display: flex; gap: 24px; justify-content: flex-end; margin-top: 8px; font-size: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.cashier.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<h2>{{ __('messages.cash_journal.title') }} · {{ $counters[$filters['counter']] ?? '' }}
    · {{ $filters['session'] ? __('messages.cash_journal.closing_number') . ' ' . $filters['session']->closing_number : $filters['from']->format('d/m/Y') . ' – ' . $filters['to']->format('d/m/Y') }}</h2>
<div>{{ __('messages.cash_journal.opening_balance') }}: <b>{{ formatMoney($journal['opening']) }}</b> · <span class="muted">{{ __('messages.cashier.printed_on', ['date' => now()->format('d/m/Y'), 'time' => now()->format('H:i')]) }}</span></div>
@include('cash_journal.table', ['print' => true])
</body>
</html>
