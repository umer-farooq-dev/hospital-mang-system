@extends('layouts.app')
@section('title')
    {{ __('messages.stock_count.counts') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        @canroute('stock-counts.store')
            <div class="card mb-7">
                <div class="card-body">
                    <h5 class="mb-4">{{ __('messages.stock_count.new') }}</h5>
                    {{ Form::open(['route' => 'stock-counts.store', 'method' => 'post', 'class' => 'row g-3 align-items-end']) }}
                    <div class="col-md-3">
                        {{ Form::label('store_id', __('messages.stock.store') . ':', ['class' => 'form-label required']) }}
                        {{ Form::select('store_id', $stores, \App\Utils\Stock::defaultStoreId(), ['class' => 'form-select', 'required']) }}
                    </div>
                    <div class="col-md-4">
                        {{ Form::label('note', __('messages.stock_transfer.note') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('note', null, ['class' => 'form-control', 'maxlength' => 500]) }}
                    </div>
                    <div class="col-md-3">
                        <label class="form-check form-switch mb-3"><input type="checkbox" class="form-check-input" name="blind" value="1" {{ $blindDefault ? 'checked' : '' }}>
                            <span class="form-check-label">{{ __('messages.stock_count.blind') }}</span></label>
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">{{ __('messages.stock_count.start') }}</button></div>
                    <div class="col-12 form-text">{{ __('messages.stock_count.hint') }}</div>
                    {{ Form::close() }}
                </div>
            </div>
        @endcanroute
        <div class="card">
            <div class="card-body">
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.stock_transfer.number') }}</th>
                            <th>{{ __('messages.stock.store') }}</th>
                            <th>{{ __('messages.stock.date') }}</th>
                            <th>{{ __('messages.stock_transfer.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($counts as $count)
                            <tr>
                                <td><a href="{{ route('stock-counts.show', $count->id) }}">{{ $count->number }}</a> @if ($count->blind)<span class="badge bg-light text-muted ms-1">{{ __('messages.stock_count.blind_short') }}</span>@endif</td>
                                <td>{{ $count->store->name ?? '' }}</td>
                                <td>{{ $count->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                <td>@include('stock_counts.status', ['status' => $count->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ __('messages.stock_count.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $counts->links() }}
            </div>
        </div>
    </div>
@endsection
