@extends('layouts.app')
@section('title')
    {{ __('messages.accounting.entries') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('accounting.entries.create')<a href="{{ route('accounting.entries.create') }}" class="btn btn-primary">{{ __('messages.accounting.new_entry') }}</a>@endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-3">{{ Form::select('journal_id', $journals, request('journal_id'), ['class' => 'form-select', 'placeholder' => __('messages.accounting.all_journals')]) }}</div>
                    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                    <div class="col-md-3"><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('messages.accounting.search') }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.stock.date') }}</th>
                            <th>{{ __('messages.claims.number') }}</th>
                            <th>{{ __('messages.accounting.journal') }}</th>
                            <th>{{ __('messages.accounting.reference') }}</th>
                            <th>{{ __('messages.accounting.label') }}</th>
                            <th class="text-end">{{ __('messages.claims.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                <td><a href="{{ route('accounting.entries.show', $entry->id) }}">{{ $entry->number }}</a></td>
                                <td>{{ $entry->journal->code ?? '' }}</td>
                                <td>{{ $entry->reference }}</td>
                                <td>{{ $entry->label }}</td>
                                <td class="text-end">{{ formatMoney($entry->total()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">{{ __('messages.accounting.no_entries') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $entries->links() }}
            </div>
        </div>
    </div>
@endsection
