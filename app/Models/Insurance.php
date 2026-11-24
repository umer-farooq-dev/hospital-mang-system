<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Class Insurance
 *
 * @version February 22, 2020, 9:01 am UTC
 *
 * @property int $id
 * @property string $name
 * @property float $service_tax
 * @property float|null $discount
 * @property string|null $remark
 * @property string $insurance_no
 * @property string $insurance_code
 * @property float $hospital_rate
 * @property float $total
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Insurance newModelQuery()
 * @method static Builder|Insurance newQuery()
 * @method static Builder|Insurance query()
 * @method static Builder|Insurance whereCreatedAt($value)
 * @method static Builder|Insurance whereDiscount($value)
 * @method static Builder|Insurance whereHospitalRate($value)
 * @method static Builder|Insurance whereId($value)
 * @method static Builder|Insurance whereInsuranceCode($value)
 * @method static Builder|Insurance whereInsuranceNo($value)
 * @method static Builder|Insurance whereName($value)
 * @method static Builder|Insurance whereRemark($value)
 * @method static Builder|Insurance whereServiceTax($value)
 * @method static Builder|Insurance whereStatus($value)
 * @method static Builder|Insurance whereTotal($value)
 * @method static Builder|Insurance whereUpdatedAt($value)
 *
 * @mixin Model
 *
 * @property int $is_default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\InsuranceDisease[] $insuranceDiseases
 * @property-read int|null $insurance_diseases_count
 *
 * @method static Builder|Insurance whereIsDefault($value)
 */
class Insurance extends Model
{
    /** How the payer share is invoiced to the payer (used by the claims module). */
    const BILLING_OPTIONS = ['per_visit', 'per_admission', 'monthly'];

    const PAYER_TYPE = 'payer_type';

    // Optional columns of the claim invoice; the date, act, total and payer share are always printed.
    const CLAIM_COLUMNS = ['patient_number', 'voucher', 'icd10', 'patient_share'];

    const DEFAULT_CLAIM_COLUMNS = ['patient_number', 'voucher', 'patient_share'];

    /** Columns this insurer's claim invoices show. */
    public function claimColumns(): array
    {
        return $this->claim_columns === null ? self::DEFAULT_CLAIM_COLUMNS : array_values(array_intersect(self::CLAIM_COLUMNS, $this->claim_columns));
    }

    public static function rules(?int $ignoreId = null): array
    {
        return [
            'name' => 'required|max:160|unique:insurances,name'.($ignoreId ? ','.$ignoreId : ''),
            'payer_type' => ['required', 'integer', Rule::in(array_keys(MasterListItem::labels(self::PAYER_TYPE)))],
            'coverage_percent' => 'nullable|numeric|min:0|max:100',
            'discount' => 'nullable|numeric|min:0|max:100',
            'service_tax' => 'nullable|numeric|min:0',
            'hospital_rate' => 'nullable|numeric|min:0',
            'insurance_no' => 'nullable|max:191',
            'insurance_code' => 'required|max:191',
            'contact_person' => 'nullable|max:160',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|max:1000',
            'billing_option' => ['required', Rule::in(self::BILLING_OPTIONS)],
            'credit_days' => 'nullable|integer|min:0|max:365',
            'contract_from' => 'nullable|date',
            'contract_to' => 'nullable|date|after_or_equal:contract_from',
            'claim_note' => 'nullable|string|max:2000',
            'claim_title' => 'nullable|string|max:100',
            'claim_columns' => 'nullable|array',
            'claim_columns.*' => ['string', Rule::in(self::CLAIM_COLUMNS)],
            'price_list_id' => 'nullable|integer|exists:price_lists,id',
            'tax_id' => 'nullable|max:50',
            'trade_register' => 'nullable|max:50',
            'tax_regime' => 'nullable|max:100',
            'tax_division' => 'nullable|max:100',
            'account_code' => 'nullable|max:30',
        ];
    }

    public $table = 'insurances';

    const STATUS_ALL = 2;

    const ACTIVE = 1;

    const INACTIVE = 0;

    const STATUS_ARR = [
        self::STATUS_ALL => 'All',
        self::ACTIVE => 'Active',
        self::INACTIVE => 'Deactive',
    ];

    const FILTER_STATUS_ARRAY = [
        0 => 'All',
        1 => 'Active',
        2 => 'Deactive',
    ];

    public $fillable = [
        'name',
        'payer_type',
        'coverage_percent',
        'contact_person',
        'phone',
        'email',
        'address',
        'billing_option',
        'credit_days',
        'contract_from',
        'contract_to',
        'claim_note',
        'claim_title',
        'claim_columns',
        'price_list_id',
        'tax_id',
        'trade_register',
        'tax_regime',
        'tax_division',
        'account_code',
        'service_tax',
        'discount',
        'remark',
        'insurance_no',
        'insurance_code',
        'hospital_rate',
        'total',
        'status',
    ];

    protected $casts = [
        'claim_columns' => 'array',
        'contract_from' => 'date',
        'contract_to' => 'date',
        'id' => 'integer',
        'payer_type' => 'integer',
        'coverage_percent' => 'float',
        'credit_days' => 'integer',
        'price_list_id' => 'integer',
        'service_tax' => 'double',
        'discount' => 'double',
        'remark' => 'string',
        'insurance_no' => 'string',
        'insurance_code' => 'string',
        'hospital_rate' => 'double',
        'total' => 'double',
        'status' => 'integer',
        'name' => 'string',
    ];

    public function insuranceDiseases(): HasMany
    {
        return $this->hasMany(InsuranceDisease::class, 'insurance_id');
    }

    /** Field names in validation messages, as labelled on the form. */
    public static function attributeNames(): array
    {
        $names = collect(array_keys(self::rules()))->mapWithKeys(fn ($f) => [$f => __('messages.insurance.'.$f)])->all();
        $names['name'] = __('messages.insurance.insurance');

        return $names;
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function coverageRules(): HasMany
    {
        return $this->hasMany(CoverageRule::class, 'insurance_id')->orderByRaw('doctor_department_id IS NULL, charge_category_id IS NULL')->orderBy('id');
    }

    public function patientInsurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class, 'insurance_id');
    }

    public function getPayerTypeNameAttribute(): ?string
    {
        return MasterListItem::label(self::PAYER_TYPE, $this->payer_type);
    }

    public static function billingOptions(): array
    {
        return collect(self::BILLING_OPTIONS)->mapWithKeys(fn ($o) => [$o => __('messages.insurance.billing_options.'.$o)])->all();
    }

    /** Active payers for a dropdown, keeping a current (possibly deactivated) one. */
    /** Active payers whose contract has not ended (insurers change every year), plus the one already chosen. */
    public static function options($currentId = null): array
    {
        $today = now()->toDateString();

        return self::where(fn ($q) => $q->where('status', self::ACTIVE)->where(fn ($c) => $c->whereNull('contract_to')->orWhereDate('contract_to', '>=', $today)))
            ->when($currentId, fn ($q) => $q->orWhere('id', $currentId))->orderBy('name')->pluck('name', 'id')->all();
    }

    /** 'ended', 'not_started' or null when the contract covers the day. */
    public function contractStatusOn($date = null): ?string
    {
        $day = \Illuminate\Support\Carbon::parse($date ?? now())->startOfDay();
        if ($this->contract_to && $this->contract_to->lt($day)) {
            return 'ended';
        }

        return $this->contract_from && $this->contract_from->gt($day) ? 'not_started' : null;
    }
}
