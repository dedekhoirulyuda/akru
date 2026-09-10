<?php

namespace App\Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Subscription — tracks company plan, status, and entitlement.
 *
 * Blueprint §2.1: Subscription controls plan, add-on, quota,
 * active period, and grace. Enforcement on UI and server.
 */
class Subscription extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'status',           // active, trial, grace, suspended, cancelled
        'starts_at',
        'ends_at',
        'started_at',
        'expires_at',
        'trial_ends_at',
        'grace_ends_at',
        'cancelled_at',
        'billing_cycle',    // monthly, yearly
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            || ($this->status === 'trial' && $this->trial_ends_at?->isFuture())
            || ($this->status === 'grace' && $this->grace_ends_at?->isFuture());
    }
}
