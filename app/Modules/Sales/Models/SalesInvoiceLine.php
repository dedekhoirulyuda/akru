<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'total',
        'sales_account_id',
        'cogs_account_id',
        'inventory_account_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function salesInvoice()
    {
        return $this->invoice();
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }
}
