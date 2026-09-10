<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\PrintTemplate;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PrintSettingTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'AkruDatabaseSeeder']);

        $this->user = User::where('email', 'owner@akru.id')->firstOrFail();
        $this->company = Company::firstOrFail();
    }

    private function sess(): array
    {
        return [
            'current_company_id' => $this->company->id,
            'active_company_id'  => $this->company->id,
            'active_company_name' => $this->company->name,
        ];
    }

    public function test_print_layout_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->get('/settings/print-layout');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Cetak');
        $response->assertSee('Laser / Inkjet');
        $response->assertSee('Dot Matrix');
    }

    public function test_can_create_laser_inkjet_template(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->post('/settings/print-layout', [
                'name'            => 'Faktur Standard A4',
                'document_type'   => 'sales_invoice',
                'is_default'      => true,
                'printer_mode'    => 'laser_inkjet',
                'paper_size'      => 'A4',
                'orientation'     => 'portrait',
                'margin_top'      => 10,
                'margin_bottom'   => 10,
                'margin_left'     => 12,
                'margin_right'    => 12,
                'font_family'     => 'Inter',
                'font_size'       => 10,
                'color_primary'   => '#1e293b',
                'color_accent'    => '#2563eb',
                'color_text'      => '#1e293b',
                'show_logo'       => true,
                'logo_position'   => 'left',
                'logo_size'       => 60,
                'show_company_name' => true,
                'show_company_address' => true,
                'show_npwp'       => true,
                'show_phone_email' => true,
                'show_page_number' => true,
                'show_gridlines'  => true,
                'table_header_bg' => '#f1f5f9',
                'table_header_text' => '#1e293b',
                'table_border_color' => '#e2e8f0',
                'header_layout'   => json_encode([
                    ['type' => 'logo', 'order' => 1, 'visible' => true],
                    ['type' => 'company_name', 'order' => 2, 'visible' => true],
                ]),
                'signatures'      => json_encode([
                    ['title' => 'Dibuat Oleh', 'name' => 'Staff', 'position' => 'left'],
                    ['title' => 'Disetujui', 'name' => 'Manager', 'position' => 'right'],
                ]),
            ]);

        $response->assertRedirect(route('print-layout.index'));

        $this->assertDatabaseHas('print_templates', [
            'name'          => 'Faktur Standard A4',
            'document_type' => 'sales_invoice',
            'printer_mode'  => 'laser_inkjet',
            'paper_size'    => 'A4',
            'font_family'   => 'Inter',
            'is_default'    => true,
        ]);
    }

    public function test_can_create_dot_matrix_template(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->post('/settings/print-layout', [
                'name'            => 'Faktur Dot Matrix CF',
                'document_type'   => 'sales_invoice',
                'printer_mode'    => 'dot_matrix',
                'paper_size'      => 'cont_9.5x11',
                'orientation'     => 'portrait',
                'margin_top'      => 6,
                'margin_bottom'   => 6,
                'margin_left'     => 8,
                'margin_right'    => 8,
                'font_family'     => 'Courier New',
                'font_size'       => 10,
                'color_primary'   => '#000000',
                'color_accent'    => '#000000',
                'color_text'      => '#000000',
                'dm_char_per_line' => 80,
                'dm_lines_per_page' => 66,
                'dm_condensed'    => false,
                'dm_separator_char' => '-',
                'dm_box_drawing'  => true,
                'show_page_number' => true,
            ]);

        $response->assertRedirect(route('print-layout.index'));

        $tpl = PrintTemplate::where('name', 'Faktur Dot Matrix CF')->first();
        $this->assertNotNull($tpl);
        $this->assertEquals('dot_matrix', $tpl->printer_mode);
        $this->assertEquals('cont_9.5x11', $tpl->paper_size);
        $this->assertFalse($tpl->show_logo); // Enforced by controller
        $this->assertEquals('Courier New', $tpl->font_family);
        $this->assertTrue($tpl->isDotMatrix());
        $this->assertTrue($tpl->isContinuousForm());

        // Verify paper dimensions
        [$w, $h] = $tpl->getPaperDimensions();
        $this->assertEquals(241.3, $w);
        $this->assertEquals(279.4, $h);
    }

    public function test_can_update_template(): void
    {
        $tpl = PrintTemplate::create([
            'company_id'     => $this->company->id,
            'name'           => 'Old Name',
            'document_type'  => 'general',
            'printer_mode'   => 'laser_inkjet',
            'paper_size'     => 'A4',
            'orientation'    => 'portrait',
            'margin_top'     => 10,
            'margin_bottom'  => 10,
            'margin_left'    => 12,
            'margin_right'   => 12,
            'font_family'    => 'Inter',
            'font_size'      => 10,
            'color_primary'  => '#1e293b',
            'color_accent'   => '#2563eb',
            'color_text'     => '#1e293b',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->put("/settings/print-layout/{$tpl->id}", [
                'name'           => 'Updated Name',
                'document_type'  => 'report',
                'printer_mode'   => 'laser_inkjet',
                'paper_size'     => 'F4',
                'orientation'    => 'landscape',
                'margin_top'     => 15,
                'margin_bottom'  => 15,
                'margin_left'    => 20,
                'margin_right'   => 20,
                'font_family'    => 'Roboto',
                'font_size'      => 12,
                'color_primary'  => '#0f172a',
                'color_accent'   => '#dc2626',
                'color_text'     => '#334155',
            ]);

        $response->assertRedirect(route('print-layout.index'));

        $tpl->refresh();
        $this->assertEquals('Updated Name', $tpl->name);
        $this->assertEquals('report', $tpl->document_type);
        $this->assertEquals('F4', $tpl->paper_size);
        $this->assertEquals('landscape', $tpl->orientation);
        $this->assertEquals('Roboto', $tpl->font_family);
        $this->assertEquals(12, $tpl->font_size);
    }

    public function test_can_duplicate_and_delete_template(): void
    {
        $tpl = PrintTemplate::create([
            'company_id'     => $this->company->id,
            'name'           => 'Original',
            'document_type'  => 'general',
            'is_default'     => true,
            'printer_mode'   => 'laser_inkjet',
            'paper_size'     => 'A4',
            'orientation'    => 'portrait',
            'margin_top'     => 10,
            'margin_bottom'  => 10,
            'margin_left'    => 12,
            'margin_right'   => 12,
            'font_family'    => 'Inter',
            'font_size'      => 10,
            'color_primary'  => '#1e293b',
            'color_accent'   => '#2563eb',
            'color_text'     => '#1e293b',
        ]);

        // Duplicate
        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->post("/settings/print-layout/{$tpl->id}/duplicate");
        $response->assertRedirect(route('print-layout.index'));
        $this->assertDatabaseHas('print_templates', ['name' => 'Original (Salinan)', 'is_default' => false]);

        // Delete clone
        $clone = PrintTemplate::where('name', 'Original (Salinan)')->first();
        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->delete("/settings/print-layout/{$clone->id}");
        $response->assertRedirect(route('print-layout.index'));
        $this->assertDatabaseMissing('print_templates', ['id' => $clone->id]);
    }

    public function test_can_set_default_template(): void
    {
        $tpl1 = PrintTemplate::create([
            'company_id' => $this->company->id, 'name' => 'Template A', 'document_type' => 'general',
            'is_default' => true, 'printer_mode' => 'laser_inkjet', 'paper_size' => 'A4',
            'orientation' => 'portrait', 'margin_top' => 10, 'margin_bottom' => 10,
            'margin_left' => 12, 'margin_right' => 12, 'font_family' => 'Inter',
            'font_size' => 10, 'color_primary' => '#1e293b', 'color_accent' => '#2563eb', 'color_text' => '#1e293b',
        ]);

        $tpl2 = PrintTemplate::create([
            'company_id' => $this->company->id, 'name' => 'Template B', 'document_type' => 'general',
            'is_default' => false, 'printer_mode' => 'laser_inkjet', 'paper_size' => 'Letter',
            'orientation' => 'portrait', 'margin_top' => 10, 'margin_bottom' => 10,
            'margin_left' => 12, 'margin_right' => 12, 'font_family' => 'Roboto',
            'font_size' => 11, 'color_primary' => '#1e293b', 'color_accent' => '#2563eb', 'color_text' => '#1e293b',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession($this->sess())
            ->post("/settings/print-layout/{$tpl2->id}/set-default");
        $response->assertRedirect(route('print-layout.index'));

        $tpl1->refresh();
        $tpl2->refresh();
        $this->assertFalse($tpl1->is_default);
        $this->assertTrue($tpl2->is_default);
    }

    public function test_paper_dimensions_and_model_helpers(): void
    {
        $tpl = new PrintTemplate();
        $tpl->paper_size = 'A4';
        $tpl->orientation = 'portrait';
        [$w, $h] = $tpl->getPaperDimensions();
        $this->assertEquals(210, $w);
        $this->assertEquals(297, $h);

        // Landscape
        $tpl->orientation = 'landscape';
        [$w, $h] = $tpl->getPaperDimensions();
        $this->assertEquals(297, $w);
        $this->assertEquals(210, $h);

        // Continuous form
        $tpl->paper_size = 'cont_9.5x5.5';
        $tpl->orientation = 'portrait';
        [$w, $h] = $tpl->getPaperDimensions();
        $this->assertEquals(241.3, $w);
        $this->assertEquals(139.7, $h);
        $this->assertTrue($tpl->isContinuousForm());

        // Custom
        $tpl->paper_size = 'custom';
        $tpl->custom_width_mm = 200;
        $tpl->custom_height_mm = 300;
        [$w, $h] = $tpl->getPaperDimensions();
        $this->assertEquals(200, $w);
        $this->assertEquals(300, $h);

        // CSS page size
        $this->assertEquals('200mm 300mm', $tpl->getCssPageSize());

        // Dot matrix
        $tpl->printer_mode = 'dot_matrix';
        $this->assertTrue($tpl->isDotMatrix());

        $tpl->printer_mode = 'laser_inkjet';
        $this->assertFalse($tpl->isDotMatrix());

        // Default signatures
        $sigs = $tpl->getSignaturesOrDefault();
        $this->assertCount(3, $sigs);
        $this->assertEquals('Dibuat Oleh', $sigs[0]['title']);

        // Default header layout
        $layout = $tpl->getHeaderLayoutOrDefault();
        $this->assertCount(5, $layout);
    }
}
