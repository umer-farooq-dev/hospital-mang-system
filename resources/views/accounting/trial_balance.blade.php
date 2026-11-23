@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.trial_balance') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @include('layouts.export_button')
            <button type="button" class="btn btn-outline-primary d-print-none" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
        </div>
    </div>
@endsection
@section('content')
    @php
        $sum = fn ($key) => $rows->sum($key);
        $balanced = abs($sum('debit') - $sum('credit')) < 0.005;
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="mb-3 {{ $balanced ? 'text-success' : 'text-danger fw-bold' }}">{{ $balanced ? __('messages.accounting.balanced') : __('messages.accounting.not_balanced') }} · {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</div>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.accounting.account') }}</th><th class="text-end">{{ __('messages.accounting.debit') }}</th><th class="text-end">{{ __('messages.accounting.credit') }}</th><th class="text-end">{{ __('messages.accounting.balance_debit') }}</th><th class="text-end">{{ __('messages.accounting.balance_credit') }}</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>@canroute('accounting.ledger')<a href="{{ route('accounting.ledger', ['account_id' => $row['account']->id, 'from' => $from, 'to' => $to]) }}">{{ $row['account']->code }} {{ $row['account']->name }}</a>@else{{ $row['account']->code }} {{ $row['account']->name }}@endcanroute</td>
                                <td class="text-end">{{ formatMoney($row['debit']) }}</td>
                                <td class="text-end">{{ formatMoney($row['credit']) }}</td>
                                <td class="text-end">{{ $row['balance_debit'] ? formatMoney($row['balance_debit']) : '' }}</td>
                                <td class="text-end">{{ $row['balance_credit'] ? formatMoney($row['balance_credit']) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('messages.accounting.no_entries') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($sum('debit')) }}</td><td class="text-end">{{ formatMoney($sum('credit')) }}</td><td class="text-end">{{ formatMoney($sum('balance_debit')) }}</td><td class="text-end">{{ formatMoney($sum('balance_credit')) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
