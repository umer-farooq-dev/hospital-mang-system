{{-- Settlement of a claim invoice: payment and rejections, rejected lines, payments received. $invoice --}}
@php
    $open = in_array($invoice->status, ['issued', 'partial'], true) && canAccessRoute('claims.settle');
    $owed = $invoice->lines->filter(fn ($l) => $l->outstanding() > 0);
    $rejected = $invoice->lines->where('status', 'rejected');
    $reasons = \App\Models\MasterListItem::options('claim_rejection_reason');
    $reasonLabels = \App\Models\MasterListItem::labels('claim_rejection_reason');
@endphp
@if ($open && $owed->isNotEmpty())
    <div class="card mb-7 d-print-none">
        <div class="card-body">
            <h5 class="mb-2">{{ __('messages.claim_settlement.record') }}</h5>
            <div class="form-text mb-4">{{ __('messages.claim_settlement.record_hint') }}</div>
            {{ Form::open(['route' => ['claims.settle', $invoice->id], 'method' => 'post']) }}
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.claims.patient') }}</th>
                            <th>{{ __('messages.claims.act') }}</th>
                            <th class="text-end">{{ __('messages.claims.payer_share') }}</th>
                            <th class="text-end">{{ __('messages.claim_settlement.paid_before') }}</th>
                            <th class="w-150px">{{ __('messages.claim_settlement.paid_now') }}</th>
                            <th class="w-200px">{{ __('messages.claim_settlement.reject_reason') }}</th>
                        </tr>
                    </thead>
                    @foreach ($owed as $line)
                        <tr>
                            <td>{{ $line->patient->patientUser->full_name ?? '' }}</td>
                            <td>{{ $line->description }}<div class="text-muted fs-7">{{ $line->service_date->format('d/m/Y') }}{{ $line->voucher_number ? ' · ' . $line->voucher_number : '' }}</div></td>
                            <td class="text-end">{{ formatMoney($line->payer_amount) }}</td>
                            <td class="text-end">{{ formatMoney($line->paid_amount) }}</td>
                            <td><input type="number" name="lines[{{ $line->id }}][paid]" value="{{ $line->outstanding() }}" min="0" max="{{ $line->outstanding() }}" step="0.01" class="form-control form-control-sm text-end"></td>
                            <td>{{ Form::select('lines[' . $line->id . '][reason]', $reasons, null, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.claim_settlement.not_rejected')]) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-md-2"><label class="form-label required">{{ __('messages.claim_settlement.paid_on') }}</label><input type="date" name="paid_on" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                <div class="col-md-3"><label class="form-label">{{ __('messages.claim_settlement.method') }}</label>{{ Form::select('payment_method', \App\Models\MasterListItem::options('payment_method'), null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}</div>
                <div class="col-md-3"><label class="form-label">{{ __('messages.claim_settlement.reference') }}</label><input type="text" name="reference" class="form-control" maxlength="100"></div>
                <div class="col-md-2"><label class="form-label">{{ __('messages.stock_transfer.note') }}</label><input type="text" name="note" class="form-control" maxlength="255"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">{{ __('messages.common.save') }}</button></div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
@endif
@if ($rejected->isNotEmpty())
    <div class="card mb-7 d-print-none">
        <div class="card-body">
            <h5 class="mb-2">{{ __('messages.claim_settlement.rejected') }}</h5>
            <div class="form-text mb-4">{{ __('messages.claim_settlement.rejected_hint') }}</div>
            <table class="table table-row-dashed align-middle mb-0">
                @foreach ($rejected as $line)
                    <tr>
                        <td>{{ $line->patient->patientUser->full_name ?? '' }}</td>
                        <td>{{ $line->description }}</td>
                        <td>{{ $reasonLabels[$line->rejection_reason] ?? '' }}</td>
                        <td class="text-end fw-bold">{{ formatMoney($line->rejectedAmount()) }}</td>
                        <td class="text-end">
                            @canroute('claims.lines.update')
                                @foreach (array_keys(\App\Models\ClaimLine::ACTIONS) as $action)
                                    {{ Form::open(['route' => ['claims.lines.update', $line->id, $action], 'method' => 'post', 'class' => 'd-inline']) }}
                                    <button type="submit" class="btn btn-sm {{ $action === 'resubmit' ? 'btn-light-primary' : ($action === 'to_patient' ? 'btn-light-warning' : 'btn-light-danger') }} mb-1">{{ __('messages.claim_settlement.actions.' . $action) }}</button>
                                    {{ Form::close() }}
                                @endforeach
                            @endcanroute
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endif
@if ($invoice->payments->isNotEmpty())
    <div class="card mb-7">
        <div class="card-body">
            <h5 class="mb-4">{{ __('messages.claim_settlement.payments') }}</h5>
            <table class="table table-row-dashed align-middle mb-0">
                @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_on->format('d/m/Y') }}</td>
                        <td>{{ \App\Models\MasterListItem::label('payment_method', $payment->payment_method, '') }}</td>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->note }}</td>
                        <td class="text-end fw-bold">{{ formatMoney($payment->amount) }}</td>
                    </tr>
                @endforeach
                <tr class="fw-bold"><td colspan="4" class="text-end">{{ __('messages.claims.outstanding') }}</td><td class="text-end">{{ formatMoney($invoice->outstanding()) }}</td></tr>
            </table>
        </div>
    </div>
@endif
