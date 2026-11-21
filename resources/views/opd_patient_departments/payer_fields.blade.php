{{-- Payer, price list and voucher number; the split is shown below and saved by the server. --}}
@php
    $visit = $opdPatientDepartment ?? null;
    $visitPatientId = $visit->patient_id ?? ($data['last_visit']->patient_id ?? null);
    $policies = $visitPatientId
        ? \App\Models\PatientInsurance::with('insurance')->where('patient_id', $visitPatientId)->get()
            ->filter(fn ($policy) => $policy->isValidOn() || $policy->id === ($visit->patient_insurance_id ?? null))
        : collect();
@endphp
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('patient_insurance_id', __('messages.split.payer') . ':', ['class' => 'form-label']) }}
        <select name="patient_insurance_id" id="opdPolicyId" class="form-select" data-control="select2"
                data-url="{{ route('opd.payer.options') }}" data-self-pay="{{ __('messages.patient_insurance.self_pay') }}">
            <option value="">{{ __('messages.patient_insurance.self_pay') }}</option>
            @foreach ($policies as $policy)
                <option value="{{ $policy->id }}" data-price-list="{{ $policy->insurance->price_list_id }}"
                    {{ (int) old('patient_insurance_id', $visit->patient_insurance_id ?? null) === $policy->id ? 'selected' : '' }}>{{ $policy->option_label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('price_list_id', __('messages.split.price_list') . ':', ['class' => 'form-label']) }}
        {{ Form::select('price_list_id', \App\Models\PriceList::options($visit->price_list_id ?? null), $visit->price_list_id ?? \App\Models\PriceList::defaultList()?->id, ['class' => 'form-select', 'id' => 'opdPriceListId', 'data-control' => 'select2']) }}
    </div>
</div>
<div class="col-md-4">
    <div class="mb-5">
        {{ Form::label('voucher_number', __('messages.split.voucher_number') . ':', ['class' => 'form-label']) }}
        {{ Form::text('voucher_number', null, ['class' => 'form-control', 'id' => 'opdVoucherNumber', 'maxlength' => 50, 'placeholder' => __('messages.split.voucher_hint')]) }}
    </div>
</div>
<div class="col-md-12">
    <div class="mb-5" id="opdSplitSummary" data-url="{{ route('opd.quote') }}" data-visit="{{ $visit->id ?? '' }}">
        @if ($visit && $visit->patient_amount !== null)
            @include('opd_patient_departments.split_summary', ['split' => [
                'gross' => $visit->standard_charge, 'discount_percent' => $visit->discount_percent, 'discount_amount' => $visit->discount_amount,
                'net' => $visit->standard_charge - $visit->discount_amount, 'payer_amount' => $visit->payer_amount,
                'patient_amount' => $visit->patient_amount, 'coverage_percent' => null, 'warnings' => []]])
        @endif
    </div>
</div>
