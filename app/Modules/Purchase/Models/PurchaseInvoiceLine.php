<?php

namespace App\Modules\Purchase\Models;

use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'tax_amount',
        'total',
        'expense_account_id',
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
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }
}
