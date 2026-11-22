<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One act on a claim invoice: what the payer owes for it, what the patient paid (T.M.), and what came back. */
class ClaimLine extends Model
{
    const PENDING = 'pending';

    const PAID = 'paid';

    const PARTIAL = 'partial';

    const REJECTED = 'rejected';

    const RESUBMIT = 'resubmit';        // rejected, to go on a later invoice

    const WRITTEN_OFF = 'written_off';

    const TO_PATIENT = 'to_patient';    // rejected amount moved to the patient's balance

    const STATUSES = [self::PENDING, self::PARTIAL, self::PAID, self::REJECTED, self::RESUBMIT, self::WRITTEN_OFF, self::TO_PATIENT];

    // Source type => model; the same keys as the cashier's payable types where they exist.
    const SOURCES = [
        'opd_visit' => OpdPatientDepartment::class,
        'exam_item' => ExamOrderItem::class,
        'medicine_bill' => MedicineBill::class,
        'ipd_bill' => IpdBill::class,
    ];

    protected $fillable = ['claim_invoice_id', 'source_type', 'source_id', 'patient_id', 'patient_insurance_id', 'voucher_number', 'service_date',
        'description', 'total', 'patient_amount', 'payer_amount', 'paid_amount', 'status', 'rejection_reason', 'note'];

    protected $casts = ['total' => 'float', 'patient_amount' => 'float', 'payer_amount' => 'float', 'paid_amount' => 'float', 'service_date' => 'date'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ClaimInvoice::class, 'claim_invoice_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** What the payer still owes on this line. */
    public function outstanding(): float
    {
        return in_array($this->status, [self::PENDING, self::PARTIAL], true) ? round($this->payer_amount - $this->paid_amount, 2) : 0.0;
    }
}
