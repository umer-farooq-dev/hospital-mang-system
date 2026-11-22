@extends('layouts.app')
@section('title')
    {{ __('messages.stock_transfer.kinds.' . $transfer->kind) }} {{ $transfer->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title') @include('stock_transfers.status', ['status' => $transfer->status])</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                <a href="{{ route('stock-transfers.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php
        $status = $transfer->status;
        $canApprove = $status === 'requested' && canAccessRoute('stock-transfers.approve');
        $doSend = $status === 'approved' && $canSend && canAccessRoute('stock-transfers.send');
        $doReceive = $status === 'sent' && ! $transfer->isSupplierReturn() && $canReceive && canAccessRoute('stock-transfers.receive');
        $editable = $canApprove ? 'approve' : ($doSend ? 'send' : null);
        $moves = \App\Models\StockMovement::with('batch', 'store', 'stockable')->forDocument($transfer)->orderBy('id')->get();
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        <div class="card mb-7">
            <div class="card-body">
                <div class="row mb-5">
                    <div class="col-md-3"><div class="text-muted">{{ __('messages.stock_transfer.from') }}</div><div class="fw-bold">{{ $transfer->fromStore->name ?? '' }}</div></div>
                    <div class="col-md-3"><div class="text-muted">{{ $transfer->isSupplierReturn() ? __('messages.stock.supplier') : __('messages.stock_transfer.to') }}</div>
                        <div class="fw-bold">{{ $transfer->toStore->name ?? ($transfer->supplier->name ?? '') }}</div></div>
                    <div class="col-md-6"><div class="text-muted">{{ __('messages.stock_transfer.note') }}</div><div>{{ $transfer->note ?: '—' }}</div></div>
                </div>
                <div class="d-flex flex-wrap gap-5 mb-5 fs-7">
                    @foreach (['requested', 'approved', 'sent', 'received', 'cancelled'] as $step)
                        @if ($transfer->{$step . '_at'})
                            <div><span class="text-muted">{{ __('messages.stock_transfer.statuses.' . $step) }}:</span>
                                {{ $transfer->{$step . '_at'}->translatedFormat('d/m/Y H:i') }} · {{ $transfer->stepUser($step)->first()->full_name ?? '' }}</div>
                        @endif
                    @endforeach
                </div>

                @if ($editable)
                    {{ Form::open(['route' => ['stock-transfers.' . $editable, $transfer->id], 'method' => 'post', 'id' => 'transferStep']) }}
                @endif
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.medicine.medicine') }}</th>
                            <th class="text-end">{{ __('messages.stock_transfer.requested') }}</th>
                            <th class="text-end">{{ __('messages.stock_transfer.approved') }}</th>
                            <th class="text-end">{{ __('messages.stock_transfer.sent') }}</th>
                            @if ($doSend)<th class="text-end">{{ __('messages.stock_transfer.on_hand', ['store' => $transfer->fromStore->name ?? '']) }}</th>@endif
                        </tr>
                    </thead>
                    @foreach ($transfer->items as $line)
                        <tr>
                            <td>{{ $line->stockable->name ?? '' }}</td>
                            <td class="text-end">{{ $line->quantity_requested }}</td>
                            <td class="text-end">
                                @if ($editable === 'approve')
                                    <input type="number" name="quantity[{{ $line->id }}]" value="{{ $line->quantity_requested }}" min="0" class="form-control form-control-sm w-100px ms-auto text-end">
                                @else
                                    {{ $line->quantity_approved ?? '—' }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($editable === 'send')
                                    <input type="number" name="quantity[{{ $line->id }}]" value="{{ $line->quantity_approved }}" min="0" max="{{ $line->quantity_approved }}" class="form-control form-control-sm w-100px ms-auto text-end">
                                @else
                                    {{ $line->quantity_sent ?? '—' }}
                                @endif
                            </td>
                            @if ($doSend)<td class="text-end">{{ $line->stockable ? \App\Utils\Stock::onHand($line->stockable, (int) $transfer->from_store_id) : 0 }}</td>@endif
                        </tr>
                    @endforeach
                </table>
                @if ($editable)
                    {{ Form::close() }}
                @endif
                <div class="d-flex justify-content-end gap-3">
                    @if ($editable === 'approve')
                        <button type="submit" form="transferStep" class="btn btn-primary">{{ __('messages.stock_transfer.approve') }}</button>
                    @elseif ($editable === 'send')
                        <button type="submit" form="transferStep" class="btn btn-primary">{{ $transfer->isSupplierReturn() ? __('messages.stock_transfer.send_supplier') : __('messages.stock_transfer.send') }}</button>
                    @endif
                    @if ($doReceive)
                        {{ Form::open(['route' => ['stock-transfers.receive', $transfer->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-success">{{ __('messages.stock_transfer.receive') }}</button>
                        {{ Form::close() }}
                    @endif
                    @if (in_array($status, ['requested', 'approved', 'sent'], true) && canAccessRoute('stock-transfers.cancel'))
                        {{ Form::open(['route' => ['stock-transfers.cancel', $transfer->id], 'method' => 'post']) }}
                        <button type="submit" class="btn btn-light-danger">{{ $status === 'requested' ? __('messages.stock_transfer.reject') : __('messages.stock_transfer.cancel') }}</button>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>

        @if ($moves->isNotEmpty())
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.stock.movements') }}</h5>
                    <table class="table table-row-dashed align-middle mb-0">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.stock.date') }}</th>
                                <th>{{ __('messages.stock.type') }}</th>
                                <th>{{ __('messages.stock.store') }}</th>
                                <th>{{ __('messages.medicine.medicine') }}</th>
                                <th>{{ __('messages.stock.batch') }}</th>
                                <th>{{ __('messages.stock.expiry') }}</th>
                                <th class="text-end">{{ __('messages.stock.quantity') }}</th>
                            </tr>
                        </thead>
                        @foreach ($moves as $move)
                            <tr>
                                <td>{{ $move->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                <td>{{ __('messages.stock.types.' . $move->movement_type) }}</td>
                                <td>{{ $move->store->name ?? '' }}</td>
                                <td>{{ $move->stockable->name ?? '' }}</td>
                                <td>{{ $move->batch->batch_no ?? '—' }}</td>
                                <td>{{ $move->batch?->expiry_date ? \Illuminate\Support\Carbon::parse($move->batch->expiry_date)->translatedFormat('d/m/Y') : '—' }}</td>
                                <td class="text-end {{ $move->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $move->quantity > 0 ? '+' : '' }}{{ $move->quantity }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
