<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Str;

/**
 * Class IpdPatientDepartment
 *
 * @version September 8, 2020, 6:42 am UTC
 *
 * @property int $patient_id
 * @property string $ipd_number
 * @property string $height
 * @property string $weight
 * @property string $bp
 * @property string $symptoms
 * @property string $notes
 * @property string $admission_date
 * @property int $case_id
 * @property bool $is_old_patient
 * @property int $doctor_id
 * @property int $bed_type_id
 * @property int $bed_id
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereAdmissionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereBedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereBedTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereBp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereIpdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereIsOldPatient($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereSymptoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpdPatientDepartment whereWeight($value)
 *
 * @mixin \Eloquent
 *
 * @property-read Bed $bed
 * @property-read BedType|null $bedType
 * @property-read Doctor|null $doctor
 * @property-read Patient $patient
 * @property-read PatientCase|null $patientCase
 * @property-read BedAssign $bedAssign
 * @property int $bill_status
 * @property-read \App\Models\IpdBill|null $bill
 *
 * @method static \Illuminate\Database\Eloquent\Builder|IpdPatientDepartment whereBillStatus($value)
 */
class IpdPatientDepartment extends Model
{
    const STATUS_ARR = [
        '' => 'All',
        0 => 'Active',
        1 => 'Discharged',
    ];

    const FILTER_STATUS_ARR = [
        0 => 'All',
        1 => 'Active',
        2 => 'Discharged',
    ];

    public static $rules = [
        'patient_id' => 'required',
        'admission_date' => 'required',
        'doctor_id' => 'required',
        'bed_type_id' => 'required',
        'bed_id' => 'required',
        'case_id' => 'required',
        'weight' => 'numeric|max:200|nullable',
        'height' => 'numeric|nullable',
        'bp' => 'numeric|max:200|nullable',
    ];

    public $table = 'ipd_patient_departments';

    public $fillable = [
        'patient_id',
        'ipd_number',
        'height',
        'weight',
        'bp',
        'symptoms',
        'notes',
        'admission_date',
        'case_id',
        'is_old_patient',
        'doctor_id',
        'bed_type_id',
        'bed_id',
        'discharge',
        'custom_field',
        'patient_insurance_id',
        'price_list_id',
        'voucher_number',
        'package_id',
        'guardian_name',
        'guardian_relation',
        'guardian_contact',
        'guardian_address',
        'discharge_date',
        'discharge_outcome',
        'discharge_checks',
        'discharge_summary',
        'discharged_by',
    ];

    const SUMMARY_PLACEHOLDERS = ['hospital', 'patient_name', 'patient_id', 'age', 'gender', 'ipd_number', 'admission_date',
        'discharge_date', 'stay_days', 'doctor', 'department', 'diagnosis', 'operations', 'outcome'];

    public function setDischargeChecksAttribute($value): void
    {
        $this->attributes['discharge_checks'] = json_encode(array_values(array_unique(array_filter(array_map('intval', (array) $value)))));
    }

    public function getDischargeChecksAttribute($value): array
    {
        return array_map('intval', json_decode((string) $value, true) ?: []);
    }

    /** Draft of the discharge summary: the admin's template with this admission's details filled in. */
    public function dischargeSummaryDraft(): string
    {
        $template = (string) Setting::where('key', 'discharge_summary_template')->value('value');
        $user = $this->patient?->patientUser;
        $discharged = $this->discharge_date ? \Illuminate\Support\Carbon::parse($this->discharge_date) : now();
        $admitted = \Illuminate\Support\Carbon::parse($this->admission_date);
        $values = [
            'hospital' => getAppName(),
            'patient_name' => $user?->full_name,
            'patient_id' => $this->patient?->patient_unique_id,
            'age' => $user?->dob ? \Illuminate\Support\Carbon::parse($user->dob)->age . ' ' . __('messages.discharge.years') : '',
            'gender' => $user ? ($user->gender == 1 ? __('messages.user.female') : __('messages.user.male')) : '',
            'ipd_number' => $this->ipd_number,
            'admission_date' => $admitted->format('d/m/Y'),
            'discharge_date' => $discharged->format('d/m/Y'),
            'stay_days' => max(1, (int) ceil($admitted->diffInMinutes($discharged) / 1440)),
            'doctor' => $this->doctor?->doctorUser?->full_name,
            'department' => $this->doctor?->department?->title,
            'diagnosis' => IpdDiagnosis::where('ipd_patient_department_id', $this->id)->orderBy('report_date')->get()
                ->map(fn ($d) => '- ' . trim($d->report_type . ' ' . strip_tags((string) $d->description)))->implode("\n"),
            'operations' => IpdOperation::with('operations')->where('ipd_patient_department_id', $this->id)->orderBy('operation_date')->get()
                ->filter->isBillable()->map(fn ($o) => '- ' . \Illuminate\Support\Carbon::parse($o->operation_date)->format('d/m/Y') . ' ' . ($o->operations->name ?? ''))->implode("\n"),
            'outcome' => MasterListItem::label('discharge_outcome', $this->discharge_outcome, ''),
        ];

        return strtr($template, collect($values)->mapWithKeys(fn ($v, $k) => ['{' . $k . '}' => (string) $v])->all());
    }

    /**
     * Reasons that stop the discharge (empty = allowed): the checklist when the admin requires it,
     * and a balance still due when the admin blocks unpaid discharges.
     */
    public static function dischargeBlockers(array $activeChecks, array $done, float $balance, bool $requireChecklist, bool $blockUnpaid): array
    {
        $reasons = [];
        $missing = array_diff(array_keys($activeChecks), $done);
        if ($requireChecklist && $missing) {
            $reasons[] = __('messages.discharge.checklist_incomplete', ['items' => implode(', ', array_intersect_key($activeChecks, array_flip($missing)))]);
        }
        if ($blockUnpaid && $balance > 0.009) {
            $reasons[] = __('messages.discharge.balance_unpaid', ['amount' => formatMoney($balance)]);
        }

        return $reasons;
    }

    /** Package and guardian of the admission (moved from the old Patient Admissions screen). */
    public static function admissionRules(): array
    {
        return [
            'package_id' => 'nullable|integer|exists:packages,id',
            'guardian_name' => 'nullable|string|max:191',
            'guardian_relation' => 'nullable|integer',
            'guardian_contact' => 'nullable|string|max:50',
            'guardian_address' => 'nullable|string|max:500',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    protected $casts = [
        'id' => 'integer',
        'patient_id' => 'integer',
        'ipd_number' => 'string',
        'height' => 'integer',
        'weight' => 'integer',
        'bp' => 'string',
        'symptoms' => 'string',
        'notes' => 'string',
        'case_id' => 'integer',
        'is_old_patient' => 'boolean',
        'doctor_id' => 'integer',
        'bed_type_id' => 'integer',
        'bed_id' => 'integer',
        'discharge' => 'boolean',
        'custom_field' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function patientCase(): BelongsTo
    {
        return $this->belongsTo(PatientCase::class, 'case_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function bedType(): BelongsTo
    {
        return $this->belongsTo(BedType::class, 'bed_type_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'bed_id');
    }

    public function bedAssign(): BelongsTo
    {
        return $this->belongsTo(BedAssign::class, 'bed_id');
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function examOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExamOrder::class, 'ipd_patient_department_id');
    }

    public function bill()
    {
        return $this->hasOne(IpdBill::class, 'ipd_patient_department_id');
    }

    public static function generateUniqueIpdNumber()
    {
        return generateCustomSerialNumber(self::class, 'ipd_number');
    }
    
}
