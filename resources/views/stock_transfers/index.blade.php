@extends('layouts.app')
@section('title')
    {{ __('messages.stock_transfer.transfers') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('stock-transfers.create')
                <div class="mt-3 mt-md-0">
                    @foreach (\App\Models\StockTransfer::KINDS as $kind)
                        <a href="{{ route('stock-transfers.create', ['kind' => $kind]) }}" class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline-primary' }} mb-2">{{ __('messages.stock_transfer.new.' . $kind) }}</a>
                    @endforeach
                </div>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('store_id', $stores, $storeId ?: null, ['class' => 'form-select', 'placeholder' => __('messages.stock.all_stores')]) }}</div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">{{ __('messages.stock_transfer.all_statuses') }}</option>
                            @foreach (\App\Models\StockTransfer::STATUSES as $option)
                                <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ __('messages.stock_transfer.statuses.' . $option) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.stock_transfer.number') }}</th>
                                <th>{{ __('messages.stock.type') }}</th>
                                <th>{{ __('messages.stock_transfer.from') }}</th>
                                <th>{{ __('messages.stock_transfer.to') }}</th>
                                <th>{{ __('messages.stock.date') }}</th>
                                <th>{{ __('messages.stock_transfer.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfers as $transfer)
                                <tr>
                                    <td><a href="{{ route('stock-transfers.show', $transfer->id) }}">{{ $transfer->number }}</a></td>
                                    <td>{{ __('messages.stock_transfer.kinds.' . $transfer->kind) }}</td>
                                    <td>{{ $transfer->fromStore->name ?? '' }}</td>
                                    <td>{{ $transfer->toStore->name ?? ($transfer->supplier->name ?? '') }}</td>
                                    <td>{{ $transfer->requested_at?->translatedFormat('d/m/Y H:i') }}</td>
                                    <td>@include('stock_transfers.status', ['status' => $transfer->status])</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('messages.stock_transfer.none') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $transfers->links() }}
            </div>
        </div>
    </div>
@endsection
