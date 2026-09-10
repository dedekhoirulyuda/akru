<?php

namespace App\Modules\Inventory\Models;

use App\Support\Traits\HasCompanyScope;
use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'item_id',
        'quantity',
        'average_cost',
        'total_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'average_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
