<?php

namespace App\Http\Requests;

use App\Models\Insurance;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInsuranceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** Amounts are typed with thousands separators; validate the plain number. */
    protected function prepareForValidation(): void
    {
        foreach (['service_tax', 'hospital_rate'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => removeCommaFromNumbers($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return Insurance::rules($this->route('insurance')->id);
    }

    public function attributes(): array
    {
        return Insurance::attributeNames();
    }
}
