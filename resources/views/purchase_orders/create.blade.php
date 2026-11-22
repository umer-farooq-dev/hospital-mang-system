@extends('layouts.app')
@section('title')
    {{ __('messages.purchase_order.new') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'purchase-orders.store', 'method' => 'post']) }}
                <div class="row">
                    <div class="col-md-3 mb-5">
                        {{ Form::label('supplier_id', __('messages.stock.supplier') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('supplier_id', $suppliers, old('supplier_id'), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                    </div>
                    <div class="col-md-3 mb-5">
                        {{ Form::label('store_id', __('messages.stock.receive_into') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('store_id', $stores, old('store_id', \App\Utils\Stock::defaultStoreId()), ['class' => 'form-select', 'required']) }}
                    </div>
                    <div class="col-md-2 mb-5">
                        {{ Form::label('expected_date', __('messages.purchase_order.expected') . ':', ['class' => 'form-label']) }}
                        {{ Form::date('expected_date', old('expected_date'), ['class' => 'form-control']) }}
                    </div>
                    <div class="col-md-4 mb-5">
                        {{ Form::label('note', __('messages.stock_transfer.note') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('note', old('note'), ['class' => 'form-control', 'maxlength' => 500]) }}
                    </div>
                </div>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.medicine.medicine') }}</th>
                            <th class="w-150px">{{ __('messages.stock.quantity') }}</th>
                            <th class="w-150px">{{ __('messages.stock.unit_cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 20; $i++)
                            <tr class="order-line {{ $i >= 3 && ! old('medicine.' . $i) ? 'd-none' : '' }}">
                                <td>{{ Form::select('medicine[]', $medicines, old('medicine.' . $i), ['class' => 'form-select', 'placeholder' => __('messages.medicine_bills.select_medicine'),
                                    'onchange' => "var c=this.closest('tr').querySelector('.unit-cost'); if (!c.value) { c.value = (window.poCosts || {})[this.value] || ''; }"]) }}</td>
                                <td>{{ Form::number('quantity[]', old('quantity.' . $i), ['class' => 'form-control', 'min' => 0]) }}</td>
                                <td>{{ Form::number('unit_cost[]', old('unit_cost.' . $i), ['class' => 'form-control unit-cost', 'min' => 0, 'step' => '0.01']) }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-light-primary" onclick="var r=document.querySelector('.order-line.d-none'); if (r) { r.classList.remove('d-none'); }">{{ __('messages.stock_transfer.add_line') }}</button>
                    {{ Form::submit(__('messages.purchase_order.place'), ['class' => 'btn btn-primary']) }}
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
    <script>window.poCosts = @json($costs);</script>
@endsection
