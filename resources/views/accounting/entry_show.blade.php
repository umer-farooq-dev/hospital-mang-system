@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.entry') }} {{ $entry->number }}
@endsection
@section('header_toolbar')
    <div class="container-fluid d-print-none">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                <a href="{{ route('accounting.entries.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.common.back') }}</a>
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-5 mb-5">
                    <div><span class="text-muted">{{ __('messages.accounting.journal') }}:</span> {{ $entry->journal->code ?? '' }} · {{ $entry->journal->name ?? '' }}</div>
                    <div><span class="text-muted">{{ __('messages.stock.date') }}:</span> {{ $entry->entry_date->format('d/m/Y') }}</div>
                    @if ($entry->reference)<div><span class="text-muted">{{ __('messages.accounting.reference') }}:</span> {{ $entry->reference }}</div>@endif
                    <div><span class="text-muted">{{ __('messages.accounting.source') }}:</span> {{ __('messages.accounting.events.' . ($entry->source_event ?: 'manual')) }}</div>
                    <div><span class="text-muted">{{ __('messages.stock.user') }}:</span> {{ $entry->author->full_name ?? '' }}</div>
                    @if ($entry->exported_at)<div class="badge bg-light-success text-success">{{ __('messages.accounting.exported', ['date' => $entry->exported_at->format('d/m/Y')]) }}</div>@endif
                </div>
                <div class="fw-bold mb-3">{{ $entry->label }}</div>
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.accounting.account') }}</th><th>{{ __('messages.accounting.label') }}</th><th class="text-end">{{ __('messages.accounting.debit') }}</th><th class="text-end">{{ __('messages.accounting.credit') }}</th></tr></thead>
                    @foreach ($entry->lines as $line)
                        <tr>
                            <td>{{ $line->account->code ?? '' }} {{ $line->account->name ?? '' }}</td>
                            <td>{{ $line->label }}</td>
                            <td class="text-end">{{ $line->debit ? formatMoney($line->debit) : '' }}</td>
                            <td class="text-end">{{ $line->credit ? formatMoney($line->credit) : '' }}</td>
                        </tr>
                    @endforeach
                    <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">{{ __('messages.claims.total') }}</td><td class="text-end">{{ formatMoney($entry->lines->sum('debit')) }}</td><td class="text-end">{{ formatMoney($entry->lines->sum('credit')) }}</td></tr></tfoot>
                </table>
                @if ($original)
                    <div class="text-muted">{{ __('messages.accounting.reverses') }} <a href="{{ route('accounting.entries.show', $original->id) }}">{{ $original->number }}</a></div>
                @elseif ($reversal)
                    <div class="text-muted">{{ __('messages.accounting.reversed_by') }} <a href="{{ route('accounting.entries.show', $reversal->id) }}">{{ $reversal->number }}</a></div>
                @else
                    @canroute('accounting.entries.reverse')
                        {{ Form::open(['route' => ['accounting.entries.reverse', $entry->id], 'method' => 'post', 'class' => 'd-flex gap-2 justify-content-end d-print-none']) }}
                        <input type="date" name="entry_date" value="{{ now()->toDateString() }}" class="form-control w-200px">
                        <button class="btn btn-light-danger">{{ __('messages.accounting.reverse') }}</button>
                        {{ Form::close() }}
                    @endcanroute
                @endif
            </div>
        </div>
    </div>
@endsection
