<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class AuditLog
 *
 * Append-only record of every create, update and delete made through Eloquent.
 * Written by App\Listeners\RecordAuditLog.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property-read string $module_name
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    public $table = 'audit_logs';

    const UPDATED_AT = null;

    public $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'event' => 'string',
        'auditable_type' => 'string',
        'auditable_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'url' => 'string',
        'ip_address' => 'string',
        'user_agent' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Records that have a "History" button: key => [model, [line model => foreign key]].
     * The lines are found through the foreign key stored in the log, so deleted lines still show.
     */
    const HISTORY = [
        'receipt' => [Receipt::class, [ReceiptLine::class => 'receipt_id', Refund::class => 'receipt_id']],
        'bill' => [Bill::class, [BillItems::class => 'bill_id']],
        'invoice' => [Invoice::class, [InvoiceItem::class => 'invoice_id']],
        'medicine_bill' => [MedicineBill::class, [SaleMedicine::class => 'medicine_bill_id']],
        'opd_visit' => [OpdPatientDepartment::class, []],
        'exam_order' => [ExamOrder::class, [ExamOrderItem::class => 'exam_order_id']],
    ];

    // never shown in a history: bookkeeping and link columns (the header already says who, when and which record)
    const HIDDEN_FIELDS = ['id', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'payable_type', 'payable_id', 'receipt_id',
        'cash_session_id', 'cash_counter_id', 'bill_id', 'invoice_id', 'medicine_bill_id', 'exam_order_id', 'testable_type', 'testable_id',
        'patient_id', 'custom_field', 'currency_symbol'];

    // amounts shown in the hospital's currency format
    const MONEY_FIELDS = ['amount', 'total', 'discount_amount', 'payer_amount', 'patient_amount', 'amount_received', 'change_given',
        'standard_charge', 'net_amount', 'price', 'paid_amount', 'total_amount', 'sale_price', 'amount_paid', 'balance'];

    /** Readable value of a field in a history line. */
    public static function displayValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return formatMoney((float) $value);
        }
        if (in_array($field, ['payment_method', 'payment_mode'], true)) {
            return PaymentMethod::label($value) ?? (string) $value;
        }
        if (in_array($field, ['doctor_id'], true)) {
            return Doctor::with('doctorUser')->find($value)?->doctorUser?->full_name ?? (string) $value;
        }
        if (is_bool($value)) {
            return $value ? __('messages.common.yes') : __('messages.common.no');
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return \Illuminate\Support\Str::limit((string) $value, 120);
    }

    public static function fieldLabel(string $field): string
    {
        return \Illuminate\Support\Facades\Lang::has('messages.audit_log.fields.'.$field)
            ? __('messages.audit_log.fields.'.$field) : Str::headline($field);
    }

    /** Name of the changed record in the reader's language (Receipt line, Visit...). */
    public function getRecordNameAttribute(): string
    {
        $base = class_basename((string) $this->auditable_type);

        return \Illuminate\Support\Facades\Lang::has('messages.audit_log.models.'.$base)
            ? __('messages.audit_log.models.'.$base) : Str::headline($base);
    }

    /** Every change of the record and of its lines, newest first. */
    public static function historyFor(string $type, int $id)
    {
        [$model, $lines] = self::HISTORY[$type];

        return self::with('user')
            ->where(function ($q) use ($model, $id, $lines) {
                $q->where(fn ($r) => $r->where('auditable_type', $model)->where('auditable_id', $id));
                foreach ($lines as $lineModel => $key) {
                    $q->orWhere(fn ($r) => $r->where('auditable_type', $lineModel)->where(fn ($v) => $v
                        ->where('new_values->'.$key, $id)->orWhere('new_values->'.$key, (string) $id)
                        ->orWhere('old_values->'.$key, $id)->orWhere('old_values->'.$key, (string) $id)));
                }
            })
            ->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /** Fields that changed (field => [before, after]), bookkeeping columns left out. */
    public function changes(): array
    {
        $old = (array) $this->old_values;
        $new = (array) $this->new_values;
        $rows = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            if (in_array($field, self::HIDDEN_FIELDS, true)) {
                continue;
            }
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;
            if ($this->event === 'updated' && $before == $after) {
                continue;
            }
            $rows[$field] = [$before, $after];
        }

        return $rows;
    }

    public function getModuleNameAttribute(): string
    {
        return Str::headline(class_basename((string) $this->auditable_type));
    }
}
