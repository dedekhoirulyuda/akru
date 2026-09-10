<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Accounting\Models\JournalSet;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpname extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'opname_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalSet(): BelongsTo
    {
        return $this->belongsTo(JournalSet::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockOpnameLine::class);
    }
}
