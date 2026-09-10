<?php

namespace App\Services\AuditEngine;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * AuditLogger — append-only audit trail logger.
 *
 * Blueprint §2.11: Audit log must record actor, action, entity,
 * before/after diff, device/IP, and correlation ID.
 * Audit logs cannot be edited or deleted by users.
 */
class AuditLogger
{
    /**
     * Log an action on an entity.
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?int $userId = null,
    ): void {
        $companyId = $companyId ?? session('current_company_id');
        $userId = $userId ?? auth()->id();
        $correlationId = request()->header('X-Correlation-ID', request()->id ?? (string) str()->uuid());

        DB::table('audit_logs')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);
    }
}
