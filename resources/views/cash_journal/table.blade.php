{{-- Journal rows and totals, shared by the screen and the print. --}}
<table class="table table-row-dashed align-middle {{ $print ?? false ? '' : 'fs-7' }}">
    <thead>
        <tr class="text-muted fw-bolder text-uppercase">
            <th>{{ __('messages.cash_journal.patient_no') }}</th>
            <th>{{ __('messages.cashier.patient') }}</th>
            <th>{{ __('messages.cash_journal.label') }}</th>
            <th>{{ __('messages.cash_journal.direction') }}</th>
            <th class="text-end">{{ __('messages.cash_journal.initial') }}</th>
            <th class="text-end">{{ __('messages.cashier.amount') }}</th>
            <th class="text-end">{{ __('messages.cash_journal.balance') }}</th>
            <th>{{ __('messages.cashier.date') }}</th>
            <th>{{ __('messages.cashier.time') }}</th>
            <th>{{ __('messages.cashier.receipt_no') }}</th>
            <th>{{ __('messages.cashier.operator') }}</th>
            <th>{{ __('messages.cashier.payment_method') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($journal['rows'] as $row)
            <tr>
                <td>{{ $row['patient']->patient_unique_id ?? '' }}</td>
                <td>{{ $row['patient']->patientUser->full_name ?? '' }}</td>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['credit'] ? __('messages.cash_journal.debit') : __('messages.cash_journal.credit') }}</td>
                <td class="text-end text-nowrap">{{ formatAmount($row['initial']) }}</td>
                <td class="text-end text-nowrap {{ $row['credit'] ? '' : 'text-danger' }}">{{ $row['credit'] ? '' : '-' }}{{ formatAmount($row['amount']) }}</td>
                <td class="text-end text-nowrap fw-bold">{{ formatAmount($row['balance']) }}</td>
                <td class="text-nowrap">{{ $row['at']->format('d/m/Y') }}</td>
                <td>{{ $row['at']->format('H:i') }}</td>
                <td>{{ $row['receipt_number'] }}</td>
                <td>{{ $row['operator'] }}</td>
                <td>{{ \App\Models\PaymentMethod::label($row['method']) }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="text-center text-muted py-5">{{ __('messages.cash_journal.empty') }}</td></tr>
        @endforelse
    </tbody>
</table>
<div class="d-flex flex-wrap gap-6 justify-content-end fs-6">
    @foreach ($journal['totals'] as $method => $total)
        <div>{{ \App\Models\PaymentMethod::label($method) }}: <b>{{ formatMoney($total) }}</b></div>
    @endforeach
    <div>{{ __('messages.cash_journal.closing_balance') }}: <b>{{ formatMoney($journal['closing']) }}</b></div>
</div>
