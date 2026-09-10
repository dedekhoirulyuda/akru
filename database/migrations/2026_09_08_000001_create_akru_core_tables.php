<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Companies (Tenants/Entities)
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('entity_type')->default('PT'); // PT, CV, Perorangan, Yayasan
            $table->string('nib')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->boolean('is_pkp')->default(false);
            $table->string('kbli', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('currency_code', 10)->default('IDR');
            $table->string('logo_path')->nullable();
            $table->string('status', 30)->default('active'); // active, suspended, trial
            $table->timestamps();
            $table->softDeletes();
        });

        // Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('address')->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        // Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('module', 50);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Company Users (Pivot)
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // Role Permissions
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Subscription Plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_per_month', 15, 2)->default(0);
            $table->integer('max_users')->default(5);
            $table->integer('max_branches')->default(1);
            $table->integer('max_transactions_per_month')->default(500);
            $table->boolean('has_ai')->default(false);
            $table->timestamps();
        });

        // Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('status', 30)->default('active'); // active, trialing, past_due, cancelled
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        // Settings (Company Key-Value)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key']);
        });

        // Document Sequences (Auto-numbering)
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('document_type', 50); // sales_invoice, purchase_invoice, journal, receipt, payment
            $table->string('prefix', 20)->default('');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->string('format_pattern')->default('{PREFIX}/{YEAR}/{MONTH}/{NUMBER}');
            $table->string('period', 10)->nullable(); // YYYY-MM
            $table->timestamps();

            $table->unique(['company_id', 'document_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
