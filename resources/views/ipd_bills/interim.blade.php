<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.ipd_bill_extra.interim_bill') }} · {{ $ipd->ipd_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0; } h2 { font-size: 14px; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #eee; text-align: left; } .num { text-align: right; white-space: nowrap; } .muted { color: #555; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="no-print" style="text-align:right"><button onclick="window.print()">{{ __('messages.cashier.print') }}</button></div>
<h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
<div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
<h2>{{ __('messages.ipd_bill_extra.interim_bill') }} · {{ $ipd->ipd_number }} · {{ __('messages.cashier.printed_on', ['date' => now()->format('d/m/Y'), 'time' => now()->format('H:i')]) }}</h2>
<div>{{ __('messages.cashier.patient') }}: <b>{{ $ipd->patient->patientUser->full_name ?? '' }}</b> · {{ __('messages.cashier.doctor') }}: {{ $ipd->doctor->doctorUser->full_name ?? '' }}
    · {{ __('messages.split.payer') }}: {{ $bill['policy']?->option_label ?? __('messages.patient_insurance.self_pay') }} · {{ __('messages.split.price_list') }}: {{ $bill['price_list']?->label }}</div>
<h2>{{ __('messages.bed.bed_charge') }}</h2>
<table>
    <tr><th>{{ __('messages.bed.bed') }}</th><th>{{ __('messages.reception_report.from') }}</th><th>{{ __('messages.reception_report.to') }}</th><th class="num">{{ __('messages.cashier.qty') }}</th><th class="num">{{ __('messages.cashier.unit_price') }}</th><th class="num">{{ __('messages.cashier.amount') }}</th></tr>
    @foreach ($bill['bed_lines'] as $l)
        <tr><td>{{ $l['bed']->name }}</td><td>{{ $l['from']->format('d/m/Y H:i') }}</td><td>{{ $l['to']->format('d/m/Y H:i') }}</td>
            <td class="num">{{ $l['units'] }} {{ __('messages.ipd_bill_extra.bases.' . $l['basis']) }}</td><td class="num">{{ formatAmount($l['unit_price']) }}</td><td class="num">{{ formatAmount($l['total']) }}</td></tr>
    @endforeach
</table>
<h2>{{ __('messages.ipd_charges') }}</h2>
<table>
    <tr><th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.cashier.nature') }}</th><th class="num">{{ __('messages.cashier.amount') }}</th></tr>
    @foreach ($bill['charges'] as $c)
        <tr><td>{{ $c->date->format('d/m/Y') }}</td><td>{{ $c->chargecategory->name ?? '' }}</td><td class="num">{{ formatAmount($c->applied_charge) }}</td></tr>
    @endforeach
    @foreach ($bill['exam_items'] as $e)
        <tr><td>{{ $e->order->ordered_at->format('d/m/Y') }}</td><td>{{ __('messages.exam_orders.exam') }} · {{ $e->description }}</td><td class="num">{{ formatAmount($e->total) }}</td></tr>
    @endforeach
    @foreach ($bill['extra_lines'] as $line)
        <tr><td>{{ $line['date']->format('d/m/Y') }}</td><td>{{ $line['type'] }} · {{ $line['description'] }}</td><td class="num">{{ formatAmount($line['amount']) }}</td></tr>
    @endforeach
</table>
<h2>{{ __('messages.bill.bill_summary') }}</h2>
<table>
    <tr><td>{{ __('messages.bed.bed_charge') }}</td><td class="num">{{ formatMoney($bill['bed_charges']) }}</td></tr>
    <tr><td>{{ __('messages.ipd_bill.total_charges') }}</td><td class="num">{{ formatMoney($bill['total_charges']) }}</td></tr>
    <tr><td>{{ __('messages.ipd_bill_extra.cover_amount') }}</td><td class="num">-{{ formatMoney($bill['cover_amount']) }}</td></tr>
    <tr><td>{{ __('messages.ipd_bill.paid_amount') }}</td><td class="num">-{{ formatMoney($bill['total_payment']) }}</td></tr>
    <tr><td>{{ __('messages.ipd_bill_extra.advances') }}</td><td class="num">-{{ formatMoney($bill['advances_total']) }}</td></tr>
    <tr><th>{{ __('messages.ipd_bill.net_payable_amount') }}</th><th class="num">{{ formatMoney(max(0, $bill['net_payable_amount'])) }}</th></tr>
</table>
</body>
</html>
