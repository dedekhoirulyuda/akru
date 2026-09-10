<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\TaxCode;
use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'contact_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'tax_code_id',
        'notes',
        'created_by',
        'approved_by',
        'posted_at',
        'idempotency_key',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function lines()
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }

    public function taxCode()
    {
        return $this->belongsTo(TaxCode::class, 'tax_code_id');
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class, 'sales_invoice_id');
    }
}
