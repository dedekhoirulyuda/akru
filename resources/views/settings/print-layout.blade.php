@extends('layouts.app')

@section('title', 'Pengaturan Cetak & Layout Dokumen — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pengaturan Cetak & Layout Dokumen</h1>
        <p class="text-sm text-slate-500 mt-0.5">Konfigurasi ukuran kertas, font, warna, kop surat, tanda tangan, dan mode printer (Laser/Inkjet & Dot Matrix)</p>
    </div>
</div>
@endsection

@section('content')
<div x-data="printLayoutEditor()" class="space-y-6">

    {{-- Template Selector Bar --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <label class="text-xs font-bold uppercase text-slate-500">Template Aktif:</label>
                <select x-model="selectedTemplateId" @change="loadTemplate()" class="rounded-lg border-slate-300 text-sm font-medium px-3 py-2 min-w-[240px]">
                    <option value="new">+ Buat Template Baru</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }} {{ $tpl->is_default ? '⭐' : '' }} — {{ $docTypes[$tpl->document_type] ?? $tpl->document_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <template x-if="selectedTemplateId !== 'new'">
                    <div class="flex items-center gap-2">
                        <form :action="'/settings/print-layout/' + selectedTemplateId + '/set-default'" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                                ⭐ Jadikan Default
                            </button>
                        </form>
                        <form :action="'/settings/print-layout/' + selectedTemplateId + '/duplicate'" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition">
                                📋 Duplikat
                            </button>
                        </form>
                        <form :action="'/settings/print-layout/' + selectedTemplateId" method="POST" class="inline" onsubmit="return confirm('Hapus template ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition">
                                🗑 Hapus
                            </button>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Main Editor: Settings Panel + Live Preview --}}
    <form :action="selectedTemplateId === 'new' ? '{{ route('print-layout.store') }}' : '/settings/print-layout/' + selectedTemplateId"
          method="POST" id="templateForm">
        @csrf
        <template x-if="selectedTemplateId !== 'new'">
            <input type="hidden" name="_method" value="PUT">
        </template>
        <input type="hidden" name="header_layout" :value="JSON.stringify(form.headerLayout)">
        <input type="hidden" name="signatures" :value="JSON.stringify(form.signatures)">

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- LEFT PANEL: Settings --}}
            <div class="xl:col-span-5 space-y-4">

                {{-- Nama & Tipe Template --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">1</span>
                        Identitas Template
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Nama Template</label>
                            <input type="text" name="name" x-model="form.name" required placeholder="Faktur Standard A4" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipe Dokumen</label>
                            <select name="document_type" x-model="form.document_type" class="w-full text-sm rounded-lg border-slate-300">
                                @foreach($docTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_default" x-model="form.is_default" value="1" class="rounded border-slate-300 text-blue-600">
                        <span>Jadikan default untuk tipe dokumen ini</span>
                    </label>
                </div>

                {{-- Mode Printer & Kertas --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">2</span>
                        Mode Printer & Kertas
                    </h3>

                    {{-- Printer Mode Toggle --}}
                    <div class="flex gap-2">
                        <button type="button" @click="form.printer_mode = 'laser_inkjet'" :class="form.printer_mode === 'laser_inkjet' ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="flex-1 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"/></svg>
                            Laser / Inkjet
                        </button>
                        <button type="button" @click="form.printer_mode = 'dot_matrix'; form.font_family = 'Courier New'; form.show_logo = false;" :class="form.printer_mode === 'dot_matrix' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="flex-1 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Dot Matrix
                        </button>
                    </div>
                    <input type="hidden" name="printer_mode" :value="form.printer_mode">

                    {{-- Paper Size --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Ukuran Kertas</label>
                            <select name="paper_size" x-model="form.paper_size" class="w-full text-sm rounded-lg border-slate-300">
                                <optgroup label="Standar">
                                    @foreach($paperSizes as $key => $sz)
                                        <option value="{{ $key }}">{{ $sz['name'] }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Continuous Form (Dot Matrix)">
                                    @foreach($contSizes as $key => $sz)
                                        <option value="{{ $key }}">{{ $sz['name'] }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Lainnya">
                                    <option value="custom">Ukuran Kustom (mm)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Orientasi</label>
                            <select name="orientation" x-model="form.orientation" class="w-full text-sm rounded-lg border-slate-300">
                                <option value="portrait">Portrait (Tegak)</option>
                                <option value="landscape">Landscape (Miring)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Custom Size --}}
                    <div x-show="form.paper_size === 'custom'" x-transition class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Lebar (mm)</label>
                            <input type="number" name="custom_width_mm" x-model.number="form.custom_width_mm" min="50" max="500" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tinggi (mm)</label>
                            <input type="number" name="custom_height_mm" x-model.number="form.custom_height_mm" min="50" max="500" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                    </div>

                    {{-- Margins --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Margin (mm)</label>
                        <div class="grid grid-cols-4 gap-2">
                            <div><label class="text-[10px] text-slate-400">Atas</label><input type="number" name="margin_top" x-model.number="form.margin_top" min="0" max="50" class="w-full text-xs rounded-lg border-slate-300 text-center"></div>
                            <div><label class="text-[10px] text-slate-400">Bawah</label><input type="number" name="margin_bottom" x-model.number="form.margin_bottom" min="0" max="50" class="w-full text-xs rounded-lg border-slate-300 text-center"></div>
                            <div><label class="text-[10px] text-slate-400">Kiri</label><input type="number" name="margin_left" x-model.number="form.margin_left" min="0" max="50" class="w-full text-xs rounded-lg border-slate-300 text-center"></div>
                            <div><label class="text-[10px] text-slate-400">Kanan</label><input type="number" name="margin_right" x-model.number="form.margin_right" min="0" max="50" class="w-full text-xs rounded-lg border-slate-300 text-center"></div>
                        </div>
                    </div>
                </div>

                {{-- Tipografi & Warna --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-violet-100 text-violet-600 flex items-center justify-center text-xs font-bold">3</span>
                        Tipografi & Warna
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Jenis Font</label>
                            <select name="font_family" x-model="form.font_family" class="w-full text-sm rounded-lg border-slate-300">
                                @foreach($fontFamilies as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Ukuran Font (pt)</label>
                            <input type="number" name="font_size" x-model.number="form.font_size" min="6" max="20" class="w-full text-sm rounded-lg border-slate-300">
                        </div>
                    </div>

                    <div x-show="form.printer_mode === 'laser_inkjet'" x-transition class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Warna Header</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_primary" x-model="form.color_primary" class="w-8 h-8 rounded cursor-pointer border-0 p-0">
                                <input type="text" x-model="form.color_primary" maxlength="7" class="flex-1 text-xs font-mono rounded-lg border-slate-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Warna Aksen</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_accent" x-model="form.color_accent" class="w-8 h-8 rounded cursor-pointer border-0 p-0">
                                <input type="text" x-model="form.color_accent" maxlength="7" class="flex-1 text-xs font-mono rounded-lg border-slate-300">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Warna Teks</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_text" x-model="form.color_text" class="w-8 h-8 rounded cursor-pointer border-0 p-0">
                                <input type="text" x-model="form.color_text" maxlength="7" class="flex-1 text-xs font-mono rounded-lg border-slate-300">
                            </div>
                        </div>
                    </div>

                    {{-- Table Styling --}}
                    <div x-show="form.printer_mode === 'laser_inkjet'" x-transition>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Gaya Tabel Data</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="text-[10px] text-slate-400">BG Header Tabel</label>
                                <div class="flex items-center gap-1">
                                    <input type="color" name="table_header_bg" x-model="form.table_header_bg" class="w-6 h-6 rounded cursor-pointer border-0 p-0">
                                    <input type="text" x-model="form.table_header_bg" maxlength="7" class="flex-1 text-[10px] font-mono rounded border-slate-300 px-1 py-0.5">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400">Teks Header</label>
                                <div class="flex items-center gap-1">
                                    <input type="color" name="table_header_text" x-model="form.table_header_text" class="w-6 h-6 rounded cursor-pointer border-0 p-0">
                                    <input type="text" x-model="form.table_header_text" maxlength="7" class="flex-1 text-[10px] font-mono rounded border-slate-300 px-1 py-0.5">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400">Border Tabel</label>
                                <div class="flex items-center gap-1">
                                    <input type="color" name="table_border_color" x-model="form.table_border_color" class="w-6 h-6 rounded cursor-pointer border-0 p-0">
                                    <input type="text" x-model="form.table_border_color" maxlength="7" class="flex-1 text-[10px] font-mono rounded border-slate-300 px-1 py-0.5">
                                </div>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-slate-600 mt-2">
                            <input type="checkbox" name="show_gridlines" x-model="form.show_gridlines" value="1" class="rounded border-slate-300 text-blue-600">
                            Tampilkan garis tabel (gridlines)
                        </label>
                    </div>
                </div>

                {{-- Kop Surat / Header (drag & drop) --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold">4</span>
                        Kop Surat / Header
                        <span class="text-[10px] text-slate-400 font-normal ml-1">— Drag untuk mengatur urutan</span>
                    </h3>

                    {{-- Draggable Header Elements --}}
                    <div class="space-y-1.5" id="headerSortable">
                        <template x-for="(item, idx) in form.headerLayout" :key="item.type">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50/80 cursor-grab active:cursor-grabbing hover:border-blue-300 transition"
                                 draggable="true"
                                 @dragstart="dragStartIdx = idx"
                                 @dragover.prevent="dragOverIdx = idx"
                                 @drop="reorderHeader()"
                                 @dragend="dragStartIdx = null; dragOverIdx = null">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                <span class="text-xs font-medium text-slate-700 flex-1" x-text="headerLabels[item.type]"></span>
                                <label class="flex items-center gap-1">
                                    <input type="checkbox" x-model="item.visible" class="rounded border-slate-300 text-blue-600 w-3.5 h-3.5">
                                    <span class="text-[10px] text-slate-400">Tampil</span>
                                </label>
                            </div>
                        </template>
                    </div>

                    {{-- Logo Settings --}}
                    <div x-show="form.printer_mode === 'laser_inkjet'" x-transition class="border-t border-slate-100 pt-3 space-y-3">
                        <label class="flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" name="show_logo" x-model="form.show_logo" value="1" class="rounded border-slate-300 text-blue-600">
                            Tampilkan Logo Perusahaan
                        </label>
                        <div x-show="form.show_logo" x-transition class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] uppercase text-slate-400 mb-1">Posisi Logo</label>
                                <select name="logo_position" x-model="form.logo_position" class="w-full text-xs rounded-lg border-slate-300">
                                    <option value="left">Kiri</option>
                                    <option value="center">Tengah</option>
                                    <option value="right">Kanan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase text-slate-400 mb-1">Ukuran Logo (px)</label>
                                <input type="range" name="logo_size" x-model.number="form.logo_size" min="20" max="150" class="w-full">
                                <span class="text-[10px] text-slate-500" x-text="form.logo_size + 'px'"></span>
                            </div>
                        </div>
                        {{-- Logo Upload --}}
                        <div x-show="form.show_logo" x-transition>
                            <form action="{{ route('print-layout.upload-logo') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                <input type="file" name="logo" accept="image/*" class="text-xs file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-500 transition">Upload</button>
                            </form>
                            @if($company->logo_path)
                                <div class="mt-2 flex items-center gap-2">
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo" class="h-10 rounded border border-slate-200">
                                    <span class="text-[10px] text-slate-400">Logo aktif</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tanda Tangan --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold">5</span>
                        Blok Tanda Tangan
                    </h3>
                    <template x-for="(sig, i) in form.signatures" :key="i">
                        <div class="flex items-end gap-2 pb-2 border-b border-slate-100 last:border-0">
                            <div class="flex-1">
                                <label class="text-[10px] uppercase text-slate-400" x-text="'Jabatan #' + (i+1)"></label>
                                <input type="text" x-model="sig.title" placeholder="Dibuat Oleh" class="w-full text-xs rounded-lg border-slate-300">
                            </div>
                            <div class="flex-1">
                                <label class="text-[10px] uppercase text-slate-400">Nama</label>
                                <input type="text" x-model="sig.name" placeholder="(kosongkan = diisi manual)" class="w-full text-xs rounded-lg border-slate-300">
                            </div>
                            <button type="button" @click="form.signatures.splice(i, 1)" class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="form.signatures.push({title: '', name: '', position: 'left'})" class="w-full py-2 rounded-lg border-2 border-dashed border-slate-300 text-xs font-semibold text-slate-500 hover:border-blue-400 hover:text-blue-600 transition">
                        + Tambah Penanda Tangan
                    </button>
                </div>

                {{-- Dot Matrix Specific --}}
                <div x-show="form.printer_mode === 'dot_matrix'" x-transition class="bg-amber-50 rounded-xl shadow-sm border border-amber-200 p-5 space-y-4">
                    <h3 class="text-sm font-bold text-amber-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-amber-200 text-amber-700 flex items-center justify-center text-xs font-bold">⚙</span>
                        Pengaturan Dot Matrix
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-amber-700 mb-1">Karakter/Baris</label>
                            <select name="dm_char_per_line" x-model.number="form.dm_char_per_line" class="w-full text-sm rounded-lg border-amber-300 bg-white">
                                <option value="80">80 Karakter (Standar 8.5" / A4 Portrait)</option>
                                <option value="96">96 Karakter (A4 / F4 Medium)</option>
                                <option value="120">120 Karakter (A4 Landscape / Continuous 11")</option>
                                <option value="132">132 Karakter (Compressed / Kertas Lebar 14.8")</option>
                                <option value="136">136 Karakter (Ultra-Compressed)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-amber-700 mb-1">Baris/Halaman</label>
                            <select name="dm_lines_per_page" x-model.number="form.dm_lines_per_page" class="w-full text-sm rounded-lg border-amber-300 bg-white">
                                <option value="66">66 (Standard 11")</option>
                                <option value="72">72</option>
                                <option value="84">84</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-amber-700 mb-1">Karakter Separator</label>
                            <select name="dm_separator_char" x-model="form.dm_separator_char" class="w-full text-sm rounded-lg border-amber-300 bg-white">
                                <option value="-">- (Dash)</option>
                                <option value="=">=  (Double Line)</option>
                                <option value="*">* (Asterisk)</option>
                                <option value="#"># (Hash)</option>
                            </select>
                        </div>
                        <div class="flex flex-col justify-end gap-2">
                            <label class="flex items-center gap-2 text-xs text-amber-800">
                                <input type="checkbox" name="dm_condensed" x-model="form.dm_condensed" value="1" class="rounded border-amber-400 text-amber-600">
                                Mode Condensed
                            </label>
                            <label class="flex items-center gap-2 text-xs text-amber-800">
                                <input type="checkbox" name="dm_box_drawing" x-model="form.dm_box_drawing" value="1" class="rounded border-amber-400 text-amber-600">
                                Karakter Box-drawing ┌─┐
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-3">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold">6</span>
                        Footer Dokumen
                    </h3>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Teks Footer Kustom</label>
                        <input type="text" name="footer_text" x-model="form.footer_text" placeholder="Dicetak dari AKRU — akru.id" class="w-full text-sm rounded-lg border-slate-300">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" name="show_page_number" x-model="form.show_page_number" value="1" class="rounded border-slate-300 text-blue-600">
                        Tampilkan nomor halaman
                    </label>
                </div>

                {{-- Save Button --}}
                <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold text-sm hover:bg-blue-500 shadow-lg shadow-blue-200 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="selectedTemplateId === 'new' ? 'Simpan Template Baru' : 'Perbarui Template'"></span>
                </button>
            </div>

            {{-- RIGHT PANEL: Live Preview --}}
            <div class="xl:col-span-7">
                <div class="sticky top-4">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="text-xs font-bold uppercase text-slate-500 tracking-wider">Live Preview</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200" 
                                      x-text="form.printer_mode === 'laser_inkjet' ? 'Laser / Inkjet' : 'Dot Matrix Continuous'"></span>
                                <span class="text-[10px] text-slate-400 font-mono hidden sm:inline" 
                                      x-text="`${form.paper_size} (${paperDims[0]}×${paperDims[1]}mm) • ${form.orientation}`"></span>
                            </div>
                            <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg">
                                <button type="button" @click="previewZoom = 0.5" :class="previewZoom === 0.5 ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-1 rounded text-[10px] transition-all cursor-pointer">50%</button>
                                <button type="button" @click="previewZoom = 0.65" :class="previewZoom === 0.65 ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-1 rounded text-[10px] transition-all cursor-pointer">65%</button>
                                <button type="button" @click="previewZoom = 0.8" :class="previewZoom === 0.8 ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-1 rounded text-[10px] transition-all cursor-pointer">80%</button>
                                <button type="button" @click="previewZoom = 1.0" :class="previewZoom === 1.0 ? 'bg-white shadow-xs text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-2 py-1 rounded text-[10px] transition-all cursor-pointer">100%</button>
                            </div>
                        </div>

                        {{-- Preview Container --}}
                        <div class="flex justify-center items-start overflow-auto bg-slate-900/5 rounded-xl p-4 sm:p-6 border border-slate-200/80 shadow-inner" style="min-height: 520px; max-height: 80vh;">
                            {{-- Scaled Viewport Wrapper: bounding box strictly matches scaled sheet --}}
                            <div class="relative transition-all duration-300 mx-auto flex-shrink-0 shadow-2xl"
                                 :style="`width: ${Math.round(paperW * previewZoom)}px; height: ${Math.round(paperH * previewZoom)}px;`">
                                
                                {{-- Laser/Inkjet Preview --}}
                                <template x-if="form.printer_mode === 'laser_inkjet'">
                                    <div class="bg-white rounded-xs origin-top-left absolute top-0 left-0 transition-transform duration-300 border border-slate-200"
                                         :style="`width: ${paperW}px; min-height: ${paperH}px; transform: scale(${previewZoom}); font-family: '${form.font_family}', sans-serif; font-size: ${form.font_size}pt; color: ${form.color_text}; padding: ${form.margin_top * 1.5}px ${form.margin_right * 1.5}px ${form.margin_bottom * 1.5}px ${form.margin_left * 1.5}px;`">
                                        
                                        {{-- Header --}}
                                        <div :style="`border-bottom: 2px solid ${form.color_primary}; padding-bottom: 10px; margin-bottom: 14px;`">
                                            <template x-for="item in form.headerLayout.filter(h => h.visible).sort((a,b) => a.order - b.order)" :key="item.type">
                                                <div>
                                                    <template x-if="item.type === 'logo' && form.show_logo">
                                                        <div :style="`text-align: ${form.logo_position};`">
                                                            @if($company->logo_path)
                                                                <img src="{{ asset('storage/' . $company->logo_path) }}" :style="`height: ${form.logo_size}px;`" alt="Logo">
                                                            @else
                                                                <div :style="`display: inline-block; width: ${form.logo_size * 2}px; height: ${form.logo_size}px; background: ${form.color_accent}20; border: 2px dashed ${form.color_accent}; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: ${form.color_accent};`">LOGO</div>
                                                            @endif
                                                        </div>
                                                    </template>
                                                    <template x-if="item.type === 'company_name' && form.show_company_name">
                                                        <div :style="`font-size: ${form.font_size + 4}pt; font-weight: 700; color: ${form.color_primary};`">{{ $company->name }}</div>
                                                    </template>
                                                    <template x-if="item.type === 'address' && form.show_company_address">
                                                        <div style="font-size: 8pt; opacity: 0.7;">{{ $company->address ?? 'Jl. Contoh Alamat No.123, Jakarta' }}</div>
                                                    </template>
                                                    <template x-if="item.type === 'npwp' && form.show_npwp">
                                                        <div style="font-size: 8pt; opacity: 0.7;">NPWP: {{ $company->tax_id ?? '00.000.000.0-000.000' }}</div>
                                                    </template>
                                                    <template x-if="item.type === 'phone_email' && form.show_phone_email">
                                                        <div style="font-size: 8pt; opacity: 0.7;">Telp: {{ $company->phone ?? '021-0000000' }} | Email: {{ $company->email ?? 'info@perusahaan.com' }}</div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Document Title --}}
                                        <div :style="`text-align: center; font-size: ${form.font_size + 2}pt; font-weight: 700; color: ${form.color_primary}; margin-bottom: 12px;`">
                                            FAKTUR PENJUALAN (CONTOH)
                                        </div>

                                        {{-- Sample Table --}}
                                        <table :style="`width: 100%; border-collapse: collapse; font-size: ${form.font_size - 1}pt;`">
                                            <thead>
                                                <tr :style="`background: ${form.table_header_bg}; color: ${form.table_header_text};`">
                                                    <th :style="`padding: 5px 6px; text-align: left; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'}; font-size: ${form.font_size - 2}pt; font-weight: 700;`">No</th>
                                                    <th :style="`padding: 5px 6px; text-align: left; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'}; font-size: ${form.font_size - 2}pt; font-weight: 700;`">Nama Barang / Jasa</th>
                                                    <th :style="`padding: 5px 6px; text-align: right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'}; font-size: ${form.font_size - 2}pt; font-weight: 700;`">Qty</th>
                                                    <th :style="`padding: 5px 6px; text-align: right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'}; font-size: ${form.font_size - 2}pt; font-weight: 700;`">Harga</th>
                                                    <th :style="`padding: 5px 6px; text-align: right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'}; font-size: ${form.font_size - 2}pt; font-weight: 700;`">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">1</td><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">Buku Tulis A5 Folio</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">10</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">15.000</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">150.000</td></tr>
                                                <tr><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">2</td><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">Pensil 2B Faber Castell</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">24</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">5.500</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">132.000</td></tr>
                                                <tr><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">3</td><td :style="`padding: 4px 6px; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">Penghapus Putih Stadler</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">12</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">3.000</td><td :style="`padding: 4px 6px; text-align:right; border: ${form.show_gridlines ? '1px solid ' + form.table_border_color : 'none'};`">36.000</td></tr>
                                            </tbody>
                                            <tfoot>
                                                <tr :style="`font-weight: 700; border-top: 2px solid ${form.color_primary};`">
                                                    <td colspan="4" :style="`padding: 5px 6px; text-align: right;`">TOTAL:</td>
                                                    <td :style="`padding: 5px 6px; text-align: right;`">Rp 318.000</td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        {{-- Signatures Preview --}}
                                        <div :style="`display: flex; justify-content: space-between; margin-top: 40px; gap: 16px;`">
                                            <template x-for="(sig, i) in form.signatures" :key="i">
                                                <div style="text-align: center; min-width: 100px;">
                                                    <div :style="`font-size: ${form.font_size - 2}pt; color: ${form.color_text}; opacity: 0.7;`" x-text="sig.title || 'Jabatan'"></div>
                                                    <div :style="`border-top: 1px solid ${form.color_primary}; margin-top: 50px; padding-top: 4px; font-size: ${form.font_size - 1}pt;`" x-text="sig.name || '( ........................ )'"></div>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Footer Preview --}}
                                        <div :style="`margin-top: 30px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 7pt; color: #94a3b8; display: flex; justify-content: space-between;`">
                                            <span x-text="form.footer_text || 'Dicetak dari AKRU'"></span>
                                            <span x-show="form.show_page_number">Halaman 1</span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Dot Matrix Preview --}}
                                <template x-if="form.printer_mode === 'dot_matrix'">
                                    <div class="bg-[#fffff5] rounded-xs origin-top-left absolute top-0 left-0 transition-transform duration-300 border border-amber-300 shadow-md box-border overflow-hidden"
                                         :style="`width: ${paperW}px; min-height: ${paperH}px; transform: scale(${previewZoom}); padding: ${form.margin_top * 1.5}px ${form.margin_right * 1.5}px ${form.margin_bottom * 1.5}px ${form.margin_left * 1.5}px;`">
                                        <div class="w-full overflow-hidden"
                                             :style="`font-family: 'Courier New', Courier, monospace; font-size: ${dmFontSizePx}px; line-height: 1.25; color: #000; width: 100%;`">
                                            <template x-if="form.dm_box_drawing">
                                                <pre class="m-0 p-0 font-mono select-none block w-full overflow-visible" style="font-family: inherit; font-size: inherit; line-height: inherit; letter-spacing: 0; white-space: pre;" x-text="dotMatrixPreview"></pre>
                                            </template>
                                            <template x-if="!form.dm_box_drawing">
                                                <pre class="m-0 p-0 font-mono select-none block w-full overflow-visible" style="font-family: inherit; font-size: inherit; line-height: inherit; letter-spacing: 0; white-space: pre;" x-text="dotMatrixPreviewSimple"></pre>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('styles')
<style>
    [draggable="true"] { user-select: none; }
    [draggable="true"]:active { opacity: 0.5; }
</style>
@endpush

<script>
function printLayoutEditor() {
    // Load existing templates as JSON
    const templates = @json($templates->keyBy('id'));
    const companyName = @json($company->name);
    const companyAddress = @json($company->address ?? 'Jl. Contoh Alamat No.123');
    const companyTax = @json($company->tax_id ?? '00.000.000.0-000.000');
    const companyPhone = @json($company->phone ?? '021-0000000');
    const companyEmail = @json($company->email ?? 'info@perusahaan.com');

    const defaultForm = {
        name: '', document_type: 'general', is_default: false,
        printer_mode: 'laser_inkjet', paper_size: 'A4', orientation: 'portrait',
        custom_width_mm: 210, custom_height_mm: 297,
        margin_top: 10, margin_bottom: 10, margin_left: 12, margin_right: 12,
        font_family: 'Inter', font_size: 10,
        color_primary: '#1e293b', color_accent: '#2563eb', color_text: '#1e293b',
        show_logo: true, logo_position: 'left', logo_size: 60,
        show_company_name: true, show_company_address: true, show_npwp: true, show_phone_email: true,
        headerLayout: [
            { type: 'logo', order: 1, visible: true },
            { type: 'company_name', order: 2, visible: true },
            { type: 'address', order: 3, visible: true },
            { type: 'npwp', order: 4, visible: true },
            { type: 'phone_email', order: 5, visible: true },
        ],
        footer_text: 'Dicetak dari AKRU', show_page_number: true,
        signatures: [
            { title: 'Dibuat Oleh', name: '', position: 'left' },
            { title: 'Diperiksa Oleh', name: '', position: 'center' },
            { title: 'Disetujui Oleh', name: '', position: 'right' },
        ],
        table_header_bg: '#f1f5f9', table_header_text: '#1e293b',
        table_border_color: '#e2e8f0', show_gridlines: true,
        dm_char_per_line: 80, dm_lines_per_page: 66,
        dm_condensed: false, dm_separator_char: '-', dm_box_drawing: true,
        custom_css: '',
    };

    const paperSizesMap = {
        'A4': [210, 297], 'A5': [148, 210], 'Letter': [215.9, 279.4],
        'Legal': [215.9, 355.6], 'F4': [215, 330],
        'cont_9.5x11': [241.3, 279.4], 'cont_9.5x5.5': [241.3, 139.7],
        'cont_9.5x7': [241.3, 177.8], 'cont_14.875x11': [377.8, 279.4],
    };

    return {
        selectedTemplateId: '{{ $templates->where("is_default", true)->first()?->id ?? "new" }}',
        form: JSON.parse(JSON.stringify(defaultForm)),
        previewZoom: 0.65,
        dragStartIdx: null,
        dragOverIdx: null,

        headerLabels: {
            'logo': '🖼️ Logo Perusahaan',
            'company_name': '🏢 Nama Perusahaan',
            'address': '📍 Alamat Domisili',
            'npwp': '📋 NPWP / NIK',
            'phone_email': '📞 Telepon & Email',
        },

        get paperDims() {
            if (this.form.paper_size === 'custom') return [this.form.custom_width_mm || 210, this.form.custom_height_mm || 297];
            return paperSizesMap[this.form.paper_size] || [210, 297];
        },
        get paperW() {
            let [w, h] = this.paperDims;
            if (this.form.orientation === 'landscape') [w, h] = [h, w];
            return w * 2.5; // scale mm to px for preview
        },
        get paperH() {
            let [w, h] = this.paperDims;
            if (this.form.orientation === 'landscape') [w, h] = [h, w];
            return h * 2.5;
        },

        get effectiveCpl() {
            let cpl = parseInt(this.form.dm_char_per_line, 10);
            if (!cpl || isNaN(cpl)) {
                cpl = this.form.orientation === 'landscape' ? 120 : 80;
            }
            return cpl;
        },

        get dmPrintableW() {
            const marginL = (this.form.margin_left || 0) * 1.5;
            const marginR = (this.form.margin_right || 0) * 1.5;
            return Math.max(120, this.paperW - (marginL + marginR));
        },

        get dmFontSizePx() {
            const printableW = this.dmPrintableW;
            const cpl = this.effectiveCpl;
            // Glyph advance width ratio for Courier New monospace is 0.6001.
            // Using 0.602 guarantees that all lines stay safely inside the printable width without truncation or overflow.
            const fontSize = printableW / (cpl * 0.602);
            return Math.max(5, fontSize);
        },

        get dotMatrixPreview() {
            const cpl = this.effectiveCpl;
            const sep = this.form.dm_separator_char || '-';
            const ln = (c, len) => c.repeat(Math.max(0, Math.floor(len)));
            const pad = (s, len, right) => {
                s = String(s || '');
                if (s.length > len) s = s.slice(0, len);
                return right ? s.padStart(len) : s.padEnd(len);
            };

            let out = '';
            out += '┌' + ln('─', cpl - 2) + '┐\n';
            out += '│' + pad(companyName, cpl - 2).padStart(Math.floor((cpl - 2 + companyName.length) / 2)).padEnd(cpl - 2) + '│\n';
            out += '│' + pad(companyAddress, cpl - 2).padStart(Math.floor((cpl - 2 + companyAddress.length) / 2)).padEnd(cpl - 2) + '│\n';
            out += '│' + pad('NPWP: ' + companyTax, cpl - 2).padStart(Math.floor((cpl - 2 + ('NPWP: ' + companyTax).length) / 2)).padEnd(cpl - 2) + '│\n';
            out += '│' + ln(' ', cpl - 2) + '│\n';
            const title = 'FAKTUR PENJUALAN (CONTOH)';
            out += '│' + pad(title, cpl - 2).padStart(Math.floor((cpl - 2 + title.length) / 2)).padEnd(cpl - 2) + '│\n';
            out += '│' + pad('No: INV-2026-0001', cpl - 2).padStart(Math.floor((cpl - 2 + 'No: INV-2026-0001'.length) / 2)).padEnd(cpl - 2) + '│\n';
            out += '╞' + ln('═', cpl - 2) + '╡\n';

            // Table header: No, Qty, Harga, Total fixed width, Nama Barang takes all available middle width
            const c1 = 4, c3 = Math.max(6, Math.floor(cpl * 0.08)), c4 = Math.max(12, Math.floor(cpl * 0.16)), c5 = Math.max(13, Math.floor(cpl * 0.17));
            const c2 = Math.max(14, cpl - 2 - (c1 + c3 + c4 + c5 + 4)); // 4 inner vertical dividers
            out += '│' + pad('No', c1) + '│' + pad('Nama Barang / Jasa', c2) + '│' + pad('Qty', c3, true) + '│' + pad('Harga', c4, true) + '│' + pad('Total', c5, true) + '│\n';
            out += '├' + ln('─', c1) + '┼' + ln('─', c2) + '┼' + ln('─', c3) + '┼' + ln('─', c4) + '┼' + ln('─', c5) + '┤\n';

            const rows = [
                ['1', 'Buku Tulis A5 Folio Bergaris', '10', '15.000', '150.000'],
                ['2', 'Pensil 2B Faber Castell Original', '24', '5.500', '132.000'],
                ['3', 'Penghapus Putih Stadler Box Besar', '12', '3.000', '36.000']
            ];
            rows.forEach(r => {
                out += '│' + pad(r[0], c1) + '│' + pad(r[1], c2) + '│' + pad(r[2], c3, true) + '│' + pad(r[3], c4, true) + '│' + pad(r[4], c5, true) + '│\n';
            });
            out += '╞' + ln('═', c1) + '╧' + ln('═', c2) + '╧' + ln('═', c3) + '╧' + ln('═', c4) + '╧' + ln('═', c5) + '╡\n';
            
            const totalText = 'TOTAL: Rp 318.000';
            out += '│' + pad('', Math.max(0, cpl - 2 - totalText.length - 2)) + totalText + '  │\n';
            out += '│' + ln(' ', cpl - 2) + '│\n';

            // Signatures bar
            if (this.form.signatures && this.form.signatures.length > 0) {
                const numSigs = this.form.signatures.length;
                const colW = Math.floor((cpl - 2) / numSigs);
                let sigTitleRow = '│';
                let sigLineRow = '│';
                this.form.signatures.forEach((sig) => {
                    const title = sig.title || 'Jabatan';
                    const name = sig.name || '( .................... )';
                    sigTitleRow += pad(title, colW);
                    sigLineRow += pad(name, colW);
                });
                sigTitleRow = sigTitleRow.padEnd(cpl - 1) + '│\n';
                sigLineRow = sigLineRow.padEnd(cpl - 1) + '│\n';
                
                out += sigTitleRow;
                out += '│' + ln(' ', cpl - 2) + '│\n';
                out += '│' + ln(' ', cpl - 2) + '│\n';
                out += sigLineRow;
            }

            out += '└' + ln('─', cpl - 2) + '┘\n';
            return out;
        },

        get dotMatrixPreviewSimple() {
            const cpl = this.effectiveCpl;
            const sep = this.form.dm_separator_char || '-';
            const ln = (c, len) => c.repeat(Math.max(0, Math.floor(len)));
            const pad = (s, len, right) => {
                s = String(s || '');
                if (s.length > len) s = s.slice(0, len);
                return right ? s.padStart(len) : s.padEnd(len);
            };

            let out = '';
            out += ln(sep, cpl) + '\n';
            out += pad(companyName, cpl).padStart(Math.floor((cpl + companyName.length) / 2)).padEnd(cpl) + '\n';
            out += pad(companyAddress, cpl).padStart(Math.floor((cpl + companyAddress.length) / 2)).padEnd(cpl) + '\n';
            out += pad('NPWP: ' + companyTax, cpl).padStart(Math.floor((cpl + ('NPWP: ' + companyTax).length) / 2)).padEnd(cpl) + '\n';
            out += '\n';
            const title = 'FAKTUR PENJUALAN (CONTOH)';
            out += pad(title, cpl).padStart(Math.floor((cpl + title.length) / 2)).padEnd(cpl) + '\n';
            out += ln('=', cpl) + '\n';

            const c1 = 4, c3 = Math.max(6, Math.floor(cpl * 0.08)), c4 = Math.max(12, Math.floor(cpl * 0.16)), c5 = Math.max(13, Math.floor(cpl * 0.17));
            const c2 = Math.max(14, cpl - (c1 + c3 + c4 + c5 + 4));
            out += pad('No', c1) + ' ' + pad('Nama Barang / Jasa', c2) + ' ' + pad('Qty', c3, true) + ' ' + pad('Harga', c4, true) + ' ' + pad('Total', c5, true) + '\n';
            out += ln(sep, cpl) + '\n';

            const rows = [
                ['1', 'Buku Tulis A5 Folio Bergaris', '10', '15.000', '150.000'],
                ['2', 'Pensil 2B Faber Castell Original', '24', '5.500', '132.000'],
                ['3', 'Penghapus Putih Stadler Box Besar', '12', '3.000', '36.000']
            ];
            rows.forEach(r => {
                out += pad(r[0], c1) + ' ' + pad(r[1], c2) + ' ' + pad(r[2], c3, true) + ' ' + pad(r[3], c4, true) + ' ' + pad(r[4], c5, true) + '\n';
            });
            out += ln('=', cpl) + '\n';
            out += pad('TOTAL: Rp 318.000', cpl, true) + '\n';
            out += '\n';

            if (this.form.signatures && this.form.signatures.length > 0) {
                const numSigs = this.form.signatures.length;
                const colW = Math.floor(cpl / numSigs);
                let sigTitleRow = '';
                let sigLineRow = '';
                this.form.signatures.forEach((sig) => {
                    sigTitleRow += pad(sig.title || 'Jabatan', colW);
                    sigLineRow += pad(sig.name || '( .................... )', colW);
                });
                out += sigTitleRow + '\n\n' + sigLineRow + '\n';
            }

            out += ln(sep, cpl) + '\n';
            return out;
        },

        loadTemplate() {
            if (this.selectedTemplateId === 'new') {
                this.form = JSON.parse(JSON.stringify(defaultForm));
                return;
            }
            const tpl = templates[this.selectedTemplateId];
            if (!tpl) return;

            // Map from DB to form
            Object.keys(defaultForm).forEach(k => {
                if (k === 'headerLayout') {
                    this.form.headerLayout = tpl.header_layout || defaultForm.headerLayout;
                } else if (k === 'signatures') {
                    this.form.signatures = tpl.signatures || defaultForm.signatures;
                } else if (tpl[k] !== undefined && tpl[k] !== null) {
                    this.form[k] = tpl[k];
                }
            });
        },

        reorderHeader() {
            if (this.dragStartIdx === null || this.dragOverIdx === null) return;
            const moved = this.form.headerLayout.splice(this.dragStartIdx, 1)[0];
            this.form.headerLayout.splice(this.dragOverIdx, 0, moved);
            // Re-index orders
            this.form.headerLayout.forEach((h, i) => h.order = i + 1);
        },

        init() {
            this.loadTemplate();
        }
    };
}
</script>
@endsection
