@extends('layouts.app')
@section('title')
    {{ __('messages.certificate.certificates') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <div class="d-flex gap-2">
                @canroute('certificate-templates.index')
                    <a href="{{ route('certificate-templates.index') }}" class="btn btn-outline-primary"><i class="fas fa-file-alt me-1"></i>{{ __('messages.certificate.templates_title') }}</a>
                @endcanroute
                @canroute('certificates.create')
                    <a href="{{ route('certificates.create') }}" class="btn btn-primary">{{ __('messages.certificate.new') }}</a>
                @endcanroute
            </div>
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid" id="certificatesPage" data-print-url="{{ request('print') ? route('certificates.show', request('print')) : '' }}">
        @include('flash::message')
        <div class="card">
            <div class="card-body">
                {{ Form::open(['route' => 'certificates.index', 'method' => 'get', 'class' => 'mb-5']) }}
                <input type="search" name="search" value="{{ $search }}" class="form-control w-md-400px" placeholder="{{ __('messages.certificate.search') }}">
                {{ Form::close() }}
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr class="text-muted fw-bolder fs-7 text-uppercase"><th>{{ __('messages.certificate.number') }}</th><th>{{ __('messages.cashier.date') }}</th><th>{{ __('messages.certificate.certificate') }}</th><th>{{ __('messages.cashier.patient') }}</th><th>{{ __('messages.certificate.doctor') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($certificates as $certificate)
                                <tr>
                                    <td class="fw-bold">{{ $certificate->number }}</td>
                                    <td>{{ $certificate->issued_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $certificate->title }}</td>
                                    <td>{{ $certificate->patient?->patientUser?->full_name }}</td>
                                    <td>{{ $certificate->doctor?->doctorUser?->full_name }}</td>
                                    <td class="text-end"><a href="{{ route('certificates.show', $certificate->id) }}" target="_blank" class="btn btn-sm btn-light-primary"><i class="fas fa-print me-1"></i>{{ __('messages.ot.print') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('messages.certificate.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $certificates->links() }}
            </div>
        </div>
    </div>
@endsection
