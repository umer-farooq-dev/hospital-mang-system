@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.cash_book') }}{{ $account ? ' · ' . trim($account->code . ' ' . $account->name) : '' }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <button type="button" class="btn btn-outline-primary d-print-none" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
        </div>
    </div>
@endsection
@section('content')
    @php
        $balance = $opening;
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-4">{{ Form::select('account_id', $accounts, $account?->id, ['class' => 'form-select']) }}</div>
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.stock.date') }}</th><th class="text-end">{{ __('messages.stock.in') }}</th><th class="text-end">{{ __('messages.stock.out') }}</th><th class="text-end">{{ __('messages.stock.balance') }}</th></tr></thead>
                    <tbody>
                        <tr class="text-muted"><td colspan="3">{{ __('messages.claim_settlement.opening') }}</td><td class="text-end">{{ formatMoney($opening) }}</td></tr>
                        @foreach ($days as $day)
                            @php
                                $balance += $day->money_in - $day->money_out;
                            @endphp
                            <tr>
                                <td>@canroute('accounting.ledger')<a href="{{ route('accounting.ledger', ['account_id' => $account->id, 'from' => $day->day, 'to' => $day->day]) }}">{{ \Illuminate\Support\Carbon::parse($day->day)->format('d/m/Y') }}</a>@else{{ \Illuminate\Support\Carbon::parse($day->day)->format('d/m/Y') }}@endcanroute</td>
                                <td class="text-end">{{ formatMoney($day->money_in) }}</td>
                                <td class="text-end">{{ formatMoney($day->money_out) }}</td>
                                <td class="text-end fw-bold">{{ formatMoney($balance) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($days->sum('money_in')) }}</td><td class="text-end">{{ formatMoney($days->sum('money_out')) }}</td><td class="text-end">{{ formatMoney($balance) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
