<?php

namespace App\Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_receipt_id',
        'sales_invoice_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(CustomerReceipt::class, 'customer_receipt_id');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
