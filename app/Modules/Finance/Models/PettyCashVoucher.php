<?php

namespace App\Modules\Finance\Models;

use App\Modules\Accounting\Models\JournalSet;
use App\Modules\MasterData\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashVoucher extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function pettyCashFund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalSet(): BelongsTo
    {
        return $this->belongsTo(JournalSet::class);
    }
}
