<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Stock Transfers (Transfer Antar-Gudang)
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('transfer_number', 50);
            $table->date('transfer_date');
            $table->string('status', 30)->default('draft'); // draft, in_transit, received, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transfer_number']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity_sent', 12, 4)->default(1);
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->string('unit', 20)->default('PCS');
            $table->timestamps();
        });

        // 2. Stock Opnames (Pemeriksaan Fisik Stok Berkala)
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('opname_number', 50);
            $table->date('opname_date');
            $table->string('status', 30)->default('draft'); // draft, counting, pending_approval, approved, posted, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('journal_set_id')->nullable()->constrained('journal_sets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'opname_number']);
        });

        Schema::create('stock_opname_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('system_quantity', 12, 4)->default(0);
            $table->decimal('physical_quantity', 12, 4)->default(0);
            $table->decimal('difference_quantity', 12, 4)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('total_difference_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // 3. Petty Cash (Kas Kecil / Imprest System)
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete(); // Akun Kas Kecil
            $table->string('fund_name', 100);
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete(); // Penanggung jawab kasir
            $table->decimal('imprest_amount', 15, 2)->default(0); // Plafon kas kecil tetap
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('petty_cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_fund_id')->constrained('petty_cash_funds')->cascadeOnDelete();
            $table->string('voucher_number', 50);
            $table->date('voucher_date');
            $table->string('type', 20)->default('expense'); // expense (pengeluaran), replenishment (pengisian ulang)
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('recipient_name', 100)->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('approved'); // pending, approved, reimbursed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_set_id')->nullable()->constrained('journal_sets')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Budgets (Anggaran Operasional & Proyek)
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 100);
            $table->year('fiscal_year');
            $table->string('status', 30)->default('draft'); // draft, active, closed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name', 'fiscal_year']);
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('annual_amount', 15, 2)->default(0);
            $table->decimal('monthly_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('petty_cash_vouchers');
        Schema::dropIfExists('petty_cash_funds');
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
