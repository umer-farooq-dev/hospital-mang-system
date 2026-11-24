<?php

namespace App\Http\Requests;

use App\Models\FrontService;
use Illuminate\Foundation\Http\FormRequest;

class FrontServiceRequest extends FormRequest
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
        $rules = FrontService::$rules;
        $rules['type'] = 'required|in:'.implode(',', array_keys(FrontService::TYPES));
        $rules['icon'] = 'required_if:type,'.FrontService::SERVICE.'|nullable|image|mimes:jpg,jpeg,png,webp|max:2048';
        $rules['image'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096';
        $rules['category'] = 'nullable|string|max:100';
        $rules['published_at'] = 'nullable|date';
        $rules['sort_order'] = 'nullable|integer|min:0';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'icon.required' => __('messages.front_services.select_icon_file'),
            'icon.mimes' => __('messages.user.validate_image_type'),
        ];
    }
}
