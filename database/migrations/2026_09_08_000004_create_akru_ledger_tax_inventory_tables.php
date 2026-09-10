<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fiscal Periods
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 50); // e.g., '2026-09' or 'September 2026'
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'start_date', 'end_date']);
        });

        // Journal Sets (General Ledger header)
        Schema::create('journal_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('journal_number', 50);
            $table->date('journal_date');
            $table->foreignId('period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();
            $table->string('source_type', 50)->nullable(); // sales_invoice, purchase_invoice, customer_receipt, manual, etc.
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('posted'); // posted, reversed
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'journal_number']);
            $table->index(['source_type', 'source_id']);
        });

        // Journal Lines (General Ledger details)
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_set_id')->constrained('journal_sets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->unsignedSmallInteger('line_order')->default(1);
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();

            $table->index(['journal_set_id', 'account_id']);
        });

        // Tax Entries (Tax Subledger)
        Schema::create('tax_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tax_code_id')->constrained('tax_codes')->cascadeOnDelete();
            $table->string('source_type', 50); // sales_invoice, purchase_invoice, manual
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date('tax_date');
            $table->string('tax_period', 10); // YYYY-MM
            $table->string('tax_type', 30); // PPN, PPH21, PPH23, PPH4_2
            $table->decimal('base_amount', 15, 2)->default(0); // DPP
            $table->decimal('rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('direction', 10)->default('output'); // output (keluaran), input (masukan)
            $table->string('invoice_number', 50)->nullable();
            $table->string('counterparty_npwp', 50)->nullable();
            $table->string('counterparty_name', 255)->nullable();
            $table->boolean('is_creditable')->default(true);
            $table->string('status', 30)->default('draft'); // draft, verified, reported
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'tax_period', 'tax_type']);
            $table->index(['source_type', 'source_id']);
        });

        // Stock Movements (Inventory Ledger)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('movement_type', 30); // sales, purchase, return_in, return_out, adjustment, transfer
            $table->date('movement_date');
            $table->decimal('quantity', 12, 4); // positive for in, negative for out
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('balance_after', 12, 4)->default(0);
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'warehouse_id', 'item_id']);
        });

        // Inventory Balances (Fast snapshot)
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'warehouse_id', 'item_id']);
        });

        // Fixed Assets
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('asset_code', 50);
            $table->string('name', 255);
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->unsignedSmallInteger('useful_life_years')->default(4);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->foreignId('asset_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'asset_code']);
        });

        // Fiscal Corrections (Koreksi Fiskal Positif / Negatif)
        Schema::create('fiscal_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('correction_type', 20); // positive, negative
            $table->string('category', 100)->nullable(); // e.g. Beda Tetap, Beda Waktu, Kenikmatan/Natura, Jamuan tanpa daftar nominatif
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('legal_basis')->nullable(); // UU HPP, PMK, dsb.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_corrections');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('tax_entries');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_sets');
        Schema::dropIfExists('fiscal_periods');
    }
};
