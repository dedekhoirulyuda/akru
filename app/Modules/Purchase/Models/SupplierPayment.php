<?php

namespace App\Modules\Purchase\Models;

use App\Modules\MasterData\Models\BankAccount;
use App\Modules\MasterData\Models\Contact;
use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'contact_id',
        'bank_account_id',
        'payment_number',
        'payment_date',
        'payment_method',
        'reference_number',
        'total_amount',
        'withholding_tax_amount',
        'status',
        'notes',
        'created_by',
        'posted_at',
        'idempotency_key',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
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
        return $this->hasMany(PaymentAllocation::class, 'supplier_payment_id');
    }
}
