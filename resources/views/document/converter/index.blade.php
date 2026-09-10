@extends('layouts.app')

@section('title', 'Konverter Rekening Koran Bank (PDF ke Excel) — AKRU')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-slate-900">Konverter Rekening Koran Bank</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                PDF to Excel Multi-Bank
            </span>
        </div>
        <p class="text-sm text-slate-500 mt-0.5">Konversi otomatis rekening koran PDF dari seluruh bank di Indonesia (BCA, Mandiri, BNI, BRI, BSI, CIMB, Permata, dll.) ke format Excel & CSV siap rekonsiliasi</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors shadow-xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Buka Rekonsiliasi Bank
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="bankConverter()" x-init="initData()" class="space-y-6">

    <!-- Bank Selection & Conversion Options Card -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Rekening Bank Entitas (Opsional)</label>
                    <select x-model="selectedBankId" @change="onBankSelectChange()" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Konversi Bebas (Tanpa Terhubung ke Akun) --</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" data-bank="{{ $ba->bank_name }}" data-account="{{ $ba->account_number }}">
                                {{ $ba->name }} — {{ $ba->account_number }} ({{ $ba->bank_name }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Pilih akun buku bank untuk langsung menghubungkan hasil konversi ke neraca rekonsiliasi.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Profil Format Bank</label>
                    <select x-model="bankProfile" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="auto">✨ Deteksi Otomatis (Semua Format Bank Indonesia)</option>
                        <option value="bca">🔵 Bank Central Asia (BCA / KlikBCA Bisnis)</option>
                        <option value="mandiri">🟡 Bank Mandiri (Kopra / Livin' Bisnis)</option>
                        <option value="bni">🟠 Bank Negara Indonesia (BNI Direct / BNI)</option>
                        <option value="bri">🔵 Bank Rakyat Indonesia (BRI / CMS BRI)</option>
                        <option value="bsi">🟢 Bank Syariah Indonesia (BSI)</option>
                        <option value="cimb">🔴 CIMB Niaga (BizChannel)</option>
                        <option value="permata">🟣 Bank Permata / Danamon / Maybank / OCBC</option>
                        <option value="digital">🟣 Bank Digital (Jenius BTPN / Bank Jago / Blu / SeaBank)</option>
                        <option value="universal">🌐 Format Universal (Tabel Bebas / Bank Lainnya)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Sistem otomatis mengenali kolom Debet, Kredit, Saldo, dan Uraian transaksi.</p>
                </div>
            </div>
        </div>

        <!-- Mode Tabs: PDF File Upload vs Raw Text Paste -->
        <div class="border-b border-slate-200 px-6 flex gap-6 text-sm font-semibold">
            <button type="button" @click="activeTab = 'pdf'" :class="activeTab === 'pdf' ? 'border-b-2 border-blue-600 text-blue-600 py-3.5' : 'text-slate-500 hover:text-slate-700 py-3.5'">
                📁 Unggah Berkas Rekening Koran PDF
            </button>
            <button type="button" @click="activeTab = 'text'" :class="activeTab === 'text' ? 'border-b-2 border-blue-600 text-blue-600 py-3.5' : 'text-slate-500 hover:text-slate-700 py-3.5'">
                📋 Tempel Teks / CSV Mentah
            </button>
        </div>

        <div class="p-6">
            <!-- TAB 1: PDF UPLOAD (WITH DRAG AND DROP & PASSWORD SUPPORT) -->
            <div x-show="activeTab === 'pdf'" class="space-y-4">
                <div 
                    @dragover.prevent="isDragging = true" 
                    @dragleave.prevent="isDragging = false" 
                    @drop.prevent="handleFileDrop($event)"
                    :class="isDragging ? 'border-blue-500 bg-blue-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50/30'"
                    class="border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer relative"
                    @click="$refs.fileInput.click()">
                    
                    <input 
                        type="file" 
                        x-ref="fileInput" 
                        @change="handleFileSelect($event)" 
                        accept=".pdf,.csv,.txt" 
                        class="hidden">

                    <div class="flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-xs">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                <span class="text-blue-600 hover:underline">Pilih berkas PDF</span> atau seret dan lepas (drag & drop) di sini
                            </p>
                            <p class="text-xs text-slate-400 mt-1">Mendukung dokumen rekening koran e-statement PDF multi-halaman dari seluruh bank (Maks. 20 MB)</p>
                        </div>
                    </div>

                    <!-- Selected File Preview Badge -->
                    <template x-if="fileName">
                        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-100/70 border border-blue-200 text-blue-800 text-xs font-semibold">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span x-text="fileName"></span>
                            <span class="text-blue-500 font-normal" x-text="'(' + fileSize + ')'"></span>
                        </div>
                    </template>
                </div>

                <!-- PDF Password Field (If encrypted) -->
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="w-full sm:w-72">
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password Dokumen PDF (Opsional)</label>
                        <input 
                            type="password" 
                            x-model="pdfPassword" 
                            placeholder="Contoh: Tgl lahir / No. identitas" 
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="w-full sm:w-auto pt-5">
                        <button 
                            type="button" 
                            @click="processSelectedFile()" 
                            :disabled="!selectedFile || isProcessing"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold shadow-sm transition-colors cursor-pointer">
                            <template x-if="isProcessing">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            </template>
                            <template x-if="!isProcessing">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </template>
                            <span x-text="isProcessing ? processStatus : 'Konversi PDF Sekarang'"></span>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div x-show="isProcessing" class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-600 h-2 transition-all duration-300" :style="'width: ' + progressPercent + '%'"></div>
                </div>
            </div>

            <!-- TAB 2: RAW TEXT PASTE FORM -->
            <div x-show="activeTab === 'text'" class="space-y-4">
                <form method="POST" action="{{ route('converter.convert') }}">
                    @csrf
                    <input type="hidden" name="bank_account_id" :value="selectedBankId">
                    <input type="hidden" name="bank_type" :value="bankProfile">
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tempelkan Teks Mutasi Rekening Koran</label>
                        <textarea 
                            name="raw_text" 
                            rows="7" 
                            required 
                            placeholder="Contoh format teks rekening koran:&#10;01/09	TRSF E-BANKING CR 0109/FTSCY/WS95011 PEMBAYARAN KLIEN PT MAJU	15,000,000.00 CR 52,430,100.00&#10;02/09	BIAYA ADM BULANAN REKENING		25,000.00 DB 52,405,100.00" 
                            class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Dapat menyalin langsung dari tabel internet banking atau hasil copy-paste PDF rekening koran.</p>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-500 shadow-sm text-sm transition-colors">
                            Ekstrak & Validasi Data Mutasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Results Section (Shows if parsed via JS or loaded from Session) -->
    <div x-show="parsedRows.length > 0" class="space-y-6" style="display: none;">

        <!-- Financial Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Transaksi</div>
                <div class="text-2xl font-extrabold text-slate-900" x-text="parsedRows.length + ' Baris'"></div>
                <div class="text-xs text-slate-400 mt-1" x-text="'Bank: ' + (detectedBankName || 'Terdeteksi Otomatis')"></div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Mutasi Masuk (Kredit)</div>
                <div class="text-2xl font-extrabold text-emerald-600" x-text="formatRupiah(totalCredit)"></div>
                <div class="text-xs text-emerald-600/80 mt-1 font-medium">Uang masuk ke rekening</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Total Mutasi Keluar (Debet)</div>
                <div class="text-2xl font-extrabold text-rose-600" x-text="formatRupiah(totalDebit)"></div>
                <div class="text-xs text-rose-600/80 mt-1 font-medium">Uang keluar dari rekening</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase text-slate-500 mb-1">Arus Kas Bersih (Net)</div>
                <div class="text-2xl font-extrabold" :class="netMovement >= 0 ? 'text-blue-600' : 'text-rose-600'" x-text="formatRupiah(netMovement)"></div>
                <div class="text-xs text-slate-400 mt-1">Selisih mutasi masuk - keluar</div>
            </div>
        </div>

        <!-- Transactions Table Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-slate-800 text-sm">
                        Rincian Mutasi Rekening Terkonversi (<span x-text="filteredRows.length"></span> Baris)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Seluruh data siap diunduh ke Excel (.xlsx / .xls) atau diimpor ke sistem rekonsiliasi</p>
                </div>
                
                <!-- Action Buttons: Export to Excel, CSV, or Clear -->
                <div class="flex items-center flex-wrap gap-2">
                    <!-- Search Filter -->
                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            placeholder="Cari uraian atau nominal..." 
                            class="text-xs rounded-lg border-slate-300 pl-8 pr-3 py-1.5 w-48 sm:w-56 focus:border-blue-500 focus:ring-blue-500">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <!-- Download Excel Button -->
                    <form method="POST" action="{{ route('converter.export.excel') }}" class="inline">
                        @csrf
                        <input type="hidden" name="bank_name" :value="detectedBankName">
                        <input type="hidden" name="rows_data" :value="JSON.stringify(parsedRows)">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Unduh Excel (.xlsx)
                        </button>
                    </form>

                    <!-- Download CSV Button -->
                    <form method="POST" action="{{ route('converter.export.csv') }}" class="inline">
                        @csrf
                        <input type="hidden" name="bank_name" :value="detectedBankName">
                        <input type="hidden" name="rows_data" :value="JSON.stringify(parsedRows)">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Unduh CSV
                        </button>
                    </form>

                    <!-- Reset / Clear Button -->
                    <button type="button" @click="clearRows()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Kosongkan Hasil">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto max-h-[550px] overflow-y-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="sticky top-0 bg-slate-100 z-10">
                        <tr class="border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 w-28 whitespace-nowrap">Tanggal</th>
                            <th class="py-3 px-4">Uraian / Deskripsi Mutasi</th>
                            <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Debet (Keluar)</th>
                            <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Kredit (Masuk)</th>
                            <th class="py-3 px-4 w-36 text-right whitespace-nowrap">Saldo Akhir</th>
                            <th class="py-3 px-3 w-16 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <template x-for="(row, idx) in filteredRows" :key="idx">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-400" x-text="idx + 1"></td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-700 font-medium whitespace-nowrap" x-text="row.date"></td>
                                <td class="py-3 px-4">
                                    <div class="text-slate-900 font-medium text-xs sm:text-sm" x-text="row.description"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-show="row.bank_detected" x-text="'Format: ' + row.bank_detected"></div>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold whitespace-nowrap" :class="row.debit > 0 ? 'text-rose-600' : 'text-slate-300'" x-text="row.debit > 0 ? formatRupiah(row.debit) : '-'"></td>
                                <td class="py-3 px-4 text-right font-mono font-bold whitespace-nowrap" :class="row.credit > 0 ? 'text-emerald-600' : 'text-slate-300'" x-text="row.credit > 0 ? formatRupiah(row.credit) : '-'"></td>
                                <td class="py-3 px-4 text-right font-mono text-slate-600 text-xs whitespace-nowrap" x-text="row.balance > 0 ? formatRupiah(row.balance) : '-'"></td>
                                <td class="py-3 px-3 text-center">
                                    <button type="button" @click="removeRow(idx)" class="text-slate-300 hover:text-rose-500 transition-colors" title="Hapus Baris Ini">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="sticky bottom-0 bg-slate-50 border-t-2 border-slate-200 font-bold text-slate-900 text-xs">
                        <tr>
                            <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider">Total Mutasi:</td>
                            <td class="py-3 px-4 text-right font-mono text-rose-600 text-sm" x-text="formatRupiah(totalDebit)"></td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 text-sm" x-text="formatRupiah(totalCredit)"></td>
                            <td class="py-3 px-4 text-right font-mono text-blue-600 text-xs" x-text="'Net: ' + formatRupiah(netMovement)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<!-- Mozilla PDF.js CDN for Client-Side High-Fidelity Multi-Bank Parsing -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Configure PDF.js Worker
    if (window.pdfjsLib) {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    function bankConverter() {
        return {
            activeTab: 'pdf',
            isDragging: false,
            selectedFile: null,
            fileName: '',
            fileSize: '',
            pdfPassword: '',
            bankProfile: 'auto',
            selectedBankId: '{{ request('bank_account_id', '') }}',
            detectedBankName: '{{ session('summary.bank_name', '') }}',
            isProcessing: false,
            processStatus: 'Memproses...',
            progressPercent: 0,
            searchQuery: '',
            parsedRows: @json(session('parsedRows', [])),

            initData() {
                // If initial rows passed from session
                if (this.parsedRows.length > 0) {
                    this.activeTab = 'pdf';
                }
            },

            get filteredRows() {
                if (!this.searchQuery) return this.parsedRows;
                const q = this.searchQuery.toLowerCase();
                return this.parsedRows.filter(r => 
                    (r.description && r.description.toLowerCase().includes(q)) ||
                    (r.date && r.date.includes(q)) ||
                    (r.debit && r.debit.toString().includes(q)) ||
                    (r.credit && r.credit.toString().includes(q))
                );
            },

            get totalDebit() {
                return this.parsedRows.reduce((sum, r) => sum + (parseFloat(r.debit) || 0), 0);
            },

            get totalCredit() {
                return this.parsedRows.reduce((sum, r) => sum + (parseFloat(r.credit) || 0), 0);
            },

            get netMovement() {
                return this.totalCredit - this.totalDebit;
            },

            onBankSelectChange() {
                const select = event.target;
                const option = select.options[select.selectedIndex];
                if (option && option.dataset.bank) {
                    const bank = option.dataset.bank.toLowerCase();
                    if (bank.includes('bca')) this.bankProfile = 'bca';
                    else if (bank.includes('mandiri')) this.bankProfile = 'mandiri';
                    else if (bank.includes('bni')) this.bankProfile = 'bni';
                    else if (bank.includes('bri')) this.bankProfile = 'bri';
                    else if (bank.includes('bsi')) this.bankProfile = 'bsi';
                    else if (bank.includes('cimb')) this.bankProfile = 'cimb';
                    this.detectedBankName = option.text;
                }
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.setFile(file);
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file) this.setFile(file);
            },

            setFile(file) {
                this.selectedFile = file;
                this.fileName = file.name;
                this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                // Auto process right away
                this.processSelectedFile();
            },

            async processSelectedFile() {
                if (!this.selectedFile) return;

                const file = this.selectedFile;
                const isPdf = file.name.toLowerCase().endsWith('.pdf');

                this.isProcessing = true;
                this.progressPercent = 10;
                this.processStatus = 'Membaca struktur dokumen...';

                try {
                    if (isPdf && window.pdfjsLib) {
                        await this.parsePdfInBrowser(file);
                    } else {
                        // Text or fallback to server
                        const text = await file.text();
                        this.parseTextLocally(text);
                    }
                } catch (err) {
                    console.warn('Browser parsing exception, falling back to server:', err);
                    // If client-side failed (e.g. strict encrypted PDF), submit to backend
                    this.submitToServerFallback();
                } finally {
                    this.isProcessing = false;
                    this.progressPercent = 100;
                }
            },

            async parsePdfInBrowser(file) {
                const arrayBuffer = await file.arrayBuffer();
                const loadingTask = window.pdfjsLib.getDocument({
                    data: arrayBuffer,
                    password: this.pdfPassword || '',
                });

                this.processStatus = 'Membuka dokumen PDF...';
                this.progressPercent = 25;

                const pdf = await loadingTask.promise;
                const numPages = pdf.numPages;
                let fullExtractedLines = [];

                for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                    this.processStatus = `Mengekstrak Halaman ${pageNum} dari ${numPages}...`;
                    this.progressPercent = 25 + Math.round((pageNum / numPages) * 55);

                    const page = await pdf.getPage(pageNum);
                    const textContent = await page.getTextContent();
                    
                    // Group text items by vertical position (Y coordinate proximity)
                    const lineMap = new Map();
                    textContent.items.forEach(item => {
                        const y = Math.round(item.transform[5]); // Y coordinate
                        const x = item.transform[4]; // X coordinate
                        
                        // Find a line bucket within 4 pixels vertical threshold
                        let foundKey = null;
                        for (const key of lineMap.keys()) {
                            if (Math.abs(key - y) <= 4) {
                                foundKey = key;
                                break;
                            }
                        }

                        const targetKey = foundKey !== null ? foundKey : y;
                        if (!lineMap.has(targetKey)) {
                            lineMap.set(targetKey, []);
                        }
                        lineMap.get(targetKey).push({ x, str: item.str });
                    });

                    // Sort lines from top to bottom (higher Y is higher on page in PDF coordinates)
                    const sortedYKeys = Array.from(lineMap.keys()).sort((a, b) => b - a);

                    sortedYKeys.forEach(yKey => {
                        const items = lineMap.get(yKey).sort((a, b) => a.x - b.x);
                        const lineText = items.map(i => i.str).join('   ').trim();
                        if (lineText) {
                            fullExtractedLines.push(lineText);
                        }
                    });
                }

                this.processStatus = 'Menganalisis tabel mutasi bank...';
                this.progressPercent = 90;

                const joinedText = fullExtractedLines.join("\n");
                this.parseTextLocally(joinedText);
            },

            parseTextLocally(rawText) {
                // Client-side regex engine matching StatementConverterController
                const lines = rawText.split(/\r?\n/);
                const rows = [];
                let currDate = '';
                let currDesc = [];
                let currDebit = 0;
                let currCredit = 0;
                let currBalance = 0;

                const dateRegex = /^(\d{1,2}[\/\-\.]\d{1,2}(?:[\/\-\.]\d{2,4})?|\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2})\b/i;

                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (!line || this.isNoise(line)) continue;

                    const match = line.match(dateRegex);
                    if (match) {
                        if (currDate && (currDesc.length || currDebit > 0 || currCredit > 0)) {
                            rows.push({
                                date: this.normalizeDate(currDate),
                                description: currDesc.join(' '),
                                debit: currDebit,
                                credit: currCredit,
                                balance: currBalance,
                                bank_detected: this.bankProfile.toUpperCase(),
                                status: 'OK'
                            });
                        }

                        currDate = match[1];
                        currDesc = [];
                        currDebit = 0;
                        currCredit = 0;
                        currBalance = 0;

                        const remaining = line.substring(match[0].length).trim();
                        this.extractLineAmounts(remaining, currDesc, (d) => currDebit = d, (c) => currCredit = c, (b) => currBalance = b);
                    } else if (currDate) {
                        this.extractLineAmounts(line, currDesc, (d) => currDebit = d, (c) => currCredit = c, (b) => currBalance = b);
                    }
                }

                if (currDate) {
                    rows.push({
                        date: this.normalizeDate(currDate),
                        description: currDesc.join(' '),
                        debit: currDebit,
                        credit: currCredit,
                        balance: currBalance,
                        bank_detected: this.bankProfile.toUpperCase(),
                        status: 'OK'
                    });
                }

                // If tabular split
                if (rows.length === 0) {
                    lines.forEach(l => {
                        const parts = l.split(/[\t,;|]+/);
                        if (parts.length >= 3) {
                            rows.push({
                                date: this.normalizeDate(parts[0].trim()),
                                description: parts[1].trim(),
                                debit: this.parseMoney(parts[2] || '0'),
                                credit: parts[3] ? this.parseMoney(parts[3]) : 0,
                                balance: parts[4] ? this.parseMoney(parts[4]) : 0,
                                bank_detected: 'TABULAR',
                                status: 'OK'
                            });
                        }
                    });
                }

                if (rows.length > 0) {
                    this.parsedRows = rows;
                    if (!this.detectedBankName) {
                        this.detectedBankName = 'Rekening Koran (' + this.bankProfile.toUpperCase() + ')';
                    }
                } else {
                    alert('Tidak menemukan baris mutasi rekening yang valid pada berkas ini. Mencoba mengirimkan ke pemroses server...');
                    this.submitToServerFallback();
                }
            },

            extractLineAmounts(text, descList, setDebit, setCredit, setBalance) {
                // Check BCA format: "15,000,000.00 CR 52,430,100.00"
                const bcaMatch = text.match(/([\d\.,]+)\s+(CR|DB)(?:\s+([\d\.,]+))?/i);
                if (bcaMatch) {
                    const amt = this.parseMoney(bcaMatch[1]);
                    const type = bcaMatch[2].toUpperCase();
                    if (type === 'CR') setCredit(amt);
                    else setDebit(amt);

                    if (bcaMatch[3]) setBalance(this.parseMoney(bcaMatch[3]));
                    const desc = text.substring(0, bcaMatch.index).trim();
                    if (desc) descList.push(desc);
                    return;
                }

                // Check 2 or 3 trailing money values
                const numMatch = text.match(/([\d\.,]+)\s+([\d\.,]+)(?:\s+([\d\.,]+))?$/);
                if (numMatch) {
                    const v1 = this.parseMoney(numMatch[1]);
                    const v2 = this.parseMoney(numMatch[2]);
                    const v3 = numMatch[3] ? this.parseMoney(numMatch[3]) : null;

                    if (v1 > 0 || v2 > 0) {
                        setDebit(v1);
                        setCredit(v2);
                        if (v3 !== null) setBalance(v3);
                        const desc = text.substring(0, numMatch.index).trim();
                        if (desc) descList.push(desc);
                        return;
                    }
                }

                // Single money number
                const singleMatch = text.match(/([\d\.,]{4,})$/);
                if (singleMatch) {
                    const v = this.parseMoney(singleMatch[1]);
                    if (v > 0) {
                        const upper = text.toUpperCase();
                        if (upper.includes('MASUK') || upper.includes('CR') || upper.includes('BUNGA')) {
                            setCredit(v);
                        } else {
                            setDebit(v);
                        }
                        const desc = text.substring(0, singleMatch.index).trim();
                        if (desc) descList.push(desc);
                        return;
                    }
                }

                if (text) descList.push(text);
            },

            isNoise(line) {
                const u = line.toUpperCase();
                return u.startsWith('HALAMAN') || u.startsWith('PAGE') || u.startsWith('NO. REKENING') || 
                       u.startsWith('SALDO AWAL') || u.startsWith('SALDO AKHIR') || u.startsWith('TANGGAL TRANSAKSI') ||
                       u.startsWith('PT BANK') || u.startsWith('REKENING KORAN') || u.startsWith('STATEMENT OF ACCOUNT');
            },

            parseMoney(str) {
                if (!str || str === '-') return 0;
                let s = str.replace(/[^\d\.,]/g, '').trim();
                if (s.includes('.') && s.includes(',')) {
                    if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
                        s = s.replace(/\./g, '').replace(',', '.');
                    } else {
                        s = s.replace(/,/g, '');
                    }
                } else if (s.includes(',')) {
                    if (/,\d{2}$/.test(s)) s = s.replace(',', '.');
                    else s = s.replace(/,/g, '');
                } else if (s.includes('.')) {
                    if (/\.\d{3}$/.test(s) && !/\.\d{2}$/.test(s)) s = s.replace(/\./g, '');
                }
                return parseFloat(s) || 0;
            },

            normalizeDate(d) {
                const yr = new Date().getFullYear();
                if (/^(\d{1,2})[\/\-](\d{1,2})$/.test(d)) {
                    const p = d.split(/[\/\-]/);
                    return `${yr}-${p[1].padStart(2, '0')}-${p[0].padStart(2, '0')}`;
                }
                if (/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/.test(d)) {
                    const p = d.split(/[\/\-]/);
                    return `${p[2]}-${p[1].padStart(2, '0')}-${p[0].padStart(2, '0')}`;
                }
                return d;
            },

            formatRupiah(val) {
                return 'Rp ' + (val || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            },

            removeRow(index) {
                this.parsedRows.splice(index, 1);
            },

            clearRows() {
                if (confirm('Apakah Anda yakin ingin mengosongkan hasil konversi ini?')) {
                    this.parsedRows = [];
                    this.selectedFile = null;
                    this.fileName = '';
                }
            },

            submitToServerFallback() {
                // Create form and submit
                const formData = new FormData();
                if (this.selectedFile) formData.append('pdf_file', this.selectedFile);
                if (this.selectedBankId) formData.append('bank_account_id', this.selectedBankId);
                formData.append('bank_type', this.bankProfile);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route('converter.convert') }}', {
                    method: 'POST',
                    body: formData,
                }).then(() => {
                    window.location.reload();
                }).catch(e => {
                    alert('Gagal mengunggah file ke server.');
                });
            }
        };
    }
</script>
@endpush
