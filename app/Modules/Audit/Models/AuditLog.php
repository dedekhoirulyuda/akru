<?php

namespace App\Modules\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — append-only audit trail record.
 *
 * Blueprint §2.11: actor, action, diff, device, correlation ID.
 * Cannot be edited or deleted by anyone.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'correlation_id',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Audit logs are immutable — disable updates and deletes.
     */
    public static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
