<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A patient's policy with an insurer, or membership of a partner company. The payer's
 * coverage % applies unless the policy sets its own; the limits are checked by the split engine.
 *
 * @property int $id
 * @property int $patient_id
 * @property int $insurance_id
 * @property string|null $plan_name
 * @property string|null $policy_number
 * @property string|null $member_number
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_to
 * @property float|null $coverage_percent
 * @property float|null $annual_limit
 * @property float|null $visit_limit
 * @property int $priority
 * @property bool $is_active
 * @property string|null $notes
 */
class PatientInsurance extends Model
{
    protected $table = 'patient_insurances';

    /** Same defaults as the table, so a policy just created is active right away. */
    protected $attributes = ['is_active' => true, 'priority' => 1];

    protected $fillable = [
        'patient_id',
        'insurance_id',
        'plan_name',
        'policy_number',
        'member_number',
        'valid_from',
        'valid_to',
        'coverage_percent',
        'annual_limit',
        'visit_limit',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'insurance_id' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'coverage_percent' => 'float',
        'annual_limit' => 'float',
        'visit_limit' => 'float',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function rules(): array
    {
        return [
            'insurance_id' => 'required|integer|exists:insurances,id',
            'plan_name' => 'nullable|max:160',
            'policy_number' => 'nullable|max:100',
            'member_number' => 'nullable|max:100',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'annual_limit' => 'nullable|numeric|min:0',
            'visit_limit' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1|max:99',
            'notes' => 'nullable|max:1000',
        ];
    }

    /** Values that fill the edit form. */
    public function formValues(): array
    {
        return $this->only(['insurance_id', 'plan_name', 'policy_number', 'member_number', 'coverage_percent', 'annual_limit', 'visit_limit', 'priority', 'is_active', 'notes'])
            + ['valid_from' => $this->valid_from?->toDateString(), 'valid_to' => $this->valid_to?->toDateString()];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    /** Active policies whose payer is active and whose dates cover the given day. */
    public function scopeValidOn(Builder $query, $date = null): Builder
    {
        $day = Carbon::parse($date ?? now())->toDateString();

        return $query->where('is_active', true)
            ->whereHas('insurance', fn (Builder $q) => $q->where('status', Insurance::ACTIVE))
            ->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day))
            ->where(fn (Builder $q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $day));
    }

    /** The share the payer pays: the policy's own % or else the payer's. */
    public function getEffectiveCoverageAttribute(): ?float
    {
        return $this->coverage_percent ?? $this->insurance->coverage_percent ?? null;
    }

    /** 'valid', 'expired', 'not_started' or 'inactive' on a given day. */
    public function statusOn($date = null): string
    {
        $day = Carbon::parse($date ?? now())->startOfDay();
        if (! $this->is_active || ! $this->insurance || $this->insurance->status != Insurance::ACTIVE) {
            return 'inactive';
        }
        if ($this->valid_to && $this->valid_to->lt($day)) {
            return 'expired';
        }
        if ($this->valid_from && $this->valid_from->gt($day)) {
            return 'not_started';
        }

        return 'valid';
    }

    public function isValidOn($date = null): bool
    {
        return $this->statusOn($date) === 'valid';
    }

    /** Today's status, for the badge shown to staff. */
    public function getValidityStatusAttribute(): string
    {
        return $this->statusOn();
    }

    /** Label for a payer dropdown: payer, number and share. */
    public function getOptionLabelAttribute(): string
    {
        $number = $this->policy_number ?: $this->member_number;

        return trim($this->insurance->name.($number ? ' · '.$number : '')
            .($this->effective_coverage !== null ? ' · '.formatPercent($this->effective_coverage) : ''));
    }

    /** The policy chosen first on a new visit, exam or admission: the valid one with the best priority, when Settings > Reception says so. */
    public static function defaultFor($policies): ?self
    {
        if (Patient::registrationSettings()['default_payer'] !== 'policy') {
            return null;
        }

        return collect($policies)->filter(fn ($p) => $p->is_active && $p->isValidOn())->sortBy('priority')->first();
    }
}
