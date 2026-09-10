<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MasterData\Models\Account;
use App\Modules\MasterData\Models\BankAccount;
use App\Services\Posting\PostingService;
use App\Services\SequenceEngine\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function __construct(
        protected SequenceGenerator $sequenceGenerator,
        protected PostingService $postingService,
    ) {}

    public function index(Request $request)
    {
        $transactions = DB::table('cash_transactions')
            ->join('bank_accounts', 'cash_transactions.bank_account_id', '=', 'bank_accounts.id')
            ->join('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->where('cash_transactions.company_id', session('current_company_id'))
            ->select(
                'cash_transactions.*',
                'bank_accounts.bank_name',
                'accounts.code as account_code',
                'accounts.name as account_name'
            )
            ->orderByDesc('transaction_date')
            ->paginate(15);

        $bankAccounts = BankAccount::where('is_active', true)->get();

        return view('finance.cash-bank.index', compact('transactions', 'bankAccounts'));
    }

    public function exportExcel(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $transactions = DB::table('cash_transactions')
            ->join('bank_accounts', 'cash_transactions.bank_account_id', '=', 'bank_accounts.id')
            ->join('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->where('cash_transactions.company_id', session('current_company_id'))
            ->select(
                'cash_transactions.*',
                'bank_accounts.bank_name',
                'accounts.code as account_code',
                'accounts.name as account_name'
            )
            ->orderByDesc('transaction_date')
            ->get();

        $headers = [
            'No. Bukti Kas/Bank',
            'Tanggal',
            'Tipe (Masuk/Keluar)',
            'Rekening Kas/Bank',
            'Akun Kontra (Lawan)',
            'Kontak / Mitra',
            'Jumlah (Rp)',
            'Deskripsi / Uraian'
        ];

        $rows = [];
        foreach ($transactions as $t) {
            $isIn = in_array($t->type, ['in', 'cash_in']);
            $rows[] = [
                $t->transaction_number,
                $t->transaction_date ? date('d/m/Y', strtotime($t->transaction_date)) : '-',
                $isIn ? 'KAS MASUK (BKM)' : 'KAS KELUAR (BKK)',
                $t->bank_name,
                $t->account_code . ' - ' . $t->account_name,
                $t->counterparty ?? '-',
                number_format($t->total_amount ?? 0, 0, ',', '.'),
                $t->notes ?? '-',
            ];
        }

        $meta = [
            'title' => 'REGISTER TRANSAKSI KAS & BANK (BKM / BKK)',
            'company' => session('active_company_name', 'PT AKRU MAJU BERSAMA'),
            'period' => 'Semua Periode',
            'date' => now()->format('d/m/Y H:i'),
        ];

        return $exportService->exportXlsx('Register_Kas_Bank_' . date('Ymd'), $headers, $rows, [22, 14, 20, 22, 28, 22, 20, 35], $meta);
    }

    public function exportPdf(Request $request, \App\Services\Export\DataExportService $exportService)
    {
        $transactions = DB::table('cash_transactions')
            ->join('bank_accounts', 'cash_transactions.bank_account_id', '=', 'bank_accounts.id')
            ->join('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->where('cash_transactions.company_id', session('current_company_id'))
            ->select(
                'cash_transactions.*',
                'bank_accounts.bank_name',
                'accounts.code as account_code',
                'accounts.name as account_name'
            )
            ->orderBy('transaction_date')
            ->get();

        $totalIn = $transactions->filter(fn($t) => in_array($t->type, ['in', 'cash_in']))->sum('total_amount');
        $totalOut = $transactions->filter(fn($t) => in_array($t->type, ['out', 'cash_out']))->sum('total_amount');

        return $exportService->exportPdf('finance.cash-bank.print-list', compact('transactions', 'totalIn', 'totalOut'), 'Register_Kas_Bank_' . date('Ymd') . '.pdf');
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $bankAccounts = BankAccount::where('is_active', true)->get();
        $accounts = Account::orderBy('code')->get();
        $bkmNumber = $this->sequenceGenerator->next('cash_in', $companyId);
        $bkkNumber = $this->sequenceGenerator->next('cash_out', $companyId);

        return view('finance.cash-bank.create', compact('bankAccounts', 'accounts', 'bkmNumber', 'bkkNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_number' => 'required|string|max:50',
            'transaction_date' => 'required|date',
            'counterparty' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string',
        ]);

        $companyId = session('current_company_id');
        $user = auth()->user();
        $bankAccount = BankAccount::with('account')->find($validated['bank_account_id']);
        $offsetAccount = Account::find($validated['account_id']);

        DB::transaction(function () use ($companyId, $user, $validated, $bankAccount, $offsetAccount) {
            $trxId = DB::table('cash_transactions')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $user->current_branch_id,
                'bank_account_id' => $validated['bank_account_id'],
                'type' => $validated['type'],
                'transaction_number' => $validated['transaction_number'],
                'transaction_date' => $validated['transaction_date'],
                'counterparty' => $validated['counterparty'],
                'total_amount' => $validated['total_amount'],
                'account_id' => $validated['account_id'],
                'status' => 'posted',
                'notes' => $validated['notes'],
                'created_by' => $user->id,
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Formulate double-entry lines:
            // If cash_in: Debit Bank/Kas, Credit Offset Account
            // If cash_out: Debit Offset Account, Credit Bank/Kas
            $amount = (float) $validated['total_amount'];
            if ($validated['type'] === 'cash_in') {
                $lines = [
                    [
                        'account_id' => $bankAccount->account_id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => "Penerimaan Kas {$validated['transaction_number']} - {$validated['counterparty']}",
                    ],
                    [
                        'account_id' => $offsetAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => $validated['notes'] ?? "Penerimaan Kas {$validated['transaction_number']}",
                    ],
                ];
            } else {
                $lines = [
                    [
                        'account_id' => $offsetAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => $validated['notes'] ?? "Pengeluaran Kas {$validated['transaction_number']}",
                    ],
                    [
                        'account_id' => $bankAccount->account_id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => "Pengeluaran Kas {$validated['transaction_number']} ({$bankAccount->bank_name})",
                    ],
                ];
            }

            $this->postingService->post(
                sourceType: 'cash_transaction',
                sourceId: $trxId,
                companyId: $companyId,
                lines: $lines,
                idempotencyKey: "POST_CASH_{$trxId}",
                actorId: $user->id,
                description: "{$validated['transaction_number']} - {$validated['counterparty']}",
                journalDate: $validated['transaction_date'],
                branchId: $user->current_branch_id,
            );
        });

        return redirect()->route('cash-bank.index')->with('success', 'Transaksi kas & bank berhasil dicatat dan diposting ke Buku Besar.');
    }
}
