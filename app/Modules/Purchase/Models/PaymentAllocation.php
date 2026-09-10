<?php

namespace App\Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_payment_id',
        'purchase_invoice_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
