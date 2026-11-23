@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.profit_loss') }}
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
        $totalIncome = $income->sum('amount');
        $totalExpense = $expense->sum('amount');
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="text-muted mb-4">{{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</div>
                <div class="row">
                    @foreach (['income' => $income, 'expense' => $expense] as $side => $rows)
                        <div class="col-md-6">
                            <h5>{{ __('messages.accounting.classes.' . $side) }}</h5>
                            <table class="table table-row-dashed align-middle">
                                @forelse ($rows as $row)
                                    <tr><td>{{ $row['account']->code }} {{ $row['account']->name }}</td><td class="text-end">{{ formatMoney($row['amount']) }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">—</td></tr>
                                @endforelse
                                <tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($side === 'income' ? $totalIncome : $totalExpense) }}</td></tr>
                            </table>
                        </div>
                    @endforeach
                </div>
                <div class="fs-4 fw-bold text-end {{ $totalIncome - $totalExpense < 0 ? 'text-danger' : 'text-success' }}">{{ __('messages.accounting.result') }}: {{ formatMoney($totalIncome - $totalExpense) }}</div>
            </div>
        </div>
    </div>
@endsection
