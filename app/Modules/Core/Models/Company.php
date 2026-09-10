<?php

namespace App\Modules\Core\Models;

use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company — represents a single business entity (PT, CV, etc.)
 *
 * All domain tables MUST be scoped by company_id.
 * Data leakage between companies is a critical failure.
 */
class Company extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'name',
        'legal_name',
        'entity_type',
        'nib',
        'npwp',
        'nik',
        'is_pkp',
        'kbli',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'website',
        'fiscal_year_start_month',
        'timezone',
        'currency_code',
        'logo_path',
        'status',
        'suspended_reason',
        'custom_modules',
        'max_users_override',
        'max_branches_override',
        'max_transactions_override',
        'max_ai_chats_override',
    ];

    protected $casts = [
        'is_pkp' => 'boolean',
        'fiscal_year_start_month' => 'integer',
        'custom_modules' => 'array',
        'max_users_override' => 'integer',
        'max_branches_override' => 'integer',
        'max_transactions_override' => 'integer',
        'max_ai_chats_override' => 'integer',
    ];

    // --- Relationships ---

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_users')
            ->withPivot('role_id', 'is_active')
            ->withTimestamps();
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function subscription()
    {
        return $this->hasOne(\App\Modules\Subscription\Models\Subscription::class);
    }

    /**
     * Get maximum daily AI chats allowed.
     * Returns null if unlimited, or an integer limit.
     */
    public function getAiChatLimit(): ?int
    {
        if ($this->max_ai_chats_override !== null) {
            return $this->max_ai_chats_override;
        }

        $subscription = $this->subscription()->with('plan')->first();
        if ($subscription && $subscription->plan) {
            if ($subscription->plan->max_ai_chats_per_day !== null) {
                return (int) $subscription->plan->max_ai_chats_per_day;
            }
            if ($subscription->plan->has_ai) {
                return null; // Unlimited
            }
        }

        // Default to trial quota (10 chats/day) for trial status or if no subscription
        if ($this->status === 'trial' || ($subscription && $subscription->status === 'trial') || !$subscription) {
            return 10;
        }

        return null;
    }

    /**
     * Check if company is on free trial mode.
     */
    public function isTrial(): bool
    {
        if ($this->status === 'trial') {
            return true;
        }

        $subscription = $this->subscription()->with('plan')->first();
        if (!$subscription) {
            return true;
        }

        if ($subscription->status === 'trial' || ($subscription->plan && ($subscription->plan->isFree() || $subscription->plan->slug === 'free-trial'))) {
            return true;
        }

        return false;
    }
}

