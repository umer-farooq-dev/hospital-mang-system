<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
/**
 * Class Patient
 *
 * @version February 14, 2020, 5:53 am UTC
 *
 * @property int user_id
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $patientUser
 *
 * @method static Builder|Patient newModelQuery()
 * @method static Builder|Patient newQuery()
 * @method static Builder|Patient query()
 * @method static Builder|Patient whereCreatedAt($value)
 * @method static Builder|Patient whereId($value)
 * @method static Builder|Patient whereUpdatedAt($value)
 * @method static Builder|Patient whereUserId($value)
 *
 * @mixin Model
 *
 * @property int $is_default
 * @property-read \App\Models\Address|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PatientAdmission[] $admissions
 * @property-read int|null $admissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AdvancedPayment[] $advancedpayments
 * @property-read int|null $advancedpayments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Appointment[] $appointments
 * @property-read int|null $appointments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Bill[] $bills
 * @property-read int|null $bills_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PatientCase[] $cases
 * @property-read int|null $cases_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Document[] $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Invoice[] $invoices
 * @property-read int|null $invoices_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VaccinatedPatients[] $vaccinations
 * @property-read int|null $vaccinations_count
 *
 * @method static Builder|Patient whereIsDefault($value)
 */
class Patient extends Model
{
    public $table = 'patients';

    public $fillable = [
        'user_id',
        'patient_unique_id',
        'template_id',
        'custom_field',
        'id_document_type',
        'id_document_number',
    ];

    // Optional patient fields the admin can make required (Settings > Reception).
    const REQUIRABLE_FIELDS = ['email', 'phone', 'dob', 'id_document', 'address1', 'city'];

    // What the duplicate check compares, and what it does on a match.
    const DUPLICATE_FIELDS = ['id_document', 'phone', 'name_dob'];

    const DUPLICATE_MODES = ['off', 'warn', 'block'];

    private static $registrationSettings;

    const STATUS_ALL = 2;

    const ACTIVE = 1;

    const INACTIVE = 0;

    const STATUS_ARR = [
        self::STATUS_ALL => 'All',
        self::ACTIVE => 'Active',
        self::INACTIVE => 'Deactive',
    ];

    const FILTER_STATUS_ARR = [
        0 => 'All',
        1 => 'Active',
        2 => 'Deactive',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'custom_field' => 'array'
    ];

    /**
     * Reception settings, read once per request.
     */
    public static function registrationSettings(): array
    {
        if (self::$registrationSettings === null) {
            $values = Setting::whereIn('key', [
                'patient_required_fields', 'patient_portal_login', 'patient_duplicate_check', 'patient_duplicate_fields',
            ])->pluck('value', 'key');
            $list = fn ($key, $allowed) => array_values(array_intersect($allowed, explode(',', (string) $values->get($key, ''))));

            self::$registrationSettings = [
                'required_fields' => $list('patient_required_fields', self::REQUIRABLE_FIELDS),
                'portal_login' => (bool) $values->get('patient_portal_login', 1),
                'duplicate_check' => in_array($values->get('patient_duplicate_check'), self::DUPLICATE_MODES) ? $values->get('patient_duplicate_check') : 'off',
                'duplicate_fields' => $list('patient_duplicate_fields', self::DUPLICATE_FIELDS),
            ];
        }

        return self::$registrationSettings;
    }

    public static function flushRegistrationSettings(): void
    {
        self::$registrationSettings = null;
    }

    public static function isRequired(string $field): bool
    {
        return in_array($field, self::registrationSettings()['required_fields']);
    }

    /**
     * Validation rules for creating ($userId null) or updating a patient.
     */
    public static function registrationRules(?int $userId = null): array
    {
        $need = fn ($field) => self::isRequired($field) ? 'required' : 'nullable';
        $idRequired = self::isRequired('id_document');

        $rules = [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => [$need('email'), 'email:filter', Rule::unique('users', 'email')->ignore($userId)],
            'gender' => 'required',
            'dob' => $need('dob').'|date',
            'phone' => $need('phone').'|numeric',
            'address1' => $need('address1').'|string',
            'address2' => 'nullable|string',
            'city' => $need('city').'|string',
            'zip' => 'nullable|integer',
            'id_document_type' => [$idRequired ? 'required' : 'required_with:id_document_number', 'nullable',
                Rule::in(array_keys(MasterListItem::labels('id_document_type')))],
            'id_document_number' => [$idRequired ? 'required' : 'required_with:id_document_type', 'nullable', 'string', 'max:50'],
        ];

        // A password is only asked when patients may log in, and only for a patient with an e-mail.
        if ($userId === null && self::registrationSettings()['portal_login']) {
            $rules['password'] = 'nullable|required_with:email|same:password_confirmation|min:6';
        }

        return $rules;
    }

