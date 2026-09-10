<?php

namespace App\Modules\Inventory\Models;

use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_sent' => 'decimal:4',
        'quantity_received' => 'decimal:4',
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
