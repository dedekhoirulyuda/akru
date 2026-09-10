<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Services\Posting\PostingService;
use App\Services\SequenceEngine\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankTransferController extends Controller
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected PostingService $postingService,
    ) {}

    public function index(Request $request)
    {
        $transfers = DB::table('bank_transfers')
            ->join('bank_accounts as from_bank', 'bank_transfers.from_bank_account_id', '=', 'from_bank.id')
            ->join('bank_accounts as to_bank', 'bank_transfers.to_bank_account_id', '=', 'to_bank.id')
            ->where('bank_transfers.company_id', session('current_company_id'))
            ->select(
                'bank_transfers.*',
                'from_bank.bank_name as from_bank_name',
                'to_bank.bank_name as to_bank_name'
            )
            ->orderByDesc('transfer_date')
            ->paginate(15);

        return view('finance.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::where('is_active', true)->get();
        $defaultNumber = $this->sequenceGenerator->next('bank_transfer', $companyId);

        return view('finance.transfers.create', compact('bankAccounts', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_bank_account_id' => 'required|exists:bank_accounts,id|different:to_bank_account_id',
            'to_bank_account_id' => 'required|exists:bank_accounts,id',
            'transfer_number' => 'required|string|max:50',
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'fee_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $companyId = session('current_company_id');
        $user = auth()->user();
        $fromBank = BankAccount::with('account')->find($validated['from_bank_account_id']);
        $toBank = BankAccount::with('account')->find($validated['to_bank_account_id']);
        $feeAccount = Account::where('company_id', $companyId)->where('code', '6800')->first(); // Beban Admin Bank

        DB::transaction(function () use ($companyId, $user, $validated, $fromBank, $toBank, $feeAccount) {
            $transferId = DB::table('bank_transfers')->insertGetId([
                'company_id' => $companyId,
                'from_bank_account_id' => $validated['from_bank_account_id'],
                'to_bank_account_id' => $validated['to_bank_account_id'],
                'transfer_number' => $validated['transfer_number'],
                'transfer_date' => $validated['transfer_date'],
                'amount' => $validated['amount'],
                'fee_amount' => $validated['fee_amount'] ?? 0,
                'status' => 'posted',
                'notes' => $validated['notes'],
                'created_by' => $user->id,
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $amount = (float) $validated['amount'];
            $fee = (float) ($validated['fee_amount'] ?? 0);
            $totalCreditFrom = $amount + $fee;

            // Lines:
            // Debit: To Bank = amount
            // Debit: Beban Admin Bank (6800) = fee (if > 0)
            // Credit: From Bank = totalCreditFrom
            $lines = [
                [
                    'account_id' => $toBank->account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Penerimaan Transfer {$validated['transfer_number']} dari {$fromBank->bank_name}",
                ],
            ];

            if ($fee > 0 && $feeAccount) {
                $lines[] = [
                    'account_id' => $feeAccount->id,
                    'debit' => $fee,
                    'credit' => 0,
                    'description' => "Biaya Transfer {$validated['transfer_number']}",
                ];
            }

            $lines[] = [
                'account_id' => $fromBank->account_id,
                'debit' => 0,
                'credit' => $totalCreditFrom,
                'description' => "Transfer Keluar {$validated['transfer_number']} ke {$toBank->bank_name}",
            ];

            $this->postingService->post(
                sourceType: 'bank_transfer',
                sourceId: $transferId,
                companyId: $companyId,
                lines: $lines,
                idempotencyKey: "POST_TRF_{$transferId}",
                actorId: $user->id,
                description: "Transfer {$fromBank->bank_name} ke {$toBank->bank_name}",
                journalDate: $validated['transfer_date'],
                branchId: $user->current_branch_id,
            );
        });

        return redirect()->route('transfers.index')->with('success', 'Transfer antar-bank berhasil dicatat dan diposting ke Buku Besar.');
    }
}
