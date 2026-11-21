{{-- Patient flow tables, shared by the screen and the print. --}}
@php($t = $report['totals'])
@if (isset($report['groups']))
    <table class="table table-row-dashed align-middle">
        <thead>
            <tr class="text-muted fw-bolder text-uppercase fs-7">
                <th>{{ $filters['tab'] === 'by_service' ? __('messages.patient_flow.service') : __('messages.cashier.nature') }}</th>
                <th class="text-end">{{ __('messages.patient_flow.count') }}</th>
                <th class="text-end">{{ __('messages.split.gross') }}</th>
                <th class="text-end">{{ __('messages.split.discount') }}</th>
                <th class="text-end">{{ __('messages.split.payer_share') }}</th>
                <th class="text-end">{{ __('messages.cashier.amount_paid') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['groups'] as $name => $g)
                <tr><td>{{ $name }}</td><td class="text-end">{{ $g['count'] }}</td><td class="text-end text-nowrap">{{ formatAmount($g['total']) }}</td>
                    <td class="text-end text-nowrap">{{ formatAmount($g['discount']) }}</td><td class="text-end text-nowrap">{{ formatAmount($g['payer_amount']) }}</td>
                    <td class="text-end text-nowrap fw-bold">{{ formatAmount($g['paid']) }}</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.patient_flow.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@else
    <table class="table table-row-dashed align-middle fs-7">
        <thead>
            <tr class="text-muted fw-bolder text-uppercase">
                <th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.cashier.receipt_no') }}</th><th>{{ __('messages.cash_journal.patient_no') }}</th>
                <th>{{ __('messages.cashier.patient') }}</th><th>{{ __('messages.cashier.nature') }}</th><th>{{ __('messages.cashier.doctor') }}</th>
                <th>{{ __('messages.split.payer') }}</th><th class="text-end">{{ __('messages.split.gross') }}</th><th class="text-end">{{ __('messages.split.discount') }}</th>
                <th class="text-end">{{ __('messages.split.payer_share') }}</th><th class="text-end">{{ __('messages.cashier.amount_paid') }}</th>
                <th>{{ __('messages.cashier.operator') }}</th><th>{{ __('messages.common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $r)
                <tr class="{{ $r['status'] === 'valid' ? '' : 'text-danger text-decoration-line-through' }}">
                    <td class="text-nowrap">{{ $r['date']->format('d/m/Y H:i') }}</td><td>{{ $r['receipt_number'] }}</td><td>{{ $r['patient_number'] }}</td>
                    <td>{{ $r['patient'] }}</td><td>{{ $r['nature'] }}</td><td>{{ $r['doctor'] }}</td><td>{{ $r['payer'] ?? __('messages.patient_insurance.self_pay') }}</td>
                    <td class="text-end text-nowrap">{{ formatAmount($r['total']) }}</td><td class="text-end text-nowrap">{{ formatAmount($r['discount']) }}</td>
                    <td class="text-end text-nowrap">{{ formatAmount($r['payer_amount']) }}</td><td class="text-end text-nowrap fw-bold">{{ formatAmount($r['paid']) }}</td>
                    <td>{{ $r['operator'] }}</td><td>{{ __('messages.receipts.status.' . $r['status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center text-muted py-5">{{ __('messages.patient_flow.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endif
<div class="d-flex flex-wrap gap-6 justify-content-end">
    <div>{{ __('messages.patient_flow.count') }}: <b>{{ $t['count'] }}</b></div>
    <div>{{ __('messages.split.gross') }}: <b>{{ formatMoney($t['total']) }}</b></div>
    <div>{{ __('messages.split.discount') }}: <b>{{ formatMoney($t['discount']) }}</b></div>
    <div>{{ __('messages.split.payer_share') }}: <b>{{ formatMoney($t['payer_amount']) }}</b></div>
    <div>{{ __('messages.cashier.amount_paid') }}: <b>{{ formatMoney($t['paid']) }}</b></div>
</div>
