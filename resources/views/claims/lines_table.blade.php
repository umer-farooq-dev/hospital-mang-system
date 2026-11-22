{{-- Consolidated detail as on the old insurer invoice: voucher, patient share (T.M.) and payer share per line. $rows: claim lines or preview rows --}}
<div class="table-responsive">
    <table class="table table-row-dashed align-middle">
        <thead>
            <tr class="text-muted fs-7 text-uppercase">
                <th>{{ __('messages.stock.date') }}</th>
                <th>{{ __('messages.claims.patient') }}</th>
                <th>{{ __('messages.claims.voucher') }}</th>
                <th>{{ __('messages.claims.act') }}</th>
                <th class="text-end">{{ __('messages.claims.total') }}</th>
                <th class="text-end">{{ __('messages.claims.patient_share') }}</th>
                <th class="text-end">{{ __('messages.claims.payer_share') }}</th>
                @if ($rows->first()?->status)<th>{{ __('messages.stock_transfer.status') }}</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->service_date)->format('d/m/Y') }}</td>
                    <td>{{ $row->patient->patientUser->full_name ?? '' }} <span class="text-muted fs-7">{{ $row->patient->patient_unique_id ?? '' }}</span></td>
                    <td>{{ $row->voucher_number ?: '—' }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-end">{{ formatMoney($row->total) }}</td>
                    <td class="text-end">{{ formatMoney($row->patient_amount) }}</td>
                    <td class="text-end fw-bold">{{ formatMoney($row->payer_amount) }}</td>
                    @if ($row->status)<td>@include('claims.status', ['status' => $row->status])</td>@endif
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">{{ __('messages.claims.nothing') }}</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">{{ __('messages.claims.total') }}</td>
                    <td class="text-end">{{ formatMoney($rows->sum('total')) }}</td>
                    <td class="text-end">{{ formatMoney($rows->sum('patient_amount')) }}</td>
                    <td class="text-end">{{ formatMoney($rows->sum('payer_amount')) }}</td>
                    @if ($rows->first()?->status)<td></td>@endif
                </tr>
            </tfoot>
        @endif
    </table>
</div>
