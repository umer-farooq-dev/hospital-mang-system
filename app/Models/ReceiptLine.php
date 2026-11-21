<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One item paid on a receipt (an OPD visit today; lab, IPD and pharmacy items later).
 */
class ReceiptLine extends Model
{
    protected $table = 'receipt_lines';

    protected $fillable = ['receipt_id', 'payable_type', 'payable_id', 'description', 'total', 'discount_amount', 'payer_amount', 'amount'];

    protected $casts = [
        'payable_id' => 'integer',
        'total' => 'float',
        'discount_amount' => 'float',
        'payer_amount' => 'float',
        'amount' => 'float',
    ];

    /** Billable types the cashier collects: type => model. */
    const PAYABLE_TYPES = [
        'opd_visit' => OpdPatientDepartment::class,
        'exam_item' => ExamOrderItem::class,
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function payable()
    {
        return $this->belongsTo(self::PAYABLE_TYPES[$this->payable_type], 'payable_id');
    }
}
