<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.cashier.close_report') }} {{ $session->closing_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 15px; margin: 14px 0 6px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #eee; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .void td { color: #b91c1c; text-decoration: line-through; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
@php
    $valid = $session->receipts->whereNull('voided_at');
    $cashTotal = $totals[$cashMethod] ?? 0;
    $expected = $session->expected_cash ?? ($session->opening_float + $cashTotal);
@endphp
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.cashier.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<h2>{{ __('messages.cashier.close_report') }} {{ $session->closing_number ? 'N° ' . $session->closing_number : '(' . __('messages.cashier.session_open') . ')' }}</h2>
<table>
    <tr><td>{{ __('messages.cashier.counter') }}</td><td>{{ $session->counter->code }} · {{ $session->counter->name }}</td>
        <td>{{ __('messages.cashier.operator') }}</td><td>{{ $session->user->full_name }}</td></tr>
    <tr><td>{{ __('messages.cashier.opened_at') }}</td><td>{{ $session->opened_at->format('d/m/Y H:i') }}</td>
        <td>{{ __('messages.cashier.closed_at') }}</td><td>{{ $session->closed_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
</table>

<h2>{{ __('messages.cashier.summary') }}</h2>
<table>
    <tr><td>{{ __('messages.cashier.opening_float') }}</td><td class="num">{{ formatMoney($session->opening_float) }}</td></tr>
    @foreach ($totals as $method => $total)
        <tr><td>{{ \App\Models\PaymentMethod::label($method) }}</td><td class="num">{{ formatMoney($total) }}</td></tr>
    @endforeach
    <tr><th>{{ __('messages.cashier.total_collected') }}</th><th class="num">{{ formatMoney(array_sum($totals)) }}</th></tr>
    <tr><td>{{ __('messages.cashier.expected_cash') }}</td><td class="num">{{ formatMoney($expected) }}</td></tr>
    @if ($session->counted_cash !== null)
        <tr><td>{{ __('messages.cashier.counted_cash') }}</td><td class="num">{{ formatMoney($session->counted_cash) }}</td></tr>
        <tr><th>{{ __('messages.cashier.difference') }}</th><th class="num">{{ formatMoney($session->counted_cash - $expected) }}</th></tr>
    @endif
</table>

<h2>{{ __('messages.cashier.receipts') }} ({{ $valid->count() }})</h2>
<table>
    <thead><tr><th>{{ __('messages.cashier.receipt_no') }}</th><th>{{ __('messages.cashier.time') }}</th><th>{{ __('messages.cashier.patient') }}</th><th>{{ __('messages.cashier.payment_method') }}</th><th class="num">{{ __('messages.cashier.amount') }}</th></tr></thead>
    <tbody>
        @foreach ($session->receipts->sortBy('receipt_number') as $receipt)
            <tr class="{{ $receipt->voided_at ? 'void' : '' }}">
                <td>{{ $receipt->receipt_number }}</td>
                <td>{{ $receipt->created_at->format('H:i') }}</td>
                <td>{{ $receipt->patient->patientUser->full_name ?? '' }}</td>
                <td>{{ $receipt->payment_method_name }}</td>
                <td class="num">{{ formatMoney($receipt->amount) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if ($session->notes)
    <p class="muted">{{ $session->notes }}</p>
@endif
</body>
</html>
