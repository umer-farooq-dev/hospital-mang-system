<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Operation extends Model
{
    use HasFactory;

    public $table = 'operations';

    public $fillable = ['operation_category_id', 'name', 'k_surgeon', 'k_assistant', 'k_anaesthetist'];

    protected $casts = [
        'id' => 'integer',
        'operation_category_id' => 'integer',
        'name' => 'string',
    ];

    public static $rules = [
        'operation_category_id' => 'required',
        'name' => 'required|unique:operations,name',
        'k_surgeon' => 'nullable|numeric|min:0|max:100000',
        'k_assistant' => 'nullable|numeric|min:0|max:100000',
        'k_anaesthetist' => 'nullable|numeric|min:0|max:100000',
    ];

    public function operation_category(): BelongsTo
    {
        return $this->belongsTo(OperationCategory::class);
    }
}
