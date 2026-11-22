@extends('layouts.app')
@section('title')
    {{ __('messages.exam_results.my_results') }}
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
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-row-dashed align-middle">
                    <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.exam_orders.exam') }}</th><th>{{ __('messages.lab.results') }}</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($examItems as $exam)
                            <tr>
                                <td class="text-nowrap">{{ $exam->released_at?->format('d/m/Y') }}</td>
                                <td>{{ $exam->description }}</td>
                                <td class="fs-7">
                                    @foreach ($exam->results as $result)
                                        <div class="{{ $result->is_abnormal ? 'text-danger fw-bold' : '' }}">{{ $result->label }}: {{ $result->value }} {{ $result->unit }} <span class="text-muted">({{ $result->reference_range }})</span></div>
                                    @endforeach
                                    @if ($exam->report_text)<div>{{ \Illuminate\Support\Str::limit($exam->report_text, 200) }}</div>@endif
                                </td>
                                <td class="text-end"><a href="{{ route('my-results.show', $exam->exam_order_id) }}" target="_blank" class="btn btn-sm btn-light-success"><i class="fas fa-print me-1"></i>{{ __('messages.ot.print') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ __('messages.exam_results.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
