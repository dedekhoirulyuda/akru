<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales Invoices
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status', 30)->default('draft'); // draft, submitted, approved, posted, partially_paid, paid, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'invoice_number']);
        });

        // Sales Invoice Lines
        Schema::create('sales_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

        // Customer Receipts
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->date('receipt_date');
            $table->string('payment_method', 50)->default('transfer'); // transfer, cash, giro
            $table->string('reference_number', 100)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('draft'); // draft, posted, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'receipt_number']);
        });

        // Receipt Allocations
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_receipt_id')->constrained('customer_receipts')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
        });

        // Purchase Invoices
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('invoice_number', 50); // Internal bill number
            $table->string('supplier_invoice_number', 100)->nullable(); // Supplier external invoice number
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status', 30)->default('draft'); // draft, submitted, approved, posted, partially_paid, paid, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'invoice_number']);
        });

        // Purchase Invoice Lines
        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

        // Supplier Payments
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('payment_number', 50);
            $table->date('payment_date');
            $table->string('payment_method', 50)->default('transfer');
            $table->string('reference_number', 100)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'payment_number']);
        });

        // Payment Allocations
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();
        });

        // Cash Transactions (Cash in / Cash out non-invoiced)
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('type', 20); // cash_in, cash_out
            $table->string('transaction_number', 50);
            $table->date('transaction_date');
            $table->string('counterparty', 255)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete(); // Offset GL Account
            $table->string('status', 30)->default('draft'); // draft, posted, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transaction_number']);
        });

        // Bank Transfers (Internal Transfer)
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('from_bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('to_bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('transfer_number', 50);
            $table->date('transfer_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transfer_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('sales_invoice_lines');
        Schema::dropIfExists('sales_invoices');
    }
};
