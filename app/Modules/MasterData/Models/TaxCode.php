<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'tax_type',
        'rate',
        'sales_account_id',
        'purchase_account_id',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }
}
