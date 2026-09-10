<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccrualScheduleLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'schedule_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function accrualSchedule(): BelongsTo
    {
        return $this->belongsTo(AccrualSchedule::class);
    }

    public function journalSet(): BelongsTo
    {
        return $this->belongsTo(JournalSet::class);
    }
}
