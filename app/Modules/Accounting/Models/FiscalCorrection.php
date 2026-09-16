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
        'fiscal_year',
        'tax_year', // Supported via mutator
        'account_id',
        'correction_type', // positive, negative
        'amount',
        'category',
        'description',
        'legal_basis',
        'created_by',
    ];

    public function getTaxYearAttribute(): ?int
    {
        return $this->attributes['fiscal_year'] ?? null;
    }

    public function setTaxYearAttribute($value): void
    {
        $this->attributes['fiscal_year'] = $value;
    }

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
