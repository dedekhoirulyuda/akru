<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Dimension;
use App\Modules\MasterData\Models\DimensionValue;
use Illuminate\Http\Request;

class DimensionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $dimensions = Dimension::with('values')
            ->where('company_id', $companyId)
            ->get();

        return view('master.dimensions.index', compact('dimensions'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30',
        ]);

        Dimension::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'is_active' => true,
        ]);

        return redirect()->route('dimensions.index')->with('success', 'Dimensi baru berhasil ditambahkan.');
    }

    public function storeValue(Request $request, $id)
    {
        $companyId = session('current_company_id');
        $dimension = Dimension::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:100',
        ]);

        DimensionValue::create([
            'dimension_id' => $dimension->id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'is_active' => true,
        ]);

        return redirect()->route('dimensions.index')->with('success', "Nilai untuk dimensi {$dimension->name} berhasil ditambahkan.");
    }
}
