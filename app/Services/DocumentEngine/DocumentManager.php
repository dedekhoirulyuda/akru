<?php

namespace App\Services\DocumentEngine;

/**
 * DocumentManager — file handling with checksum and tenant isolation.
 *
 * Blueprint §2.9: Files have checksum, permission, retention,
 * preview, and source link. Signed access with company scope.
 */
class DocumentManager
{
    /**
     * Store a document with checksum and tenant path isolation.
     */
    public function store(
        $file,
        string $category,
        int $companyId,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $uploadedBy = null,
    ): array {
        throw new \RuntimeException('DocumentManager::store() not yet implemented.');
    }

    /**
     * Generate a signed URL for secure file access.
     */
    public function signedUrl(int $documentId, int $companyId): string
    {
        throw new \RuntimeException('DocumentManager::signedUrl() not yet implemented.');
    }
}
