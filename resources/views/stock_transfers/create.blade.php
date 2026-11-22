@extends('layouts.app')
@section('title')
    {{ __('messages.stock_transfer.new.' . $kind) }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('stock-transfers.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $isRequisition = $kind === 'requisition';
        $rows = 15;
    @endphp
    <div class="container-fluid">
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                <div class="form-text mb-5">{{ __('messages.stock_transfer.hints.' . $kind) }}</div>
                {{ Form::open(['route' => 'stock-transfers.store', 'method' => 'post']) }}
                {{ Form::hidden('kind', $kind) }}
                <div class="row">
                    <div class="col-md-4 mb-5">
                        {{ Form::label('from_store_id', __('messages.stock_transfer.from') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('from_store_id', $isRequisition ? $allStores : $myStores, old('from_store_id', $isRequisition ? null : \App\Utils\Stock::defaultStoreId()), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                    </div>
                    <div class="col-md-4 mb-5">
                        @if ($kind === 'supplier_return')
                            {{ Form::label('supplier_id', __('messages.stock.supplier') . ':', ['class' => 'form-label required']) }}
                            {{ Form::select('supplier_id', $suppliers, old('supplier_id'), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                        @else
                            {{ Form::label('to_store_id', __('messages.stock_transfer.to') . ':', ['class' => 'form-label required']) }}
                            {{ Form::select('to_store_id', $isRequisition ? $myStores : $allStores, old('to_store_id'), ['class' => 'form-select', 'required', 'placeholder' => __('messages.common.choose')]) }}
                        @endif
                    </div>
                    <div class="col-md-4 mb-5">
                        {{ Form::label('note', __('messages.stock_transfer.note') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('note', old('note'), ['class' => 'form-control', 'maxlength' => 500]) }}
                    </div>
                </div>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.medicine.medicine') }} <span class="text-muted text-lowercase">({{ __('messages.stock_transfer.total_in_stock') }})</span></th>
                            <th class="w-150px">{{ __('messages.stock.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $rows; $i++)
                            <tr class="transfer-line {{ $i >= 3 && ! old('medicine.' . $i) ? 'd-none' : '' }}">
                                <td>{{ Form::select('medicine[]', $medicines, old('medicine.' . $i), ['class' => 'form-select', 'placeholder' => __('messages.medicine_bills.select_medicine')]) }}</td>
                                <td>{{ Form::number('quantity[]', old('quantity.' . $i), ['class' => 'form-control', 'min' => 0]) }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-light-primary" onclick="var r=document.querySelector('.transfer-line.d-none'); if (r) { r.classList.remove('d-none'); }">{{ __('messages.stock_transfer.add_line') }}</button>
                    {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
