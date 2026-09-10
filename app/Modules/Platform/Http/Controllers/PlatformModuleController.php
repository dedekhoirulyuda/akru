<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use App\Modules\Platform\Models\PlatformSetting;
use App\Modules\Subscription\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformModuleController extends Controller
{
    protected array $allModules = [
        'accounting' => [
            'name' => 'Akuntansi & Buku Besar',
            'desc' => 'Daftar akun COA, jurnal umum, penyesuaian, neraca lajur, dan penutupan buku.',
            'badge' => 'Core',
            'icon' => 'book-open',
        ],
        'finance' => [
            'name' => 'Kas, Bank & Rekonsiliasi',
            'desc' => 'Pengeluaran/penerimaan kas, multi-rekening bank, dan rekonsiliasi mutasi.',
            'badge' => 'Finance',
            'icon' => 'banknotes',
        ],
        'sales' => [
            'name' => 'Penjualan & Piutang Dagang',
            'desc' => 'Faktur penjualan, penawaran harga, surat jalan, dan penerimaan pelunasan piutang.',
            'badge' => 'Commercial',
            'icon' => 'document-currency-dollar',
        ],
        'purchase' => [
            'name' => 'Pembelian & Hutang Dagang',
            'desc' => 'Pesanan pembelian (PO), penerimaan barang, faktur pemasok, dan pelunasan hutang.',
            'badge' => 'Commercial',
            'icon' => 'shopping-cart',
        ],
        'inventory' => [
            'name' => 'Inventaris & Multi-Gudang',
            'desc' => 'Stok opname, kartu stok perpetual FIFO/Average, transfer antar gudang, dan HPP otomatis.',
            'badge' => 'Operations',
            'icon' => 'archive-box',
        ],
        'tax' => [
            'name' => 'Perpajakan & Coretax Export',
            'desc' => 'Faktur pajak PPN 11%/12%, PPh 21, PPh 23, PPh Final, dan XML/CSV export siap Coretax DJP.',
            'badge' => 'Compliance',
            'icon' => 'shield-check',
        ],
        'ai' => [
            'name' => 'Asisten Finansial AI (Gemini/OpenAI)',
            'desc' => 'Konsultasi laporan keuangan, analisis rasio likuiditas, dan rekomendasi audit otomatis berbasis AI.',
            'badge' => 'Innovation',
            'icon' => 'sparkles',
        ],
        'converter' => [
            'name' => 'Bank Statement PDF Converter',
            'desc' => 'Ekstraksi mutasi rekening koran PDF (BCA, Mandiri, BRI, BNI) menjadi transaksi terstruktur.',
            'badge' => 'Productivity',
            'icon' => 'document-arrow-up',
        ],
        'multibranch' => [
            'name' => 'Multi-Cabang & Konsolidasi',
            'desc' => 'Otorisasi per cabang, eliminasi transaksi antar cabang, dan laporan konsolidasi grup.',
            'badge' => 'Enterprise',
            'icon' => 'building-office-2',
        ],
        'audit' => [
            'name' => 'Audit Trail & Approval Multi-Tier',
            'desc' => 'Log aktivitas immutable, approval workflow bertingkat berdasar limit nominal.',
            'badge' => 'Security',
            'icon' => 'check-badge',
        ],
    ];

    public function index(): View
    {
        $modules = $this->allModules;
        $plans = Plan::all();
        $companies = Company::orderBy('name')->take(20)->get();

        // Get global flags
        $globalFlags = [];
        foreach (array_keys($this->allModules) as $key) {
            $globalFlags[$key] = PlatformSetting::get("module_active_{$key}", '1') === '1';
        }

        return view('platform.modules.index', compact('modules', 'plans', 'companies', 'globalFlags'));
    }

    public function updatePlanModules(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);

        $selectedModules = $request->input('modules', []);
        $plan->update([
            'modules' => $selectedModules,
            'has_ai' => in_array('ai', $selectedModules),
        ]);

        return back()->with('success', "Konfigurasi modul untuk paket '{$plan->name}' berhasil disimpan.");
    }

    public function updateCompanyModules(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $customModules = $request->input('custom_modules', null);
        $company->update([
            'custom_modules' => $customModules,
        ]);

        return back()->with('success', "Modul kustom untuk perusahaan '{$company->name}' berhasil diperbarui.");
    }

    public function toggleGlobal(Request $request, string $moduleKey): RedirectResponse
    {
        if (!array_key_exists($moduleKey, $this->allModules)) {
            return back()->with('error', 'Modul tidak dikenal.');
        }

        $current = PlatformSetting::get("module_active_{$moduleKey}", '1');
        $new = ($current === '1') ? '0' : '1';

        PlatformSetting::set("module_active_{$moduleKey}", $new, 'modules', false, "Status global modul {$moduleKey}");

        $stateText = $new === '1' ? 'diaktifkan secara global' : 'dinonaktifkan secara global';

        return back()->with('success', "Modul {$this->allModules[$moduleKey]['name']} berhasil {$stateText}.");
    }
}
