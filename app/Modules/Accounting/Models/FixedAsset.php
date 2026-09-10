<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\MasterData\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FixedAsset extends Model
{
    protected $table = 'fixed_assets';

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_code',
        'name',
        'acquisition_date',
        'acquisition_cost',
        'useful_life_years',
        'salvage_value',
        'depreciation_method',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'is_active',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'useful_life_years' => 'integer',
        'salvage_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    /**
     * Monthly depreciation using Straight-line method (Garis Lurus)
     */
    public function getMonthlyDepreciationAttribute(): float
    {
        $months = max(1, $this->useful_life_years * 12);
        $depreciable = max(0, $this->acquisition_cost - $this->salvage_value);
        return round($depreciable / $months, 2);
    }

    /**
     * Total depreciation already posted to accumulated depreciation account
     */
    public function getAccumulatedDepreciationTotalAttribute(): float
    {
        $totalCredit = DB::table('journal_lines')
            ->join('journal_sets', 'journal_lines.journal_set_id', '=', 'journal_sets.id')
            ->where('journal_sets.company_id', $this->company_id)
            ->where('journal_sets.source_type', 'fixed_asset_depreciation')
            ->where('journal_sets.source_id', $this->id)
            ->where('journal_lines.account_id', $this->accumulated_depreciation_account_id)
            ->sum('journal_lines.credit');

        return (float) $totalCredit;
    }

    /**
     * Current Net Book Value (Nilai Buku Bersih)
     */
    public function getBookValueAttribute(): float
    {
        return max(0, (float) $this->acquisition_cost - $this->accumulated_depreciation_total);
    }
}
