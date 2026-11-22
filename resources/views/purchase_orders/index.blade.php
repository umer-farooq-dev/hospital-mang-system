@extends('layouts.app')
@section('title')
    {{ __('messages.purchase_order.orders') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="mt-3 mt-md-0">
                @canroute('purchase-orders.reorder')<a href="{{ route('purchase-orders.reorder') }}" class="btn btn-outline-primary mb-2">{{ __('messages.purchase_order.reorder') }}</a>@endcanroute
                @canroute('purchase-orders.levels')<a href="{{ route('purchase-orders.levels') }}" class="btn btn-outline-primary mb-2">{{ __('messages.purchase_order.levels') }}</a>@endcanroute
                @canroute('purchase-orders.create')<a href="{{ route('purchase-orders.create') }}" class="btn btn-primary mb-2">{{ __('messages.purchase_order.new') }}</a>@endcanroute
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('messages.stock_transfer.all_statuses') }}</option>
                            @foreach (\App\Models\PurchaseOrder::STATUSES as $option)
                                <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ __('messages.purchase_order.statuses.' . $option) }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.stock_transfer.number') }}</th>
                            <th>{{ __('messages.stock.supplier') }}</th>
                            <th>{{ __('messages.stock.store') }}</th>
                            <th>{{ __('messages.stock.date') }}</th>
                            <th>{{ __('messages.purchase_order.expected') }}</th>
                            <th class="text-end">{{ __('messages.purchase_order.total') }}</th>
                            <th>{{ __('messages.stock_transfer.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><a href="{{ route('purchase-orders.show', $order->id) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->supplier->name ?? '' }}</td>
                                <td>{{ $order->store->name ?? '' }}</td>
                                <td>{{ $order->created_at->translatedFormat('d/m/Y') }}</td>
                                <td>{{ $order->expected_date ? \Illuminate\Support\Carbon::parse($order->expected_date)->translatedFormat('d/m/Y') : '—' }}</td>
                                <td class="text-end">{{ formatMoney($order->total()) }}</td>
                                <td>@include('purchase_orders.status', ['status' => $order->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('messages.purchase_order.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection
