<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Writes an audit_logs row for every Eloquent create, update and delete.
 *
 * ponytail: only Eloquent model events are captured; mass updates/deletes done
 * with the query builder (Model::where()->update()) bypass model events and are
 * not logged. Use model saves for money, stock and patient data.
 */
class RecordAuditLog
{
    // Field names (or Setting keys) whose values must never be stored in clear text.
    const SENSITIVE = '/password|secret|token|salt|api_key|private_key|access_key/i';

    // Setting rows (key/value) whose key matches hold credentials, e.g. open_ai_key, stripe_secret.
    const SENSITIVE_SETTING = '/password|secret|token|salt|_key$/i';

    const MASK = '******';

    // Models that are already a ledger (every row says who moved what and why): auditing them only doubles the writes.
    const OWN_LEDGER = [\App\Models\StockBatch::class, \App\Models\StockMovement::class];

    private static ?bool $tableExists = null;

    public function handle(string $eventName, array $payload): void
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model || $model instanceof AuditLog || in_array($model::class, self::OWN_LEDGER, true)) {
            return;
        }

        // Migrations that run before audit_logs exists (fresh installs) must keep working.
        self::$tableExists ??= Schema::hasTable('audit_logs');
        if (! self::$tableExists) {
            return;
        }

        // "eloquent.updated: App\Models\Patient" -> "updated"
        $event = substr($eventName, 9, strpos($eventName, ':') - 9);

        $old = $new = [];
        if ($event === 'created') {
            $new = $model->getAttributes();
        } elseif ($event === 'deleted') {
            $old = $model->getAttributes();
        } else {
            $new = $model->getChanges();
            $old = array_intersect_key($model->getRawOriginal(), $new);
        }

        $ignore = array_flip(array_merge($model->getHidden(), ['created_at', 'updated_at']));
        $old = $this->clean(array_diff_key($old, $ignore), $model);
        $new = $this->clean(array_diff_key($new, $ignore), $model);

        // An update that only touched timestamps is not worth a row.
        if ($event === 'updated' && empty($new)) {
            return;
        }

        self::write($model, $event, $old, $new);
    }

    /**
     * Writes one audit row. Also used directly for changes that fire no model event,
     * such as a role's permissions (a pivot table).
     */
    public static function write(Model $model, string $event, array $old, array $new): void
    {
        $inConsole = app()->runningInConsole();

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => is_numeric($model->getKey()) ? $model->getKey() : null,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'url' => $inConsole ? null : request()->fullUrl(),
            'ip_address' => $inConsole ? null : request()->ip(),
            'user_agent' => $inConsole ? null : request()->userAgent(),
        ]);
    }

    private function clean(array $values, Model $model): array
    {
        // Settings store credentials as key/value rows, e.g. key=stripe_secret.
        $settingKey = $model->getAttributes()['key'] ?? null;
        $maskValue = is_string($settingKey) && preg_match(self::SENSITIVE_SETTING, $settingKey);

        foreach ($values as $field => $value) {
            $sensitive = preg_match(self::SENSITIVE, $field) || ($maskValue && $field === 'value');
            if ($sensitive && $value !== null && $value !== '') {
                $values[$field] = self::MASK;
            }
        }

        return $values;
    }
}
