@extends('layouts.auth')

@section('title', 'Panduan Pengaturan Awal (Onboarding) — AKRU')

@section('content')
<div x-data="{
    step: 1,
    company: {
        entity_type: 'PT',
        npwp: '01.234.567.8-012.000',
        is_pkp: true,
        address: 'Jl. Sudirman Kav. 50, Jakarta Selatan',
        fiscal_month: 1
    },
    industry_template: 'distributor',
    bank: {
        name: 'Bank Central Asia (BCA)',
        number: '123-456-7890',
        holder: 'PT Akru Maju Bersama'
    }
}" class="text-white">

    <!-- Progress Indicator -->
    <div class="mb-6">
        <div class="flex items-center justify-between text-xs font-medium text-slate-400 mb-2">
            <span :class="step >= 1 ? 'text-blue-400 font-semibold' : ''">1. Profil Pajak</span>
            <span :class="step >= 2 ? 'text-blue-400 font-semibold' : ''">2. Template Akun</span>
            <span :class="step >= 3 ? 'text-blue-400 font-semibold' : ''">3. Kas & Bank</span>
            <span :class="step >= 4 ? 'text-blue-400 font-semibold' : ''">4. Go-Live</span>
        </div>
        <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
            <div class="bg-blue-500 h-full transition-all duration-300" :style="'width: ' + (step * 25) + '%'"></div>
        </div>
    </div>

    <!-- Step 1: Profil Pajak & Badan Hukum -->
    <div x-show="step === 1" class="space-y-4">
        <div>
            <h3 class="text-lg font-bold text-white">Profil Legal & Perpajakan</h3>
            <p class="text-xs text-slate-400">Tentukan bentuk entitas bisnis untuk penyesuaian aturan fiskal.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Bentuk Badan Usaha</label>
            <select x-model="company.entity_type" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400">
                <option value="PT" class="bg-slate-900">PT (Perseroan Terbatas)</option>
                <option value="CV" class="bg-slate-900">CV (Commanditaire Vennootschap)</option>
                <option value="Perorangan" class="bg-slate-900">Usaha Perorangan / Toko</option>
                <option value="Firma" class="bg-slate-900">Firma / Kemitraan</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">NPWP Perusahaan (16 Digit Coretax / 15 Digit Lama)</label>
            <input type="text" x-model="company.npwp" placeholder="01.234.567.8-012.000" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400">
        </div>

        <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/10">
            <div>
                <p class="text-sm font-medium text-white">Pengusaha Kena Pajak (PKP)</p>
                <p class="text-xs text-slate-400">Apakah entitas ini menerbitkan & mengkreditkan Faktur Pajak PPN?</p>
            </div>
            <input type="checkbox" x-model="company.is_pkp" class="w-5 h-5 rounded border-white/20 bg-white/5 text-blue-500 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Alamat Domisili Pajak</label>
            <textarea x-model="company.address" rows="2" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400"></textarea>
        </div>

        <button @click="step = 2" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition-colors">
            Lanjut: Pilih Template Akun &rarr;
        </button>
    </div>

    <!-- Step 2: Template Bagan Akun (COA) -->
    <div x-show="step === 2" class="space-y-4" style="display: none;">
        <div>
            <h3 class="text-lg font-bold text-white">Pilih Template Bagan Akun (COA)</h3>
            <p class="text-xs text-slate-400">Struktur akun standar Indonesia (SAK) sesuai industri Anda.</p>
        </div>

        <div class="space-y-2">
            <label class="flex items-start p-3 rounded-lg border cursor-pointer transition-colors" :class="industry_template === 'distributor' ? 'border-blue-400 bg-blue-500/10' : 'border-white/10 bg-white/5'">
                <input type="radio" name="industry" value="distributor" x-model="industry_template" class="mt-1 text-blue-500">
                <div class="ml-3">
                    <p class="text-sm font-medium text-white">Perdagangan, Distributor & Retail</p>
                    <p class="text-xs text-slate-400">Termasuk akun Persediaan, HPP, Retur, Diskon & PPN Masukan/Keluaran.</p>
                </div>
            </label>

            <label class="flex items-start p-3 rounded-lg border cursor-pointer transition-colors" :class="industry_template === 'services' ? 'border-blue-400 bg-blue-500/10' : 'border-white/10 bg-white/5'">
                <input type="radio" name="industry" value="services" x-model="industry_template" class="mt-1 text-blue-500">
                <div class="ml-3">
                    <p class="text-sm font-medium text-white">Jasa & Konsultasi Profesional</p>
                    <p class="text-xs text-slate-400">Fokus pada Pendapatan Jasa, Beban Tenaga Ahli, dan PPh 23 / PPh 21.</p>
                </div>
            </label>
        </div>

        <div class="flex gap-2">
            <button @click="step = 1" class="w-1/3 rounded-lg bg-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/15">Kembali</button>
            <button @click="step = 3" class="w-2/3 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">Lanjut: Kas & Bank &rarr;</button>
        </div>
    </div>

    <!-- Step 3: Rekening Kas & Bank -->
    <div x-show="step === 3" class="space-y-4" style="display: none;">
        <div>
            <h3 class="text-lg font-bold text-white">Rekening Kas & Bank Utama</h3>
            <p class="text-xs text-slate-400">Tambahkan rekening operasional untuk mencatat mutasi kas & pembayaran.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Nama Bank / Kas</label>
            <input type="text" x-model="bank.name" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Nomor Rekening</label>
            <input type="text" x-model="bank.number" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Atas Nama Rekening</label>
            <input type="text" x-model="bank.holder" class="w-full rounded-lg bg-white/5 border border-white/10 text-white px-3 py-2 text-sm focus:border-blue-400">
        </div>

        <div class="flex gap-2">
            <button @click="step = 2" class="w-1/3 rounded-lg bg-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/15">Kembali</button>
            <button @click="step = 4" class="w-2/3 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">Lanjut: Konfirmasi &rarr;</button>
        </div>
    </div>

    <!-- Step 4: Konfirmasi Go-Live -->
    <div x-show="step === 4" class="space-y-4 text-center" style="display: none;">
        <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto text-2xl font-bold">✓</div>
        <div>
            <h3 class="text-lg font-bold text-white">Konfigurasi Siap Digunakan!</h3>
            <p class="text-xs text-slate-400 mt-1">Bagan akun standar, aturan tarif pajak PPN/PPh, dan rekening bank siap digunakan untuk transaksi perdana Anda.</p>
        </div>

        <div class="p-3 bg-white/5 rounded-lg border border-white/10 text-left text-xs space-y-1 text-slate-300">
            <p><strong>Bentuk Usaha:</strong> <span x-text="company.entity_type"></span></p>
            <p><strong>Status Pajak:</strong> <span x-text="company.is_pkp ? 'PKP (Faktur Pajak Aktif)' : 'Non-PKP'"></span></p>
            <p><strong>Bagan Akun:</strong> Standar SAK Indonesia Terpasang</p>
            <p><strong>Rekening Bank:</strong> <span x-text="bank.name"></span> (<span x-text="bank.number"></span>)</p>
        </div>

        <form method="POST" action="{{ route('onboarding.complete') }}">
            @csrf
            <div class="flex gap-2">
                <button type="button" @click="step = 3" class="w-1/3 rounded-lg bg-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/15">Kembali</button>
                <button type="submit" class="w-2/3 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 shadow-lg shadow-emerald-500/20">
                    Mulai Bekerja (Go-Live)
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
