@extends('layouts.app')
@section('title')
    {{ __('messages.referrer_commissions.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="d-print-none">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">{{ __('messages.stock_transfer.print') }}</button>
                @canroute('referrer-commissions.export')<a href="{{ route('referrer-commissions.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="btn btn-outline-primary ms-2">{{ __('messages.stock_report.export') }}</a>@endcanroute
            </div>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canPay = canAccessRoute('referrer-commissions.pay');
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5 d-print-none">
                    <div class="col-md-3"><input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control"></div>
                    <div class="col-md-3"><input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="text-muted mb-3">{{ __('messages.claims.period') }}: {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }} · {{ __('messages.referrer_commissions.hint') }}</div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.referrer_commissions.referrer') }}</th>
                                <th class="text-end">{{ __('messages.referrer_commissions.visits') }}</th>
                                <th class="text-end">{{ __('messages.referrer_commissions.amount') }}</th>
                                <th class="text-end">%</th>
                                <th class="text-end">{{ __('messages.referrer_commissions.commission') }}</th>
                                <th class="text-end">{{ __('messages.referrer_commissions.paid_column') }}</th>
                                <th class="text-end">{{ __('messages.referrer_commissions.due') }}</th>
                                @if ($canPay)<th class="d-print-none"></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-end">{{ $row['visits'] }}</td>
                                    <td class="text-end">{{ formatMoney($row['amount']) }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($row['percent'], 2, ',', ''), '0'), ',') }}</td>
                                    <td class="text-end fw-bold">{{ formatMoney($row['commission']) }}</td>
                                    <td class="text-end">{{ formatMoney($row['paid']) }}</td>
                                    <td class="text-end {{ $row['due'] > 0 ? 'text-danger fw-bold' : '' }}">{{ formatMoney($row['due']) }}</td>
                                    @if ($canPay)
                                        <td class="d-print-none">
                                            @if ($row['due'] > 0)
                                                {{ Form::open(['route' => 'referrer-commissions.pay', 'method' => 'post', 'class' => 'd-flex gap-2']) }}
                                                {{ Form::hidden('referrer_code', $row['code']) }}
                                                {{ Form::hidden('from', $from->toDateString()) }}
                                                {{ Form::hidden('to', $to->toDateString()) }}
                                                {{ Form::hidden('amount', $row['due']) }}
                                                <input type="date" name="paid_on" class="form-control form-control-sm" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                                <input type="text" name="reference" class="form-control form-control-sm" maxlength="100" placeholder="{{ __('messages.claim_settlement.reference') }}">
                                                <button class="btn btn-sm btn-success text-nowrap">{{ __('messages.referrer_commissions.pay') }}</button>
                                                {{ Form::close() }}
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('messages.referrer_commissions.none') }}</td></tr>
                            @endforelse
                        </tbody>
                        @if ($rows)
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>{{ __('messages.claims.total') }}</td>
                                    <td class="text-end">{{ array_sum(array_column($rows, 'visits')) }}</td>
                                    <td class="text-end">{{ formatMoney(array_sum(array_column($rows, 'amount'))) }}</td>
                                    <td></td>
                                    <td class="text-end">{{ formatMoney(array_sum(array_column($rows, 'commission'))) }}</td>
                                    <td class="text-end">{{ formatMoney(array_sum(array_column($rows, 'paid'))) }}</td>
                                    <td class="text-end">{{ formatMoney(array_sum(array_column($rows, 'due'))) }}</td>
                                    @if ($canPay)<td class="d-print-none"></td>@endif
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        @canroute('referrer-commissions.rates')
            <div class="card d-print-none">
                <div class="card-body">
                    <h5 class="mb-2">{{ __('messages.referrer_commissions.rates') }}</h5>
                    <div class="form-text mb-4">{{ __('messages.referrer_commissions.rates_hint') }}
                        @canroute('master-lists.index') · <a href="{{ route('master-lists.index', ['type' => 'referrer']) }}">{{ __('messages.master_list.types.referrer') }}</a>@endcanroute
                    </div>
                    {{ Form::open(['route' => 'referrer-commissions.rates', 'method' => 'post']) }}
                    {{ Form::hidden('from', $from->toDateString()) }}
                    {{ Form::hidden('to', $to->toDateString()) }}
                    <div class="row g-3">
                        @forelse ($referrers as $code => $name)
                            <div class="col-md-4 d-flex align-items-center gap-3">
                                <span class="flex-grow-1">{{ $name }}</span>
                                <input type="number" name="rates[{{ $code }}]" value="{{ $rates[$code] ?? 0 }}" min="0" max="100" step="0.01" class="form-control w-100px"> %
                            </div>
                        @empty
                            <div class="text-muted">{{ __('messages.referrer_commissions.no_referrers') }}</div>
                        @endforelse
                    </div>
                    @if ($referrers)
                        <div class="d-flex justify-content-end mt-5">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
                    @endif
                    {{ Form::close() }}
                </div>
            </div>
        @endcanroute
    </div>
@endsection
