<?php

namespace App\Support\Traits;

use App\Modules\Audit\Models\AuditLog;

/**
 * HasAuditTrail — records who created/updated a record.
 *
 * Blueprint §2.11: Audit trail is append-only with actor, action,
 * diff, reason, device, and correlation ID.
 */
trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'created_by')) {
                    $model->created_by = $model->created_by ?? auth()->id();
                }
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                    $model->updated_by = $model->updated_by ?? auth()->id();
                }
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                    $model->updated_by = auth()->id();
                }
            }
        });
    }

    /**
     * Get the user who created this record.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
