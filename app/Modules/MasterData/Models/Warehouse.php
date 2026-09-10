<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;

/**
 * Warehouse — physical or logical storage location.
 *
 * Blueprint §2.1: Branch and warehouse scope for inventory.
 */
class Warehouse extends Model
{
    use HasCompanyScope, HasAuditTrail;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Modules\Core\Models\Branch::class);
    }
}
