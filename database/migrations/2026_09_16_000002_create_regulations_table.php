<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulations', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // akuntansi, pajak, kepabeanan, pmk, surat_edaran
            $table->string('level', 50); // Undang-Undang, Peraturan Pemerintah, PMK, Standar Akuntansi, Surat Edaran
            $table->string('code', 100)->unique(); // unique reference code e.g. PMK-190-2022
            $table->string('number', 150); // e.g. PMK No. 190/PMK.04/2022
            $table->integer('year');
            $table->string('title', 300);
            $table->text('about');
            $table->string('status', 30)->default('berlaku'); // berlaku, diubah, dicabut
            $table->date('effective_date')->nullable();
            $table->text('summary');
            $table->json('key_points')->nullable(); // list of key clauses / percentages
            $table->text('accounting_implications')->nullable(); // journal debit/credit instructions
            $table->text('tax_implications')->nullable(); // withholding, creditability, reporting
            $table->json('keywords')->nullable(); // tags for semantic search
            $table->string('official_source_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulations');
    }
};
