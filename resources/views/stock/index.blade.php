@extends('layouts.app')
@section('title')
    {{ __('messages.stock.stock') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div>
                @canroute('medicine-purchase.create')<a href="{{ route('medicine-purchase.create') }}" class="btn btn-primary">{{ __('messages.purchase_medicine.purchase_medicine') }}</a>@endcanroute
                @canroute('stores.index')<a href="{{ route('stores.index') }}" class="btn btn-outline-primary ms-2">{{ __('messages.stock.stores') }}</a>@endcanroute
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-5">
                    @foreach (array_keys(\App\Http\Controllers\StockController::KINDS) as $option)
                        <li class="nav-item"><a class="nav-link {{ $kind === $option ? 'active' : '' }}" href="{{ route('stock.index', ['kind' => $option]) }}">{{ __('messages.stock.kinds.' . $option) }}</a></li>
                    @endforeach
                </ul>
                <form method="get" class="row g-3 mb-5">
                    <input type="hidden" name="kind" value="{{ $kind }}">
                    <div class="col-md-3">{{ Form::select('store_id', $stores, $storeId ?: null, ['class' => 'form-select', 'placeholder' => __('messages.stock.all_stores')]) }}</div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            @foreach (\App\Http\Controllers\StockController::FILTERS as $option)
                                <option value="{{ $option }}" {{ $filter === $option ? 'selected' : '' }}>{{ __('messages.stock.filters.' . $option, ['days' => $alertDays]) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('messages.stock.search') }}"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('messages.common.filter') }}</button></div>
                </form>
                <div class="d-flex justify-content-end mb-3 fw-bold">{{ __('messages.stock.total_value') }}: {{ formatMoney($value) }}</div>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                            <tr class="text-muted fs-7 text-uppercase">
                                <th>{{ __('messages.medicine.medicine') }}</th>
                                <th>{{ __('messages.stock.store') }}</th>
                                <th>{{ __('messages.stock.batch') }}</th>
                                <th>{{ __('messages.stock.expiry') }}</th>
                                <th class="text-end">{{ __('messages.stock.quantity') }}</th>
                                <th class="text-end">{{ __('messages.stock.unit_cost') }}</th>
                                <th class="text-end">{{ __('messages.stock.value') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                <tr>
                                    <td>{{ $batch->item_name }}</td>
                                    <td>{{ $batch->store->name ?? '' }}</td>
                                    <td>{{ $batch->batch_no ?? '—' }}</td>
                                    <td>
                                        @if ($batch->expiry_date)
                                            {{ \Illuminate\Support\Carbon::parse($batch->expiry_date)->translatedFormat('d/m/Y') }}
                                            @if ($batch->isExpired())<span class="badge bg-light-danger text-danger ms-1">{{ __('messages.stock.expired') }}</span>
                                            @elseif ($batch->expiresWithin($alertDays))<span class="badge bg-light-warning text-warning ms-1">{{ __('messages.stock.expiring') }}</span>@endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end {{ $batch->quantity < 0 ? 'text-danger fw-bold' : '' }}">{{ $batch->quantity }}</td>
                                    <td class="text-end">{{ formatMoney($batch->unit_cost) }}</td>
                                    <td class="text-end">{{ formatMoney($batch->quantity * $batch->unit_cost) }}</td>
                                    <td class="text-end"><a href="{{ $kind === 'items' ? route('stock.items.movements', ['item' => $batch->stockable_id, 'store_id' => $batch->store_id]) : route('stock.movements', ['medicine' => $batch->stockable_id, 'store_id' => $batch->store_id]) }}">{{ __('messages.stock.movements') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">{{ __('messages.stock.no_batches') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $batches->links() }}
            </div>
        </div>
    </div>
@endsection
