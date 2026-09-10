<?php

namespace App\Services\SyncEngine;

/**
 * SyncManager — offline synchronization and conflict handling.
 *
 * Blueprint §4.6:
 * - Retry with idempotency key returns canonical result
 * - No silent last-write-wins for financial data
 * - Server re-validates everything
 * - Posting/closing/approval/tax final are online-only
 */
class SyncManager
{
    /**
     * Process an incoming sync request from an offline device.
     */
    public function processSync(
        array $payload,
        string $idempotencyKey,
        int $companyId,
        int $userId,
        string $deviceId,
    ): array {
        throw new \RuntimeException('SyncManager::processSync() not yet implemented.');
    }

    /**
     * Detect conflicts between server and device versions.
     */
    public function detectConflict(
        string $entityType,
        int $entityId,
        int $deviceVersion,
        int $serverVersion,
    ): bool {
        return $deviceVersion < $serverVersion;
    }
}
