@extends('layouts.app')
@section('title')
    {{ __('messages.cashier.counters') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('cashier.nav')
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('cash-counters.update');
        $disabled = $canEdit ? '' : 'disabled';
        $counterOptions = $counters->mapWithKeys(fn ($c) => [$c->id => $c->code . ' · ' . $c->name])->all();
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            @include('layouts.errors')
            {{ Form::open(['route' => 'cash-counters.update', 'method' => 'post']) }}

            <div class="card mb-7">
                <div class="card-header border-0 pt-6">
                    <div>
                        <h3 class="card-title fw-bold mb-1">{{ __('messages.cashier.counters') }}</h3>
                        <div class="text-muted fs-7">{{ __('messages.cashier.counters_hint') }}</div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th style="width: 110px">{{ __('messages.cashier.code') }}</th>
                                    <th>{{ __('messages.cashier.name') }}</th>
                                    <th class="text-center">{{ __('messages.cashier.reception_counter') }}</th>
                                    <th>{{ __('messages.cashier.account_cash') }}</th>
                                    <th>{{ __('messages.cashier.account_cheque') }}</th>
                                    <th>{{ __('messages.cashier.account_electronic') }}</th>
                                    <th class="text-center">{{ __('messages.common.active') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($counters->push(null) as $counter)
                                    @continue(! $counter && ! $canEdit)
                                    @php($f = $counter ? 'counters[' . $counter->id . ']' : 'new')
                                    <tr class="{{ $counter ? '' : 'bg-light' }}">
                                        <td><input type="text" class="form-control form-control-sm" name="{{ $f }}[code]" value="{{ $counter?->code }}" maxlength="20" placeholder="{{ $counter ? '' : __('messages.common.add') }}" {{ $counter ? 'required' : '' }} {{ $disabled }}></td>
                                        <td><input type="text" class="form-control form-control-sm" name="{{ $f }}[name]" value="{{ $counter?->name }}" maxlength="100" {{ $counter ? 'required' : '' }} {{ $disabled }}></td>
                                        <td class="text-center"><input type="checkbox" class="form-check-input" name="{{ $f }}[is_reception]" value="1" {{ $counter?->is_reception ? 'checked' : '' }} {{ $disabled }}></td>
                                        @foreach (['account_cash', 'account_cheque', 'account_electronic'] as $account)
                                            <td><input type="text" class="form-control form-control-sm" name="{{ $f }}[{{ $account }}]" value="{{ $counter?->$account }}" maxlength="30" {{ $disabled }}></td>
                                        @endforeach
                                        <td class="text-center">
                                            @if ($counter)
                                                <input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $counter->is_active ? 'checked' : '' }} {{ $disabled }}>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-7 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <div>
                                <h3 class="card-title fw-bold mb-1">{{ __('messages.cashier.users_per_counter') }}</h3>
                                <div class="text-muted fs-7">{{ __('messages.cashier.users_hint') }}</div>
                            </div>
                        </div>
                        <div class="card-body pt-0" style="max-height: 480px; overflow-y: auto">
                            <table class="table align-middle table-row-dashed">
                                @foreach ($users as $staff)
                                    <tr>
                                        <td>{{ $staff->first_name }} {{ $staff->last_name }}</td>
                                        <td style="width: 55%">
                                            {{ Form::select('user_counter[' . $staff->id . ']', $counterOptions, $staff->cash_counter_id, ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.cashier.no_counter_short')] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">{{ __('messages.cashier.receipt_settings') }}</h3>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-5">
                                {{ Form::label('receipt_start_number', __('messages.cashier.receipt_start_number') . ':', ['class' => 'form-label']) }}
                                {{ Form::number('receipt_start_number', $settings['receipt_start_number'] ?? 1, ['class' => 'form-control', 'min' => 1, 'required'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                <div class="form-text">{{ __('messages.cashier.receipt_start_hint') }}</div>
                            </div>
                            <div class="mb-5">
                                {{ Form::label('receipt_paper', __('messages.cashier.receipt_paper') . ':', ['class' => 'form-label']) }}
                                {{ Form::select('receipt_paper', collect(\App\Http\Controllers\CashCounterController::PAPER_SIZES)->mapWithKeys(fn ($p) => [$p => __('messages.cashier.paper.' . $p)]), $settings['receipt_paper'] ?? 'a5', ['class' => 'form-select'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                            </div>
                            <div class="mb-5">
                                {{ Form::label('receipt_followup_days', __('messages.cashier.receipt_followup_days') . ':', ['class' => 'form-label']) }}
                                {{ Form::number('receipt_followup_days', $settings['receipt_followup_days'] ?? 14, ['class' => 'form-control', 'min' => 0, 'max' => 365, 'required'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                <div class="form-text">{{ __('messages.cashier.receipt_followup_hint') }}</div>
                            </div>
                            <div class="mb-5">
                                {{ Form::label('receipt_footer_text', __('messages.cashier.receipt_footer_text') . ':', ['class' => 'form-label']) }}
                                {{ Form::textarea('receipt_footer_text', $settings['receipt_footer_text'] ?? '', ['class' => 'form-control', 'rows' => 3, 'maxlength' => 500, 'placeholder' => __('messages.cashier.followup_footer'), ] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                <div class="form-text">{{ __('messages.cashier.receipt_footer_hint') }}</div>
                            </div>
                            <div class="mb-5">
                                {{ Form::label('cash_payment_method', __('messages.cashier.cash_payment_method') . ':', ['class' => 'form-label']) }}
                                {{ Form::select('cash_payment_method', \App\Models\PaymentMethod::options([], $settings['cash_payment_method'] ?? 1), $settings['cash_payment_method'] ?? 1, ['class' => 'form-select'] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                                <div class="form-text">{{ __('messages.cashier.cash_payment_method_hint') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <div class="d-flex justify-content-end">
                    {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                </div>
            @endif
            {{ Form::close() }}
        </div>
    </div>
@endsection
