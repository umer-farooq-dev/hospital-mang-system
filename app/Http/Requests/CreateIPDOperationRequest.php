<?php

namespace App\Http\Requests;

use App\Models\IpdOperation;
use Illuminate\Foundation\Http\FormRequest;

class CreateIPDOperationRequest extends FormRequest
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
        return IpdOperation::$rules + IpdOperation::otRules();
    }

    /** A room or a surgeon cannot be booked twice at the same time. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $id = $this->route('id');
            $clash = IpdOperation::clash($this->all(), $id ? (int) $id : null);
            if ($clash) {
                [$other, $kind] = $clash;
                $validator->errors()->add('scheduled_start', __('messages.ot.clash_' . $kind, [
                    'room' => \App\Models\MasterListItem::label('ot_room', $other->ot_room, ''),
                    'from' => $other->scheduled_start->format('d/m/Y H:i'), 'to' => $other->scheduled_end->format('H:i'), 'ref' => $other->ref_no,
                ]));
            }
        });
    }

    /**
     * @return array|string[]
     */
    public function messages(): array
    {
        return [
            'operation_category_id.required' => __('messages.operation_category.operation_category_field_required'),
            'operation_id.required' => __('messages.operation.operation_field_required'),
            'operation_date' => __('messages.operation.select_operation_date'),
            'doctor_id.required' => __('messages.doctor_department.doctor_field_required'),
        ];
    }
}
