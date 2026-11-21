<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.cashier.receipt') }} {{ $receipt->receipt_number }}</title>
    @php
        $user = $receipt->patient->patientUser;
        $lineTotal = $receipt->lines->sum('total');
        $discount = $receipt->lines->sum('discount_amount');
        $payer = $receipt->lines->sum('payer_amount');
        $patientShare = $lineTotal - $discount - $payer;
        $discountPercent = $lineTotal > 0 ? round($discount / $lineTotal * 100, 2) : 0;
        $isConsultation = $receipt->lines->contains('payable_type', 'opd_visit');
        $width = ['a5' => '148mm', 'a4' => '210mm', 'thermal' => '80mm'][$paper] ?? '148mm';
    @endphp
    <style>
        @page { size: {{ $paper === 'thermal' ? '80mm auto' : strtoupper($paper) }}; margin: 8mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: {{ $paper === 'thermal' ? '10px' : '12px' }}; color: #111; margin: 0; }
        .receipt { width: {{ $width }}; max-width: 100%; margin: 0 auto; padding: 4mm; box-sizing: border-box; }
        .head { display: flex; gap: 10px; align-items: center; border-bottom: 2px solid #111; padding-bottom: 6px; }
        .head img { max-height: 55px; max-width: 70px; }
        .head .name { font-size: 1.35em; font-weight: bold; }
        .muted { color: #555; }
        .title { text-align: center; font-size: 1.3em; font-weight: bold; margin: 10px 0 6px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2px 0; vertical-align: top; }
        .lines th, .lines td { border: 1px solid #333; padding: 4px; }
        .lines th { background: #eee; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .totals td { padding: 3px 4px; }
        .totals tr.strong td { font-weight: bold; font-size: 1.1em; border-top: 1px solid #333; }
        .footer { margin-top: 10px; font-size: .9em; border: 1px dashed #777; padding: 6px; }
        .sign { margin-top: 14px; display: flex; justify-content: space-between; }
        .void { color: #b91c1c; font-weight: bold; text-align: center; border: 2px solid #b91c1c; padding: 4px; margin-top: 6px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="receipt">
    <div class="no-print" style="text-align:right; margin-bottom:6px">
        <button onclick="window.print()">{{ __('messages.cashier.print') }}</button>
    </div>
    <div class="head">
        <img src="{{ getLogoUrl() }}" alt="">
        <div>
            <div class="name">{{ $settings['company_name']->value ?? getAppName() }}</div>
            <div class="muted">{{ $settings['hospital_address']->value ?? '' }}</div>
            <div class="muted">{{ $settings['hospital_phone']->value ?? '' }} @if (! empty($settings['hospital_email']->value)) · {{ $settings['hospital_email']->value }} @endif</div>
        </div>
    </div>
    <div class="muted" style="margin-top:4px">{{ __('messages.cashier.printed_on', ['date' => now()->format('d/m/Y'), 'time' => now()->format('H:i')]) }}</div>

    <div class="title">{{ __('messages.cashier.receipt_no') }} {{ $receipt->receipt_number }}</div>
    @if ($receipt->voided_at)
        <div class="void">{{ __('messages.cashier.voided') }} · {{ $receipt->void_reason_name }}</div>
    @elseif ($receipt->refund)
        <div class="void">{{ __('messages.receipts.status.refunded') }} · {{ $receipt->refund->reason_name }} · {{ $receipt->refund->created_at->format('d/m/Y') }}</div>
    @endif
    @if (request('duplicate'))
        <div class="void" style="color:#111; border-color:#111">{{ __('messages.receipts.duplicate_mark') }}</div>
    @endif

    <table class="info">
        <tr>
            <td><b>{{ __('messages.cashier.patient') }}:</b> {{ $user->full_name }}</td>
            <td class="num"><b>ID:</b> {{ $receipt->patient->patient_unique_id }}</td>
        </tr>
        <tr>
            <td>
                {{ $user->gender == 1 ? __('messages.user.female') : __('messages.user.male') }}
                @if ($user->dob) · {{ __('messages.reception.age_years', ['count' => \Carbon\Carbon::parse($user->dob)->age]) }} @endif
                @if ($user->phone) · {{ $user->phone }} @endif
            </td>
            <td class="num">{{ $receipt->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        @if ($doctor)
            <tr><td colspan="2"><b>{{ __('messages.cashier.doctor') }}:</b> {{ $doctor }}</td></tr>
        @endif
    </table>

    <table class="lines" style="margin-top:8px">
        <thead>
            <tr>
                <th>{{ __('messages.cashier.nature') }}</th>
                <th class="num">{{ __('messages.cashier.unit_price') }}</th>
                <th class="num">{{ __('messages.cashier.qty') }}</th>
                <th class="num">{{ __('messages.cashier.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ formatAmount($line->total) }}</td>
                    <td class="num">1</td>
                    <td class="num">{{ formatAmount($line->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:6px">
        <tr><td>{{ __('messages.cashier.total_amount') }}</td><td class="num">{{ formatMoney($lineTotal) }}</td></tr>
        <tr><td>{{ __('messages.split.discount') }} ({{ formatPercent($discountPercent) }})</td><td class="num">{{ formatMoney($discount) }}</td></tr>
        <tr><td>{{ __('messages.cashier.payer_pays') }}</td><td class="num">{{ formatMoney($payer) }}</td></tr>
        <tr><td>{{ __('messages.cashier.patient_pays') }}</td><td class="num">{{ formatMoney($patientShare) }}</td></tr>
        <tr class="strong"><td>{{ __('messages.cashier.amount_paid') }}</td><td class="num">{{ formatMoney($receipt->amount) }}</td></tr>
        <tr><td>{{ __('messages.cashier.balance_due') }}</td><td class="num">{{ formatMoney(max(0, $patientShare - $receipt->amount)) }}</td></tr>
        <tr><td>{{ __('messages.cashier.payment_method') }}</td><td class="num">{{ mb_strtoupper($receipt->payment_method_name ?? '') }}
            @if ($receipt->cheque_number) · {{ $receipt->cheque_number }} @endif</td></tr>
        @if ($receipt->change_given > 0)
            <tr><td>{{ __('messages.cashier.change') }}</td><td class="num">{{ formatMoney($receipt->change_given) }}</td></tr>
        @endif
    </table>

    @if ($isConsultation && $followupDays > 0)
        <div class="footer">{{ __('messages.cashier.followup_footer', ['days' => $followupDays, 'date' => $receipt->created_at->copy()->addDays($followupDays)->format('d/m/Y')]) }}</div>
    @endif

    <div class="sign">
        <div><b>{{ __('messages.cashier.cashier_signature') }}</b><br>{{ $receipt->user->full_name ?? '' }}</div>
        <div class="muted">{{ $receipt->counter->code ?? '' }}</div>
    </div>
</div>
@if (request('autoprint'))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
