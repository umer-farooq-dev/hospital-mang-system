@if ($patients->isEmpty())
    <div class="text-muted py-3">
        {{ __('messages.reception_desk.no_patient_found') }}
        @canroute('patients.create')
        <a href="{{ route('patients.create', ['from' => 'reception-desk']) }}" class="ms-2">{{ __('messages.patient.new_patient') }}</a>
        @endcanroute
    </div>
@else
    @foreach ($patients as $patient)
        @php($user = $patient->patientUser)
        <div class="d-flex flex-wrap align-items-center justify-content-between border rounded p-4 mb-3">
            <div class="d-flex align-items-center me-5 mb-2 mb-md-0">
                <img src="{{ $user->image_url }}" alt="" class="rounded-circle me-4" width="48" height="48">
                <div>
                    <div class="fs-5 fw-bold text-gray-800">
                        {{ $user->full_name }}
                        @if (! $user->status)
                            <span class="badge bg-light-danger ms-2">{{ __('messages.common.de_active') }}</span>
                        @endif
                    </div>
                    <div class="text-muted fs-7">
                        {{ $patient->patient_unique_id }}
                        · {{ $user->gender == 1 ? __('messages.user.female') : __('messages.user.male') }}
                        @if ($user->dob)
                            · {{ __('messages.reception.age_years', ['count' => \Carbon\Carbon::parse($user->dob)->age]) }}
                        @endif
                        @if ($user->phone)
                            · {{ $user->phone }}
                        @endif
                        @if ($patient->id_document_number)
                            · {{ $patient->id_document_type_name }} {{ $patient->id_document_number }}
                        @endif
                    </div>
                    <div class="mt-1">
                        @forelse ($patient->insurances->where('validity_status', '!=', 'inactive') as $policy)
                            <span class="badge bg-light-{{ $policy->validity_status === 'valid' ? 'success' : 'danger' }} me-1">
                                {{ $policy->insurance->name }}
                                @if ($policy->effective_coverage !== null)
                                    {{ formatPercent($policy->effective_coverage) }}
                                @endif
                                @if ($policy->validity_status !== 'valid')
                                    · {{ __('messages.patient_insurance.status.' . $policy->validity_status) }}
                                @endif
                            </span>
                        @empty
                            <span class="badge bg-light-secondary">{{ __('messages.patient_insurance.self_pay') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @canroute('opd.patient.create')
                <a href="{{ route('opd.patient.create', ['patient_id' => $patient->id, 'from' => 'reception-desk']) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-stethoscope me-1"></i>{{ __('messages.reception_desk.consultation') }}
                </a>
                @endcanroute
                @canroute('pathology.test.create')
                <a href="{{ route('pathology.test.create', ['patient_id' => $patient->id, 'from' => 'reception-desk']) }}" class="btn btn-sm btn-light-primary">
                    <i class="fas fa-vial me-1"></i>{{ __('messages.reception_desk.exams') }}
                </a>
                @endcanroute
                @canroute('ipd.patient.create')
                <a href="{{ route('ipd.patient.create', ['patient_id' => $patient->id, 'from' => 'reception-desk']) }}" class="btn btn-sm btn-light-primary">
                    <i class="fas fa-procedures me-1"></i>{{ __('messages.reception_desk.hospitalisation') }}
                </a>
                @endcanroute
                @canroute('cashier.index')
                <a href="{{ route('cashier.index', ['patient' => $patient->id]) }}" class="btn btn-sm btn-light-success">
                    <i class="fas fa-cash-register me-1"></i>{{ __('messages.cashier.collect') }}
                </a>
                @endcanroute
                @canroute('patients.show')
                <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-sm btn-light">
                    <i class="fas fa-folder-open me-1"></i>{{ __('messages.reception_desk.patient_file') }}
                </a>
                @endcanroute
            </div>
        </div>
    @endforeach
@endif
