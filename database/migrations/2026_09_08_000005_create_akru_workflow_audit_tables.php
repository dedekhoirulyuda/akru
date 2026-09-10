<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Approval Policies
        Schema::create('approval_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 50); // sales_invoice, purchase_invoice, payment, journal
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->foreignId('approver_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->boolean('anti_self_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Approval Requests
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['document_type', 'document_id']);
        });

        // Audit Logs (Append-Only)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // created, updated, posted, reversed, deleted, approved
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // Sync Queues (Offline PWA Sync)
        Schema::create('sync_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 100)->nullable();
            $table->string('local_id', 100);
            $table->string('entity_type', 50); // sales_invoice, expense, payment
            $table->string('action', 30)->default('create');
            $table->json('payload');
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed, conflict
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queues');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_policies');
    }
};
