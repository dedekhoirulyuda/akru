@props([
    'excelUrl' => '#',
    'pdfUrl' => '#',
    'label' => 'Export',
    'align' => 'right'
])

<div class="relative inline-block text-left" x-data="{ open: false }">
    <button @click="open = !open" 
            @click.outside="open = false" 
            type="button" 
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 shadow-2xs hover:shadow-xs transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>{{ $label }}</span>
        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         style="display: none;" 
         class="absolute {{ $align === 'left' ? 'left-0' : 'right-0' }} mt-2 w-56 rounded-xl bg-white shadow-xl ring-1 ring-black/5 border border-slate-100 divide-y divide-slate-50 z-40 focus:outline-none overflow-hidden">
        
        <div class="px-3.5 py-2 bg-slate-50/70 border-b border-slate-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pilih Format Unduhan</p>
        </div>

        <div class="py-1">
            <a href="{{ $excelUrl }}" 
               class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors group">
                <div class="w-6 h-6 rounded bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-semibold">Microsoft Excel (.xlsx)</div>
                    <div class="text-[10px] text-slate-400">Format spreadsheet resmi & formula ready</div>
                </div>
            </a>

            <a href="{{ $pdfUrl }}" 
               target="_blank"
               class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition-colors group">
                <div class="w-6 h-6 rounded bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 group-hover:bg-rose-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-semibold">Cetak / Export PDF</div>
                    <div class="text-[10px] text-slate-400">Dokumen resmi, kop surat & ttd</div>
                </div>
            </a>
        </div>
    </div>
</div>
