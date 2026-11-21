<?php

namespace App\Http\Requests;

use App\Models\MasterListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterListItemRequest extends FormRequest
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
        $item = $this->route('masterListItem');
        $type = $item ? $item->type : $this->input('type');

        return [
            'type' => $item ? 'nullable' : ['required', Rule::in(array_keys(MasterListItem::TYPES))],
            'name' => [
                'required', 'string', 'max:191',
                Rule::unique('master_list_items', 'name')->where('type', $type)->ignore($item?->id),
            ],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable',
        ];
    }
}
