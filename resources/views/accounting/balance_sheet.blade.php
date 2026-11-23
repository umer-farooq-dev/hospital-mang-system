@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.balance_sheet') }}
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
        $assetTotal = $assets->sum('amount');
        $rightTotal = $liabilities->sum('amount') + $equity->sum('amount') + $current + $previous;
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-3"><input type="date" name="at" value="{{ $at }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="text-muted mb-4">{{ __('messages.accounting.at', ['date' => \Illuminate\Support\Carbon::parse($at)->format('d/m/Y')]) }}
                    @if ($unclassified) · <span class="text-danger">{{ __('messages.accounting.unclassified', ['count' => $unclassified]) }}</span>@endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ __('messages.accounting.classes.asset') }}</h5>
                        <table class="table table-row-dashed align-middle">
                            @foreach ($assets as $row)
                                <tr><td>{{ $row['account']->code }} {{ $row['account']->name }}</td><td class="text-end">{{ formatMoney($row['amount']) }}</td></tr>
                            @endforeach
                            <tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($assetTotal) }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>{{ __('messages.accounting.classes.liability') }} / {{ __('messages.accounting.classes.equity') }}</h5>
                        <table class="table table-row-dashed align-middle">
                            @foreach ($liabilities->concat($equity) as $row)
                                <tr><td>{{ $row['account']->code }} {{ $row['account']->name }}</td><td class="text-end">{{ formatMoney($row['amount']) }}</td></tr>
                            @endforeach
                            @if (abs($previous) >= 0.005)<tr><td>{{ __('messages.accounting.previous_result') }}</td><td class="text-end">{{ formatMoney($previous) }}</td></tr>@endif
                            <tr><td>{{ __('messages.accounting.current_result') }}</td><td class="text-end">{{ formatMoney($current) }}</td></tr>
                            <tr class="fw-bold"><td>{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($rightTotal) }}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="{{ abs($assetTotal - $rightTotal) < 0.005 ? 'text-success' : 'text-danger fw-bold' }}">{{ abs($assetTotal - $rightTotal) < 0.005 ? __('messages.accounting.sheet_balanced') : __('messages.accounting.sheet_not_balanced') }}</div>
            </div>
        </div>
    </div>
@endsection
