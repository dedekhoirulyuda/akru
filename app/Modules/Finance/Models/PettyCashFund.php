<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashFund extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'imprest_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(PettyCashVoucher::class);
    }
}
