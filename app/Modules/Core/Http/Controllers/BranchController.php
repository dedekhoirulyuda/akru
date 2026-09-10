<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Branch;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * BranchController — CRUD operations for branches/locations.
 *
 * Blueprint §2.1: Multi-branch support per company.
 */
class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id', session('current_company_id'));
        $branches = Branch::where('company_id', $companyId)->orderBy('is_head_office', 'desc')->get();
        $warehouses = Warehouse::where('company_id', $companyId)->get();

        return view('settings.branches.index', compact('branches', 'warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'is_head_office' => 'nullable|boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_head_office'] = $request->has('is_head_office');
        $validated['is_active'] = true;

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->is_head_office) {
            return back()->with('error', 'Kantor Pusat tidak dapat dihapus.');
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
