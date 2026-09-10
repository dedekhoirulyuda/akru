<?php

namespace App\Modules\Purchase\Models;

use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'estimated_cost' => 'decimal:2',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
