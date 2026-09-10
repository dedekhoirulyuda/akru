<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'bank_name',
        'account_number',
        'account_holder_name',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
