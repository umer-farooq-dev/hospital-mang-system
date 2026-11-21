@php
    $badges = ['valid' => 'success', 'expired' => 'danger', 'not_started' => 'warning', 'inactive' => 'secondary'];
@endphp
@if ($policies->isEmpty())
    <div class="text-muted py-5 text-center">{{ __('messages.patient_insurance.no_policy') }}</div>
@else
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr class="text-muted fw-bolder fs-7 text-uppercase">
                    <th>{{ __('messages.patient_insurance.priority') }}</th>
                    <th>{{ __('messages.patient_insurance.insurance_id') }}</th>
                    <th>{{ __('messages.patient_insurance.policy_number') }}</th>
                    <th>{{ __('messages.patient_insurance.validity') }}</th>
                    <th class="text-end">{{ __('messages.patient_insurance.coverage_percent') }}</th>
                    <th class="text-end">{{ __('messages.patient_insurance.limits') }}</th>
                    <th>{{ __('messages.common.status') }}</th>
                    <th class="text-center">{{ __('messages.common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($policies as $policy)
                    <tr>
                        <td>{{ $policy->priority }}</td>
                        <td>
                            <div class="fw-bold">{{ $policy->insurance->name }}</div>
                            <div class="text-muted fs-7">
                                {{ $policy->insurance->payer_type_name }}
                                @if ($policy->plan_name)
                                    · {{ $policy->plan_name }}
                                @endif
                            </div>
                            @if ($policy->notes)
                                <div class="text-muted fs-7 fst-italic">{{ $policy->notes }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $policy->policy_number ?? __('messages.common.n/a') }}
                            @if ($policy->member_number)
                                <div class="text-muted fs-7">{{ __('messages.patient_insurance.member_number') }}: {{ $policy->member_number }}</div>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            {{ $policy->valid_from ? $policy->valid_from->translatedFormat('d/m/Y') : '…' }}
                            – {{ $policy->valid_to ? $policy->valid_to->translatedFormat('d/m/Y') : '…' }}
                        </td>
                        <td class="text-end">
                            {{ $policy->effective_coverage !== null ? formatPercent($policy->effective_coverage) : __('messages.common.n/a') }}
                            @if ($policy->insurance->discount)
                                <div class="text-muted fs-7">{{ __('messages.insurance.discount') }} {{ formatPercent($policy->insurance->discount) }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <div>{{ __('messages.patient_insurance.annual_limit') }}: {{ $policy->annual_limit !== null ? formatMoney($policy->annual_limit) : __('messages.patient_insurance.no_limit') }}</div>
                            <div class="text-muted fs-7">{{ __('messages.patient_insurance.visit_limit') }}: {{ $policy->visit_limit !== null ? formatMoney($policy->visit_limit) : __('messages.patient_insurance.no_limit') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light-{{ $badges[$policy->validity_status] }}">{{ __('messages.patient_insurance.status.' . $policy->validity_status) }}</span>
                        </td>
                        <td class="text-center text-nowrap">
                            @canroute('patient-insurances.update')
                            <a href="javascript:void(0)" class="btn px-1 text-primary fs-3 edit-patient-insurance" title="{{ __('messages.common.edit') }}"
                               data-url="{{ route('patient-insurances.update', $policy->id) }}"
                               data-policy="{{ json_encode($policy->formValues()) }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            @endcanroute
                            @canroute('patient-insurances.destroy')
                            <a href="javascript:void(0)" class="btn px-1 text-danger fs-3 delete-patient-insurance" title="{{ __('messages.common.delete') }}"
                               data-url="{{ route('patient-insurances.destroy', $policy->id) }}" data-label="{{ __('messages.patient_insurance.policy') }}">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                            @endcanroute
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
