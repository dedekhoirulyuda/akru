<?php

namespace App\Support\Traits;

/**
 * Postable — for models that follow the document state machine.
 *
 * Blueprint §9.1: draft → submitted → approved → processed/posted
 * → partially_paid/paid/closed
 *
 * Correction paths: draft → cancelled, posted → reversed/voided
 */
trait Postable
{
    /**
     * Initialize the postable trait with default status.
     */
    public function initializePostable(): void
    {
        $this->attributes['status'] = $this->attributes['status'] ?? 'draft';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    /**
     * Check if the document can be edited (only in draft).
     */
    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the document can be submitted.
     */
    public function canSubmit(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the document can be posted.
     */
    public function canPost(): bool
    {
        return in_array($this->status, ['approved', 'submitted']);
    }

    /**
     * Get all allowed status transitions.
     */
    public static function allowedTransitions(): array
    {
        return [
            'draft'      => ['submitted', 'cancelled'],
            'submitted'  => ['approved', 'rejected', 'draft'],
            'approved'   => ['posted', 'draft'],
            'posted'     => ['reversed', 'voided'],
            'reversed'   => [],
            'voided'     => [],
            'cancelled'  => [],
        ];
    }
}
