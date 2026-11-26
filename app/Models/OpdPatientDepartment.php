<?php

namespace App\Models;

use App\Utils\PriceSplit;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Str;

/**
 * App\Models\OpdPatientDepartment
 *
 * @property int $id
 * @property int $patient_id
 * @property string $opd_number
 * @property string|null $height
 * @property string|null $weight
 * @property string|null $bp
 * @property string|null $symptoms
 * @property string|null $notes
 * @property Carbon $appointment_date
 * @property int|null $case_id
 * @property bool|null $is_old_patient
 * @property int|null $doctor_id
 * @property float $standard_charge
 * @property int $payment_mode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Doctor|null $doctor
 * @property-read mixed $payment_mode_name
 * @property-read Patient $patient
 * @property-read PatientCase|null $patientCase
 *
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereAppointmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereBp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereIsOldPatient($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereOpdNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment wherePaymentMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereStandardCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereSymptoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OpdPatientDepartment whereWeight($value)
 *
 * @mixin \Eloquent
 */
class OpdPatientDepartment extends Model
{
    public $table = 'opd_patient_departments';

    public $fillable = [
        'patient_id',
        'opd_number',
        'height',
        'weight',
        'bp',
        'symptoms',
        'notes',
        'appointment_date',
        'case_id',
        'is_old_patient',
        'doctor_id',
        'standard_charge',
        'payment_mode',
        'patient_origin',
        'emergency_level',
        'referrer',
        'price_list_id',
        'patient_insurance_id',
        'voucher_number',
        'discount_percent',
        'discount_amount',
        'payer_amount',
        'patient_amount',
        'is_followup',
        'currency_symbol',
        'custom_field',
    ];

    public static $rules = [
        'patient_id' => 'required',
        'appointment_date' => 'required',
        'doctor_id' => 'required',
        'standard_charge' => 'required',
        'payment_mode' => 'required',
        'weight' => 'numeric|max:200|nullable',
        'height' => 'numeric|nullable',
        'bp' => 'nullable|numeric|max:200',
    ];

    protected $appends = ['payment_mode_name'];

    /** Visit details picked from Master Lists, as on the old reception screen; the column is named after the list. */
    const VISIT_LISTS = ['patient_origin', 'emergency_level', 'referrer'];

    /** Payer, price list and voucher number chosen at reception. */
    public static function payerRules(): array
    {
        return [
            'patient_insurance_id' => 'nullable|integer|exists:patient_insurances,id',
            'price_list_id' => 'nullable|integer|exists:price_lists,id',
            'voucher_number' => 'nullable|string|max:50',
        ];
    }

    /**
     * The payer/patient split of a visit from the form input. Only a policy of the visit's
     * own patient counts; the doctor's department brings its discount and coverage rule.
     *
     * @return array{0: ?PatientInsurance, 1: array}
     */
    public static function splitFor(array $input, ?int $visitId = null): array
    {
        $policy = filled($input['patient_insurance_id'] ?? null)
            ? PatientInsurance::with('insurance')->where('patient_id', (int) ($input['patient_id'] ?? 0))->find($input['patient_insurance_id'])
            : null;
        $departmentId = Doctor::whereKey((int) ($input['doctor_id'] ?? 0))->value('doctor_department_id');
        $gross = (float) removeCommaFromNumbers((string) ($input['standard_charge'] ?? 0));
        $followup = self::followupFactor($input, $visitId);
        $split = PriceSplit::compute($gross * ($followup ?? 1), $policy, $departmentId, [
            'date' => $input['appointment_date'] ?? now(),
            'voucher_number' => $input['voucher_number'] ?? null,
            'ignore_visit_id' => $visitId,
            'extra_discount' => $input['extra_discount'] ?? null,
        ]);
        if ($followup !== null) {
            $split['warnings'][] = $followup == 0 ? 'followup_free' : 'followup_reduced';
        }
        $split['is_followup'] = $followup !== null;

        return [$policy, $split];
    }

    /**
     * Follow-up rule (Settings > Cash registers): a consultation with the same doctor within the
     * validity days of a paid consultation is free or reduced. Returns the price factor, or null.
     * ponytail: "same condition" cannot be checked by the system; the cashier decides by removing the doctor or the visit.
     */
    public static function followupFactor(array $input, ?int $visitId = null): ?float
    {
        $settings = Setting::whereIn('key', ['followup_mode', 'followup_percent', 'receipt_followup_days'])->pluck('value', 'key');
        $mode = $settings['followup_mode'] ?? 'off';
        $days = (int) ($settings['receipt_followup_days'] ?? 0);
        if (! in_array($mode, ['free', 'percent']) || $days <= 0 || empty($input['patient_id']) || empty($input['doctor_id'])) {
            return null;
        }
        $date = Carbon::parse($input['appointment_date'] ?? now());
        $previous = self::where('patient_id', (int) $input['patient_id'])->where('doctor_id', (int) $input['doctor_id'])
            ->where('is_followup', false)
            ->when($visitId, fn ($q) => $q->where('id', '!=', $visitId))
            ->whereBetween('appointment_date', [$date->copy()->subDays($days)->startOfDay(), $date])
            ->pluck('id');
        $paid = $previous->isNotEmpty() && ReceiptLine::where('payable_type', 'opd_visit')->whereIn('payable_id', $previous)
            ->whereHas('receipt', fn ($q) => $q->whereNull('voided_at')->whereDoesntHave('refund'))->exists();
        if (! $paid) {
            return null;
        }

        return $mode === 'free' ? 0.0 : max(0, 1 - min(100, (float) ($settings['followup_percent'] ?? 0)) / 100);
    }

    /** Form input with the split columns filled in, ready to save. */
    public static function withSplit(array $input, ?int $visitId = null): array
    {
        [$policy, $split] = self::splitFor($input, $visitId);
        PriceSplit::assertPolicyAllowed($split);

        return array_merge($input, [
            'is_followup' => $split['is_followup'],
            'standard_charge' => $split['gross'],
            'patient_insurance_id' => $policy?->id,
            'discount_percent' => $split['discount_percent'],
            'discount_amount' => $split['discount_amount'],
            'payer_amount' => $split['payer_amount'],
            'patient_amount' => $split['patient_amount'],
        ]);
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public static function visitListRules(): array
    {
        return collect(self::VISIT_LISTS)
            ->mapWithKeys(fn ($list) => [$list => ['nullable', 'integer', Rule::in(array_keys(MasterListItem::labels($list)))]])
            ->all();
    }

    protected $casts = [
        'id' => 'integer',
        'patient_id' => 'integer',
        'opd_number' => 'string',
        'appointment_date' => 'datetime',
        'height' => 'integer',
        'weight' => 'integer',
        'bp' => 'integer',
        'symptoms' => 'string',
        'notes' => 'string',
        'case_id' => 'integer',
        'is_old_patient' => 'boolean',
        'doctor_id' => 'integer',
        'standard_charge' => 'double',
        'payment_mode' => 'integer',
        'patient_origin' => 'integer',
        'emergency_level' => 'integer',
        'referrer' => 'integer',
        'price_list_id' => 'integer',
        'patient_insurance_id' => 'integer',
        'discount_percent' => 'float',
        'discount_amount' => 'float',
        'payer_amount' => 'float',
        'patient_amount' => 'float',
        'is_followup' => 'boolean',
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

    public static function generateUniqueOpdNumber()
    {
        return generateCustomSerialNumber(self::class, 'opd_number');
    }
    

    public function getPaymentModeNameAttribute()
    {
        return PaymentMethod::label($this->payment_mode);
    }
}
