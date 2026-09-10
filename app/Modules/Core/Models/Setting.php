<?php

namespace App\Modules\Core\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Setting — company/branch/user settings with type and versioning.
 */
class Setting extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'group',
        'key',
        'value',
        'type',
        'version',
        'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'version' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
