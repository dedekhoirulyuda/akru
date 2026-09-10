<?php

namespace App\Support\Enums;

/**
 * ImportRowStatus — per-row status during Excel import.
 *
 * Blueprint §9.2: OK, Perlu Cek, Error, Duplicate, Skipped, Imported.
 */
enum ImportRowStatus: string
{
    case OK = 'ok';
    case NeedsReview = 'needs_review';
    case Error = 'error';
    case Duplicate = 'duplicate';
    case Skipped = 'skipped';
    case Imported = 'imported';

    public function label(): string
    {
        return match ($this) {
            self::OK          => 'OK',
            self::NeedsReview => 'Perlu Cek',
            self::Error       => 'Error',
            self::Duplicate   => 'Duplikat',
            self::Skipped     => 'Dilewati',
            self::Imported    => 'Berhasil Diimport',
        };
    }
}
