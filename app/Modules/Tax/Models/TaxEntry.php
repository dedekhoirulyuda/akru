<?php

namespace App\Modules\Tax\Models;

use App\Support\Traits\HasCompanyScope;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Modules\MasterData\Models\TaxCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TaxEntry — individual tax record linked to a source transaction line.
 *
 * Blueprint §2.8: Base, rate, amount, counterparty identity,
 * source, GL link. Tax entries are the atomic units of tax control.
 */
class TaxEntry extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'tax_code_id',
        'source_type',
        'source_id',
        'source_line_id',
        'contact_id',
        'tax_date',
        'tax_period',
        'tax_type',
        'base_amount',      // DPP
        'rate',
        'tax_amount',
        'direction',        // output (keluaran) / input (masukan)
        'invoice_number',
        'counterparty_npwp',
        'counterparty_name',
        'is_creditable',
        'status',           // draft, verified, reported
        'account_id',
    ];

    protected $casts = [
        'tax_date' => 'date',
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_creditable' => 'boolean',
    ];

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
