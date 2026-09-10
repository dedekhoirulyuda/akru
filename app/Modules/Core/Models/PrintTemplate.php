<?php

namespace App\Modules\Core\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * PrintTemplate — company-specific document print layout configuration.
 *
 * Supports laser/inkjet (modern) and dot-matrix (legacy) printer modes,
 * with continuous form paper sizes and visual layout customization.
 */
class PrintTemplate extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'document_type',
        'name',
        'is_default',
        'printer_mode',
        'paper_size',
        'orientation',
        'custom_width_mm',
        'custom_height_mm',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
        'font_family',
        'font_size',
        'color_primary',
        'color_accent',
        'color_text',
        'show_logo',
        'logo_position',
        'logo_size',
        'show_company_name',
        'show_company_address',
        'show_npwp',
        'show_phone_email',
        'header_layout',
        'footer_text',
        'show_page_number',
        'signatures',
        'table_header_bg',
        'table_header_text',
        'table_border_color',
        'show_gridlines',
        'dm_char_per_line',
        'dm_lines_per_page',
        'dm_condensed',
        'dm_separator_char',
        'dm_box_drawing',
        'custom_css',
    ];

    protected $casts = [
        'is_default'           => 'boolean',
        'show_logo'            => 'boolean',
        'show_company_name'    => 'boolean',
        'show_company_address' => 'boolean',
        'show_npwp'            => 'boolean',
        'show_phone_email'     => 'boolean',
        'show_page_number'     => 'boolean',
        'show_gridlines'       => 'boolean',
        'dm_condensed'         => 'boolean',
        'dm_box_drawing'       => 'boolean',
        'header_layout'        => 'array',
        'signatures'           => 'array',
        'font_size'            => 'integer',
        'logo_size'            => 'integer',
        'margin_top'           => 'integer',
        'margin_bottom'        => 'integer',
        'margin_left'          => 'integer',
        'margin_right'         => 'integer',
        'dm_char_per_line'     => 'integer',
        'dm_lines_per_page'    => 'integer',
    ];

    // --- Paper Size Constants ---

    const PAPER_SIZES = [
        'A4'     => ['name' => 'A4 (210 × 297 mm)',      'w' => 210,   'h' => 297],
        'A5'     => ['name' => 'A5 (148 × 210 mm)',      'w' => 148,   'h' => 210],
        'Letter' => ['name' => 'Letter (216 × 279 mm)',  'w' => 215.9, 'h' => 279.4],
        'Legal'  => ['name' => 'Legal (216 × 356 mm)',   'w' => 215.9, 'h' => 355.6],
        'F4'     => ['name' => 'F4/Folio (215 × 330 mm)', 'w' => 215, 'h' => 330],
    ];

    const CONTINUOUS_SIZES = [
        'cont_9.5x11'    => ['name' => '9½" × 11" (Standard)',      'w' => 241.3, 'h' => 279.4],
        'cont_9.5x5.5'   => ['name' => '9½" × 5½" (Half-page)',     'w' => 241.3, 'h' => 139.7],
        'cont_9.5x7'     => ['name' => '9½" × 7" (Faktur 3-ply)',   'w' => 241.3, 'h' => 177.8],
        'cont_14.875x11' => ['name' => '14⅞" × 11" (Wide/Greenbar)', 'w' => 377.8, 'h' => 279.4],
    ];

    const DOCUMENT_TYPES = [
        'general'          => 'Umum (Default)',
        'sales_invoice'    => 'Faktur Penjualan',
        'purchase_invoice' => 'Faktur Pembelian',
        'receipt'          => 'Kwitansi / Tanda Terima',
        'journal'          => 'Jurnal & Buku Besar',
        'report'           => 'Laporan Keuangan',
        'inventory'        => 'Dokumen Gudang / Inventori',
    ];

    const FONT_FAMILIES = [
        'Inter'            => 'Inter (Modern Sans)',
        'Roboto'           => 'Roboto (Google Sans)',
        'Arial'            => 'Arial (Classic)',
        'Times New Roman'  => 'Times New Roman (Serif)',
        'Courier New'      => 'Courier New (Monospace)',
        'Lucida Console'   => 'Lucida Console (Monospace)',
    ];

    // --- Scopes ---

    public function scopeForDocument($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('document_type', $type)
              ->orWhere('document_type', 'general');
        })->where('is_default', true)
          ->orderByRaw("CASE WHEN document_type = ? THEN 0 ELSE 1 END", [$type]);
    }

    // --- Helpers ---

    public function isDotMatrix(): bool
    {
        return $this->printer_mode === 'dot_matrix';
    }

    public function isContinuousForm(): bool
    {
        return str_starts_with($this->paper_size, 'cont_');
    }

    /**
     * Return paper dimensions in mm [width, height].
     */
    public function getPaperDimensions(): array
    {
        if ($this->paper_size === 'custom') {
            return [$this->custom_width_mm ?? 210, $this->custom_height_mm ?? 297];
        }

        $allSizes = array_merge(self::PAPER_SIZES, self::CONTINUOUS_SIZES);

        if (isset($allSizes[$this->paper_size])) {
            $size = $allSizes[$this->paper_size];
            if ($this->orientation === 'landscape') {
                return [$size['h'], $size['w']];
            }
            return [$size['w'], $size['h']];
        }

        return [210, 297]; // fallback A4
    }

    /**
     * Get CSS @page size value.
     */
    public function getCssPageSize(): string
    {
        [$w, $h] = $this->getPaperDimensions();
        return "{$w}mm {$h}mm";
    }

    /**
     * Get default signatures array.
     */
    public function getSignaturesOrDefault(): array
    {
        if (!empty($this->signatures)) {
            return $this->signatures;
        }

        return [
            ['title' => 'Dibuat Oleh', 'name' => '', 'position' => 'left'],
            ['title' => 'Diperiksa Oleh', 'name' => '', 'position' => 'center'],
            ['title' => 'Disetujui Oleh', 'name' => '', 'position' => 'right'],
        ];
    }

    /**
     * Get default header layout order.
     */
    public function getHeaderLayoutOrDefault(): array
    {
        if (!empty($this->header_layout)) {
            return $this->header_layout;
        }

        return [
            ['type' => 'logo', 'order' => 1, 'visible' => true],
            ['type' => 'company_name', 'order' => 2, 'visible' => true],
            ['type' => 'address', 'order' => 3, 'visible' => true],
            ['type' => 'npwp', 'order' => 4, 'visible' => true],
            ['type' => 'phone_email', 'order' => 5, 'visible' => true],
        ];
    }

    // --- Relationships ---

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
