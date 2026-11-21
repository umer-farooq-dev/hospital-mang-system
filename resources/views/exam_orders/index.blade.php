@extends('layouts.app')
@section('title')
    {{ __('messages.exam_orders.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('exam-orders.create')
            <a href="{{ route('exam-orders.create') }}" class="btn btn-primary">{{ __('messages.exam_orders.new') }}</a>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed">
                            <thead>
                                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                                    <th>{{ __('messages.cashier.date') }}</th>
                                    <th>{{ __('messages.cashier.patient') }}</th>
                                    <th>{{ __('messages.exam_orders.exams') }}</th>
                                    <th>{{ __('messages.exam_orders.prescriber') }}</th>
                                    <th class="text-end">{{ __('messages.split.gross') }}</th>
                                    <th class="text-end">{{ __('messages.split.patient_share') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="text-nowrap">{{ $order->ordered_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $order->patient->patientUser->full_name ?? '' }}
                                            <div class="text-muted fs-7">{{ $order->patient->patient_unique_id ?? '' }}</div></td>
                                        <td>{{ $order->items->pluck('description')->implode(', ') }}</td>
                                        <td>{{ $order->doctor->doctorUser->full_name ?? '' }}</td>
                                        <td class="text-end text-nowrap">{{ formatMoney($order->items->sum('total')) }}</td>
                                        <td class="text-end text-nowrap fw-bold">{{ formatMoney($order->items->sum('patient_amount')) }}</td>
                                        <td class="text-end">
                                            @canroute('cashier.index')
                                            <a href="{{ route('cashier.index', ['patient' => $order->patient_id]) }}" class="btn btn-sm btn-light-success"><i class="fas fa-cash-register"></i></a>
                                            @endcanroute
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.exam_orders.none') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
