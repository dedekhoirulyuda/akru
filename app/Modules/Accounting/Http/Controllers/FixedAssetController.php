<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\FixedAsset;
use App\Modules\Core\Models\Branch;
use App\Modules\MasterData\Models\Account;
use App\Services\Posting\PostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    public function __construct(
        protected PostingService $postingService
    ) {}

    public function index(): View
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $assets = FixedAsset::with(['branch', 'assetAccount', 'accumulatedDepreciationAccount', 'depreciationExpenseAccount'])
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAcquisition = $assets->sum('acquisition_cost');
        $totalAccumulated = $assets->sum(fn($a) => $a->accumulated_depreciation_total);
        $totalBookValue = $assets->sum(fn($a) => $a->book_value);

        // Account selectors for new asset modal
        $assetAccounts = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('type', 'asset')->orWhere('code', 'like', '1%');
            })
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('type', 'expense')->orWhere('code', 'like', '6%');
            })
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $companyId)->get();

        return view('accounting.assets.index', compact(
            'assets',
            'totalAcquisition',
            'totalAccumulated',
            'totalBookValue',
            'assetAccounts',
            'expenseAccounts',
            'branches'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $validated = $request->validate([
            'asset_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:1',
            'useful_life_years' => 'required|integer|min:1|max:50',
            'salvage_value' => 'nullable|numeric|min:0',
            'asset_account_id' => 'required|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounts,id',
        ]);

        $exists = FixedAsset::where('company_id', $companyId)
            ->where('asset_code', $validated['asset_code'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Kode aset '{$validated['asset_code']}' sudah digunakan.");
        }

        FixedAsset::create([
            'company_id' => $companyId,
            'branch_id' => $validated['branch_id'] ?? null,
            'asset_code' => $validated['asset_code'],
            'name' => $validated['name'],
            'acquisition_date' => $validated['acquisition_date'],
            'acquisition_cost' => $validated['acquisition_cost'],
            'useful_life_years' => $validated['useful_life_years'],
            'salvage_value' => $validated['salvage_value'] ?? 0,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => $validated['asset_account_id'],
            'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
            'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
            'is_active' => true,
        ]);

        return redirect()->route('assets.index')
            ->with('success', "Aset tetap '{$validated['name']}' ({$validated['asset_code']}) berhasil didaftarkan ke buku register.");
    }

    public function depreciate(FixedAsset $asset): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        if ($asset->company_id !== $companyId) {
            abort(403);
        }

        $monthlyAmount = $asset->monthly_depreciation;

        if ($monthlyAmount <= 0) {
            return back()->with('error', 'Nilai penyusutan aset ini adalah nol.');
        }

        if ($asset->book_value <= ($asset->salvage_value ?? 0)) {
            return back()->with('error', 'Aset telah habis disusutkan (mencapai nilai residu).');
        }

        $periodMonth = now()->format('Y-m');
        $idempotencyKey = "asset_deprec_{$asset->id}_{$periodMonth}";

        try {
            $this->postingService->post(
                sourceType: 'fixed_asset_depreciation',
                sourceId: $asset->id,
                companyId: $companyId,
                lines: [
                    [
                        'account_id' => $asset->depreciation_expense_account_id,
                        'debit' => $monthlyAmount,
                        'credit' => 0,
                        'description' => "Beban Penyusutan {$asset->name} ({$asset->asset_code}) Periode {$periodMonth}",
                    ],
                    [
                        'account_id' => $asset->accumulated_depreciation_account_id,
                        'debit' => 0,
                        'credit' => $monthlyAmount,
                        'description' => "Akum. Penyusutan {$asset->name} ({$asset->asset_code}) Periode {$periodMonth}",
                    ],
                ],
                idempotencyKey: $idempotencyKey,
                actorId: auth()->id(),
                description: "Penyusutan Aset Tetap: {$asset->name} Periode {$periodMonth}",
                journalDate: now()->toDateString()
            );

            return redirect()->route('assets.index')
                ->with('success', "Penyusutan bulanan sebesar Rp " . number_format($monthlyAmount, 0, ',', '.') . " untuk aset {$asset->name} berhasil dibukukan ke jurnal.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses jurnal penyusutan: ' . $e->getMessage());
        }
    }
}
