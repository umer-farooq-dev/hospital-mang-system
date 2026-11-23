{{-- Consolidated detail as on the old insurer invoice: voucher, patient share (T.M.) and payer share per line. $rows: claim lines or preview rows;
     $columns: the insurer's optional columns (Insurance::claimColumns), all of them when not given. --}}
@php($columns = $columns ?? \App\Models\Insurance::CLAIM_COLUMNS)
@php($span = 3 + (int) in_array('voucher', $columns) + (int) in_array('icd10', $columns))
<div class="table-responsive">
    <table class="table table-row-dashed align-middle">
        <thead>
            <tr class="text-muted fs-7 text-uppercase">
                <th>{{ __('messages.stock.date') }}</th>
                <th>{{ __('messages.claims.patient') }}</th>
                @if (in_array('voucher', $columns))<th>{{ __('messages.claims.voucher') }}</th>@endif
                <th>{{ __('messages.claims.act') }}</th>
                @if (in_array('icd10', $columns))<th>{{ __('messages.claims.columns.icd10') }}</th>@endif
                <th class="text-end">{{ __('messages.claims.total') }}</th>
                @if (in_array('patient_share', $columns))<th class="text-end">{{ __('messages.claims.patient_share') }}</th>@endif
                <th class="text-end">{{ __('messages.claims.payer_share') }}</th>
                @if ($rows->first()?->status)<th>{{ __('messages.stock_transfer.status') }}</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->service_date)->format('d/m/Y') }}</td>
                    <td>{{ $row->patient->patientUser->full_name ?? '' }} @if (in_array('patient_number', $columns))<span class="text-muted fs-7">{{ $row->patient->patient_unique_id ?? '' }}</span>@endif</td>
                    @if (in_array('voucher', $columns))<td>{{ $row->voucher_number ?: '—' }}</td>@endif
                    <td>{{ $row->description }}</td>
                    @if (in_array('icd10', $columns))<td>{{ $row instanceof \App\Models\ClaimLine ? implode(', ', $row->icd10Codes()) : '' }}</td>@endif
                    <td class="text-end">{{ formatMoney($row->total) }}</td>
                    @if (in_array('patient_share', $columns))<td class="text-end">{{ formatMoney($row->patient_amount) }}</td>@endif
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
                    <td colspan="{{ $span }}" class="text-end">{{ __('messages.claims.total') }}</td>
                    <td class="text-end">{{ formatMoney($rows->sum('total')) }}</td>
                    @if (in_array('patient_share', $columns))<td class="text-end">{{ formatMoney($rows->sum('patient_amount')) }}</td>@endif
                    <td class="text-end">{{ formatMoney($rows->sum('payer_amount')) }}</td>
                    @if ($rows->first()?->status)<td></td>@endif
                </tr>
            </tfoot>
        @endif
    </table>
</div>
