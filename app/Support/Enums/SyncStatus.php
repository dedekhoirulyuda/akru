<?php

namespace App\Support\Enums;

/**
 * SyncStatus — offline synchronization states.
 *
 * Blueprint §9.3: draft → pending_sync → syncing → synced/sync_failed/conflict/rejected
 */
enum SyncStatus: string
{
    case Draft = 'draft';
    case PendingSync = 'pending_sync';
    case Syncing = 'syncing';
    case Synced = 'synced';
    case SyncFailed = 'sync_failed';
    case Conflict = 'conflict';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft       => 'Draft Lokal',
            self::PendingSync => 'Menunggu Sinkronisasi',
            self::Syncing     => 'Sedang Sinkronisasi',
            self::Synced      => 'Tersinkronisasi',
            self::SyncFailed  => 'Gagal Sinkronisasi',
            self::Conflict    => 'Konflik',
            self::Rejected    => 'Ditolak Server',
        };
    }
}
