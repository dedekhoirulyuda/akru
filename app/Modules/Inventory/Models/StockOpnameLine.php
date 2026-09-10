<?php

namespace App\Modules\Inventory\Models;

use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'physical_quantity' => 'decimal:4',
        'difference_quantity' => 'decimal:4',
        'cost_price' => 'decimal:2',
        'total_difference_amount' => 'decimal:2',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
