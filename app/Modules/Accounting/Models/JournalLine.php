<?php

namespace App\Modules\Accounting\Models;

use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JournalLine — a single debit or credit entry within a JournalSet.
 *
 * Blueprint §1.9: Money as decimal, not float.
 */
class JournalLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'journal_set_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'line_order',
        'contact_id',
        'item_id',
        'tax_entry_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalSet(): BelongsTo
    {
        return $this->belongsTo(JournalSet::class, 'journal_set_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
