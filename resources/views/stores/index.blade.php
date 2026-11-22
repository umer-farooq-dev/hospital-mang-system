@extends('layouts.app')
@section('title')
    {{ __('messages.stock.stores') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('stock.index')<a href="{{ route('stock.index') }}" class="btn btn-outline-primary">{{ __('messages.stock.stock') }}</a>@endcanroute
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('stores.update');
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <div class="form-text mb-5">{{ __('messages.stock.users_hint') }}
                    @canroute('master-lists.index') · <a href="{{ route('master-lists.index', ['type' => 'store_type']) }}">{{ __('messages.master_list.types.store_type') }}</a>@endcanroute
                </div>
                {{ Form::open(['route' => 'stores.update', 'method' => 'post']) }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.stock.store') }}</th>
                                <th>{{ __('messages.stock.code') }}</th>
                                <th>{{ __('messages.stock.type') }}</th>
                                <th class="min-w-250px">{{ __('messages.stock.users') }}</th>
                                <th>{{ __('messages.stock.sort') }}</th>
                                <th>{{ __('messages.stock.can_sell') }}</th>
                                <th>{{ __('messages.common.active') }}</th>
                            </tr>
                        </thead>
                        @foreach ($stores->push(null) as $store)
                            @continue(! $store && ! $canEdit)
                            @php
                                $f = $store ? 'stores[' . $store->id . ']' : 'new';
                                $off = $canEdit ? [] : ['disabled' => 'disabled'];
                            @endphp
                            <tr class="{{ $store ? '' : 'bg-light-primary' }}">
                                <td>{{ Form::text($f . '[name]', $store?->name, ['class' => 'form-control form-control-sm', 'maxlength' => 150, 'placeholder' => $store ? '' : __('messages.stock.new_store')] + ($store ? ['required' => 'required'] : []) + $off) }}</td>
                                <td>{{ Form::text($f . '[code]', $store?->code, ['class' => 'form-control form-control-sm', 'maxlength' => 30] + $off) }}</td>
                                <td>{{ Form::select($f . '[store_type]', \App\Models\MasterListItem::options('store_type', $store?->store_type), $store?->store_type, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.common.choose')] + $off) }}</td>
                                <td>{{ Form::select($f . '[users][]', $users, $store ? $store->users->pluck('id')->all() : [], ['class' => 'form-select form-select-sm', 'multiple' => 'multiple', 'data-control' => 'select2'] + $off) }}</td>
                                <td>{{ Form::number($f . '[sort_order]', $store?->sort_order ?? 0, ['class' => 'form-control form-control-sm w-80px', 'min' => 0] + $off) }}</td>
                                <td><input type="checkbox" class="form-check-input" name="{{ $f }}[can_sell]" value="1" {{ $store?->can_sell ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}></td>
                                <td>@if ($store)<input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $store->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>@endif</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @if ($canEdit)
                    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
                {{ Form::close() }}
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-5">{{ __('messages.stock.settings') }}</h5>
                {{ Form::open(['route' => 'stores.settings', 'method' => 'post']) }}
                <div class="row">
                    <div class="col-md-6 mb-5">
                        <label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="stock_fefo" value="1" {{ ($settings['stock_fefo'] ?? '1') === '1' ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                            <span class="form-check-label">{{ __('messages.stock.fefo') }}</span></label>
                        <div class="form-text">{{ __('messages.stock.fefo_hint') }}</div>
                    </div>
                    <div class="col-md-6 mb-5">
                        <label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="stock_allow_negative" value="1" {{ ($settings['stock_allow_negative'] ?? '0') === '1' ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                            <span class="form-check-label">{{ __('messages.stock.allow_negative') }}</span></label>
                        <div class="form-text">{{ __('messages.stock.allow_negative_hint') }}</div>
                    </div>
                    <div class="col-md-6 mb-5">
                        <label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="stock_sell_expired" value="1" {{ ($settings['stock_sell_expired'] ?? '0') === '1' ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                            <span class="form-check-label">{{ __('messages.stock.sell_expired') }}</span></label>
                    </div>
                    <div class="col-md-3 mb-5">
                        {{ Form::label('stock_expiry_alert_days', __('messages.stock.expiry_alert_days') . ':', ['class' => 'form-label']) }}
                        {{ Form::number('stock_expiry_alert_days', $settings['stock_expiry_alert_days'] ?? 90, ['class' => 'form-control', 'min' => 0, 'max' => 3650, 'required'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                    </div>
                    <div class="col-md-3 mb-5">
                        {{ Form::label('pharmacy_default_store', __('messages.stock.default_store') . ':', ['class' => 'form-label']) }}
                        {{ Form::select('pharmacy_default_store', $stores->filter()->where('is_active', true)->pluck('name', 'id'), $settings['pharmacy_default_store'] ?? null, ['class' => 'form-select', 'required'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                        <div class="form-text">{{ __('messages.stock.default_store_hint') }}</div>
                    </div>
                </div>
                @if ($canEdit)
                    <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                @endif
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
