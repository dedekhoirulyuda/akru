<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of Accounts (COA)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 255);
            $table->string('type', 50); // asset, liability, equity, revenue, cogs, expense
            $table->string('sub_type', 100)->nullable(); // current_asset, fixed_asset, current_liability, long_term_liability, etc.
            $table->string('normal_balance', 10)->default('debit'); // debit, credit
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Contacts (Customers, Suppliers, Employees)
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type', 30); // customer, supplier, both, employee
            $table->string('code', 50)->nullable();
            $table->string('name', 255);
            $table->string('company_name', 255)->nullable();
            $table->string('identity_type', 20)->default('NPWP'); // NPWP, NIK
            $table->string('identity_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('tax_address')->nullable();
            $table->integer('payment_terms_days')->default(0); // 0 = Cash/COD, 30 = Net 30
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 255);
            $table->string('code', 50);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Units of Measurement
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('symbol', 20); // Pcs, Box, Kg, Liter, Rim
            $table->timestamps();
        });

        // Item Categories
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->timestamps();
        });

        // Items (Products & Services)
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->string('type', 30)->default('goods'); // goods, service
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->boolean('is_stockable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
        });

        // Bank Accounts (Cash & Bank)
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete(); // Linked GL Account
            $table->string('bank_name', 100); // BCA, Mandiri, BNI, BRI, Kas Tunai
            $table->string('account_number', 50)->nullable();
            $table->string('account_holder_name', 255)->nullable();
            $table->string('currency_code', 10)->default('IDR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tax Codes & Rates
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50); // PPN11, PPN12, PPH21, PPH23, PPH42
            $table->string('name', 255);
            $table->string('tax_type', 50); // PPN, PPH21, PPH22, PPH23, PPH4_2, PPH25
            $table->decimal('rate', 5, 2); // 11.00, 12.00, 2.00, 0.50
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete(); // PPN Keluaran
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete(); // PPN Masukan
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('units');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('accounts');
    }
};
