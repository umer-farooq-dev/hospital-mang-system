@extends('layouts.app')
@section('title')
    {{ __('messages.purchase_order.levels') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div>
                @canroute('purchase-orders.reorder')<a href="{{ route('purchase-orders.reorder', ['store_id' => $storeId]) }}" class="btn btn-outline-primary">{{ __('messages.purchase_order.reorder') }}</a>@endcanroute
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('purchase-orders.levels.update');
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('store_id', $stores, $storeId, ['class' => 'form-select']) }}</div>
                    <div class="col-md-4"><input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('messages.medicine.medicine') }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="form-text mb-4">{{ __('messages.purchase_order.levels_hint') }}</div>
                {{ Form::open(['route' => 'purchase-orders.levels.update', 'method' => 'post']) }}
                {{ Form::hidden('store_id', $storeId) }}
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.medicine.medicine') }}</th>
                            <th class="text-end">{{ __('messages.stock.on_hand_short') }}</th>
                            <th class="w-150px">{{ __('messages.purchase_order.level') }}</th>
                            <th class="w-150px">{{ __('messages.purchase_order.reorder_quantity') }}</th>
                        </tr>
                    </thead>
                    @foreach ($medicines as $medicine)
                        @php
                            $level = $levels[$medicine->id] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $medicine->name }}</td>
                            <td class="text-end">{{ (int) ($onHand[$medicine->id] ?? 0) }}</td>
                            <td>{{ Form::number('levels[' . $medicine->id . '][reorder_level]', $level->reorder_level ?? 0, ['class' => 'form-control form-control-sm', 'min' => 0] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}</td>
                            <td>{{ Form::number('levels[' . $medicine->id . '][reorder_quantity]', $level->reorder_quantity ?? 0, ['class' => 'form-control form-control-sm', 'min' => 0] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($canEdit)
                    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
                {{ Form::close() }}
                {{ $medicines->links() }}
            </div>
        </div>
    </div>
@endsection
