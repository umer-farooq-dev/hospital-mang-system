<?php

namespace App\Models;

use App\Utils\PriceSplit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exams requested at reception for one patient, with the payer and price list chosen there.
 */
class ExamOrder extends Model
{
    protected $table = 'exam_orders';

    protected $fillable = ['patient_id', 'ipd_patient_department_id', 'doctor_id', 'patient_insurance_id', 'price_list_id', 'voucher_number', 'ordered_at', 'user_id', 'notes'];

    protected $casts = [
        'patient_id' => 'integer',
        'doctor_id' => 'integer',
        'patient_insurance_id' => 'integer',
        'price_list_id' => 'integer',
        'ordered_at' => 'datetime',
    ];

    /** Exam catalogues: type => model (the tests defined in Pathology and Radiology). */
    const TEST_TYPES = [
        'pathology_test' => PathologyTest::class,
        'radiology_test' => RadiologyTest::class,
        'package' => Package::class, // check-ups
    ];

    public static function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'patient_insurance_id' => 'nullable|integer|exists:patient_insurances,id',
            'price_list_id' => 'nullable|integer|exists:price_lists,id',
            'voucher_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'tests' => 'required|array|min:1',
            'tests.*' => ['string', 'regex:/^(pathology_test|radiology_test|package):\d+$/'],
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamOrderItem::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Price and split every chosen test: the price list's price, then reduction and payer
     * share by the test's charge category and the prescriber's department.
     *
     * @param  string[]  $tests  "pathology_test:12", "radiology_test:3"...
     * @return array<int, array{type: string, id: int, description: string, split: array}>
     */
    public static function priceTests(array $tests, array $input): array
    {
        $policy = filled($input['patient_insurance_id'] ?? null)
            ? PatientInsurance::with('insurance')->where('patient_id', (int) ($input['patient_id'] ?? 0))->find($input['patient_insurance_id'])
            : null;
        $list = PriceList::find($input['price_list_id'] ?? null) ?? ($policy?->insurance->priceList) ?? PriceList::defaultList();
        $departmentId = filled($input['doctor_id'] ?? null) ? Doctor::whereKey($input['doctor_id'])->value('doctor_department_id') : null;
        $at = $input['ordered_at'] ?? now();

        $lines = [];
        foreach (array_unique($tests) as $key) {
            [$type, $id] = explode(':', $key) + [null, null];
            $test = isset(self::TEST_TYPES[$type]) && ctype_digit((string) $id) ? self::TEST_TYPES[$type]::find((int) $id) : null;
            if (! $test) {
                continue;
            }
            $gross = $list ? $list->priceFor($test, $at) : ItemPrice::basePrice($test);
            $lines[] = [
                'type' => $type,
                'id' => $test->id,
                'description' => $test->test_name ?? $test->name,
                'split' => PriceSplit::compute($gross, $policy, $departmentId, [
                    'date' => $at, 'voucher_number' => $input['voucher_number'] ?? null, 'charge_category_id' => $test->charge_category_id,
                    'extra_discount' => $input['extra_discount'] ?? null,
                ]),
            ];
        }

        return $lines;
    }

    /** Save an order and its priced lines (from priceTests). Exams of an admitted patient go on the admission's bill. */
    public static function place(array $input, array $lines, ?int $userId): self
    {
        $order = self::create([
            'patient_id' => $input['patient_id'],
            'ipd_patient_department_id' => IpdPatientDepartment::where('patient_id', $input['patient_id'])->where('discharge', 0)->latest('id')->value('id'),
            'doctor_id' => $input['doctor_id'] ?? null,
            // only a policy of this patient counts
            'patient_insurance_id' => PatientInsurance::where('patient_id', $input['patient_id'])->find($input['patient_insurance_id'] ?? null)?->id,
            'price_list_id' => $input['price_list_id'] ?? null,
            'voucher_number' => $input['voucher_number'] ?? null,
            'ordered_at' => now(),
            'user_id' => $userId,
            'notes' => $input['notes'] ?? null,
        ]);
        foreach ($lines as $line) {
            $order->items()->create([
                'testable_type' => $line['type'],
                'testable_id' => $line['id'],
                'description' => $line['description'],
                'total' => $line['split']['gross'],
                'discount_amount' => $line['split']['discount_amount'],
                'payer_amount' => $line['split']['payer_amount'],
                'patient_amount' => $line['split']['patient_amount'],
            ]);
        }

        return $order;
    }
}
