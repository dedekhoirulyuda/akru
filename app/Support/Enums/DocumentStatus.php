<?php

namespace App\Support\Enums;

/**
 * DocumentStatus — universal document state machine.
 *
 * Blueprint §9.1: draft → submitted → approved → processed/posted
 * Correction: posted → reversed/voided
 */
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Posted = 'posted';
    case Reversed = 'reversed';
    case Voided = 'voided';
    case Cancelled = 'cancelled';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft         => 'Draft',
            self::Submitted     => 'Diajukan',
            self::Approved      => 'Disetujui',
            self::Rejected      => 'Ditolak',
            self::Posted        => 'Posted',
            self::Reversed      => 'Reversed',
            self::Voided        => 'Void',
            self::Cancelled     => 'Dibatalkan',
            self::PartiallyPaid => 'Sebagian Dibayar',
            self::Paid          => 'Lunas',
            self::Closed        => 'Ditutup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft         => 'gray',
            self::Submitted     => 'blue',
            self::Approved      => 'indigo',
            self::Rejected      => 'red',
            self::Posted        => 'green',
            self::Reversed      => 'orange',
            self::Voided        => 'red',
            self::Cancelled     => 'gray',
            self::PartiallyPaid => 'yellow',
            self::Paid          => 'green',
            self::Closed        => 'slate',
        };
    }
}
