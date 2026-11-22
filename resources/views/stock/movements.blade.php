@extends('layouts.app')
@section('title')
    {{ __('messages.stock.movements') }} · {{ $medicine->name }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('stock.index') }}" class="btn btn-outline-primary">{{ __('messages.common.back') }}</a>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="get" class="row g-3 mb-5">
                    <div class="col-md-4">{{ Form::select('store_id', $stores, $storeId ?: null, ['class' => 'form-select', 'placeholder' => __('messages.stock.all_stores'), 'onchange' => 'this.form.submit()']) }}</div>
                </form>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.stock.date') }}</th>
                                <th>{{ __('messages.stock.type') }}</th>
                                <th>{{ __('messages.stock.document') }}</th>
                                <th>{{ __('messages.stock.store') }}</th>
                                <th>{{ __('messages.stock.batch') }}</th>
                                <th class="text-end">{{ __('messages.stock.in') }}</th>
                                <th class="text-end">{{ __('messages.stock.out') }}</th>
                                <th class="text-end">{{ __('messages.stock.balance') }}</th>
                                <th>{{ __('messages.stock.user') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($moves->isNotEmpty())
                                <tr class="text-muted"><td colspan="7">{{ __('messages.stock.balance_before') }}</td><td class="text-end">{{ $balance }}</td><td></td></tr>
                            @endif
                            @forelse ($moves as $move)
                                @php
                                    $balance += $move->quantity;
                                    [$label, $url] = $move->documentLink();
                                @endphp
                                <tr>
                                    <td>{{ $move->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                    <td>{{ __('messages.stock.types.' . $move->movement_type) }}</td>
                                    <td>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif @if ($move->note)<div class="text-muted fs-7">{{ $move->note }}</div>@endif</td>
                                    <td>{{ $move->store->name ?? '' }}</td>
                                    <td>{{ $move->batch->batch_no ?? '—' }}@if ($move->batch?->expiry_date) · {{ \Illuminate\Support\Carbon::parse($move->batch->expiry_date)->translatedFormat('d/m/Y') }}@endif</td>
                                    <td class="text-end text-success">{{ $move->quantity > 0 ? $move->quantity : '' }}</td>
                                    <td class="text-end text-danger">{{ $move->quantity < 0 ? -$move->quantity : '' }}</td>
                                    <td class="text-end fw-bold">{{ $balance }}</td>
                                    <td>{{ $move->user->full_name ?? '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted">{{ __('messages.stock.no_movements') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
