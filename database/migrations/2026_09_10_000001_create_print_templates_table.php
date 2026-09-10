<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 50)->default('general');
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);

            // Printer mode
            $table->string('printer_mode', 15)->default('laser_inkjet'); // laser_inkjet | dot_matrix
            $table->string('paper_size', 20)->default('A4');
            $table->string('orientation', 10)->default('portrait');
            $table->unsignedSmallInteger('custom_width_mm')->nullable();
            $table->unsignedSmallInteger('custom_height_mm')->nullable();

            // Margins (mm)
            $table->unsignedSmallInteger('margin_top')->default(10);
            $table->unsignedSmallInteger('margin_bottom')->default(10);
            $table->unsignedSmallInteger('margin_left')->default(12);
            $table->unsignedSmallInteger('margin_right')->default(12);

            // Typography
            $table->string('font_family', 50)->default('Inter');
            $table->unsignedTinyInteger('font_size')->default(10);
            $table->string('color_primary', 7)->default('#1e293b');
            $table->string('color_accent', 7)->default('#2563eb');
            $table->string('color_text', 7)->default('#1e293b');

            // Header / Kop Surat
            $table->boolean('show_logo')->default(true);
            $table->string('logo_position', 10)->default('left');
            $table->unsignedSmallInteger('logo_size')->default(60);
            $table->boolean('show_company_name')->default(true);
            $table->boolean('show_company_address')->default(true);
            $table->boolean('show_npwp')->default(true);
            $table->boolean('show_phone_email')->default(true);
            $table->json('header_layout')->nullable();

            // Footer
            $table->text('footer_text')->nullable();
            $table->boolean('show_page_number')->default(true);

            // Signatures
            $table->json('signatures')->nullable();

            // Table styling
            $table->string('table_header_bg', 7)->default('#f1f5f9');
            $table->string('table_header_text', 7)->default('#1e293b');
            $table->string('table_border_color', 7)->default('#e2e8f0');
            $table->boolean('show_gridlines')->default(true);

            // Dot matrix specific
            $table->unsignedSmallInteger('dm_char_per_line')->default(80);
            $table->unsignedSmallInteger('dm_lines_per_page')->default(66);
            $table->boolean('dm_condensed')->default(false);
            $table->string('dm_separator_char', 1)->default('-');
            $table->boolean('dm_box_drawing')->default(true);

            // Advanced
            $table->text('custom_css')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'document_type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
