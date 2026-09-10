<?php

namespace App\Modules\Purchase\Models;

use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'accepted_quantity' => 'decimal:4',
        'rejected_quantity' => 'decimal:4',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
