<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalSet;
use App\Services\Posting\PostingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class JournalController extends Controller
{
    public function __construct(
        protected PostingService $postingService
    ) {}

    public function index(Request $request): View
    {
        $companyId = session('active_company_id');

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $sourceType = $request->query('source_type');
        $status = $request->query('status');

        $query = JournalSet::with(['lines.account', 'postedByUser'])
            ->where('company_id', $companyId)
            ->whereBetween('journal_date', [$dateFrom, $dateTo]);

        if ($sourceType) {
            $query->where('source_type', $sourceType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $journals = $query->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $totalDebit = JournalSet::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereBetween('journal_date', [$dateFrom, $dateTo])
            ->sum('total_debit');

        $totalCredit = JournalSet::where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereBetween('journal_date', [$dateFrom, $dateTo])
            ->sum('total_credit');

        return view('accounting.journals.index', compact(
            'journals',
            'dateFrom',
            'dateTo',
            'sourceType',
            'status',
            'totalDebit',
            'totalCredit'
        ));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $sourceType = $request->query('source_type');
        $status = $request->query('status');

        $query = JournalSet::with(['lines.account', 'postedByUser'])
            ->where('company_id', $companyId)
            ->whereBetween('journal_date', [$dateFrom, $dateTo]);

        if ($sourceType) {
            $query->where('source_type', $sourceType);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $journals = $query->orderBy('journal_date', 'desc')->orderBy('id', 'desc')->get();

        $headers = [
            'No. Jurnal',
            'Tanggal',
            'Sumber Modul',
            'Uraian / Deskripsi',
            'Total Debit (Rp)',
            'Total Kredit (Rp)',
            'Status',
            'Diposting Oleh'
        ];

        $rows = [];
        foreach ($journals as $j) {
            $rows[] = [
                $j->journal_number,
                $j->journal_date ? date('d/m/Y', strtotime($j->journal_date)) : '-',
                $j->source_type ?? '-',
                $j->description ?? '-',
                number_format($j->total_debit, 0, ',', '.'),
                number_format($j->total_credit, 0, ',', '.'),
                strtoupper($j->status ?? 'POSTED'),
                $j->postedByUser?->name ?? 'System',
            ];
        }

        $meta = [
            'title' => 'LAPORAN JURNAL UMUM (JOURNAL EXPLORER)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => date('d/m/Y', strtotime($dateFrom)) . ' s/d ' . date('d/m/Y', strtotime($dateTo)),
            'date' => now()->format('d/m/Y H:i'),
        ];

        $filename = 'Laporan_Jurnal_Umum_' . str_replace('-', '', $dateFrom) . '_' . str_replace('-', '', $dateTo);

        return $exportService->exportXlsx($filename, $headers, $rows, [22, 14, 20, 42, 22, 22, 14, 22], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $companyId = session('active_company_id');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $sourceType = $request->query('source_type');
        $status = $request->query('status');

        $query = JournalSet::with(['lines.account', 'postedByUser'])
            ->where('company_id', $companyId)
            ->whereBetween('journal_date', [$dateFrom, $dateTo]);

        if ($sourceType) {
            $query->where('source_type', $sourceType);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $journals = $query->orderBy('journal_date', 'asc')->orderBy('id', 'asc')->get();
        $totalDebit = $journals->where('status', 'posted')->sum('total_debit');
        $totalCredit = $journals->where('status', 'posted')->sum('total_credit');

        $filename = 'Laporan_Jurnal_Umum_' . $dateFrom . '_' . $dateTo . '.pdf';

        return $exportService->exportPdf('accounting.journals.print', compact(
            'journals', 'dateFrom', 'dateTo', 'totalDebit', 'totalCredit'
        ), $filename);
    }

    public function show(JournalSet $journal): View
    {
        $this->authorizeCompany($journal);
        $journal->load(['lines.account', 'postedByUser', 'reversedByUser']);

        return view('accounting.journals.show', compact('journal'));
    }

    public function reverse(Request $request, JournalSet $journal): RedirectResponse
    {
        $this->authorizeCompany($journal);

        $request->validate([
            'reversal_reason' => 'required|string|max:255',
        ]);

        try {
            $this->postingService->reverse(
                journalSetId: $journal->id,
                reason: $request->input('reversal_reason'),
                actorId: auth()->id()
            );

            return redirect()->route('journals.show', $journal)
                ->with('success', "Jurnal {$journal->journal_number} berhasil dibalik (reversed).");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membalik jurnal: ' . $e->getMessage());
        }
    }

    protected function authorizeCompany(JournalSet $journal): void
    {
        if ($journal->company_id != session('active_company_id')) {
            abort(403, 'Akses tidak diizinkan untuk entitas ini.');
        }
    }
}
