<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Account — Chart of Accounts entry.
 *
 * Blueprint §2.2: COA manual or template, parent/detail structure,
 * unique code, posting account, report mapping.
 */
class Account extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail;

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',             // asset, liability, equity, revenue, expense
        'normal_balance',   // debit, credit
        'is_posting',       // true = can receive journal lines
        'is_active',
        'report_group',
        'description',
    ];

    protected $casts = [
        'is_posting' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
