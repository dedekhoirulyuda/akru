<?php

namespace App\Modules\Accounting\Models;

use App\Support\Traits\HasCompanyScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    public function getStatusAttribute(): string
    {
        return $this->is_closed ? 'closed' : 'open';
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
