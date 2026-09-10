<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = session('active_company_id', session('current_company_id'));

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $selectedDate = Carbon::createFromDate($year, $month, 1);
        $nextMonth = $selectedDate->copy()->addMonth();

        // Calculate tax liabilities from tax_entries for this period
        $periodString = $selectedDate->format('Y-m');

        $ppnOutput = (float) DB::table('tax_entries')
            ->where('company_id', $companyId)
            ->where('tax_type', 'ppn_output')
            ->where('tax_period', $periodString)
            ->sum('tax_amount');

        $ppnInput = (float) DB::table('tax_entries')
            ->where('company_id', $companyId)
            ->where('tax_type', 'ppn_input')
            ->where('tax_period', $periodString)
            ->sum('tax_amount');

        $ppnPayable = max(0, $ppnOutput - $ppnInput);

        $pph23Total = (float) DB::table('tax_entries')
            ->where('company_id', $companyId)
            ->where('tax_type', 'pph23')
            ->where('tax_period', $periodString)
            ->sum('tax_amount');

        $pph21Total = (float) DB::table('tax_entries')
            ->where('company_id', $companyId)
            ->where('tax_type', 'pph21')
            ->where('tax_period', $periodString)
            ->sum('tax_amount');

        $pph42Total = (float) DB::table('tax_entries')
            ->where('company_id', $companyId)
            ->where('tax_type', 'pph4_2')
            ->where('tax_period', $periodString)
            ->sum('tax_amount');

        $today = now()->startOfDay();

        // Standard Indonesian Tax Schedule Deadlines
        $schedules = [
            [
                'tax_name' => 'PPh Pasal 21 / 26 (Masa)',
                'category' => 'Withholding PPh',
                'description' => "Pemotongan pajak penghasilan atas gaji, upah, honorarium karyawan & tenaga ahli masa {$selectedDate->translatedFormat('F Y')}.",
                'deposit_deadline' => $nextMonth->copy()->day(10),
                'filing_deadline' => $nextMonth->copy()->day(20),
                'estimated_amount' => $pph21Total,
                'form' => 'SPT Masa PPh 21 / Unifikasi',
                'portal' => 'Coretax DJP / e-Bupot 21',
            ],
            [
                'tax_name' => 'PPh Pasal 23 / 26 (Masa)',
                'category' => 'Withholding PPh',
                'description' => "Pemotongan atas jasa teknik, manajemen, sewa alat dan dividen masa {$selectedDate->translatedFormat('F Y')}.",
                'deposit_deadline' => $nextMonth->copy()->day(10),
                'filing_deadline' => $nextMonth->copy()->day(20),
                'estimated_amount' => $pph23Total,
                'form' => 'SPT Masa PPh Unifikasi',
                'portal' => 'Coretax DJP e-Bupot Unifikasi',
            ],
            [
                'tax_name' => 'PPh Final Pasal 4 ayat (2)',
                'category' => 'Withholding PPh',
                'description' => "Pajak final atas persewaan tanah/bangunan dan jasa konstruksi masa {$selectedDate->translatedFormat('F Y')}.",
                'deposit_deadline' => $nextMonth->copy()->day(10),
                'filing_deadline' => $nextMonth->copy()->day(20),
                'estimated_amount' => $pph42Total,
                'form' => 'SPT Masa PPh 4(2) Unifikasi',
                'portal' => 'Coretax DJP',
            ],
            [
                'tax_name' => 'PPN & PPnBM (SPT Masa 1111)',
                'category' => 'Pajak Pertambahan Nilai',
                'description' => "Pelunasan PPN Kurang Bayar dan pelaporan SPT Masa PPN masa {$selectedDate->translatedFormat('F Y')}.",
                'deposit_deadline' => $nextMonth->copy()->endOfMonth(),
                'filing_deadline' => $nextMonth->copy()->endOfMonth(),
                'estimated_amount' => $ppnPayable,
                'form' => 'SPT Masa PPN 1111 / Coretax XML',
                'portal' => 'Coretax DJP / Web e-Faktur',
            ],
            [
                'tax_name' => 'PPh Pasal 25 (Angsuran Badan)',
                'category' => 'PPh Korporasi',
                'description' => "Penyetoran angsuran pajak penghasilan bulanan masa {$selectedDate->translatedFormat('F Y')}.",
                'deposit_deadline' => $nextMonth->copy()->day(15),
                'filing_deadline' => null, // Tidak wajib lapor jika SSP NTPN tervalidasi
                'estimated_amount' => 0,
                'form' => 'Surat Setoran Elektronik (Biling)',
                'portal' => 'Coretax Billing',
            ],
            [
                'tax_name' => 'SPT Tahunan PPh Badan (Form 1771)',
                'category' => 'Kepatuhan Tahunan',
                'description' => "Laporan SPT Tahunan PPh Badan beserta Rekonsiliasi Fiskal dan Lampiran Khusus Tahun Pajak {$year}.",
                'deposit_deadline' => Carbon::createFromDate($year + 1, 4, 30),
                'filing_deadline' => Carbon::createFromDate($year + 1, 4, 30),
                'estimated_amount' => 0,
                'form' => 'Formulir 1771 & Lampiran Fiskal',
                'portal' => 'Coretax DJP E-Form 1771',
            ],
        ];

        // Enrich schedule with status and remaining days
        foreach ($schedules as &$item) {
            $depositDiff = $today->diffInDays($item['deposit_deadline'], false);
            $item['deposit_days_left'] = $depositDiff;

            if ($depositDiff < 0) {
                $item['deposit_status'] = 'Terlambat';
                $item['deposit_color'] = 'rose';
            } elseif ($depositDiff <= 3) {
                $item['deposit_status'] = "H-{$depositDiff} Kritis";
                $item['deposit_color'] = 'amber';
            } else {
                $item['deposit_status'] = "H-{$depositDiff}";
                $item['deposit_color'] = 'emerald';
            }

            if ($item['filing_deadline']) {
                $filingDiff = $today->diffInDays($item['filing_deadline'], false);
                $item['filing_days_left'] = $filingDiff;
                if ($filingDiff < 0) {
                    $item['filing_status'] = 'Terlambat';
                    $item['filing_color'] = 'rose';
                } elseif ($filingDiff <= 3) {
                    $item['filing_status'] = "H-{$filingDiff} Kritis";
                    $item['filing_color'] = 'amber';
                } else {
                    $item['filing_status'] = "H-{$filingDiff}";
                    $item['filing_color'] = 'blue';
                }
            } else {
                $item['filing_status'] = 'Auto-NTPN';
                $item['filing_color'] = 'slate';
            }
        }

        return view('tax.calendar', compact(
            'month',
            'year',
            'selectedDate',
            'nextMonth',
            'schedules',
            'ppnPayable',
            'pph21Total',
            'pph23Total',
            'pph42Total'
        ));
    }
}
