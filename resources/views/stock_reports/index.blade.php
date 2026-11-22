@extends('layouts.app')
@section('title')
    {{ __('messages.stock_report.reports') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('stock-reports.export')
                <a href="{{ route('stock-reports.export', request()->query() + ['report' => $report]) }}" class="btn btn-outline-primary">{{ __('messages.stock_report.export') }}</a>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    @php
        $valueColumn = array_search(__('messages.stock_report.value'), $head, true);
    @endphp
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-5">
                    @foreach (\App\Http\Controllers\StockReportController::REPORTS as $option)
                        <li class="nav-item"><a class="nav-link {{ $report === $option ? 'active' : '' }}" href="{{ route('stock-reports.index', ['report' => $option, 'kind' => $filters['kind']]) }}">{{ __('messages.stock_report.names.' . $option) }}</a></li>
                    @endforeach
                </ul>
                <form method="get" class="row g-3 mb-5 align-items-end">
                    <input type="hidden" name="report" value="{{ $report }}">
                    <div class="col-md-2">
                        <label class="form-label">{{ __('messages.stock_report.what') }}</label>
                        {{ Form::select('kind', collect(array_keys(\App\Http\Controllers\StockController::KINDS))->mapWithKeys(fn ($k) => [$k => __('messages.stock.kinds.' . $k)]), $filters['kind'], ['class' => 'form-select']) }}
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('messages.stock_report.store') }}</label>
                        {{ Form::select('store_id', $stores, $filters['storeId'] ?: null, ['class' => 'form-select', 'placeholder' => __('messages.stock.all_stores')]) }}
                    </div>
                    @if (in_array($report, ['issued', 'movements'], true))
                        <div class="col-md-2"><label class="form-label">{{ __('messages.stock_report.from') }}</label><input type="date" name="from" value="{{ $filters['from']->toDateString() }}" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">{{ __('messages.stock_report.to') }}</label><input type="date" name="to" value="{{ $filters['to']->toDateString() }}" class="form-control"></div>
                    @endif
                    @if (in_array($report, ['expiry', 'slow'], true))
                        <div class="col-md-2"><label class="form-label">{{ __('messages.stock_report.days_' . $report) }}</label><input type="number" name="days" min="0" value="{{ $filters['days'] }}" class="form-control"></div>
                    @endif
                    @if ($filters['groups'])
                        <div class="col-md-2">
                            <label class="form-label">{{ __('messages.stock_report.group_by') }}</label>
                            <select name="group" class="form-select">
                                @foreach ($filters['groups'] as $option)
                                    <option value="{{ $option }}" {{ $filters['group'] === $option ? 'selected' : '' }}>{{ __('messages.stock_report.groups.' . $option) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="form-text mb-4">{{ __('messages.stock_report.hints.' . $report) }}</div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                @foreach ($head as $i => $title)
                                    <th class="{{ $i > 0 && is_numeric($shown[0][$i] ?? null) ? 'text-end' : '' }}">{{ $title }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shown as $row)
                                <tr>
                                    @foreach ($row as $i => $cell)
                                        <td class="{{ is_numeric($cell) && $i > 0 ? 'text-end' : '' }} {{ is_numeric($cell) && $cell < 0 ? 'text-danger' : '' }}">{{ $i === $valueColumn ? formatMoney($cell) : $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($head) }}" class="text-center text-muted">{{ __('messages.stock_report.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if ($total)
                            <tfoot>
                                <tr class="fw-bold">
                                    @foreach ($total as $i => $cell)
                                        <td class="{{ $i === $valueColumn ? 'text-end' : '' }}">{{ $i === $valueColumn ? formatMoney($cell) : $cell }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @if (count($rows) > count($shown))
                    <div class="form-text">{{ __('messages.stock_report.more_rows', ['shown' => count($shown), 'total' => count($rows)]) }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
