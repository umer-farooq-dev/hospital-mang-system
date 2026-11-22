{{-- Payer, price list and voucher number of the admission (used by the IPD bill). --}}
@php
    $admission = $ipdPatientDepartment ?? null;
    $admissionPatient = $admission->patient_id ?? request('patient_id');
    $admissionPolicies = $admissionPatient
        ? \App\Models\PatientInsurance::with('insurance')->where('patient_id', $admissionPatient)->get()
            ->filter(fn ($p) => $p->isValidOn() || $p->id === ($admission->patient_insurance_id ?? null))
        : collect();
@endphp
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('patient_insurance_id', __('messages.split.payer') . ':', ['class' => 'form-label']) }}
        <select name="patient_insurance_id" id="ipdPolicyId" class="form-select" data-control="select2" data-url="{{ route('opd.payer.options') }}">
            <option value="">{{ __('messages.patient_insurance.self_pay') }}</option>
            @foreach ($admissionPolicies as $policy)
                <option value="{{ $policy->id }}" data-price-list="{{ $policy->insurance->price_list_id }}" {{ (int) ($admission->patient_insurance_id ?? 0) === $policy->id ? 'selected' : '' }}>{{ $policy->option_label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('price_list_id', __('messages.split.price_list') . ':', ['class' => 'form-label']) }}
        {{ Form::select('price_list_id', \App\Models\PriceList::options($admission->price_list_id ?? null), $admission->price_list_id ?? null, ['class' => 'form-select', 'id' => 'ipdPriceListId', 'data-control' => 'select2', 'placeholder' => __('messages.insurance.price_list_default')]) }}
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('voucher_number', __('messages.split.voucher_number') . ':', ['class' => 'form-label']) }}
        {{ Form::text('voucher_number', $admission->voucher_number ?? null, ['class' => 'form-control', 'maxlength' => 50, 'placeholder' => __('messages.split.voucher_hint')]) }}
    </div>
</div>
