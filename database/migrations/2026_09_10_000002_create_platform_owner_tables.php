<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add is_superadmin to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_superadmin')) {
                $table->boolean('is_superadmin')->default(false)->after('password');
            }
        });

        // 2. Add module flags & annual pricing to plans table
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'price_per_year')) {
                $table->decimal('price_per_year', 15, 2)->nullable()->after('price_per_month');
            }
            if (!Schema::hasColumn('plans', 'modules')) {
                $table->json('modules')->nullable()->after('has_ai');
            }
            if (!Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('has_ai');
            }
        });

        // 3. Add custom quotas and suspended reason to companies table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'suspended_reason')) {
                $table->text('suspended_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('companies', 'custom_modules')) {
                $table->json('custom_modules')->nullable()->after('suspended_reason');
            }
            if (!Schema::hasColumn('companies', 'max_users_override')) {
                $table->integer('max_users_override')->nullable()->after('custom_modules');
            }
            if (!Schema::hasColumn('companies', 'max_branches_override')) {
                $table->integer('max_branches_override')->nullable()->after('max_users_override');
            }
            if (!Schema::hasColumn('companies', 'max_transactions_override')) {
                $table->integer('max_transactions_override')->nullable()->after('max_branches_override');
            }
        });

        // 3b. Add billing_cycle to subscriptions table
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly')->after('status');
            }
        });

        // 4. Platform Settings table (API Keys, Payment, AI, WhatsApp, Coretax)
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general'); // ai, payment, coretax, notification, general
            $table->boolean('is_secret')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 5. Subscription Invoices (B2B Billing, Manual & Gateway Invoices)
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
            $table->string('status', 30)->default('unpaid'); // unpaid, paid, cancelled, overdue
            $table->string('payment_method', 50)->nullable(); // manual_transfer, midtrans, xendit
            $table->string('payment_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Platform API Tokens (Partner & External Integrations)
        Schema::create('platform_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_api_tokens');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('platform_settings');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'suspended_reason',
                'custom_modules',
                'max_users_override',
                'max_branches_override',
                'max_transactions_override'
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['price_per_year', 'modules', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }
};
