@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.ledger') }}{{ $account ? ' · ' . trim($account->code . ' ' . $account->name) : '' }}
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
        $balance = $opening;
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-5">{{ Form::select('account_id', $accounts, $account?->id, ['class' => 'form-select', 'required', 'placeholder' => __('messages.accounting.account'), 'data-control' => 'select2']) }}</div>
                    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                @if ($account)
                    <table class="table table-row-dashed align-middle">
                        <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.stock.date') }}</th><th>{{ __('messages.claims.number') }}</th><th>{{ __('messages.accounting.journal') }}</th><th>{{ __('messages.accounting.label') }}</th><th class="text-end">{{ __('messages.accounting.debit') }}</th><th class="text-end">{{ __('messages.accounting.credit') }}</th><th class="text-end">{{ __('messages.stock.balance') }}</th></tr></thead>
                        <tbody>
                            <tr class="text-muted"><td colspan="6">{{ __('messages.claim_settlement.opening') }}</td><td class="text-end">{{ formatMoney($opening) }}</td></tr>
                            @foreach ($lines as $line)
                                @php
                                    $balance += $line->debit - $line->credit;
                                @endphp
                                <tr>
                                    <td>{{ $line->entry->entry_date->format('d/m/Y') }}</td>
                                    <td><a href="{{ route('accounting.entries.show', $line->journal_entry_id) }}">{{ $line->entry->number }}</a></td>
                                    <td>{{ $line->entry->journal->code ?? '' }}</td>
                                    <td>{{ $line->label ?: $line->entry->label }}</td>
                                    <td class="text-end">{{ $line->debit ? formatMoney($line->debit) : '' }}</td>
                                    <td class="text-end">{{ $line->credit ? formatMoney($line->credit) : '' }}</td>
                                    <td class="text-end fw-bold">{{ formatMoney($balance) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($lines->sum('debit')) }}</td><td class="text-end">{{ formatMoney($lines->sum('credit')) }}</td><td class="text-end">{{ formatMoney($balance) }}</td></tr></tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
