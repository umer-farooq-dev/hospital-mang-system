@extends('layouts.app')
@section('title')
    {{ __('messages.data_import.title') }}
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
        <ul class="nav nav-tabs mb-5 flex-wrap">
            @foreach (\App\Utils\DataImport::KINDS as $k)
                <li class="nav-item"><a class="nav-link {{ $k === $kind ? 'active' : '' }}" href="{{ route('data-import.index', ['kind' => $k]) }}">{{ __('messages.data_import.kinds.' . $k) }}</a></li>
            @endforeach
        </ul>
        <div class="card mb-7">
            <div class="card-body">
                <div class="form-text mb-2">{{ __('messages.data_import.hints.' . $kind) }}</div>
                <div class="form-text mb-3">{{ __('messages.data_import.hint') }}</div>
                <div class="mb-5">
                    @foreach ($fields as $field)
                        <span class="badge {{ in_array($field, $required) ? 'bg-primary' : 'bg-light text-dark' }} me-1 mb-1">{{ \App\Utils\DataImport::label($field) }}</span>
                    @endforeach
                    <a href="{{ route('data-import.template', $kind) }}" class="ms-3">{{ __('messages.data_import.template') }}</a>
                    <a href="{{ route('data-import.templates') }}" class="ms-3">{{ __('messages.data_import.template_pack') }}</a>
                </div>
                {{ Form::open(['route' => 'data-import.preview', 'method' => 'post', 'files' => true, 'class' => 'row g-3 align-items-end']) }}
                {{ Form::hidden('kind', $kind) }}
                <div class="col-md-4">
                    {{ Form::label('file', __('messages.data_import.file') . ':', ['class' => 'form-label required']) }}
                    <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                </div>
                @if (in_array($kind, ['patients', 'doctors', 'staff']))
                    <div class="col-md-2">
                        {{ Form::label('prefix', __('messages.data_import.prefix') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('prefix', $pending['options']['prefix'] ?? null, ['class' => 'form-control', 'placeholder' => '226']) }}
                    </div>
                @endif
                @if ($kind === 'opening_stock')
                    <div class="col-md-3">
                        {{ Form::label('store_id', __('messages.data_import.default_store') . ':', ['class' => 'form-label']) }}
                        {{ Form::select('store_id', $stores, $pending['options']['store_id'] ?? null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}
                    </div>
                @endif
                @if ($kind === 'opening_balances')
                    <div class="col-md-2">
                        {{ Form::label('date', __('messages.data_import.fields.date') . ':', ['class' => 'form-label required']) }}
                        {{ Form::date('date', $pending['options']['date'] ?? null, ['class' => 'form-control', 'required']) }}
                    </div>
                    <div class="col-md-2">
                        {{ Form::label('journal', __('messages.data_import.journal') . ':', ['class' => 'form-label']) }}
                        {{ Form::select('journal', $journals, $pending['options']['journal'] ?? 'OD', ['class' => 'form-select']) }}
                    </div>
                @endif
                <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">{{ __('messages.data_import.check') }}</button></div>
                {{ Form::close() }}
            </div>
        </div>

        @if ($preview && $pending)
            <div class="card mb-7">
                <div class="card-body">
                    <div class="d-md-flex justify-content-between align-items-center mb-4">
                        <div>
                            <b>{{ $pending['name'] }}</b> ·
                            {{ __('messages.data_import.counts', ['total' => $preview['total'], 'create' => $preview['create'], 'update' => $preview['update'], 'exists' => $preview['exists'], 'failed' => $preview['failed']]) }}
                            @if ($kind === 'opening_balances')
                                · {{ __('messages.accounting.debit') }} {{ formatAmount($preview['debit']) }} / {{ __('messages.accounting.credit') }} {{ formatAmount($preview['credit']) }}
                            @endif
                            @if ($kind === 'opening_stock')
                                · {{ __('messages.data_import.stock_value', ['value' => formatAmount($preview['value'])]) }}
                            @endif
                        </div>
                        @if ($preview['create'] + $preview['update'] > 0)
                            {{ Form::open(['route' => 'data-import.store', 'method' => 'post', 'class' => 'mt-3 mt-md-0']) }}
                            <button type="submit" class="btn btn-primary">{{ __('messages.data_import.import', ['count' => $preview['create'] + $preview['update']]) }}</button>
                            {{ Form::close() }}
                        @endif
                    </div>
                    @if ($preview['unknown'])
                        <div class="alert alert-warning py-2">{{ __('messages.data_import.unknown_columns') }} {{ implode(', ', $preview['unknown']) }}</div>
                    @endif
                    @if ($kind === 'opening_balances' && $preview['failed'])
                        <div class="alert alert-danger py-2">{{ __('messages.data_import.balances_not_posted') }}</div>
                    @endif
                    @foreach (array_filter($preview['errors'], fn ($e) => $e['line'] === 0) as $error)
                        <div class="alert alert-danger py-2">{{ $error['message'] }}</div>
                    @endforeach
                    <div class="table-responsive">
                        <table class="table table-sm table-row-dashed align-middle">
                            <thead><tr class="fw-bold text-muted">
                                <th>{{ __('messages.data_import.line') }}</th>
                                @foreach ($fields as $field)<th>{{ \App\Utils\DataImport::label($field) }}</th>@endforeach
                                <th>{{ __('messages.data_import.result') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach ($preview['rows'] as $row)
                                <tr>
                                    <td>{{ $row['line'] }}</td>
                                    @foreach ($fields as $field)<td>{{ $row['data'][$field] ?? '' }}</td>@endforeach
                                    <td>
                                        <span class="badge {{ ['create' => 'bg-success', 'update' => 'bg-info', 'exists' => 'bg-secondary text-dark', 'failed' => 'bg-danger'][$row['action']] }}">{{ __('messages.data_import.actions.' . $row['action']) }}</span>
                                        @if ($row['error'])<div class="text-danger small">{{ $row['error'] }}</div>@endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @php($more = array_filter($preview['errors'], fn ($e) => $e['line'] > ($preview['rows'] ? end($preview['rows'])['line'] : 0)))
                    @foreach (array_slice($more, 0, 200) as $error)
                        <div class="text-danger small">{{ __('messages.data_import.line') }} {{ $error['line'] }}: {{ $error['message'] }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h3 class="mb-4">{{ __('messages.data_import.history') }}</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-row-dashed align-middle">
                        <thead><tr class="fw-bold text-muted">
                            <th>{{ __('messages.common.created_on') }}</th><th>{{ __('messages.data_import.kind') }}</th><th>{{ __('messages.data_import.file') }}</th>
                            <th class="text-end">{{ __('messages.data_import.actions.create') }}</th><th class="text-end">{{ __('messages.data_import.actions.update') }}</th><th class="text-end">{{ __('messages.data_import.actions.exists') }}</th>
                            <th class="text-end">{{ __('messages.data_import.actions.failed') }}</th><th></th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse ($runs as $run)
                            <tr>
                                <td>{{ $run->created_at->format('d/m/Y H:i') }}<div class="text-muted small">{{ $run->user->full_name ?? '' }}</div></td>
                                <td>{{ __('messages.data_import.kinds.' . $run->kind) }}</td>
                                <td>{{ $run->file_name }}</td>
                                <td class="text-end">{{ $run->created }}</td><td class="text-end">{{ $run->updated }}</td><td class="text-end">{{ $run->existing }}</td><td class="text-end">{{ $run->failed }}</td>
                                <td>{{ $run->note }}</td>
                                <td>@if ($run->errors)<a href="{{ route('data-import.errors', $run) }}">{{ __('messages.data_import.error_file') }}</a>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">{{ __('messages.common.no_data_available') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
