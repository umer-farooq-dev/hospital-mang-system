<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class IpdOperation extends Model
{
    use HasFactory;

    protected $table = 'ipd_operation';

    // Theatre fields saved from the add and edit forms.
    const OT_FIELDS = ['ot_room', 'scheduled_start', 'scheduled_end', 'status', 'intervention_type', 'anesthesia_type',
        'assistant_doctor_id', 'anaesthetist_doctor_id', 'preop_checks'];

    protected $fillable = [
        'ref_no', 'operation_category_id', 'operation_id', 'ipd_patient_department_id', 'operation_date',
        'assistant_consultant_1', 'assistant_consultant_2', 'doctor_id', 'anesthetist', 'anesthesia_type',
        'ot_technician', 'ot_assistant', 'remark', 'result',
        'ot_room', 'scheduled_start', 'scheduled_end', 'status', 'intervention_type', 'assistant_doctor_id',
        'anaesthetist_doctor_id', 'preop_checks',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime:Y-m-d H:i',
        'scheduled_end' => 'datetime:Y-m-d H:i',
        'preop_checks' => 'array',
        'k_value' => 'float',
        'surgeon_fee' => 'float',
        'assistant_fee' => 'float',
        'anaesthetist_fee' => 'float',
    ];

    public static $rules = [
        'operation_category_id' => 'required',
        'operation_id' => 'required',
        'operation_date' => 'required',
        'doctor_id' => 'required',
    ];

    public static function otRules(): array
    {
        return [
            'ot_room' => 'nullable|integer',
            'scheduled_start' => 'nullable|date|required_with:scheduled_end',
            'scheduled_end' => 'nullable|date|required_with:scheduled_start|after:scheduled_start',
            'status' => 'nullable|integer',
            'intervention_type' => 'nullable|integer',
            'anesthesia_type' => 'nullable|string|max:191',
            'assistant_doctor_id' => 'nullable|integer|exists:doctors,id',
            'anaesthetist_doctor_id' => 'nullable|integer|exists:doctors,id',
            'preop_checks' => 'nullable|array',
            'preop_checks.*' => 'nullable|integer',
        ];
    }

    protected static function booted(): void
    {
        // Fees are priced once (new operation, other act, or never priced) so later K changes do not rewrite old bills.
        static::saving(function (IpdOperation $operation) {
            if (! $operation->exists || $operation->isDirty('operation_id') || $operation->k_value === null) {
                $operation->priceFees();
            }
        });
    }

    public function setPreopChecksAttribute($value): void
    {
        $codes = array_values(array_unique(array_filter(array_map('intval', (array) $value))));
        $this->attributes['preop_checks'] = json_encode($codes);
    }

    /** Status codes that free the room and are not billed (Settings on the OT calendar). */
    public static function excludedStatuses(): array
    {
        $value = Setting::where('key', 'ot_excluded_statuses')->value('value');

        return array_values(array_filter(array_map('intval', explode(',', (string) $value))));
    }

    public function isBillable(): bool
    {
        return ! in_array((int) $this->status, self::excludedStatuses(), true);
    }

    public function getTotalFeeAttribute(): float
    {
        return round($this->surgeon_fee + $this->assistant_fee + $this->anaesthetist_fee, 2);
    }

    /** Value of K from the admission's price list, else the default list. */
    public static function kValueFor(?IpdPatientDepartment $ipd): float
    {
        $list = $ipd?->priceList ?? $ipd?->patientInsurance?->insurance?->priceList;
        $default = PriceList::defaultList();

        return (float) ($list?->k_value ?? $default?->k_value ?? 0);
    }

    /** Surgeon, assistant and anaesthetist fees = K coefficient of the act x value of K. */
    public function priceFees(): void
    {
        $act = Operation::find($this->operation_id);
        $k = self::kValueFor(IpdPatientDepartment::find($this->ipd_patient_department_id));
        $this->k_value = $k;
        $this->surgeon_fee = round((float) ($act?->k_surgeon ?? 0) * $k, 2);
        $this->assistant_fee = round((float) ($act?->k_assistant ?? 0) * $k, 2);
        $this->anaesthetist_fee = round((float) ($act?->k_anaesthetist ?? 0) * $k, 2);
    }

    /**
     * First operation that overlaps the given slot in the same room or with the same surgeon,
     * ignoring statuses that free the room. Returns [operation, 'room'|'surgeon'] or null.
     */
    public static function clash(array $data, ?int $ignoreId = null): ?array
    {
        if (empty($data['scheduled_start']) || empty($data['scheduled_end'])) {
            return null;
        }
        $start = Carbon::parse($data['scheduled_start']);
        $end = Carbon::parse($data['scheduled_end']);
        $excluded = self::excludedStatuses();
        $overlapping = self::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('scheduled_start', '<', $end)->where('scheduled_end', '>', $start)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', $excluded ?: [0]));
        foreach (['room' => ['ot_room', $data['ot_room'] ?? null], 'surgeon' => ['doctor_id', $data['doctor_id'] ?? null]] as $kind => [$column, $value]) {
            if ($value && ($found = (clone $overlapping)->where($column, $value)->first())) {
                return [$found, $kind];
            }
        }

        return null;
    }

    public function ipd_patient_department(): BelongsTo
    {
        return $this->belongsTo(IpdPatientDepartment::class);
    }

    public function operations(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function assistantDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'assistant_doctor_id');
    }

    public function anaesthetistDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anaesthetist_doctor_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(OperationFollowup::class)->orderBy('noted_at');
    }
}
