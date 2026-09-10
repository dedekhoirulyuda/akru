<?php

namespace App\Modules\Accounting\Models;

use App\Support\Traits\HasCompanyScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * JournalSet — a balanced set of journal lines from a single posting.
 *
 * Blueprint §2.7: "Posting service creates balanced journal set."
 * Total debit MUST equal total credit. Immutable after posting.
 */
class JournalSet extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'branch_id',
        'journal_number',
        'journal_date',
        'period_id',
        'source_type',
        'source_id',
        'description',
        'status',           // posted, reversed
        'total_debit',
        'total_credit',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_set_id')->orderBy('line_order');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
