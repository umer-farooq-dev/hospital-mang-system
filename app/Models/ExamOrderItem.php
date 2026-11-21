<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One exam of a request, with its price and the payer/patient split.
 */
class ExamOrderItem extends Model
{
    protected $table = 'exam_order_items';

    protected $fillable = ['exam_order_id', 'testable_type', 'testable_id', 'description', 'total', 'discount_amount', 'payer_amount', 'patient_amount'];

    protected $casts = [
        'testable_id' => 'integer',
        'total' => 'float',
        'discount_amount' => 'float',
        'payer_amount' => 'float',
        'patient_amount' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ExamOrder::class, 'exam_order_id');
    }

    /** The prescriber, so a receipt can name the doctor whatever it pays. */
    public function getDoctorAttribute(): ?Doctor
    {
        return $this->order?->doctor;
    }
}
