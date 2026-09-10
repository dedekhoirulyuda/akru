<?php

namespace App\Services\ExportEngine;

/**
 * ExportService — Excel and PDF export.
 *
 * Blueprint invariant: NO CSV export. Only Excel and PDF.
 *
 * Blueprint §2.9: Total and filter must match the screen display.
 */
class ExportService
{
    /**
     * Export data as Excel.
     */
    public function toExcel(
        string $reportType,
        array $filters,
        int $companyId,
        int $requestedBy,
    ): string {
        // Returns file path of generated Excel
        throw new \RuntimeException('ExportService::toExcel() not yet implemented.');
    }

    /**
     * Export data as PDF.
     */
    public function toPdf(
        string $reportType,
        array $filters,
        int $companyId,
        int $requestedBy,
    ): string {
        // Returns file path of generated PDF
        throw new \RuntimeException('ExportService::toPdf() not yet implemented.');
    }
}
