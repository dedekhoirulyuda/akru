<?php

namespace App\Modules\Finance\Models;

use App\Modules\MasterData\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'annual_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
