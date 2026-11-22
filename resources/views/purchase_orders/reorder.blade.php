@extends('layouts.app')
@section('title')
    {{ __('messages.purchase_order.reorder') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div>
                @canroute('purchase-orders.levels')<a href="{{ route('purchase-orders.levels', ['store_id' => $storeId]) }}" class="btn btn-outline-primary">{{ __('messages.purchase_order.levels') }}</a>@endcanroute
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('store_id', $stores, $storeId, ['class' => 'form-select', 'onchange' => 'this.form.submit()']) }}</div>
                </form>
                <div class="form-text mb-4">{{ __('messages.purchase_order.reorder_hint') }}</div>
                @if ($rows->isEmpty())
                    <div class="text-center text-muted py-5">{{ __('messages.purchase_order.nothing_to_reorder') }}</div>
                @else
                    {{ Form::open(['route' => 'purchase-orders.store', 'method' => 'post']) }}
                    {{ Form::hidden('store_id', $storeId) }}
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.medicine.medicine') }}</th>
                                <th class="text-end">{{ __('messages.stock.on_hand_short') }}</th>
                                <th class="text-end">{{ __('messages.purchase_order.level') }}</th>
                                <th class="text-end">{{ __('messages.purchase_order.on_order') }}</th>
                                <th class="w-125px">{{ __('messages.purchase_order.to_order') }}</th>
                                <th class="w-125px">{{ __('messages.stock.unit_cost') }}</th>
                            </tr>
                        </thead>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['medicine']->name }}{{ Form::hidden('medicine[]', $row['medicine']->id) }}</td>
                                <td class="text-end {{ $row['on_hand'] <= 0 ? 'text-danger fw-bold' : '' }}">{{ $row['on_hand'] }}</td>
                                <td class="text-end">{{ $row['level'] }}</td>
                                <td class="text-end">{{ $row['on_order'] ?: '—' }}</td>
                                <td>{{ Form::number('quantity[]', $row['suggested'], ['class' => 'form-control form-control-sm', 'min' => 0]) }}</td>
                                <td>{{ Form::number('unit_cost[]', $row['medicine']->buying_price, ['class' => 'form-control form-control-sm', 'min' => 0, 'step' => '0.01']) }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @canroute('purchase-orders.store')
                        <div class="row g-3 justify-content-end align-items-end">
                            <div class="col-md-4">
                                {{ Form::label('supplier_id', __('messages.stock.supplier') . ':', ['class' => 'form-label required']) }}
                                {{ Form::select('supplier_id', $suppliers, null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                            </div>
                            <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">{{ __('messages.purchase_order.make_order') }}</button></div>
                        </div>
                    @endcanroute
                    {{ Form::close() }}
                @endif
            </div>
        </div>
    </div>
@endsection
