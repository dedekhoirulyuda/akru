<?php

namespace App\Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_month',
        'price_per_year',
        'max_users',
        'max_branches',
        'max_transactions_per_month',
        'has_ai',
        'max_ai_chats_per_day',
        'modules',
        'is_active',
    ];

    protected $casts = [
        'price_per_month' => 'decimal:2',
        'price_per_year' => 'decimal:2',
        'max_users' => 'integer',
        'max_branches' => 'integer',
        'max_transactions_per_month' => 'integer',
        'has_ai' => 'boolean',
        'max_ai_chats_per_day' => 'integer',
        'modules' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return floatval($this->price_per_month) == 0 && floatval($this->price_per_year) == 0;
    }

    public function hasAiChatLimit(): bool
    {
        return $this->max_ai_chats_per_day !== null && $this->max_ai_chats_per_day > 0;
    }
}
