<?php

namespace App\Modules\Inventory\Models;

use App\Support\Traits\HasCompanyScope;
use App\Modules\MasterData\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockMovement — immutable ledger of all inventory changes.
 *
 * Blueprint §2.5: Every stock change traceable to source document.
 */
class StockMovement extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'item_id',
        'movement_type',    // sales, purchase, return_in, return_out, adjustment, transfer
        'movement_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_after',
        'source_type',
        'source_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'balance_after' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
