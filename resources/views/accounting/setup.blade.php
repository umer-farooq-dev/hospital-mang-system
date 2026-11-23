@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.setup') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    @php
        $classes = collect(\App\Http\Controllers\AccountingSetupController::CLASSES)->mapWithKeys(fn ($c) => [$c => __('messages.accounting.classes.' . $c)])->all();
        $types = [1 => __('messages.accounting.debit'), 2 => __('messages.accounting.credit')];
        $parents = $accounts->where('is_postable', false)->mapWithKeys(fn ($a) => [$a->id => trim($a->code . ' ' . $a->name)])->all();
        $kinds = collect(\App\Models\Journal::KINDS)->mapWithKeys(fn ($k) => [$k => __('messages.accounting.kinds.' . $k)])->all();
        $statuses = collect(\App\Models\FiscalYear::STATUSES)->mapWithKeys(fn ($s) => [$s => __('messages.accounting.year_statuses.' . $s)])->all();
        $edit = fn ($route) => canAccessRoute($route) ? [] : ['disabled' => 'disabled'];
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')

        <div class="card mb-7">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.accounting.fiscal_years') }}</h5>
                <div class="form-text mb-4">{{ __('messages.accounting.years_hint') }}</div>
                {{ Form::open(['route' => 'accounting.years.update', 'method' => 'post']) }}
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.accounting.year') }}</th><th>{{ __('messages.stock_report.from') }}</th><th>{{ __('messages.stock_report.to') }}</th><th>{{ __('messages.stock_transfer.status') }}</th></tr></thead>
                    @foreach ($years->push(null) as $year)
                        @php
                            $f = $year ? 'years[' . $year->id . ']' : 'new';
                            $off = $edit('accounting.years.update') + ($year?->status === 'locked' ? ['disabled' => 'disabled'] : []);
                        @endphp
                        <tr class="{{ $year ? '' : 'bg-light-primary' }}">
                            <td>{{ Form::text($f . '[name]', $year?->name, ['class' => 'form-control form-control-sm', 'maxlength' => 60, 'placeholder' => $year ? '' : __('messages.accounting.new_year')] + $off) }}</td>
                            <td>{{ Form::date($f . '[starts_on]', $year?->starts_on?->toDateString(), ['class' => 'form-control form-control-sm'] + $off) }}</td>
                            <td>{{ Form::date($f . '[ends_on]', $year?->ends_on?->toDateString(), ['class' => 'form-control form-control-sm'] + $off) }}</td>
                            <td>@if ($year){{ Form::select($f . '[status]', $statuses, $year->status, ['class' => 'form-select form-select-sm'] + $off) }}@endif</td>
                        </tr>
                    @endforeach
                </table>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        {{ Form::label('accounting_lock_date', __('messages.accounting.lock_date') . ':', ['class' => 'form-label']) }}
                        {{ Form::date('accounting_lock_date', $lockDate ?: null, ['class' => 'form-control'] + $edit('accounting.years.update')) }}
                    </div>
                    <div class="col-md-7 form-text">{{ __('messages.accounting.lock_hint') }}</div>
                    @canroute('accounting.years.update')<div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.save') }}</button></div>@endcanroute
                </div>
                {{ Form::close() }}
            </div>
        </div>

        <div class="card mb-7">
            <div class="card-body">
                <h5 class="mb-4">{{ __('messages.accounting.journals') }}</h5>
                {{ Form::open(['route' => 'accounting.journals.update', 'method' => 'post']) }}
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.stock.code') }}</th><th>{{ __('messages.accounting.name') }}</th><th>{{ __('messages.stock.type') }}</th><th>{{ __('messages.common.active') }}</th></tr></thead>
                    @foreach ($journals->push(null) as $journal)
                        @php
                            $f = $journal ? 'journals[' . $journal->id . ']' : 'new';
                            $off = $edit('accounting.journals.update');
                        @endphp
                        <tr class="{{ $journal ? '' : 'bg-light-primary' }}">
                            <td>{{ Form::text($f . '[code]', $journal?->code, ['class' => 'form-control form-control-sm w-100px', 'maxlength' => 10, 'placeholder' => $journal ? '' : __('messages.accounting.new_journal')] + $off) }}</td>
                            <td>{{ Form::text($f . '[name]', $journal?->name, ['class' => 'form-control form-control-sm', 'maxlength' => 100] + $off) }}</td>
                            <td>{{ Form::select($f . '[kind]', $kinds, $journal?->kind ?? 'general', ['class' => 'form-select form-select-sm'] + $off) }}</td>
                            <td>@if ($journal)<input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $journal->is_active ? 'checked' : '' }} {{ $off ? 'disabled' : '' }}>@endif</td>
                        </tr>
                    @endforeach
                </table>
                @canroute('accounting.journals.update')<div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>@endcanroute
                {{ Form::close() }}
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.accounting.chart') }}</h5>
                <div class="form-text mb-4">{{ __('messages.accounting.chart_hint') }}</div>
                {{ Form::open(['route' => 'accounting.accounts.update', 'method' => 'post']) }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr class="text-muted fs-7 text-uppercase"><th class="w-125px">{{ __('messages.stock.code') }}</th><th>{{ __('messages.accounting.name') }}</th><th>{{ __('messages.accounting.class') }}</th><th>{{ __('messages.accounting.normal_side') }}</th><th>{{ __('messages.accounting.parent') }}</th><th>{{ __('messages.accounting.postable') }}</th><th>{{ __('messages.common.active') }}</th></tr></thead>
                        @foreach ($accounts->push(null) as $account)
                            @php
                                $f = $account ? 'accounts[' . $account->id . ']' : 'new';
                                $off = $edit('accounting.accounts.update');
                            @endphp
                            <tr class="{{ $account ? '' : 'bg-light-primary' }} {{ $account && ! $account->is_postable ? 'fw-bold' : '' }}">
                                <td>{{ Form::text($f . '[code]', $account?->code, ['class' => 'form-control form-control-sm', 'maxlength' => 20, 'placeholder' => $account ? '' : __('messages.accounting.new_account')] + $off) }}</td>
                                <td>{{ Form::text($f . '[name]', $account?->name, ['class' => 'form-control form-control-sm', 'maxlength' => 160] + $off) }}</td>
                                <td>{{ Form::select($f . '[account_class]', $classes, $account?->account_class, ['class' => 'form-select form-select-sm', 'placeholder' => '—'] + $off) }}</td>
                                <td>{{ Form::select($f . '[type]', $types, $account?->type ?? 1, ['class' => 'form-select form-select-sm'] + $off) }}</td>
                                <td>{{ Form::select($f . '[parent_id]', $parents, $account?->parent_id, ['class' => 'form-select form-select-sm', 'placeholder' => '—'] + $off) }}</td>
                                <td><input type="checkbox" class="form-check-input" name="{{ $f }}[is_postable]" value="1" {{ ($account?->is_postable ?? true) ? 'checked' : '' }} {{ $off ? 'disabled' : '' }}></td>
                                <td>@if ($account)<input type="checkbox" class="form-check-input" name="{{ $f }}[status]" value="1" {{ $account->status ? 'checked' : '' }} {{ $off ? 'disabled' : '' }}>@endif</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @canroute('accounting.accounts.update')<div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>@endcanroute
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
