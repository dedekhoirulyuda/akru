<?php

namespace App\Modules\Core\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Branch — represents a physical or logical branch/outlet.
 */
class Branch extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'city',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouses()
    {
        return $this->hasMany(\App\Modules\MasterData\Models\Warehouse::class);
    }
}
