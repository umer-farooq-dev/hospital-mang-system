<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Admin-managed dropdown lists (Settings > Master Lists).
 *
 * Records elsewhere store an item's "code", which for the seeded items equals the
 * key of the PHP constant the list replaced, so old data stays valid.
 *
 * @property int $id
 * @property string $type
 * @property int $code
 * @property string $name
 * @property string|null $lang_key
 * @property int $sort_order
 * @property bool $is_active
 */
class MasterListItem extends Model
{
    public $table = 'master_list_items';

    // List type => [table, column] holding its codes. Used to block deleting an item in use.
    const TYPES = [
        'income_head' => ['incomes', 'income_head'],
        'expense_head' => ['expenses', 'expense_head'],
        'charge_type' => ['charge_categories', 'charge_type'],
        'visitor_purpose' => ['visitors', 'purpose'],
        'dose_interval' => ['prescriptions_medicines', 'dose_interval'],
        'dose_duration' => ['prescriptions_medicines', 'day'],
        'meal_timing' => ['prescriptions_medicines', 'time'],
        'id_document_type' => ['patients', 'id_document_type'],
        'payment_method' => ['opd_patient_departments', 'payment_mode'],
        'payer_type' => ['insurances', 'payer_type'],
        'patient_origin' => ['opd_patient_departments', 'patient_origin'],
        'emergency_level' => ['opd_patient_departments', 'emergency_level'],
        'referrer' => ['opd_patient_departments', 'referrer'],
        'refund_reason' => ['refunds', 'reason'],
        'transfer_destination' => ['fund_transfers', 'destination'],
        'guardian_relation' => ['ipd_patient_departments', 'guardian_relation'],
        'ot_room' => ['ipd_operation', 'ot_room'],
        'operation_status' => ['ipd_operation', 'status'],
        'intervention_type' => ['ipd_operation', 'intervention_type'],
        'anaesthesia_type' => ['ipd_operation', 'anesthesia_type'],
        'preop_check' => ['ipd_operation', 'preop_checks'],
        'discharge_check' => ['ipd_patient_departments', 'discharge_checks'],
        'discharge_outcome' => ['ipd_patient_departments', 'discharge_outcome'],
        'nursing_shift' => ['nursing_notes', 'shift'],
        'intake_type' => ['intake_outputs', 'intake_type'],
        'output_type' => ['intake_outputs', 'output_type'],
        'mar_status' => ['medication_administrations', 'status'],
        'diagnosis_status' => ['ipd_diagnoses', 'diagnosis_status'],
        'imaging_modality' => ['radiology_tests', 'modality'],
        'store_type' => ['stores', 'store_type'],
        'medicine_unit' => ['medicines', 'unit'],
        'dosage_form' => ['medicines', 'dosage_form'],
        'medicine_route' => ['medicines', 'route'],
        'claim_rejection_reason' => ['claim_lines', 'rejection_reason'],
    ];

    // Lists whose items carry a number, and what it means (shown on the Master Lists screen).
    const QUANTITY_TYPES = ['dose_interval' => 'doses_per_day', 'dose_duration' => 'days'];

    // Other tables that also store codes of a list.
    const ALSO_USED_IN = [
        'imaging_modality' => [['imaging_report_templates', 'modality']],
        'diagnosis_status' => [['opd_diagnoses', 'diagnosis_status']],
        'refund_reason' => [['receipts', 'void_reason']],
        'payment_method' => [['ipd_payments', 'payment_mode'], ['appointments', 'payment_type'], ['appointment_transactions', 'payment_type']],
        'charge_type' => [['charges', 'charge_type'], ['ipd_charges', 'charge_type_id']],
        'dose_interval' => [['ipd_prescription_items', 'dose_interval'], ['opd_prescription_items', 'dose_interval']],
        'dose_duration' => [['ipd_prescription_items', 'day'], ['opd_prescription_items', 'day']],
        'meal_timing' => [['ipd_prescription_items', 'time'], ['opd_prescription_items', 'time']],
    ];

    public $fillable = [
        'type',
        'code',
        'name',
        'quantity',
        'lang_key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'id' => 'integer',
        'type' => 'string',
        'code' => 'integer',
        'name' => 'string',
        'lang_key' => 'string',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @var array<string, \Illuminate\Support\Collection> items per type, loaded once per request */
    private static array $loaded = [];

    /** Translated label: the original translation for seeded items, otherwise the name as entered. */
    public function getLabelAttribute(): string
    {
        if ($this->lang_key) {
            $translated = __($this->lang_key);
            if ($translated !== $this->lang_key) {
                return $translated;
            }
        }

        return $this->name;
    }

    private static function items(string $type)
    {
        return self::$loaded[$type] ??= self::where('type', $type)->orderBy('sort_order')->orderBy('code')->get();
    }

    /**
     * code => label of the active items, for dropdowns. Pass the record's current code
     * on edit forms so a since-deactivated item stays selectable for that record.
     */
    public static function options(string $type, $currentCode = null): array
    {
        return self::items($type)
            ->filter(fn ($item) => $item->is_active || ($currentCode !== null && $currentCode !== '' && $item->code === (int) $currentCode))
            ->pluck('label', 'code')->all();
    }

    /** code => label of every item, active or not, for showing stored values (lists, exports, PDFs). */
    public static function labels(string $type): array
    {
        return self::items($type)->pluck('label', 'code')->all();
    }

    /** Same as options() with an "All" entry (code 0) first, for list filters. */
    public static function filterOptions(string $type): array
    {
        return [0 => __('messages.filter.All')] + self::options($type);
    }

    /** Number behind an item (doses per day, days...), or null. */
    public static function quantity(string $type, $code): ?float
    {
        $value = self::items($type)->firstWhere('code', (int) $code)?->quantity;

        return $value === null ? null : (float) $value;
    }

    /** Label for a stored code, also for items that were deactivated later. */
    public static function label(string $type, $code, ?string $default = null): ?string
    {
        if ($code === null || $code === '') {
            return $default;
        }

        return self::items($type)->firstWhere('code', (int) $code)?->label ?? $default;
    }

    /**
     * Lowest code for items the admin adds. Payment methods share their column with the
     * online gateway codes (3-9), so new methods start above them.
     */
    const FIRST_NEW_CODE = [
        'payment_method' => 100,
    ];

    public static function nextCode(string $type): int
    {
        return max((int) self::where('type', $type)->max('code') + 1, self::FIRST_NEW_CODE[$type] ?? 1);
    }

    /** Whether any record still stores this item's code. */
    public function isInUse(): bool
    {
        $places = array_merge([self::TYPES[$this->type]], self::ALSO_USED_IN[$this->type] ?? []);
        foreach ($places as [$table, $column]) {
            if (DB::table($table)->where($column, $this->code)->exists()) {
                return true;
            }
        }

        return false;
    }

    public static function flushCache(): void
    {
        self::$loaded = [];
    }
}
