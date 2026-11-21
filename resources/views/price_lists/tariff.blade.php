@extends('layouts.app')
@section('title')
    {{ __('messages.price_list.tariff') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('price-lists.index') }}" class="btn btn-outline-primary">{{ __('messages.price_list.price_lists') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('price-lists.tariff.update');
    @endphp
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            @include('layouts.errors')

            <ul class="nav nav-tabs mb-5 pb-1 overflow-auto flex-nowrap text-nowrap">
                @foreach (array_keys(\App\Models\ItemPrice::ITEM_TYPES) as $itemType)
                    <li class="nav-item position-relative me-7 mb-3">
                        <a class="nav-link p-0 {{ $type === $itemType ? 'active' : '' }}" href="{{ route('price-lists.tariff', ['type' => $itemType]) }}">
                            {{ __('messages.price_list.types.' . $itemType) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('price-lists.tariff') }}" class="row g-3 mb-5">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <div class="col-md-4">
                            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('messages.common.search') }}">
                        </div>
                        <div class="col-md-8 text-muted fs-7 d-flex align-items-center">{{ __('messages.price_list.tariff_hint') }}</div>
                    </form>

                    @if ($items->isEmpty())
                        <div class="text-muted py-5 text-center">{{ __('messages.price_list.no_items') }}</div>
                    @else
                        {{ Form::open(['route' => 'price-lists.tariff.update', 'method' => 'post']) }}
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="page" value="{{ $items->currentPage() }}">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed">
                                <thead>
                                    <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                        <th>{{ __('messages.price_list.item') }}</th>
                                        <th class="text-end">{{ $default?->label ?? __('messages.price_list.base_price') }}</th>
                                        <th style="width: 110px">{{ __('messages.price_list.doctor_share') }} %</th>
                                        <th style="width: 110px">{{ __('messages.price_list.assistant_share') }} %</th>
                                        @foreach ($lists as $list)
                                            <th style="width: 150px">
                                                {{ $list->label }}
                                                <div class="text-muted fw-normal text-lowercase">{{ $list->adjustment_percent >= 0 ? '+' : '' }}{{ formatPercent($list->adjustment_percent) }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            $base = \App\Models\ItemPrice::basePrice($item);
                                            $own = $item->listPrices;
                                            $shares = $default ? $own->get($default->id) : null;
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ \App\Repositories\PriceListRepository::itemName($item, $type) }}</td>
                                            <td class="text-end text-nowrap">{{ formatMoney($base) }}</td>
                                            <td><input type="number" class="form-control form-control-sm" name="rows[{{ $item->id }}][doctor]" value="{{ $shares?->doctor_share_percent !== null ? $shares->doctor_share_percent + 0 : '' }}" min="0" max="100" step="0.01" {{ $canEdit ? '' : 'disabled' }}></td>
                                            <td><input type="number" class="form-control form-control-sm" name="rows[{{ $item->id }}][assistant]" value="{{ $shares?->assistant_share_percent !== null ? $shares->assistant_share_percent + 0 : '' }}" min="0" max="100" step="0.01" {{ $canEdit ? '' : 'disabled' }}></td>
                                            @foreach ($lists as $list)
                                                @php($price = $own->get($list->id)?->price)
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" name="rows[{{ $item->id }}][{{ $list->id }}]" value="{{ $price !== null ? $price + 0 : '' }}" min="0" step="any"
                                                        placeholder="{{ formatAmount($base * (1 + $list->adjustment_percent / 100)) }}" {{ $canEdit ? '' : 'disabled' }}>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>{{ $items->links() }}</div>
                            @if ($canEdit)
                                {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}
                            @endif
                        </div>
                        {{ Form::close() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
