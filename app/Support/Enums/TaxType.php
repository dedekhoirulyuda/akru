<?php

namespace App\Support\Enums;

/**
 * TaxType — Indonesian tax types supported by AKRU.
 *
 * Blueprint §2.8: PPN, PPh 21/22/23/4(2)/26.
 */
enum TaxType: string
{
    case PPN = 'ppn';
    case PPh21 = 'pph21';
    case PPh22 = 'pph22';
    case PPh23 = 'pph23';
    case PPh4Ayat2 = 'pph4_2';
    case PPh26 = 'pph26';
    case PPnBM = 'ppnbm';

    public function label(): string
    {
        return match ($this) {
            self::PPN      => 'PPN',
            self::PPh21    => 'PPh 21',
            self::PPh22    => 'PPh 22',
            self::PPh23    => 'PPh 23',
            self::PPh4Ayat2 => 'PPh 4(2)',
            self::PPh26    => 'PPh 26',
            self::PPnBM    => 'PPnBM',
        };
    }

    /**
     * Whether this tax type requires withholding evidence (bukti potong).
     */
    public function requiresWithholdingEvidence(): bool
    {
        return in_array($this, [
            self::PPh21,
            self::PPh22,
            self::PPh23,
            self::PPh4Ayat2,
            self::PPh26,
        ]);
    }
}
