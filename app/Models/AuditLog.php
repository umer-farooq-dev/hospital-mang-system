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

    public function getModuleNameAttribute(): string
    {
        return Str::headline(class_basename((string) $this->auditable_type));
    }
}
