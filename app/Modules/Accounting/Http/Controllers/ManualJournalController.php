<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\Contact;
use App\Services\Posting\PostingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ManualJournalController extends Controller
{
    public function __construct(
        protected PostingService $postingService
    ) {}

    public function create(): View
    {
        $companyId = session('active_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $contacts = Contact::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.journals.manual', compact('accounts', 'contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = session('active_company_id');

        $validated = $request->validate([
            'journal_date' => 'required|date',
            'description' => 'required|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.contact_id' => 'nullable|exists:contacts,id',
        ]);

        $postingLines = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($validated['lines'] as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $postingLines[] = [
                'account_id' => $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? $validated['description'],
                'contact_id' => !empty($line['contact_id']) ? $line['contact_id'] : null,
            ];
        }

        if (count($postingLines) < 2) {
            return back()->withInput()->with('error', 'Minimal harus mengisi 2 baris jurnal (Debit & Kredit).');
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()->with('error', "Jurnal tidak seimbang! Total Debit (Rp " . number_format($totalDebit, 0, ',', '.') . ") ≠ Total Kredit (Rp " . number_format($totalCredit, 0, ',', '.') . ").");
        }

        try {
            $journal = $this->postingService->post(
                sourceType: 'manual',
                sourceId: 0,
                companyId: $companyId,
                lines: $postingLines,
                idempotencyKey: 'manual_' . $companyId . '_' . uniqid(),
                actorId: auth()->id(),
                description: $validated['description'],
                journalDate: $validated['journal_date']
            );

            return redirect()->route('journals.show', $journal->id)
                ->with('success', 'Jurnal Memorial / Penyesuaian berhasil dibukukan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memposting jurnal: ' . $e->getMessage());
        }
    }
}
