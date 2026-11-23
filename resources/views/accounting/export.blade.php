@extends('layouts.app')
@section('title')
    {{ __('messages.sage.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('accounting.export.settings');
        $off = $canEdit ? [] : ['disabled' => 'disabled'];
        $columnOptions = collect(\App\Utils\SageExport::COLUMNS)->mapWithKeys(fn ($c) => [$c => __('messages.sage.columns.' . $c)])->all();
        $chosen = array_pad($options['columns'], count(\App\Utils\SageExport::COLUMNS), null);
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.sage.export') }}</h5>
                <div class="form-text mb-4">{{ __('messages.sage.export_hint', ['pending' => $pending, 'exported' => $exported]) }}</div>
                <form method="get" action="{{ route('accounting.export.download') }}" class="row g-3 align-items-end">
                    <div class="col-md-2"><label class="form-label">{{ __('messages.stock_report.from') }}</label><input type="date" name="from" value="{{ $from }}" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label">{{ __('messages.stock_report.to') }}</label><input type="date" name="to" value="{{ $to }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">{{ __('messages.accounting.journal') }}</label>{{ Form::select('journal_id', $journals, null, ['class' => 'form-select', 'placeholder' => __('messages.accounting.all_journals')]) }}</div>
                    <div class="col-md-3">
                        <label class="form-check mb-1"><input type="checkbox" class="form-check-input" name="mark" value="1" checked> <span class="form-check-label">{{ __('messages.sage.mark') }}</span></label>
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="again" value="1"> <span class="form-check-label">{{ __('messages.sage.again') }}</span></label>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.sage.download') }}</button></div>
                </form>
                @if ($preview)
                    <div class="table-responsive mt-5">
                        <div class="text-muted fs-7 mb-2">{{ __('messages.sage.preview') }}</div>
                        <table class="table table-sm table-bordered fs-7 mb-0">
                            <thead><tr>@foreach ($options['columns'] as $c)<th>{{ __('messages.sage.columns.' . $c) }}</th>@endforeach</tr></thead>
                            @foreach ($preview as $row)
                                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-2">{{ __('messages.sage.layout') }}</h5>
                <div class="form-text mb-4">{{ __('messages.sage.layout_hint') }}</div>
                {{ Form::open(['route' => 'accounting.export.settings', 'method' => 'post']) }}
                <div class="row g-3 mb-5">
                    @foreach ($chosen as $i => $column)
                        <div class="col-md-2">{{ Form::select('columns[' . $i . ']', $columnOptions, $column, ['class' => 'form-select form-select-sm', 'placeholder' => ($i + 1) . '. —'] + $off) }}</div>
                    @endforeach
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">{{ Form::label('separator', __('messages.sage.separator'), ['class' => 'form-label']) }}{{ Form::select('separator', \App\Utils\SageExport::SEPARATORS, $options['separator'], ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-2">{{ Form::label('date_format', __('messages.sage.date_format'), ['class' => 'form-label']) }}{{ Form::select('date_format', \App\Utils\SageExport::DATE_FORMATS, $options['date_format'], ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-2">{{ Form::label('decimal', __('messages.sage.decimal'), ['class' => 'form-label']) }}{{ Form::select('decimal', [',' => '1250,50', '.' => '1250.50'], $options['decimal'], ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-2">{{ Form::label('encoding', __('messages.sage.encoding'), ['class' => 'form-label']) }}{{ Form::select('encoding', \App\Utils\SageExport::ENCODINGS, $options['encoding'], ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-1">{{ Form::label('extension', __('messages.sage.extension'), ['class' => 'form-label']) }}{{ Form::select('extension', ['csv' => '.csv', 'txt' => '.txt'], $options['extension'], ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-3">{{ Form::label('group', __('messages.sage.group'), ['class' => 'form-label']) }}{{ Form::select('group', collect(\App\Utils\SageExport::GROUPS)->mapWithKeys(fn ($g) => [$g => __('messages.sage.groups.' . $g)]), $options['group'] ?? 'none', ['class' => 'form-select'] + $off) }}</div>
                    <div class="col-md-2"><label class="form-check"><input type="checkbox" class="form-check-input" name="header" value="1" {{ $options['header'] ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}> <span class="form-check-label">{{ __('messages.sage.header') }}</span></label></div>
                    @if ($canEdit)<div class="col-md-1"><button class="btn btn-primary w-100">{{ __('messages.common.save') }}</button></div>@endif
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
