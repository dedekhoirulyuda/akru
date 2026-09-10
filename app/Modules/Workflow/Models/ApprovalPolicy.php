<?php

namespace App\Modules\Workflow\Models;

use App\Support\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;

/**
 * ApprovalPolicy — defines approval requirements per document type.
 *
 * Blueprint §2.11: Policy versioned; decision, waktu, reason,
 * delegation tercatat. SoD enforcement.
 */
class ApprovalPolicy extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'document_type',
        'name',
        'version',
        'min_amount',
        'max_amount',
        'branch_id',
        'steps',            // JSON: [{role_id, min_approvers, sod_required}]
        'require_sod',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'steps' => 'array',
        'require_sod' => 'boolean',
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
