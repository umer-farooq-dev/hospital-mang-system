@extends('layouts.app')
@section('title')
    {{ __('messages.purchase_order.order') }} {{ $order->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') @include('purchase_orders.status', ['status' => $order->status])</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-5 mb-5">
                    <div><span class="text-muted">{{ __('messages.stock.supplier') }}:</span> <b>{{ $order->supplier->name ?? '' }}</b>
                        @if ($order->supplier?->phone) · {{ $order->supplier->phone }}@endif</div>
                    <div><span class="text-muted">{{ __('messages.stock.receive_into') }}:</span> {{ $order->store->name ?? '' }}</div>
                    <div><span class="text-muted">{{ __('messages.stock.date') }}:</span> {{ $order->created_at->translatedFormat('d/m/Y') }} · {{ $order->stepUser('created')->first()->full_name ?? '' }}</div>
                    @if ($order->expected_date)<div><span class="text-muted">{{ __('messages.purchase_order.expected') }}:</span> {{ \Illuminate\Support\Carbon::parse($order->expected_date)->translatedFormat('d/m/Y') }}</div>@endif
                    @if ($order->note)<div><span class="text-muted">{{ __('messages.stock_transfer.note') }}:</span> {{ $order->note }}</div>@endif
                </div>
                @if ($canReceive)
                    <div class="form-text mb-3">{{ __('messages.purchase_order.receive_hint') }}</div>
                    {{ Form::open(['route' => ['purchase-orders.receive', $order->id], 'method' => 'post', 'id' => 'receiveForm']) }}
                @endif
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.medicine.medicine') }}</th>
                                <th class="text-end">{{ __('messages.purchase_order.ordered') }}</th>
                                <th class="text-end">{{ __('messages.stock.unit_cost') }}</th>
                                <th class="text-end">{{ __('messages.purchase_order.received_qty') }}</th>
                                @if ($canReceive)
                                    <th class="w-100px">{{ __('messages.purchase_order.delivered_now') }}</th>
                                    <th class="w-150px">{{ __('messages.stock.batch') }}</th>
                                    <th class="w-150px">{{ __('messages.stock.expiry') }}</th>
                                    <th class="w-125px">{{ __('messages.stock.unit_cost') }}</th>
                                @endif
                            </tr>
                        </thead>
                        @foreach ($order->items as $line)
                            <tr>
                                <td>{{ $line->stockable->name ?? '' }}</td>
                                <td class="text-end">{{ $line->quantity }}</td>
                                <td class="text-end">{{ formatMoney($line->unit_cost) }}</td>
                                <td class="text-end">{{ $line->received_quantity }}</td>
                                @if ($canReceive)
                                    <td>{{ Form::number('lines[' . $line->id . '][quantity]', old('lines.' . $line->id . '.quantity', $line->remaining() ?: null), ['class' => 'form-control form-control-sm', 'min' => 0]) }}</td>
                                    <td>{{ Form::text('lines[' . $line->id . '][lot_no]', old('lines.' . $line->id . '.lot_no'), ['class' => 'form-control form-control-sm', 'maxlength' => 100]) }}</td>
                                    <td>{{ Form::date('lines[' . $line->id . '][expiry_date]', old('lines.' . $line->id . '.expiry_date'), ['class' => 'form-control form-control-sm']) }}</td>
                                    <td>{{ Form::number('lines[' . $line->id . '][unit_cost]', old('lines.' . $line->id . '.unit_cost', $line->unit_cost), ['class' => 'form-control form-control-sm', 'min' => 0, 'step' => '0.01']) }}</td>
                                @endif
                            </tr>
                        @endforeach
                        <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">{{ __('messages.purchase_order.total') }}</td><td class="text-end">{{ formatMoney($order->total()) }}</td><td colspan="5"></td></tr></tfoot>
                    </table>
                </div>
                @if ($canReceive)
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            {{ Form::label('payment_type', __('messages.purchase_medicine.payment_mode') . ':', ['class' => 'form-label']) }}
                            {{ Form::select('payment_type', array_slice(getPurchaseMedicinePaymentTypes(), 0, 2, true), 0, ['class' => 'form-select']) }}
                        </div>
                        <div class="col-md-3">
                            <label class="form-check form-switch mb-3"><input type="checkbox" class="form-check-input" name="paid" value="1">
                                <span class="form-check-label">{{ __('messages.purchase_order.paid_now') }}</span></label>
                        </div>
                        <div class="col-md-4">
                            {{ Form::label('note', __('messages.stock_transfer.note') . ':', ['class' => 'form-label']) }}
                            {{ Form::text('note', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => __('messages.purchase_order.delivery_note')]) }}
                        </div>
                    </div>
                    {{ Form::close() }}
                @endif
                <div class="d-flex justify-content-end gap-3 mt-5">
                    @if ($canReceive)
                        <button type="submit" form="receiveForm" class="btn btn-primary">{{ __('messages.purchase_order.receive') }}</button>
                    @endif
                    @if ($order->status === 'partial' && canAccessRoute('purchase-orders.close'))
                        {{ Form::open(['route' => ['purchase-orders.close', $order->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-light-warning">{{ __('messages.purchase_order.close') }}</button>
                        {{ Form::close() }}
                    @endif
                    @if ($order->status === 'ordered' && canAccessRoute('purchase-orders.cancel'))
                        {{ Form::open(['route' => ['purchase-orders.cancel', $order->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-light-danger">{{ __('messages.stock_transfer.cancel') }}</button>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
        @if ($order->receipts->isNotEmpty())
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.purchase_order.receipts') }}</h5>
                    <table class="table table-row-dashed align-middle mb-0">
                        @foreach ($order->receipts as $receipt)
                            <tr>
                                <td>@canroute('medicine-purchase.show')<a href="{{ route('medicine-purchase.show', $receipt->id) }}">{{ $receipt->purchase_no }}</a>@else{{ $receipt->purchase_no }}@endcanroute</td>
                                <td>{{ $receipt->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                <td class="text-end">{{ formatMoney($receipt->net_amount) }}</td>
                                <td>{{ $receipt->payment_status ? __('messages.purchase_order.paid') : __('messages.purchase_order.unpaid') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