    /**
     * Patients who may be the same person, using the fields chosen in Settings > Reception.
     */
    public static function findDuplicates(array $input, ?int $ignorePatientId = null): Collection
    {
        $fields = self::registrationSettings()['duplicate_fields'];
        $checks = [];

        $number = self::normalizeIdNumber($input['id_document_number'] ?? null);
        if (in_array('id_document', $fields) && $number) {
            $checks[] = fn ($q) => $q->orWhere('id_document_number', $number);
        }
        $digits = preg_replace('/\D/', '', (string) ($input['phone'] ?? ''));
        if (in_array('phone', $fields) && strlen($digits) >= 6) {
            $phone = preparePhoneNumber(['phone' => $digits, 'prefix_code' => $input['prefix_code'] ?? ''], 'phone');
            $checks[] = fn ($q) => $q->orWhereHas('patientUser', fn ($u) => $u->where('phone', $phone));
        }
        if (in_array('name_dob', $fields) && ! empty($input['first_name']) && ! empty($input['last_name']) && ! empty($input['dob'])) {
            $checks[] = fn ($q) => $q->orWhereHas('patientUser', fn ($u) => $u->where('first_name', trim($input['first_name']))
                ->where('last_name', trim($input['last_name']))->whereDate('dob', $input['dob']));
        }

        if (empty($checks)) {
            return collect();
        }

        return self::with('patientUser')
            ->where(function ($q) use ($checks) {
                foreach ($checks as $check) {
                    $check($q);
                }
            })
            ->when($ignorePatientId, fn ($q) => $q->where('id', '!=', $ignorePatientId))
            ->limit(5)
            ->get();
    }

    /**
     * In "block" mode a registration that matches an existing patient is refused.
     */
    public static function blockDuplicates(\Illuminate\Validation\Validator $validator, array $input, ?int $ignorePatientId = null): void
    {
        if (self::registrationSettings()['duplicate_check'] !== 'block' || $validator->errors()->isNotEmpty()) {
            return;
        }

        $matches = self::findDuplicates($input, $ignorePatientId);
        if ($matches->isNotEmpty()) {
            $validator->errors()->add('duplicate', __('messages.reception.duplicate_found').' '.
                $matches->map(fn ($p) => $p->patientUser->full_name.' ('.$p->patient_unique_id.')')->implode(', '));
        }
    }

    public static function registrationAttributes(): array
    {
        return [
            'id_document_type' => __('messages.reception.id_document_type'),
            'id_document_number' => __('messages.reception.id_document_number'),
        ];
    }

    public static function normalizeIdNumber(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', (string) $value));

        return $value === '' ? null : $value;
    }

    public function setIdDocumentNumberAttribute($value): void
    {
        $this->attributes['id_document_number'] = self::normalizeIdNumber($value);
    }

    public function setIdDocumentTypeAttribute($value): void
    {
        $this->attributes['id_document_type'] = $value === '' || $value === null ? null : (int) $value;
    }

    public function getIdDocumentTypeNameAttribute(): ?string
    {
        return $this->id_document_type ? MasterListItem::label('id_document_type', $this->id_document_type) : null;
    }

    public static function generateUniquePatientId()
    {
        return generateCustomSerialNumber(self::class, 'patient_unique_id');
    }

    public static function getActivePatientNames()
    {
        $patients = DB::table('users')
            ->where('status', '=', 1)
            ->join('patients', 'users.id', '=', 'patients.user_id')
            ->select(['users.first_name', 'users.last_name', 'patients.id'])
            ->orderBy('first_name')
            ->get();
        $patientsNames = collect();
        foreach ($patients as $patient) {
            $patientsNames[$patient->id] = ucfirst($patient->first_name).' '.ucfirst($patient->last_name);
        }

        return $patientsNames;
    }

    public function prepareData()
    {
        return [
            'id' => $this->id,
            'patient_name' => $this->patientUser->full_name,
        ];
    }

    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'owner');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(PatientCase::class, 'patient_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(PatientAdmission::class, 'patient_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'patient_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }

    public function advancedpayments(): HasMany
    {
        return $this->hasMany(AdvancedPayment::class, 'patient_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'patient_id');
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(VaccinatedPatients::class, 'patient_id');
    }

    public function opd()
    {
        return $this->hasMany(OpdPatientDepartment::class, 'patient_id');
    }

    public function opdDepartment(): HasOne
    {
        return $this->hasOne(OpdPatientDepartment::class, 'patient_id');
    }

    public function idCardTemplate(): BelongsTo
    {
        return $this->belongsTo(PatientIdCardTemplate::class, 'template_id');
    }
}
