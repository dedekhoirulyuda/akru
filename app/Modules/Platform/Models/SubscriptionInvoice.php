<?php

namespace App\Modules\Platform\Models;

use App\Modules\Core\Models\Company;
use App\Modules\Subscription\Models\Plan;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'company_id',
        'plan_id',
        'amount',
        'billing_cycle',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
