<?php

namespace App\Http\Requests;

use App\Models\FrontService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFrontServiceRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = FrontService::$rules;
        unset($rules['icon']);
        $rules['icon'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';
        $rules['image'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096';
        $rules['category'] = 'nullable|string|max:100';
        $rules['published_at'] = 'nullable|date';
        $rules['sort_order'] = 'nullable|integer|min:0';

        return $rules;
    }
}
