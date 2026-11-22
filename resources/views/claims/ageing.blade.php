@extends('layouts.app')
@section('title')
    {{ __('messages.claim_settlement.ageing') }}
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
        $labels = [];
        $low = 0;
        foreach ($buckets as $limit) {
            $labels[] = __('messages.claim_settlement.days_range', ['from' => $low, 'to' => $limit]);
            $low = $limit + 1;
        }
        $labels[] = __('messages.claim_settlement.days_over', ['days' => end($buckets)]);
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="card mb-7">
            <div class="card-body">
                <div class="form-text mb-4">{{ __('messages.claim_settlement.ageing_hint') }}</div>
                <table class="table table-row-dashed align-middle">
                    <thead>
                        <tr class="text-muted fs-7 text-uppercase">
                            <th>{{ __('messages.claims.payer') }}</th>
                            @foreach ($labels as $label)<th class="text-end">{{ $label }}</th>@endforeach
                            <th class="text-end">{{ __('messages.claims.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>@canroute('claims.statement')<a href="{{ route('claims.statement', ['insurance_id' => $row['id']]) }}">{{ $row['payer'] }}</a>@else{{ $row['payer'] }}@endcanroute</td>
                                @foreach ($row['slots'] as $i => $amount)<td class="text-end {{ $i === count($row['slots']) - 1 && $amount > 0 ? 'text-danger fw-bold' : '' }}">{{ $amount ? formatMoney($amount) : '—' }}</td>@endforeach
                                <td class="text-end fw-bold">{{ formatMoney($row['total']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($labels) + 2 }}" class="text-center text-muted">{{ __('messages.claim_settlement.nothing_due') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($rows)
                        <tfoot>
                            <tr class="fw-bold">
                                <td>{{ __('messages.claims.total') }}</td>
                                @foreach ($labels as $i => $label)<td class="text-end">{{ formatMoney(array_sum(array_map(fn ($r) => $r['slots'][$i], $rows))) }}</td>@endforeach
                                <td class="text-end">{{ formatMoney(array_sum(array_column($rows, 'total'))) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @canroute('claims.ageing.update')
            <div class="card d-print-none">
                <div class="card-body">
                    {{ Form::open(['route' => 'claims.ageing.update', 'method' => 'post', 'class' => 'row g-3 align-items-end']) }}
                    <div class="col-md-4">
                        {{ Form::label('claim_ageing_buckets', __('messages.claim_settlement.buckets') . ':', ['class' => 'form-label']) }}
                        {{ Form::text('claim_ageing_buckets', $setting, ['class' => 'form-control', 'maxlength' => 50, 'required']) }}
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.save') }}</button></div>
                    <div class="col-12 form-text">{{ __('messages.claim_settlement.buckets_hint') }}</div>
                    {{ Form::close() }}
                </div>
            </div>
        @endcanroute
    </div>
@endsection
