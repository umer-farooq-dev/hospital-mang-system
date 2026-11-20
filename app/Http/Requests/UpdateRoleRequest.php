<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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
        $role = $this->route('role');

        return [
            // Built-in roles keep their name; the field is not sent for them.
            'name' => $role->isBuiltIn() ? 'nullable' : 'required|string|max:191|unique:departments,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}
