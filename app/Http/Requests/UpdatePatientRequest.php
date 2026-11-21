<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = Patient::registrationRules($this->route('patient')->patientUser->id);
        $rules['image'] = 'mimes:jpeg,jpg,png,gif';

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => Patient::blockDuplicates($validator, $this->all(), $this->route('patient')->id));
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'image.mimes' => __('messages.user.validate_image_type'),
        ];
    }

    public function attributes(): array
    {
        return Patient::registrationAttributes();
    }
}
