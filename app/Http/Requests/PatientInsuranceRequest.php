<?php

namespace App\Http\Requests;

use App\Models\PatientInsurance;
use Illuminate\Foundation\Http\FormRequest;

class PatientInsuranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Limits are typed with thousands separators; validate the plain number. */
    protected function prepareForValidation(): void
    {
        foreach (['annual_limit', 'visit_limit'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => removeCommaFromNumbers($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return PatientInsurance::rules();
    }

    public function attributes(): array
    {
        return collect(array_keys(PatientInsurance::rules()))
            ->mapWithKeys(fn ($field) => [$field => __('messages.patient_insurance.'.$field)])->all();
    }
}
