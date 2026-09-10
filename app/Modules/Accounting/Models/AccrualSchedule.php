<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccrualSchedule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_per_period' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function prepaidAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'prepaid_account_id');
    }

    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccrualScheduleLine::class);
    }
}
