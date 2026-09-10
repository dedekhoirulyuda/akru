<?php

namespace App\Modules\Partner\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerClient extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'consent_granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function partnerWorkspace(): BelongsTo
    {
        return $this->belongsTo(PartnerWorkspace::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
