<?php

namespace App\Modules\Accounting\Models;

use App\Support\Traits\HasCompanyScope;
use App\Modules\MasterData\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalCorrection extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'tax_year',
        'account_id',
        'correction_type', // positive, negative
        'amount',
        'category',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
