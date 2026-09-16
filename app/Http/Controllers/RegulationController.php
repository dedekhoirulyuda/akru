<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegulationController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category', 'all');
        $search = $request->query('q', '');

        $query = Regulation::query();

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $term = '%' . strtolower(trim($search)) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('about', 'like', $term)
                  ->orWhere('summary', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhere('accounting_implications', 'like', $term)
                  ->orWhere('tax_implications', 'like', $term);
            });
        }

        $regulations = $query->orderBy('year', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        // Category counts
        $counts = [
            'all' => Regulation::count(),
            'akuntansi' => Regulation::where('category', 'akuntansi')->count(),
            'pajak' => Regulation::where('category', 'pajak')->count(),
            'kepabeanan' => Regulation::where('category', 'kepabeanan')->count(),
            'pmk' => Regulation::where('category', 'pmk')->count(),
            'surat_edaran' => Regulation::where('category', 'surat_edaran')->count(),
        ];

        return view('regulations.index', compact('regulations', 'category', 'search', 'counts'));
    }

    public function show(int $id): JsonResponse
    {
        $regulation = Regulation::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $regulation,
        ]);
    }
}
