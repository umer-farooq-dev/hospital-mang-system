@extends('layouts.app')
@section('title')
    {{ __('messages.reception_desk.reception_desk') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('patients.create')
            <a href="{{ route('patients.create', ['from' => 'reception-desk']) }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>{{ __('messages.patient.new_patient') }}
            </a>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            <div class="card mb-7">
                <div class="card-body">
                    <div class="position-relative">
                        <span class="position-absolute top-50 translate-middle-y ms-4 text-gray-500">
                            <i class="fas fa-search fs-4"></i>
                        </span>
                        <input type="search" id="receptionDeskSearch" class="form-control form-control-lg ps-12"
                            autocomplete="off" autofocus data-url="{{ route('reception-desk.search') }}"
                            placeholder="{{ __('messages.reception_desk.search_placeholder') }}">
                    </div>
                    <div class="text-muted fs-7 mt-2">{{ __('messages.reception_desk.search_hint') }}</div>
                    <div id="receptionDeskResults" class="mt-5">
                        @if ($selected->isNotEmpty())
                            @include('reception_desk.patient_results', ['patients' => $selected])
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">{{ __('messages.reception_desk.today_appointments') }}</h3>
                            <span class="badge bg-light-primary align-self-center">{{ $appointments->count() }}</span>
                        </div>
                        <div class="card-body pt-0">
                            @if ($appointments->isEmpty())
                                <div class="text-muted py-5">{{ __('messages.reception_desk.no_appointments') }}</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed mb-0">
                                        <thead>
                                            <tr class="text-gray-600 fw-bold">
                                                <th>{{ __('messages.reception_desk.time') }}</th>
                                                <th>{{ __('messages.advanced_payment.patient') }}</th>
                                                <th>{{ __('messages.case.doctor') }}</th>
                                                <th>{{ __('messages.common.status') }}</th>
                                                <th class="text-end">{{ __('messages.common.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($appointments as $appointment)
                                                <tr>
                                                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($appointment->opd_date)->format('H:i') }}</td>
                                                    <td>
                                                        <div>{{ $appointment->patient->patientUser->full_name }}</div>
                                                        <div class="text-muted fs-7">{{ $appointment->patient->patient_unique_id }}</div>
                                                    </td>
                                                    <td>{{ $appointment->doctor->doctorUser->full_name ?? __('messages.common.n/a') }}</td>
                                                    <td>
                                                        <span class="badge bg-light-{{ ['0' => 'warning', '1' => 'success', '3' => 'danger', '4' => 'info', '5' => 'primary'][$appointment->is_completed] ?? 'secondary' }}">
                                                            {{ __('messages.reception_desk.status.' . $appointment->is_completed) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        @if ($appointment->is_completed == \App\Models\Appointment::STATUS_PENDING)
                                                            @canroute('opd.patient.create')
                                                            <a href="{{ route('opd.patient.create', ['patient_id' => $appointment->patient_id, 'appointment_id' => $appointment->id, 'from' => 'reception-desk']) }}"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fas fa-sign-in-alt me-1"></i>{{ __('messages.reception_desk.check_in') }}
                                                            </a>
                                                            @endcanroute
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 mb-7">
                    <div class="card h-100">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold">{{ __('messages.reception_desk.recent_patients') }}</h3>
                        </div>
                        <div class="card-body pt-0">
                            @forelse ($recentPatients as $patient)
                                <a href="javascript:void(0)" class="d-flex align-items-center py-3 border-bottom text-decoration-none reception-desk-pick"
                                    data-search="{{ $patient->patient_unique_id }}">
                                    <img src="{{ $patient->patientUser->image_url }}" alt="" class="rounded-circle me-3" width="36" height="36">
                                    <div>
                                        <div class="text-gray-800">{{ $patient->patientUser->full_name }}</div>
                                        <div class="text-muted fs-7">{{ $patient->patient_unique_id }} · {{ $patient->created_at->translatedFormat('d M, H:i') }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-muted py-5">{{ __('messages.common.no_data_available') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
{{-- JS File :- assets/js/reception_desk/reception_desk.js --}}
