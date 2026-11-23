@extends('layouts.app')
@section('title')
    {{ __('messages.cash_journal.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('layouts.export_button')
            @include('cashier.nav')
        </div>
    </div>
@endsection
@section('content')
    @php
        $query = array_filter([
            'from' => $filters['from']->toDateString(), 'to' => $filters['to']->toDateString(),
            'counter' => $filters['counter'], 'session' => $filters['session']?->id,
        ]);
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('cash-journal.index') }}" class="row g-3 align-items-end mb-6">
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.from') }}</label>
                            <input type="date" name="from" class="form-control" value="{{ $query['from'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.reception_report.to') }}</label>
                            <input type="date" name="to" class="form-control" value="{{ $query['to'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.cashier.counter') }}</label>
                            {{ Form::select('counter', $counters, $filters['counter'], ['class' => 'form-select']) }}
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.cash_journal.closing_number') }}</label>
                            {{ Form::select('session', $sessions, $filters['session']?->id, ['class' => 'form-select', 'placeholder' => __('messages.cash_journal.whole_period')]) }}
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            <a href="{{ route('cash-journal.print', $query) }}" target="_blank" class="btn btn-light-primary" title="{{ __('messages.cash_journal.print') }}"><i class="fas fa-print"></i></a>
                        </div>
                    </form>
                    <div class="mb-4 fs-6">{{ __('messages.cash_journal.opening_balance') }}: <b>{{ formatMoney($journal['opening']) }}</b></div>
                    <div class="table-responsive">
                        @include('cash_journal.table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
