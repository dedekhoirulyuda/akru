<?php

namespace App\Modules\Workflow\Models;

use App\Support\Traits\HasCompanyScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'document_type',
        'document_id',
        'requester_id',
        'approver_id',
        'status',           // pending, approved, rejected
        'notes',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
