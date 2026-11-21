@if (! $lines)
    <div class="text-muted">{{ __('messages.exam_orders.choose_exams') }}</div>
@else
    @php
        $sum = fn ($key) => array_sum(array_map(fn ($l) => $l['split'][$key], $lines));
        $warnings = array_unique(array_merge(...array_map(fn ($l) => $l['split']['warnings'], $lines)));
    @endphp
    <div class="border rounded p-4 bg-light">
        <table class="table table-sm align-middle mb-3">
            <thead>
                <tr class="text-muted fs-7 text-uppercase">
                    <th>{{ __('messages.cashier.nature') }}</th>
                    <th class="text-end">{{ __('messages.split.gross') }}</th>
                    <th class="text-end">{{ __('messages.split.discount') }}</th>
                    <th class="text-end">{{ __('messages.split.payer_share') }}</th>
                    <th class="text-end">{{ __('messages.split.patient_share') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td>{{ $line['description'] }}</td>
                        <td class="text-end text-nowrap">{{ formatMoney($line['split']['gross']) }}</td>
                        <td class="text-end text-nowrap">{{ formatMoney($line['split']['discount_amount']) }}</td>
                        <td class="text-end text-nowrap">{{ formatMoney($line['split']['payer_amount']) }}</td>
                        <td class="text-end text-nowrap fw-bold">{{ formatMoney($line['split']['patient_amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bolder">
                    <td>{{ __('messages.cashier.total_amount') }}</td>
                    <td class="text-end text-nowrap">{{ formatMoney($sum('gross')) }}</td>
                    <td class="text-end text-nowrap">{{ formatMoney($sum('discount_amount')) }}</td>
                    <td class="text-end text-nowrap">{{ formatMoney($sum('payer_amount')) }}</td>
                    <td class="text-end text-nowrap text-primary fs-5">{{ formatMoney($sum('patient_amount')) }}</td>
                </tr>
            </tfoot>
        </table>
        @foreach ($warnings as $warning)
            <div class="alert alert-warning py-2 px-3 mb-2 fs-7">{{ __('messages.split.warnings.' . $warning) }}</div>
        @endforeach
    </div>
@endif
