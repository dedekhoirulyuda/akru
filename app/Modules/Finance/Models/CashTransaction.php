<?php

namespace App\Modules\Finance\Models;

use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Support\Traits\HasAuditTrail;
use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashTransaction extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'bank_account_id',
        'type', // cash_in, cash_out
        'transaction_number',
        'transaction_date',
        'counterparty',
        'total_amount',
        'account_id',
        'status',
        'notes',
        'created_by',
        'posted_at',
        'idempotency_key',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
