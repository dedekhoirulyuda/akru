<?php

namespace App\Support\Enums;

/**
 * PostingStatus — for tracking the posting lifecycle of a document.
 */
enum PostingStatus: string
{
    case NotPosted = 'not_posted';
    case Posting = 'posting';
    case Posted = 'posted';
    case PartiallyPosted = 'partially_posted';
    case PostingFailed = 'posting_failed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::NotPosted        => 'Belum Posted',
            self::Posting          => 'Sedang Posting',
            self::Posted           => 'Posted',
            self::PartiallyPosted  => 'Sebagian Posted',
            self::PostingFailed    => 'Gagal Posting',
            self::Reversed         => 'Reversed',
        };
    }
}
