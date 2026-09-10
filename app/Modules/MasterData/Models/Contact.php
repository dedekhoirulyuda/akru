<?php

namespace App\Modules\MasterData\Models;

use App\Support\Traits\HasCompanyScope;
use App\Support\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contact — unified model for customers, suppliers, or both.
 *
 * Blueprint §2.2: Customer & Supplier with tax profile, terms,
 * credit limit, duplicate detection.
 */
class Contact extends Model
{
    use HasFactory, HasCompanyScope, HasAuditTrail, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'code',
        'name',
        'company_name',
        'identity_type',
        'identity_number',
        'address',
        'city',
        'postal_code',
        'tax_address',
        'phone',
        'email',
        'payment_terms_days',
        'credit_limit',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];
}
