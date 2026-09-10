<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add AI chat quota limit to plans table
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'max_ai_chats_per_day')) {
                $table->integer('max_ai_chats_per_day')->nullable()->after('has_ai');
            }
        });

        // 2. Add AI chat quota override to companies table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'max_ai_chats_override')) {
                $table->integer('max_ai_chats_override')->nullable()->after('max_transactions_override');
            }
        });

        // 3. Create persistent AI Chat Usage tracking table
        if (!Schema::hasTable('ai_chat_usages')) {
            Schema::create('ai_chat_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('usage_date');
                $table->integer('message_count')->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'usage_date']);
            });
        }

        // 4. Seed / Register Official "AKRU Free Trial (Gratis)" Plan
        DB::table('plans')->updateOrInsert(
            ['slug' => 'free-trial'],
            [
                'name' => 'AKRU Free Trial (Uji Coba Gratis)',
                'description' => 'Paket uji coba gratis dengan akses fitur akuntansi esensial, laporan keuangan, dan konsultasi asisten AI dibatasi 10 pertanyaan per hari.',
                'price_per_month' => 0,
                'price_per_year' => 0,
                'max_users' => 2,
                'max_branches' => 1,
                'max_transactions_per_month' => 100,
                'has_ai' => true,
                'max_ai_chats_per_day' => 10,
                'modules' => json_encode([
                    'core', 'accounting', 'finance', 'sales', 'purchase',
                    'inventory', 'tax', 'ai', 'converter', 'audit'
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('slug', 'free-trial')->delete();

        Schema::dropIfExists('ai_chat_usages');

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'max_ai_chats_override')) {
                $table->dropColumn('max_ai_chats_override');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'max_ai_chats_per_day')) {
                $table->dropColumn('max_ai_chats_per_day');
            }
        });
    }
};
