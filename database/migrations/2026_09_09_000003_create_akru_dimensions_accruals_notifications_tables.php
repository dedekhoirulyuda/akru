<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dimensions (Cost Center, Department, Project, Channel)
        Schema::create('dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 50); // e.g., 'Department', 'Project', 'Cost Center'
            $table->string('code', 30); // DEPT, PROJ, CC
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('dimension_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dimension_id')->constrained('dimensions')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dimension_id', 'code']);
        });

        // 2. Accruals & Prepaid Expense Schedules
        Schema::create('accrual_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('schedule_number', 50);
            $table->string('name', 100); // e.g. "Sewa Gedung 2026-2027"
            $table->string('type', 30)->default('prepaid_expense'); // prepaid_expense, accrued_expense, unearned_revenue
            $table->foreignId('prepaid_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('target_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('periods_count')->default(12);
            $table->decimal('amount_per_period', 15, 2)->default(0);
            $table->string('status', 30)->default('active'); // active, completed, cancelled
            $table->timestamps();

            $table->unique(['company_id', 'schedule_number']);
        });

        Schema::create('accrual_schedule_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accrual_schedule_id')->constrained('accrual_schedules')->cascadeOnDelete();
            $table->unsignedInteger('period_number');
            $table->date('schedule_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('pending'); // pending, posted, skipped
            $table->foreignId('journal_set_id')->nullable()->constrained('journal_sets')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Saved Views (Preset Filter/Kolom)
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('module', 50); // e.g., 'sales', 'purchases', 'ledger'
            $table->string('view_name', 100);
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 4. Notifications (In-App Notification Center)
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50); // approval, due_date, low_stock, tax_alert, sync
            $table->string('title', 150);
            $table->text('message');
            $table->string('action_url', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 5. Partner Console (Portal Konsultan Akuntansi / KKP)
        Schema::create('partner_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('license_number', 100)->nullable(); // Nomor Ijin KKP
            $table->string('contact_email', 100);
            $table->string('contact_phone', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('partner_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_workspace_id')->constrained('partner_workspaces')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('access_level', 30)->default('full_review'); // view_only, tax_preparer, full_review
            $table->timestamp('consent_granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_clients');
        Schema::dropIfExists('partner_workspaces');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('saved_views');
        Schema::dropIfExists('accrual_schedule_lines');
        Schema::dropIfExists('accrual_schedules');
        Schema::dropIfExists('dimension_values');
        Schema::dropIfExists('dimensions');
    }
};
