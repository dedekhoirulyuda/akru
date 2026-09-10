<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\PrintTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrintSettingController extends Controller
{
    public function index(): View
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);
        $templates = PrintTemplate::where('company_id', $companyId)->orderByDesc('is_default')->orderBy('name')->get();

        return view('settings.print-layout', [
            'company'       => $company,
            'templates'     => $templates,
            'paperSizes'    => PrintTemplate::PAPER_SIZES,
            'contSizes'     => PrintTemplate::CONTINUOUS_SIZES,
            'docTypes'      => PrintTemplate::DOCUMENT_TYPES,
            'fontFamilies'  => PrintTemplate::FONT_FAMILIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = session('current_company_id');
        $validated = $this->validateTemplate($request);

        // Jika is_default, reset default lain untuk tipe ini
        if (!empty($validated['is_default'])) {
            PrintTemplate::where('company_id', $companyId)
                ->where('document_type', $validated['document_type'])
                ->update(['is_default' => false]);
        }

        $validated['company_id'] = $companyId;
        $validated['header_layout'] = $this->parseJsonField($request, 'header_layout');
        $validated['signatures'] = $this->parseJsonField($request, 'signatures');

        // Enforce dot matrix constraints
        if (($validated['printer_mode'] ?? 'laser_inkjet') === 'dot_matrix') {
            $validated['show_logo'] = false;
            if (!in_array($validated['font_family'] ?? '', ['Courier New', 'Lucida Console'])) {
                $validated['font_family'] = 'Courier New';
            }
        }

        PrintTemplate::create($validated);

        return redirect()->route('print-layout.index')->with('success', 'Template cetak berhasil disimpan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $companyId = session('current_company_id');
        $template = PrintTemplate::where('company_id', $companyId)->findOrFail($id);
        $validated = $this->validateTemplate($request);

        if (!empty($validated['is_default'])) {
            PrintTemplate::where('company_id', $companyId)
                ->where('document_type', $validated['document_type'])
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $validated['header_layout'] = $this->parseJsonField($request, 'header_layout');
        $validated['signatures'] = $this->parseJsonField($request, 'signatures');

        if (($validated['printer_mode'] ?? 'laser_inkjet') === 'dot_matrix') {
            $validated['show_logo'] = false;
            if (!in_array($validated['font_family'] ?? '', ['Courier New', 'Lucida Console'])) {
                $validated['font_family'] = 'Courier New';
            }
        }

        $template->update($validated);

        return redirect()->route('print-layout.index')->with('success', 'Template cetak berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $companyId = session('current_company_id');
        $template = PrintTemplate::where('company_id', $companyId)->findOrFail($id);
        $template->delete();

        return redirect()->route('print-layout.index')->with('success', 'Template cetak berhasil dihapus.');
    }

    public function duplicate(int $id): RedirectResponse
    {
        $companyId = session('current_company_id');
        $template = PrintTemplate::where('company_id', $companyId)->findOrFail($id);

        $clone = $template->replicate();
        $clone->name = $template->name . ' (Salinan)';
        $clone->is_default = false;
        $clone->save();

        return redirect()->route('print-layout.index')->with('success', 'Template berhasil diduplikat.');
    }

    public function setDefault(int $id): RedirectResponse
    {
        $companyId = session('current_company_id');
        $template = PrintTemplate::where('company_id', $companyId)->findOrFail($id);

        PrintTemplate::where('company_id', $companyId)
            ->where('document_type', $template->document_type)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);

        return redirect()->route('print-layout.index')->with('success', "Template \"{$template->name}\" dijadikan default untuk " . (PrintTemplate::DOCUMENT_TYPES[$template->document_type] ?? $template->document_type) . '.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        $companyId = session('current_company_id');
        $path = $request->file('logo')->store("logos/{$companyId}", 'public');

        // Update company logo_path
        Company::where('id', $companyId)->update(['logo_path' => $path]);

        if ($request->wantsJson()) {
            return response()->json(['path' => $path, 'url' => asset('storage/' . $path)]);
        }

        return redirect()->route('print-layout.index')->with('success', 'Logo berhasil diunggah.');
    }

    public function preview(int $id)
    {
        $companyId = session('current_company_id');
        $template = PrintTemplate::where('company_id', $companyId)->findOrFail($id);
        $company = Company::findOrFail($companyId);

        if ($template->isDotMatrix()) {
            return view('layouts.dot-matrix-preview', compact('template', 'company'));
        }

        return view('layouts.pdf-preview', compact('template', 'company'));
    }

    // --- Private ---

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'name'               => 'required|string|max:100',
            'document_type'      => 'required|string|max:50',
            'is_default'         => 'nullable|boolean',
            'printer_mode'       => 'required|in:laser_inkjet,dot_matrix',
            'paper_size'         => 'required|string|max:20',
            'orientation'        => 'required|in:portrait,landscape',
            'custom_width_mm'    => 'nullable|integer|min:50|max:500',
            'custom_height_mm'   => 'nullable|integer|min:50|max:500',
            'margin_top'         => 'required|integer|min:0|max:50',
            'margin_bottom'      => 'required|integer|min:0|max:50',
            'margin_left'        => 'required|integer|min:0|max:50',
            'margin_right'       => 'required|integer|min:0|max:50',
            'font_family'        => 'required|string|max:50',
            'font_size'          => 'required|integer|min:6|max:20',
            'color_primary'      => 'required|string|max:7',
            'color_accent'       => 'required|string|max:7',
            'color_text'         => 'required|string|max:7',
            'show_logo'          => 'nullable|boolean',
            'logo_position'      => 'nullable|in:left,center,right',
            'logo_size'          => 'nullable|integer|min:20|max:150',
            'show_company_name'  => 'nullable|boolean',
            'show_company_address' => 'nullable|boolean',
            'show_npwp'          => 'nullable|boolean',
            'show_phone_email'   => 'nullable|boolean',
            'footer_text'        => 'nullable|string|max:500',
            'show_page_number'   => 'nullable|boolean',
            'table_header_bg'    => 'nullable|string|max:7',
            'table_header_text'  => 'nullable|string|max:7',
            'table_border_color' => 'nullable|string|max:7',
            'show_gridlines'     => 'nullable|boolean',
            'dm_char_per_line'   => 'nullable|integer|in:80,132,136',
            'dm_lines_per_page'  => 'nullable|integer|in:66,72,84',
            'dm_condensed'       => 'nullable|boolean',
            'dm_separator_char'  => 'nullable|string|max:1',
            'dm_box_drawing'     => 'nullable|boolean',
            'custom_css'         => 'nullable|string|max:5000',
        ]);
    }

    private function parseJsonField(Request $request, string $field): ?array
    {
        $val = $request->input($field);
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : null;
        }
        return is_array($val) ? $val : null;
    }
}
