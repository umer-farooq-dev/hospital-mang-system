@extends('layouts.app')
@section('title')
    {{ __('messages.claim_settlement.statement') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title'){{ $insuranceId ? ' · ' . ($insurances[$insuranceId] ?? '') : '' }}</h1>
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
                    <div class="col-md-4">{{ Form::select('insurance_id', $insurances, $insuranceId ?: null, ['class' => 'form-select', 'required', 'placeholder' => __('messages.claims.payer')]) }}</div>
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                @if ($insuranceId)
                    <div class="text-muted mb-3">{{ __('messages.claims.period') }}: {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</div>
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.stock.date') }}</th>
                                <th>{{ __('messages.claim_settlement.entry') }}</th>
                                <th>{{ __('messages.claims.number') }}</th>
                                <th class="text-end">{{ __('messages.claim_settlement.debit') }}</th>
                                <th class="text-end">{{ __('messages.claim_settlement.credit') }}</th>
                                <th class="text-end">{{ __('messages.stock.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-muted"><td colspan="5">{{ __('messages.claim_settlement.opening') }}</td><td class="text-end">{{ formatMoney($opening) }}</td></tr>
                            @forelse ($entries as $entry)
                                @php
                                    $balance += $entry['amount'];
                                @endphp
                                <tr>
                                    <td>{{ $entry['date']->format('d/m/Y') }}</td>
                                    <td>{{ $entry['label'] }}</td>
                                    <td>@canroute('claims.show')<a href="{{ route('claims.show', $entry['invoice_id']) }}">{{ $entry['number'] }}</a>@else{{ $entry['number'] }}@endcanroute</td>
                                    <td class="text-end">{{ $entry['amount'] > 0 ? formatMoney($entry['amount']) : '' }}</td>
                                    <td class="text-end">{{ $entry['amount'] < 0 ? formatMoney(-$entry['amount']) : '' }}</td>
                                    <td class="text-end fw-bold">{{ formatMoney($balance) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('messages.stock.no_movements') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="5" class="text-end">{{ __('messages.claim_settlement.closing') }}</td><td class="text-end">{{ formatMoney($balance) }}</td></tr></tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
