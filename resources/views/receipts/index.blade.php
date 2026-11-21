@extends('layouts.app')
@section('title')
    {{ __('messages.receipts.receipts') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('cashier.nav')
        </div>
    </div>
@endsection
@section('content')
    @php
        $canVoid = canAccessRoute('receipts.void');
        $canRefund = canAccessRoute('receipts.refund');
        $canMethod = canAccessRoute('receipts.method');
        $canDoctor = canAccessRoute('receipts.doctor');
        $badges = ['valid' => 'success', 'voided' => 'danger', 'refunded' => 'warning'];
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            @include('layouts.errors')
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('receipts.index') }}" class="row g-3 align-items-end mb-6">
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.cashier.receipt_no') }}</label>
                            <input type="number" name="number" class="form-control" value="{{ request('number') }}" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.from') }}</label>
                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.to') }}</label>
                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.cashier.counter') }}</label>
                            {{ Form::select('counter', $counters, request('counter'), ['class' => 'form-select', 'placeholder' => __('messages.filter.All')]) }}
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.cashier.payment_method') }}</label>
                            {{ Form::select('method', $methods, request('method'), ['class' => 'form-select', 'placeholder' => __('messages.filter.All')]) }}
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            <a href="{{ route('receipts.index') }}" class="btn btn-secondary">{{ __('messages.common.reset') }}</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th>{{ __('messages.cashier.receipt_no') }}</th>
                                    <th>{{ __('messages.cashier.date') }}</th>
                                    <th>{{ __('messages.cashier.patient') }}</th>
                                    <th>{{ __('messages.cashier.counter') }}</th>
                                    <th>{{ __('messages.cashier.payment_method') }}</th>
                                    <th class="text-end">{{ __('messages.cashier.amount') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th class="text-end">{{ __('messages.common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($receipts as $receipt)
                                    @php($open = ! $receipt->session->closed_at && $receipt->status === 'valid')
                                    <tr>
                                        <td class="fw-bold">{{ $receipt->receipt_number }}</td>
                                        <td class="text-nowrap">{{ $receipt->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $receipt->patient->patientUser->full_name ?? '' }}
                                            <div class="text-muted fs-7">{{ $receipt->patient->patient_unique_id ?? '' }} · {{ $receipt->user->full_name ?? '' }}</div></td>
                                        <td>{{ $receipt->counter->code ?? '' }}</td>
                                        <td>{{ $receipt->payment_method_name }}</td>
                                        <td class="text-end text-nowrap">{{ formatMoney($receipt->amount) }}</td>
                                        <td>
                                            <span class="badge bg-light-{{ $badges[$receipt->status] }}">{{ __('messages.receipts.status.' . $receipt->status) }}</span>
                                            @if ($receipt->status === 'voided')
                                                <div class="text-muted fs-7">{{ $receipt->void_reason_name }}</div>
                                            @elseif ($receipt->refund)
                                                <div class="text-muted fs-7">{{ $receipt->refund->reason_name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('receipts.print', [$receipt->id, 'duplicate' => 1]) }}" target="_blank" class="btn btn-sm btn-light" title="{{ __('messages.receipts.duplicate') }}"><i class="fas fa-copy"></i></a>
                                            @if ($open && $canMethod)
                                                <button type="button" class="btn btn-sm btn-light-primary receipt-action" data-bs-toggle="modal" data-bs-target="#receiptMethodModal"
                                                        data-url="{{ route('receipts.method', $receipt->id) }}" data-number="{{ $receipt->receipt_number }}" title="{{ __('messages.receipts.change_method') }}"><i class="fas fa-exchange-alt"></i></button>
                                            @endif
                                            @if ($canDoctor && $receipt->status === 'valid' && $receipt->lines->contains('payable_type', 'opd_visit'))
                                                <button type="button" class="btn btn-sm btn-light-info receipt-action" data-bs-toggle="modal" data-bs-target="#receiptDoctorModal"
                                                        data-url="{{ route('receipts.doctor', $receipt->id) }}" data-number="{{ $receipt->receipt_number }}" title="{{ __('messages.receipts.change_doctor') }}"><i class="fas fa-user-md"></i></button>
                                            @endif
                                            @if ($open && $canVoid)
                                                <button type="button" class="btn btn-sm btn-light-danger receipt-action" data-bs-toggle="modal" data-bs-target="#receiptReasonModal"
                                                        data-url="{{ route('receipts.void', $receipt->id) }}" data-number="{{ $receipt->receipt_number }}" data-title="{{ __('messages.receipts.void') }}" title="{{ __('messages.receipts.void') }}"><i class="fas fa-ban"></i></button>
                                            @endif
                                            @if ($receipt->status === 'valid' && $canRefund)
                                                <button type="button" class="btn btn-sm btn-light-warning receipt-action" data-bs-toggle="modal" data-bs-target="#receiptReasonModal"
                                                        data-url="{{ route('receipts.refund', $receipt->id) }}" data-number="{{ $receipt->receipt_number }}" data-title="{{ __('messages.receipts.refund') }}" title="{{ __('messages.receipts.refund') }}"><i class="fas fa-undo"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-5">{{ __('messages.receipts.none') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $receipts->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="receiptReasonModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" id="receiptReasonForm">
                @csrf
                <div class="modal-header">
                    <h2 class="receipt-modal-title"></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">{{ __('messages.cashier.receipt_no') }} <b class="receipt-modal-number"></b></p>
                    <div class="mb-5">
                        <label class="form-label">{{ __('messages.receipts.reason') }}:</label><span class="required"></span>
                        {{ Form::select('reason', $reasons, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                    </div>
                    <div>
                        <label class="form-label">{{ __('messages.cashier.notes') }}:</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">{{ __('messages.common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div id="receiptDoctorModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h2>{{ __('messages.receipts.change_doctor') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">{{ __('messages.cashier.receipt_no') }} <b class="receipt-modal-number"></b></p>
                    {{ Form::select('doctor_id', $doctors, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('messages.common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div id="receiptMethodModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content" id="receiptMethodForm">
                @csrf
                <div class="modal-header">
                    <h2>{{ __('messages.receipts.change_method') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">{{ __('messages.cashier.receipt_no') }} <b class="receipt-modal-number"></b></p>
                    {{ Form::select('payment_method', $methods, null, ['class' => 'form-select', 'required']) }}
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('messages.common.save') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
