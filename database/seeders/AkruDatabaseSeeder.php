<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Posting\PostingService;
use App\Services\TaxEngine\TaxCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AkruDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions
        $roles = [
            ['name' => 'Pemilik Perusahaan (Owner)', 'slug' => 'owner', 'description' => 'Akses penuh ke seluruh modul dan pengaturan'],
            ['name' => 'Finance Manager', 'slug' => 'finance_manager', 'description' => 'Pengelolaan kas, bank, piutang, hutang dan approval'],
            ['name' => 'Senior Accountant', 'slug' => 'accountant', 'description' => 'Buku besar, jurnal penyesuaian, laporan keuangan SAK'],
            ['name' => 'Staf Pajak', 'slug' => 'tax_staff', 'description' => 'Rekapitulasi PPN, PPh, rekonsiliasi fiskal, Coretax export'],
        ];

        $roleIds = [];
        foreach ($roles as $r) {
            $roleIds[$r['slug']] = DB::table('roles')->insertGetId([
                'name' => $r['name'],
                'slug' => $r['slug'],
                'description' => $r['description'],
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Subscription Plan
        $planId = DB::table('plans')->insertGetId([
            'name' => 'AKRU Professional (Accounting to Tax)',
            'slug' => 'professional',
            'description' => 'Paket komplit akuntansi, kontrol keuangan, inventaris & pajak Coretax-ready',
            'price_per_month' => 299000,
            'max_users' => 10,
            'max_branches' => 3,
            'max_transactions_per_month' => 5000,
            'has_ai' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Demo Company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'PT Akru Maju Bersama',
            'legal_name' => 'PT Akru Maju Bersama',
            'entity_type' => 'PT',
            'nib' => '1234567890123',
            'npwp' => '01.234.567.8-012.000',
            'nik' => '3171012345670001',
            'is_pkp' => true,
            'kbli' => '46590', // Perdagangan Besar Mesin, Peralatan dan Perlengkapan Lainnya
            'address' => 'Jl. Jenderal Sudirman Kav. 52-53, SCBD',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'phone' => '021-5152535',
            'email' => 'info@akrumaju.co.id',
            'website' => 'https://akrumaju.co.id',
            'fiscal_year_start_month' => 1,
            'timezone' => 'Asia/Jakarta',
            'currency_code' => 'IDR',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Subscription
        DB::table('subscriptions')->insert([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Branch
        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Kantor Pusat Jakarta',
            'code' => 'HO-JKT',
            'address' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan',
            'is_head_office' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Warehouse
        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => 'Gudang Utama Cilandak',
            'code' => 'GDG-01',
            'address' => 'Kawasan Pergudangan Cilandak No. 18, Jakarta Selatan',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Demo Users
        $usersData = [
            ['name' => 'Budi Santoso', 'email' => 'owner@akru.id', 'role' => 'owner'],
            ['name' => 'Siti Rahma', 'email' => 'finance@akru.id', 'role' => 'finance_manager'],
            ['name' => 'Ahmad Fauzi', 'email' => 'accountant@akru.id', 'role' => 'accountant'],
            ['name' => 'Dewi Lestari', 'email' => 'tax@akru.id', 'role' => 'tax_staff'],
        ];

        foreach ($usersData as $ud) {
            $user = User::updateOrCreate(
                ['email' => $ud['email']],
                [
                    'name' => $ud['name'],
                    'password' => Hash::make('password'),
                    'is_superadmin' => ($ud['role'] === 'owner'),
                    'current_company_id' => $companyId,
                    'current_branch_id' => $branchId,
                ]
            );

            DB::table('company_users')->updateOrInsert(
                ['company_id' => $companyId, 'user_id' => $user->id],
                ['role_id' => $roleIds[$ud['role']], 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $ownerUser = User::where('email', 'owner@akru.id')->first();

        // 5. Indonesian Standard Chart of Accounts (COA)
        $accounts = [
            // Aset Lancar
            ['code' => '1100', 'name' => 'Kas Tunai', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1110', 'name' => 'Bank BCA Operasional', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1120', 'name' => 'Bank Mandiri Utama', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1200', 'name' => 'Piutang Usaha', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1300', 'name' => 'Persediaan Barang Dagang', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1400', 'name' => 'PPN Masukan (Pajak Dibayar di Muka)', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            ['code' => '1500', 'name' => 'Uang Muka Pembelian & Sewa Dibayar di Muka', 'type' => 'asset', 'sub_type' => 'current_asset', 'normal_balance' => 'debit'],
            // Aset Tetap
            ['code' => '1600', 'name' => 'Peralatan Kantor & Komputer', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'normal_balance' => 'debit'],
            ['code' => '1610', 'name' => 'Akumulasi Penyusutan Peralatan Kantor', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'normal_balance' => 'credit'],
            ['code' => '1700', 'name' => 'Kendaraan Operasional', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'normal_balance' => 'debit'],
            ['code' => '1710', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'asset', 'sub_type' => 'fixed_asset', 'normal_balance' => 'credit'],
            // Kewajiban Lancar
            ['code' => '2100', 'name' => 'Hutang Usaha (Pemasok)', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            ['code' => '2200', 'name' => 'Hutang PPN Keluaran', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            ['code' => '2210', 'name' => 'Hutang PPh 21 Karyawan', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            ['code' => '2220', 'name' => 'Hutang PPh 23 Jasa', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            ['code' => '2230', 'name' => 'Hutang PPh 4(2) Final Sewa', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            ['code' => '2300', 'name' => 'Hutang Gaji & Upah', 'type' => 'liability', 'sub_type' => 'current_liability', 'normal_balance' => 'credit'],
            // Ekuitas
            ['code' => '3100', 'name' => 'Modal Saham Disetor', 'type' => 'equity', 'sub_type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3200', 'name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'equity', 'sub_type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3300', 'name' => 'Laba Periode Berjalan', 'type' => 'equity', 'sub_type' => 'equity', 'normal_balance' => 'credit'],
            // Pendapatan
            ['code' => '4100', 'name' => 'Pendapatan Penjualan Barang Dagang', 'type' => 'revenue', 'sub_type' => 'operating_revenue', 'normal_balance' => 'credit'],
            ['code' => '4200', 'name' => 'Pendapatan Jasa & Instalasi', 'type' => 'revenue', 'sub_type' => 'operating_revenue', 'normal_balance' => 'credit'],
            ['code' => '4300', 'name' => 'Potongan & Diskon Penjualan', 'type' => 'revenue', 'sub_type' => 'operating_revenue', 'normal_balance' => 'debit'],
            // HPP
            ['code' => '5100', 'name' => 'Harga Pokok Penjualan (HPP)', 'type' => 'cogs', 'sub_type' => 'cogs', 'normal_balance' => 'debit'],
            ['code' => '5200', 'name' => 'Biaya Pengiriman & Freight Pembelian', 'type' => 'cogs', 'sub_type' => 'cogs', 'normal_balance' => 'debit'],
            // Beban Operasional
            ['code' => '6100', 'name' => 'Beban Gaji & Tunjangan Karyawan', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6200', 'name' => 'Beban Sewa Gedung & Kantor', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6300', 'name' => 'Beban Listrik, Air & Internet', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6400', 'name' => 'Beban Pemasaran & Promosi', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6500', 'name' => 'Beban Penyusutan Aset Tetap', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6600', 'name' => 'Beban Perlengkapan & ATK Kantor', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6700', 'name' => 'Beban Transportasi & Bahan Bakar', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6800', 'name' => 'Beban Administrasi Bank', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
            ['code' => '6900', 'name' => 'Beban Operasional Lain-lain', 'type' => 'expense', 'sub_type' => 'operating_expense', 'normal_balance' => 'debit'],
        ];

        $accMap = [];
        foreach ($accounts as $acc) {
            $id = DB::table('accounts')->insertGetId([
                'company_id' => $companyId,
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'sub_type' => $acc['sub_type'],
                'normal_balance' => $acc['normal_balance'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $accMap[$acc['code']] = $id;
        }

        // 6. Tax Codes
        $taxCodes = [
            ['code' => 'PPN11', 'name' => 'PPN 11% (Tarif Berlaku)', 'tax_type' => 'PPN', 'rate' => 11.00, 'sales_account' => '2200', 'purchase_account' => '1400'],
            ['code' => 'PPN12', 'name' => 'PPN 12% (UU HPP)', 'tax_type' => 'PPN', 'rate' => 12.00, 'sales_account' => '2200', 'purchase_account' => '1400'],
            ['code' => 'PPH23', 'name' => 'PPh 23 Jasa (2%)', 'tax_type' => 'PPH23', 'rate' => 2.00, 'sales_account' => '2220', 'purchase_account' => '2220'],
            ['code' => 'PPH4_2', 'name' => 'PPh 4(2) Final Sewa (10%)', 'tax_type' => 'PPH4_2', 'rate' => 10.00, 'sales_account' => '2230', 'purchase_account' => '2230'],
        ];

        $taxMap = [];
        foreach ($taxCodes as $tc) {
            $taxMap[$tc['code']] = DB::table('tax_codes')->insertGetId([
                'company_id' => $companyId,
                'code' => $tc['code'],
                'name' => $tc['name'],
                'tax_type' => $tc['tax_type'],
                'rate' => $tc['rate'],
                'sales_account_id' => $accMap[$tc['sales_account']] ?? null,
                'purchase_account_id' => $accMap[$tc['purchase_account']] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. Units of Measurement
        $unitIds = [
            'Pcs' => DB::table('units')->insertGetId(['company_id' => $companyId, 'name' => 'Pieces', 'symbol' => 'Pcs', 'created_at' => now(), 'updated_at' => now()]),
            'Box' => DB::table('units')->insertGetId(['company_id' => $companyId, 'name' => 'Box', 'symbol' => 'Box', 'created_at' => now(), 'updated_at' => now()]),
            'Rim' => DB::table('units')->insertGetId(['company_id' => $companyId, 'name' => 'Rim', 'symbol' => 'Rim', 'created_at' => now(), 'updated_at' => now()]),
        ];

        // 8. Items / Products
        $items = [
            [
                'sku' => 'LAPTOP-01',
                'name' => 'Laptop Bisnis Pro 14" i7 16GB',
                'type' => 'goods',
                'unit_id' => $unitIds['Pcs'],
                'buy_price' => 9500000,
                'sell_price' => 13500000,
                'stock' => 20,
            ],
            [
                'sku' => 'MONITOR-01',
                'name' => 'Monitor LED 24" UltraSharp IPS',
                'type' => 'goods',
                'unit_id' => $unitIds['Pcs'],
                'buy_price' => 1600000,
                'sell_price' => 2400000,
                'stock' => 35,
            ],
            [
                'sku' => 'PRINTER-01',
                'name' => 'Printer InkTank Eco Wireless',
                'type' => 'goods',
                'unit_id' => $unitIds['Pcs'],
                'buy_price' => 2200000,
                'sell_price' => 3200000,
                'stock' => 15,
            ],
            [
                'sku' => 'KERTAS-A4',
                'name' => 'Kertas HVS A4 80gsm (1 Rim)',
                'type' => 'goods',
                'unit_id' => $unitIds['Rim'],
                'buy_price' => 45000,
                'sell_price' => 60000,
                'stock' => 100,
            ],
            [
                'sku' => 'SRV-SETUP',
                'name' => 'Jasa Setup & Konfigurasi Jaringan Kantor',
                'type' => 'service',
                'unit_id' => $unitIds['Pcs'],
                'buy_price' => 0,
                'sell_price' => 1500000,
                'stock' => 0,
            ],
        ];

        $itemMap = [];
        foreach ($items as $it) {
            $itemId = DB::table('items')->insertGetId([
                'company_id' => $companyId,
                'unit_id' => $it['unit_id'],
                'sku' => $it['sku'],
                'name' => $it['name'],
                'type' => $it['type'],
                'buy_price' => $it['buy_price'],
                'sell_price' => $it['sell_price'],
                'is_stockable' => $it['type'] === 'goods',
                'is_active' => true,
                'inventory_account_id' => $accMap['1300'],
                'sales_account_id' => $accMap['4100'],
                'cogs_account_id' => $accMap['5100'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $itemMap[$it['sku']] = $itemId;

            if ($it['stock'] > 0) {
                DB::table('inventory_balances')->insert([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $itemId,
                    'quantity' => $it['stock'],
                    'average_cost' => $it['buy_price'],
                    'total_value' => $it['stock'] * $it['buy_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 9. Bank Accounts
        $bankBcaId = DB::table('bank_accounts')->insertGetId([
            'company_id' => $companyId,
            'account_id' => $accMap['1110'],
            'bank_name' => 'Bank Central Asia (BCA)',
            'account_number' => '123-456-7890',
            'account_holder_name' => 'PT Akru Maju Bersama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bankMandiriId = DB::table('bank_accounts')->insertGetId([
            'company_id' => $companyId,
            'account_id' => $accMap['1120'],
            'bank_name' => 'Bank Mandiri',
            'account_number' => '987-654-3210',
            'account_holder_name' => 'PT Akru Maju Bersama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 10. Contacts
        $cust1Id = DB::table('contacts')->insertGetId([
            'company_id' => $companyId,
            'type' => 'customer',
            'code' => 'CUST-001',
            'name' => 'PT Citra Mandiri Solusindo',
            'company_name' => 'PT Citra Mandiri Solusindo',
            'identity_type' => 'NPWP',
            'identity_number' => '02.345.678.9-021.000',
            'email' => 'finance@citramandiri.co.id',
            'phone' => '021-7891234',
            'address' => 'Gedung Wisma Niaga Lt. 8, Jl. Rasuna Said Kav. 10',
            'city' => 'Jakarta Selatan',
            'payment_terms_days' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cust2Id = DB::table('contacts')->insertGetId([
            'company_id' => $companyId,
            'type' => 'customer',
            'code' => 'CUST-002',
            'name' => 'CV Sejahtera Abadi',
            'company_name' => 'CV Sejahtera Abadi',
            'identity_type' => 'NPWP',
            'identity_number' => '03.456.789.0-032.000',
            'email' => 'sejahtera.abadi@gmail.com',
            'phone' => '021-8899776',
            'address' => 'Ruko Sentra Niaga Blok B-12',
            'city' => 'Bekasi Barat',
            'payment_terms_days' => 14,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supp1Id = DB::table('contacts')->insertGetId([
            'company_id' => $companyId,
            'type' => 'supplier',
            'code' => 'SUPP-001',
            'name' => 'PT Distributor Teknologi Indonesia',
            'company_name' => 'PT Distributor Teknologi Indonesia',
            'identity_type' => 'NPWP',
            'identity_number' => '01.999.888.7-011.000',
            'email' => 'orders@distributorteknologi.co.id',
            'phone' => '021-2345678',
            'address' => 'Kawasan Industri Pulogadung Blok III/15',
            'city' => 'Jakarta Timur',
            'payment_terms_days' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 11. Fiscal Period (Active)
        $periodId = DB::table('fiscal_periods')->insertGetId([
            'company_id' => $companyId,
            'name' => 'September 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'is_closed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 12. Seed Opening Balances & Balanced Initial Journal
        $postingService = app(PostingService::class);
        $taxCalculator = app(TaxCalculator::class);

        // Initial Capital injection Journal
        // Debit Bank BCA Rp 100.000.000, Bank Mandiri Rp 50.000.000, Kas Tunai Rp 5.000.000
        // Debit Persediaan Barang Dagang Rp 280.900.000
        // Kredit Modal Saham Disetor Rp 435.900.000
        $initLines = [
            ['account_id' => $accMap['1110'], 'debit' => 100000000, 'credit' => 0, 'description' => 'Saldo Awal Bank BCA'],
            ['account_id' => $accMap['1120'], 'debit' => 50000000, 'credit' => 0, 'description' => 'Saldo Awal Bank Mandiri'],
            ['account_id' => $accMap['1100'], 'debit' => 5000000, 'credit' => 0, 'description' => 'Saldo Awal Kas Tunai'],
            ['account_id' => $accMap['1300'], 'debit' => 280900000, 'credit' => 0, 'description' => 'Saldo Awal Persediaan Barang Dagang'],
            ['account_id' => $accMap['3100'], 'debit' => 0, 'credit' => 435900000, 'description' => 'Modal Saham Disetor'],
        ];

        $postingService->post(
            sourceType: 'opening_balance',
            sourceId: 1,
            companyId: $companyId,
            lines: $initLines,
            idempotencyKey: 'OB-2026-09',
            actorId: $ownerUser->id,
            description: 'Saldo Awal Modal & Kas Perusahaan',
            journalDate: '2026-09-01',
            branchId: $branchId,
        );

        // 13. Posted Sales Invoice Demo Transaction
        // Sell: 2x Laptop Pro @ 13.500.000 = 27.000.000
        // PPN 11% = 2.970.000
        // Total = 29.970.000
        $invNumber = 'INV/2026/09/0001';
        $subtotal = 27000000;
        $ppnAmount = 2970000;
        $totalInv = 29970000;

        $invId = DB::table('sales_invoices')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'contact_id' => $cust1Id,
            'invoice_number' => $invNumber,
            'invoice_date' => '2026-09-05',
            'due_date' => '2026-10-05',
            'status' => 'posted',
            'subtotal' => $subtotal,
            'tax_amount' => $ppnAmount,
            'total_amount' => $totalInv,
            'paid_amount' => 15000000,
            'remaining_amount' => 14970000,
            'tax_code_id' => $taxMap['PPN11'],
            'notes' => 'Pengadaan 2 unit laptop divisi keuangan',
            'created_by' => $ownerUser->id,
            'posted_at' => now(),
            'idempotency_key' => 'INV-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'sales_invoice_id' => $invId,
            'item_id' => $itemMap['LAPTOP-01'],
            'description' => 'Laptop Bisnis Pro 14" i7 16GB',
            'quantity' => 2,
            'unit_price' => 13500000,
            'subtotal' => 27000000,
            'tax_amount' => 2970000,
            'total' => 29970000,
            'sales_account_id' => $accMap['4100'],
            'cogs_account_id' => $accMap['5100'],
            'inventory_account_id' => $accMap['1300'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Post Journal for Sales Invoice:
        // Debit Piutang Usaha 29.970.000
        // Kredit Pendapatan Penjualan 27.000.000
        // Kredit Hutang PPN Keluaran 2.970.000
        // AND HPP: Debit HPP 19.000.000, Kredit Persediaan 19.000.000
        $salesLines = [
            ['account_id' => $accMap['1200'], 'debit' => $totalInv, 'credit' => 0, 'description' => "Piutang Penjualan {$invNumber}", 'contact_id' => $cust1Id],
            ['account_id' => $accMap['4100'], 'debit' => 0, 'credit' => $subtotal, 'description' => "Penjualan Barang {$invNumber}"],
            ['account_id' => $accMap['2200'], 'debit' => 0, 'credit' => $ppnAmount, 'description' => "PPN Keluaran 11% {$invNumber}"],
            // HPP lines
            ['account_id' => $accMap['5100'], 'debit' => 19000000, 'credit' => 0, 'description' => "HPP 2 unit Laptop {$invNumber}"],
            ['account_id' => $accMap['1300'], 'debit' => 0, 'credit' => 19000000, 'description' => "Pengeluaran Stok Laptop {$invNumber}"],
        ];

        $postingService->post(
            sourceType: 'sales_invoice',
            sourceId: $invId,
            companyId: $companyId,
            lines: $salesLines,
            idempotencyKey: 'POST_INV_001',
            actorId: $ownerUser->id,
            description: "Faktur Penjualan {$invNumber} - PT Citra Mandiri Solusindo",
            journalDate: '2026-09-05',
            branchId: $branchId,
        );

        // Record Tax Entry (PPN Keluaran)
        $taxCalculator->recordEntry(
            companyId: $companyId,
            taxCodeId: $taxMap['PPN11'],
            sourceType: 'sales_invoice',
            sourceId: $invId,
            baseAmount: $subtotal,
            taxAmount: $ppnAmount,
            taxDate: '2026-09-05',
            direction: 'output',
            contactId: $cust1Id,
            invoiceNumber: $invNumber,
            counterpartyNpwp: '02.345.678.9-021.000',
            counterpartyName: 'PT Citra Mandiri Solusindo',
            accountId: $accMap['2200'],
        );

        // 14. Customer Receipt (Payment from PT Citra Mandiri Solusindo)
        $rcpId = DB::table('customer_receipts')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'contact_id' => $cust1Id,
            'bank_account_id' => $bankBcaId,
            'receipt_number' => 'CR/2026/09/0001',
            'receipt_date' => '2026-09-07',
            'payment_method' => 'transfer',
            'reference_number' => 'TRF-BCA-88991',
            'total_amount' => 15000000,
            'status' => 'posted',
            'notes' => 'Pembayaran termin 1 Faktur INV/2026/09/0001',
            'created_by' => $ownerUser->id,
            'posted_at' => now(),
            'idempotency_key' => 'CR-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('receipt_allocations')->insert([
            'customer_receipt_id' => $rcpId,
            'sales_invoice_id' => $invId,
            'allocated_amount' => 15000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Post Journal for Receipt:
        // Debit Bank BCA 15.000.000, Kredit Piutang Usaha 15.000.000
        $rcpLines = [
            ['account_id' => $accMap['1110'], 'debit' => 15000000, 'credit' => 0, 'description' => 'Penerimaan Pembayaran Bank BCA CR/2026/09/0001', 'contact_id' => $cust1Id],
            ['account_id' => $accMap['1200'], 'debit' => 0, 'credit' => 15000000, 'description' => "Pelunasan Piutang {$invNumber}", 'contact_id' => $cust1Id],
        ];

        $postingService->post(
            sourceType: 'customer_receipt',
            sourceId: $rcpId,
            companyId: $companyId,
            lines: $rcpLines,
            idempotencyKey: 'POST_CR_001',
            actorId: $ownerUser->id,
            description: "Penerimaan Pembayaran CR/2026/09/0001 - PT Citra Mandiri Solusindo",
            journalDate: '2026-09-07',
            branchId: $branchId,
        );

        // 15. Operational Expense: Listrik & Internet
        $cashTrxId = DB::table('cash_transactions')->insertGetId([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'bank_account_id' => $bankMandiriId,
            'type' => 'cash_out',
            'transaction_number' => 'BKK/2026/09/0001',
            'transaction_date' => '2026-09-06',
            'counterparty' => 'PT PLN & Telkom Indihome',
            'total_amount' => 2500000,
            'account_id' => $accMap['6300'], // Beban Listrik, Air & Internet
            'status' => 'posted',
            'notes' => 'Tagihan utilitas kantor bulan September 2026',
            'created_by' => $ownerUser->id,
            'posted_at' => now(),
            'idempotency_key' => 'BKK-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Post Journal for Expense:
        // Debit Beban Listrik & Internet Rp 2.500.000
        // Kredit Bank Mandiri Rp 2.500.000
        $expLines = [
            ['account_id' => $accMap['6300'], 'debit' => 2500000, 'credit' => 0, 'description' => 'Beban Listrik & Internet BKK/2026/09/0001'],
            ['account_id' => $accMap['1120'], 'debit' => 0, 'credit' => 2500000, 'description' => 'Pembayaran via Bank Mandiri BKK/2026/09/0001'],
        ];

        $postingService->post(
            sourceType: 'cash_transaction',
            sourceId: $cashTrxId,
            companyId: $companyId,
            lines: $expLines,
            idempotencyKey: 'POST_BKK_001',
            actorId: $ownerUser->id,
            description: "Pengeluaran Kas BKK/2026/09/0001 - Utilitas Kantor",
            journalDate: '2026-09-06',
            branchId: $branchId,
        );

        // 16. Fixed Asset Seed
        DB::table('fixed_assets')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'asset_code' => 'AST-001',
            'name' => 'Server Dell PowerEdge R450',
            'acquisition_date' => '2026-01-15',
            'acquisition_cost' => 45000000,
            'useful_life_years' => 4,
            'salvage_value' => 5000000,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => $accMap['1600'],
            'accumulated_depreciation_account_id' => $accMap['1610'],
            'depreciation_expense_account_id' => $accMap['6500'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 17. Fiscal Correction Seed (Koreksi Fiskal Positif)
        DB::table('fiscal_corrections')->insert([
            'company_id' => $companyId,
            'fiscal_year' => 2026,
            'correction_type' => 'positive',
            'category' => 'Beda Tetap - Biaya Jamuan / Hiburan tanpa Daftar Nominatif',
            'description' => 'Entertainment client tanpa rincian daftar nominatif PMK-02/2010',
            'amount' => 4500000,
            'account_id' => $accMap['6400'],
            'legal_basis' => 'Pasal 9 ayat (1) huruf h UU PPh & PMK-02/PMK.03/2010',
            'created_by' => $ownerUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
