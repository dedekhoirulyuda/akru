<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\Contact;
use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReceipt extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'contact_id',
        'bank_account_id',
        'receipt_number',
        'receipt_date',
        'payment_method',
        'reference_number',
        'total_amount',
        'status',
        'notes',
        'created_by',
        'posted_at',
        'idempotency_key',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function allocations()
    {
        return $this->hasMany(ReceiptAllocation::class, 'customer_receipt_id');
    }
}
