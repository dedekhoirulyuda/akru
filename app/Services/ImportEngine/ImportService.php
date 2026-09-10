<?php

namespace App\Services\ImportEngine;

/**
 * ImportService — Excel import with preview, validation, and commit.
 *
 * Blueprint §2.9, §9.2:
 * - Schema version, preview, row status (OK/Perlu Cek/Error/Duplicate)
 * - Checksum, rollback, idempotent batch
 * - No CSV export. Excel only.
 */
class ImportService
{
    /**
     * Upload and parse an Excel file, returning a preview.
     */
    public function preview(
        string $filePath,
        string $entityType,
        int $companyId,
        array $columnMapping = [],
    ): array {
        throw new \RuntimeException('ImportService::preview() not yet implemented.');
    }

    /**
     * Commit a validated import batch.
     */
    public function commit(int $batchId, int $actorId): void
    {
        throw new \RuntimeException('ImportService::commit() not yet implemented.');
    }
}
