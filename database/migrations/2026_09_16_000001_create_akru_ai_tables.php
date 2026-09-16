<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for the AKRU AI Module (Blueprint_AKRU_AI.md Chapter 16).
     */
    public function up(): void
    {
        $this->down();

        // 1. ai_providers (global)
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // akru_native, google_gemini, openai
            $table->string('name', 100);
            $table->string('adapter_class');
            $table->boolean('is_active')->default(true);
            $table->json('capability_flags_json')->nullable();
            $table->timestamps();
        });

        // 2. ai_provider_models (global)
        Schema::create('ai_provider_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('model_code', 100);
            $table->string('display_name', 100);
            $table->json('capability_flags_json')->nullable();
            $table->unsignedInteger('context_limit')->default(8192);
            $table->unsignedInteger('output_limit')->default(4096);
            $table->json('pricing_metadata_json')->nullable();
            $table->string('status', 20)->default('active'); // active, deprecated, beta
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'model_code']);
        });

        // 3. ai_company_provider_settings (tenant)
        Schema::create('ai_company_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->string('credential_mode', 20)->default('managed'); // managed, byok
            $table->text('encrypted_credential_ref')->nullable(); // encrypted API Key
            $table->foreignId('default_model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->json('data_policy_json')->nullable();
            $table->boolean('fallback_allowed')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'provider_id']);
        });

        // 4. ai_capability_routes (tenant)
        Schema::create('ai_capability_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('capability_code', 50); // navigation, kpi_reporting, analysis, document_extraction, anomaly, tax_consulting
            $table->foreignId('primary_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('primary_model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('fallback_model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->decimal('max_cost_per_request', 10, 4)->nullable();
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'capability_code']);
        });

        // 5. ai_conversations (tenant)
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255)->default('Percakapan Baru');
            $table->string('mode', 50)->default('general'); // data_analyst, accounting, finance, tax, business, guide, anomaly
            $table->json('branch_scope_json')->nullable();
            $table->json('period_scope_json')->nullable();
            $table->string('provider_preference', 50)->nullable();
            $table->string('status', 20)->default('active'); // active, archived
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('retention_expires_at')->nullable();
            $table->timestamps();
        });

        // 6. ai_messages (tenant)
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 20); // user, assistant, system
            $table->string('message_type', 30)->default('text'); // text, structured, error
            $table->longText('content');
            $table->json('structured_content_json')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('risk_level', 20)->default('low'); // low, medium, high
            $table->timestamp('data_as_of')->nullable();
            $table->string('status', 20)->default('success'); // success, error, fallback
            $table->foreignId('parent_message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->timestamps();
        });

        // 7. ai_message_citations (tenant)
        Schema::create('ai_message_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->string('source_type', 50); // journal, invoice, bank, tax, knowledge
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_version', 50)->nullable();
            $table->string('label', 255);
            $table->json('locator_json')->nullable();
            $table->string('deep_link', 255)->nullable();
            $table->timestamp('data_as_of')->nullable();
            $table->timestamps();
        });

        // 8. ai_tool_calls (tenant)
        Schema::create('ai_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->string('tool_code', 100);
            $table->string('tool_version', 20)->default('1.0');
            $table->json('arguments_redacted_json')->nullable();
            $table->json('result_summary_json')->nullable();
            $table->string('authorization_result', 20)->default('authorized'); // authorized, denied
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 20)->default('success'); // success, error
            $table->string('error_code', 50)->nullable();
            $table->timestamps();
        });

        // 9. ai_usage_events (tenant audit)
        Schema::create('ai_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->string('capability_code', 50)->nullable();
            $table->unsignedInteger('request_units')->default(1);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 4)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->boolean('cache_hit')->default(false);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status', 20)->default('success');
            $table->string('request_id', 64)->nullable()->index();
            $table->timestamps();
        });

        // 10. ai_consent_logs (tenant)
        Schema::create('ai_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('consent_type', 50); // external_ai_data_sharing, byok_usage
            $table->string('policy_version', 20);
            $table->boolean('granted')->default(true);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 11. ai_redaction_events (tenant)
        Schema::create('ai_redaction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('request_id', 64)->nullable();
            $table->string('rule_code', 50);
            $table->string('field_type', 50);
            $table->string('action', 20)->default('mask');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();
        });

        // 12. ai_knowledge_sources (global or tenant)
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('source_type', 50); // product_guide, accounting_standard, tax_regulation, company_sop
            $table->string('name', 255);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jurisdiction', 50)->default('ID');
            $table->string('sensitivity', 30)->default('internal');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // 13. ai_knowledge_documents (global or tenant)
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('ai_knowledge_sources')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('version', 20)->default('1.0');
            $table->string('language', 10)->default('id');
            $table->timestamp('effective_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('approved'); // draft, in_review, approved, effective, expired, archived
            $table->string('checksum', 64)->nullable();
            $table->string('file_reference')->nullable();
            $table->foreignId('supersedes_id')->nullable()->constrained('ai_knowledge_documents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 14. ai_knowledge_chunks (global or tenant)
        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('ai_knowledge_documents')->cascadeOnDelete();
            $table->string('section_path', 255)->nullable();
            $table->text('chunk_text');
            $table->text('search_text')->nullable();
            $table->string('embedding_reference')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            if (DB::getDriverName() === 'mysql') {
                $table->fullText('search_text');
            }
        });

        // 15. ai_anomaly_rules (global or tenant)
        Schema::create('ai_anomaly_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 50); // duplicate, threshold, statistical, timing, reconciliation
            $table->string('version', 20)->default('1.0');
            $table->string('detector_type', 50)->default('rule'); // rule, statistical, time_series
            $table->json('configuration_json')->nullable();
            $table->string('base_severity', 20)->default('medium'); // info, low, medium, high, critical
            $table->boolean('is_active')->default(true);
            $table->boolean('shadow_mode')->default(false);
            $table->timestamp('effective_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 16. ai_anomaly_runs (tenant)
        Schema::create('ai_anomaly_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('rule_set_version', 20)->default('1.0');
            $table->string('trigger_type', 30)->default('scheduled'); // manual, scheduled, pre_closing
            $table->json('scope_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('completed'); // running, completed, failed
            $table->unsignedInteger('records_scanned')->default(0);
            $table->unsignedInteger('findings_count')->default(0);
            $table->text('error_summary')->nullable();
            $table->timestamps();
        });

        // 17. ai_anomaly_findings (tenant)
        Schema::create('ai_anomaly_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('run_id')->nullable()->constrained('ai_anomaly_runs')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('ai_anomaly_rules')->nullOnDelete();
            $table->string('fingerprint', 64)->index();
            $table->string('category', 50);
            $table->string('severity', 20); // info, low, medium, high, critical
            $table->unsignedTinyInteger('risk_score')->default(50); // 0-100
            $table->decimal('confidence', 5, 4)->default(1.0);
            $table->decimal('materiality_value', 15, 2)->nullable();
            $table->string('currency', 10)->default('IDR');
            $table->string('title', 255);
            $table->text('explanation_summary');
            $table->string('status', 30)->default('open'); // open, assigned, in_review, confirmed_issue, false_positive, accepted_risk, resolved
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_code', 50)->nullable();
            $table->timestamps();
        });

        // 18. ai_anomaly_evidence (tenant)
        Schema::create('ai_anomaly_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('finding_id')->constrained('ai_anomaly_findings')->cascadeOnDelete();
            $table->string('source_type', 50); // sales_invoice, purchase_invoice, cash_transaction, journal
            $table->unsignedBigInteger('source_id');
            $table->json('observed_json')->nullable();
            $table->json('expected_json')->nullable();
            $table->timestamp('snapshot_at')->nullable();
            $table->timestamps();
        });

        // 19. ai_suggestions (tenant - human in the loop)
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('suggestion_type', 50); // account_mapping, journal_draft, tax_mapping, reconciliation_match
            $table->string('source_type', 50)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('ai_provider_models')->nullOnDelete();
            $table->string('policy_version', 20)->default('1.0');
            $table->json('payload_json');
            $table->text('reason_summary')->nullable();
            $table->decimal('confidence', 5, 4)->default(0.85);
            $table->string('risk_level', 20)->default('low');
            $table->string('status', 30)->default('awaiting_review'); // awaiting_review, accepted, edited, rejected, expired, converted_to_draft
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 20. ai_suggestion_reviews (tenant)
        Schema::create('ai_suggestion_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('suggestion_id')->constrained('ai_suggestions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 20); // accept, edit, reject
            $table->json('edited_payload_json')->nullable();
            $table->text('comment')->nullable();
            $table->string('converted_draft_type', 50)->nullable(); // journal_set, purchase_invoice, etc.
            $table->unsignedBigInteger('converted_draft_id')->nullable();
            $table->timestamps();
        });

        // 21. ai_prompt_templates & versions (global or tenant override)
        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('capability_code', 50);
            $table->string('scope_type', 20)->default('global'); // global, company
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('ai_prompt_templates')->cascadeOnDelete();
            $table->string('version', 20);
            $table->string('system_policy_ref', 50)->default('v1');
            $table->text('template_text');
            $table->json('output_schema_json')->nullable();
            $table->string('change_summary', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'version']);
        });

        // 22. ai_feedback (tenant)
        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('rating', 20); // helpful, not_helpful
            $table->string('category', 50)->nullable(); // wrong_numbers, wrong_source, irrelevant, slow, unsafe
            $table->text('comment')->nullable();
            $table->string('review_status', 20)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_suggestion_reviews');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_anomaly_evidence');
        Schema::dropIfExists('ai_anomaly_findings');
        Schema::dropIfExists('ai_anomaly_runs');
        Schema::dropIfExists('ai_anomaly_rules');
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_knowledge_sources');
        Schema::dropIfExists('ai_redaction_events');
        Schema::dropIfExists('ai_consent_logs');
        Schema::dropIfExists('ai_usage_events');
        Schema::dropIfExists('ai_tool_calls');
        Schema::dropIfExists('ai_message_citations');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_capability_routes');
        Schema::dropIfExists('ai_company_provider_settings');
        Schema::dropIfExists('ai_provider_models');
        Schema::dropIfExists('ai_providers');
    }
};
