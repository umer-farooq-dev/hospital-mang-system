@extends('layouts.app')
@section('title')
    {{ __('messages.cashier.cashier') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if ($session)
                    <span class="badge bg-light-success fs-6">{{ $session->counter->code }} · {{ $session->counter->name }}</span>
                @endif
                @include('cashier.nav')
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid" id="cashierPage"
         data-print-url="{{ request('print') ? route('receipts.print', request('print')) : '' }}"
         data-index-url="{{ route('cashier.index') }}">
        <div class="d-flex flex-column">
            @include('flash::message')
            @include('layouts.errors')

            @if (request('closed'))
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>{{ __('messages.cashier.session_closed_hint') }}</span>
                    <a href="{{ route('cash-sessions.print', request('closed')) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-print me-1"></i>{{ __('messages.cashier.print_close') }}</a>
                </div>
            @endif

            @if (! $user->cash_counter_id)
                <div class="alert alert-warning">{{ __('messages.cashier.no_counter') }}</div>
            @elseif (! $session)
                <div class="card">
                    <div class="card-body">
                        <h3 class="mb-5">{{ __('messages.cashier.open_session') }}</h3>
                        {{ Form::open(['route' => 'cashier.open', 'method' => 'post', 'class' => 'row g-4 align-items-end']) }}
                        {{ Form::hidden('patient', request('patient')) }}
                        <div class="col-md-4">
                            {{ Form::label('opening_float', __('messages.cashier.opening_float') . ':', ['class' => 'form-label']) }}
                            {{ Form::number('opening_float', 0, ['class' => 'form-control', 'min' => 0, 'step' => 'any']) }}
                        </div>
                        <div class="col-md-4">
                            {{ Form::submit(__('messages.cashier.open_session'), ['class' => 'btn btn-primary']) }}
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-xl-8 mb-7">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="mb-5">{{ __('messages.cashier.collect') }}</h3>
                                <form method="GET" action="{{ route('cashier.index') }}" class="mb-5">
                                    <select name="patient" id="cashierPatient" class="form-select" data-control="select2" data-placeholder="{{ __('messages.cashier.choose_patient') }}">
                                        <option value=""></option>
                                        @foreach ($patients as $id => $label)
                                            <option value="{{ $id }}" {{ (int) $patientId === $id ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>

                                @if ($patientId && $items->isEmpty())
                                    <div class="text-muted py-5 text-center">{{ __('messages.cashier.nothing_to_pay') }}</div>
                                @elseif ($items->isNotEmpty())
                                    {{ Form::open(['route' => 'cashier.collect', 'method' => 'post', 'id' => 'cashierCollectForm']) }}
                                    {{ Form::hidden('patient_id', $patientId) }}
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed">
                                            <thead>
                                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                                    <th></th>
                                                    <th>{{ __('messages.cashier.date') }}</th>
                                                    <th>{{ __('messages.cashier.nature') }}</th>
                                                    <th class="text-end">{{ __('messages.split.gross') }}</th>
                                                    <th class="text-end">{{ __('messages.split.discount') }}</th>
                                                    <th class="text-end">{{ __('messages.split.payer_share') }}</th>
                                                    <th class="text-end">{{ __('messages.cashier.to_pay') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($items as $item)
                                                    <tr>
                                                        <td><input type="checkbox" class="form-check-input cashier-line" name="items[]" value="{{ $item['key'] }}" data-amount="{{ $item['outstanding'] }}" checked></td>
                                                        <td class="text-nowrap">{{ $item['date']->format('d/m/Y H:i') }}</td>
                                                        <td>
                                                            {{ $item['description'] }}
                                                            <div class="text-muted fs-7">{{ $item['detail'] }}</div>
                                                        </td>
                                                        <td class="text-end text-nowrap">{{ formatMoney($item['total']) }}</td>
                                                        <td class="text-end text-nowrap">{{ formatMoney($item['discount']) }}</td>
                                                        <td class="text-end text-nowrap">{{ formatMoney($item['payer']) }}</td>
                                                        <td class="text-end text-nowrap fw-bold">{{ formatMoney($item['outstanding']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-3">
                                            {{ Form::label('payment_method', __('messages.cashier.payment_method') . ':', ['class' => 'form-label']) }}
                                            {{ Form::select('payment_method', $paymentMethods, old('payment_method', array_key_first($paymentMethods)), ['class' => 'form-select', 'required']) }}
                                        </div>
                                        <div class="col-md-3">
                                            {{ Form::label('amount_received', __('messages.cashier.amount_received') . ':', ['class' => 'form-label']) }}
                                            {{ Form::number('amount_received', old('amount_received'), ['class' => 'form-control', 'id' => 'cashierReceived', 'min' => 0, 'step' => 'any']) }}
                                        </div>
                                        <div class="col-md-3">
                                            {{ Form::label('cheque_number', __('messages.cashier.cheque_number') . ':', ['class' => 'form-label']) }}
                                            {{ Form::text('cheque_number', old('cheque_number'), ['class' => 'form-control', 'maxlength' => 50]) }}
                                        </div>
                                        <div class="col-md-3">
                                            {{ Form::label('cheque_drawer', __('messages.cashier.cheque_drawer') . ':', ['class' => 'form-label']) }}
                                            {{ Form::text('cheque_drawer', old('cheque_drawer'), ['class' => 'form-control', 'maxlength' => 100]) }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-6 gap-3">
                                        <div class="fs-4">
                                            {{ __('messages.cashier.total_to_pay') }}: @php($fmt = currencyFormatSettings())<span class="fw-bolder text-primary" id="cashierTotal" data-symbol="{{ getCurrencySymbol() }}" data-decimals="{{ $fmt['decimals'] }}" data-dec="{{ $fmt['decimal_separator'] }}" data-thousands="{{ $fmt['thousands_separator'] }}" data-position="{{ $fmt['symbol_position'] }}">{{ formatMoney($items->sum('outstanding')) }}</span>
                                            <span class="ms-6 text-muted fs-6">{{ __('messages.cashier.change') }}: <span id="cashierChange">{{ formatMoney(0) }}</span></span>
                                        </div>
                                        {{ Form::submit(__('messages.cashier.validate'), ['class' => 'btn btn-primary']) }}
                                    </div>
                                    {{ Form::close() }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 mb-7">
                        <div class="card mb-7">
                            <div class="card-body">
                                <h3 class="mb-4">{{ __('messages.cashier.session') }}</h3>
                                <div class="text-muted mb-1">{{ __('messages.cashier.opened_at') }}: {{ $session->opened_at->format('d/m/Y H:i') }} · {{ $session->user->full_name }}</div>
                                <div class="text-muted mb-4">{{ __('messages.cashier.opening_float') }}: {{ formatMoney($session->opening_float) }}</div>
                                <table class="table table-sm mb-4">
                                    @forelse ($totals as $method => $total)
                                        <tr><td>{{ \App\Models\PaymentMethod::label($method) }}</td><td class="text-end fw-bold">{{ formatMoney($total) }}</td></tr>
                                    @empty
                                        <tr><td class="text-muted">{{ __('messages.cashier.no_receipt_yet') }}</td></tr>
                                    @endforelse
                                </table>
                                @canroute('cashier.close')
                                <button type="button" class="btn btn-light-danger w-100" data-bs-toggle="modal" data-bs-target="#cashierCloseModal">{{ __('messages.cashier.close_session') }}</button>
                                @endcanroute
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h3 class="mb-4">{{ __('messages.cashier.recent_receipts') }}</h3>
                                @forelse ($recentReceipts as $receipt)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div>
                                            <div class="fw-bold">N° {{ $receipt->receipt_number }} · {{ formatMoney($receipt->amount) }}</div>
                                            <div class="text-muted fs-7">{{ $receipt->patient->patientUser->full_name ?? '' }} · {{ $receipt->created_at->format('H:i') }}</div>
                                        </div>
                                        <a href="{{ route('receipts.print', $receipt->id) }}" target="_blank" class="btn btn-sm btn-light" title="{{ __('messages.cashier.print') }}"><i class="fas fa-print"></i></a>
                                    </div>
                                @empty
                                    <div class="text-muted">{{ __('messages.cashier.no_receipt_yet') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div id="cashierCloseModal" class="modal fade" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            {{ Form::open(['route' => 'cashier.close', 'method' => 'post']) }}
                            <div class="modal-header">
                                <h2>{{ __('messages.cashier.close_session') }}</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                @php($cashMethod = \App\Repositories\CashierRepository::cashMethod())
                                <p class="text-muted">{{ __('messages.cashier.expected_cash') }}: <b>{{ formatMoney($session->opening_float + ($totals[$cashMethod] ?? 0)) }}</b></p>
                                <div class="mb-5">
                                    {{ Form::label('counted_cash', __('messages.cashier.counted_cash') . ':', ['class' => 'form-label']) }}
                                    <span class="required"></span>
                                    {{ Form::number('counted_cash', null, ['class' => 'form-control', 'min' => 0, 'step' => 'any', 'required']) }}
                                </div>
                                <div>
                                    {{ Form::label('notes', __('messages.cashier.notes') . ':', ['class' => 'form-label']) }}
                                    {{ Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 1000]) }}
                                </div>
                            </div>
                            <div class="modal-footer">
                                {{ Form::submit(__('messages.cashier.close_session'), ['class' => 'btn btn-danger']) }}
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
