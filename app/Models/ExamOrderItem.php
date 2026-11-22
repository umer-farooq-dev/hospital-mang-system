<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One exam of a request, with its price and the payer/patient split.
 */
class ExamOrderItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const FILES = 'exam_files';

    // Images, PDF and DICOM files attached to a result.
    public const FILE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'dcm', 'dicom'];
    protected $table = 'exam_order_items';

    protected $fillable = ['exam_order_id', 'testable_type', 'testable_id', 'description', 'total', 'discount_amount', 'payer_amount', 'patient_amount',
        'status', 'sample_number', 'collected_at', 'collected_by', 'result_entered_at', 'result_entered_by', 'validated_at', 'validated_by',
        'released_at', 'released_by', 'result_note', 'report_text'];

    // Work steps of a lab or imaging exam, in order.
    const STATUSES = ['ordered', 'collected', 'in_progress', 'validated', 'released'];

    const WORK_TYPES = ['pathology_test', 'radiology_test'];

    protected $casts = [
        'testable_id' => 'integer',
        'total' => 'float',
        'discount_amount' => 'float',
        'payer_amount' => 'float',
        'patient_amount' => 'float',
        'collected_at' => 'datetime',
        'result_entered_at' => 'datetime',
        'validated_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class)->orderBy('sort_order')->orderBy('id');
    }

    public function testable()
    {
        $class = ExamOrder::TEST_TYPES[$this->testable_type] ?? null;

        return $class ? $class::find($this->testable_id) : null;
    }

    /** Viewer link built from the admin's PACS URL template, or null when no PACS is set. */
    public function pacsUrl(?string $template): ?string
    {
        if (! $template || ! $this->sample_number) {
            return null;
        }

        return strtr($template, ['{sample_number}' => rawurlencode($this->sample_number),
            '{patient_id}' => rawurlencode((string) $this->order?->patient?->patient_unique_id)]);
    }

    /** Lab and imaging exams of a patient or of an admission, newest first. */
    public static function workFor(?int $patientId = null, ?int $ipdId = null)
    {
        return self::with('order.doctor.doctorUser', 'results')
            ->whereIn('testable_type', self::WORK_TYPES)
            ->whereHas('order', fn ($q) => $q->when($patientId, fn ($q) => $q->where('patient_id', $patientId))
                ->when($ipdId, fn ($q) => $q->where('ipd_patient_department_id', $ipdId)))
            ->orderByDesc('id')->get();
    }

    public function getIsLabAttribute(): bool
    {
        return $this->testable_type === 'pathology_test';
    }

    public function stepUser(string $step): ?User
    {
        $id = $this->{$step . '_by'};

        return $id ? User::find($id) : null;
    }

    /** Paid at the cashier, or billed on the admission (IPD orders go on the IPD bill). */
    public function isPaid(): bool
    {
        if ($this->order?->ipd_patient_department_id || $this->patient_amount <= 0) {
            return true;
        }

        return DB::table('receipt_lines')->join('receipts', 'receipts.id', '=', 'receipt_lines.receipt_id')
            ->where('receipt_lines.payable_type', 'exam_item')->where('receipt_lines.payable_id', $this->id)
            ->whereNull('receipts.voided_at')->exists();
    }

    public static function sampleNumber(string $prefix, int $id): string
    {
        return ($prefix !== '' ? $prefix : 'S') . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /** Parameters of a pathology test (name, unit, reference range) for the result entry. */
    public static function parametersOf(?PathologyTest $test): array
    {
        if (! $test) {
            return [];
        }

        return PathologyParameterItem::with('pathologyParameter.pathologyUnit')->where('pathology_id', $test->id)->orderBy('id')->get()
            ->filter(fn ($i) => $i->pathologyParameter)
            ->map(fn ($i) => ['pathology_parameter_id' => $i->pathologyParameter->id, 'label' => $i->pathologyParameter->parameter_name,
                'unit' => $i->pathologyParameter->pathologyUnit?->name, 'reference_range' => $i->pathologyParameter->reference_range, 'value' => null])
            ->values()->all();
    }

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
