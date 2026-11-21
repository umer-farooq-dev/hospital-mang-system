<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
        $rules = Setting::$rules;
        $rules['currency_decimals'] = 'nullable|integer|between:0,3';
        $rules['currency_symbol_position'] = 'nullable|in:before,after';
        $rules['currency_thousands_separator'] = ['nullable', \Illuminate\Validation\Rule::in(array_keys(Setting::THOUSANDS_SEPARATORS))];
        $rules['currency_decimal_separator'] = ['nullable', \Illuminate\Validation\Rule::in(array_keys(Setting::DECIMAL_SEPARATORS))];

        return $rules;
    }


}
