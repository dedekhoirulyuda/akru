# AKRU — Data Nyata. Kendali Penuh.

> **AKRU** adalah platform SaaS *Accounting, Finance & Tax Control* terintegrasi yang dirancang khusus untuk entitas bisnis di Indonesia (UMKM berkembang hingga *mid-market*).

---

## Daftar Isi

- [Tentang AKRU](#tentang-akru)
- [Fitur Utama & Prinsip Desain](#fitur-utama--prinsip-desain)
- [Teknologi & Stack](#teknologi--stack)
- [Arsitektur Sistem (Modular Monolith)](#arsitektur-sistem-modular-monolith)
  - [16 Domain Modules](#16-domain-modules)
  - [9 Shared Engines](#9-shared-engines)
  - [Support Layer & Enums](#support-layer--enums)
- [Struktur Direktori](#struktur-direktori)
- [Panduan Instalasi & Menjalankan](#panduan-instalasi--menjalankan)
- [Aturan Arsitektur & Konvensi Kode](#aturan-arsitektur--konvensi-kode)
- [PWA & Offline-First Capability](#pwa--offline-first-capability)
- [Lisensi](#lisensi)

---

## Tentang AKRU

AKRU dibangun berdasarkan kebutuhan operasional dan kepatuhan fiskal di Indonesia:
- **Kepatuhan Pajak Indonesia (Coretax Ready)**: PPh 21/23/4(2)/25/29, PPN 11%/12%, ekspor XML/CSV format DJP Coretax.
- **Single Source of Truth**: Buku besar akuntansi (*General Ledger*) yang tidak dapat dimanipulasi tanpa jejak audit (*append-only*).
- **Multi-Company & Multi-Branch**: Pengelolaan banyak entitas (PT, CV, perorangan) dan cabang dalam satu akun terpusat.
- **PWA Offline-First**: Kasir dan input transaksi lapangan tetap berjalan saat koneksi internet terputus menggunakan IndexedDB & Background Sync.

---

## Fitur Utama & Prinsip Desain

1. **Deterministic Financial Ledger**: Jurnal akuntansi hanya dapat dibentuk melalui *Posting Engine* terpusat — dilarang keras membuat entri jurnal manual atau acak di luar alur resmi.
2. **Strict Multi-Tenant Scoping**: Setiap query data bisnis wajib berlingkup `company_id`. Dilarang ada celah kebocoran data antar-perusahaan (*data leakage zero tolerance*).
3. **Immutability & Audit Trail**: Transaksi yang telah di-*post* tidak boleh diubah atau dihapus (*no hard-deletes, no in-place edits*). Perubahan dilakukan melalui mekanisme koreksi/reversal resmi.
4. **Segregation of Duties (SoD)**: Pemisahan peran ketat antara pembuat transaksi (*maker*), penyetuju (*checker/approver*), dan pembayar/eksekutor (*releaser*).
5. **No Floating-Point Money**: Seluruh kalkulasi moneter menggunakan integer terkecil (sen/rupiah) atau `bcmath` dengan *banker's rounding* (round half to even).

---

## Teknologi & Stack

| Lapisan | Teknologi | Keterangan |
|---|---|---|
| **Backend** | PHP 8.2+ / Laravel 12 | Modular Monolith Architecture |
| **Frontend** | Blade + Tailwind CSS v4 + Alpine.js | Modern, reaktif tanpa *overhead* SPA berat |
| **Database** | MySQL / MariaDB | Skema multi-tenant dengan `company_id` |
| **Build Tool** | Vite 7 | Hot Module Replacement (HMR) & asset bundling |
| **PWA / Offline** | Service Worker + IndexedDB | Cache-first untuk shell, IndexedDB untuk antrean sync |
| **Queue & Scheduler**| Laravel Queue (Database/Redis) + Task Scheduler | Pemrosesan background & recurring jobs |

---

## Arsitektur Sistem (Modular Monolith)

Aplikasi diorganisir menggunakan pola **Modular Monolith** di dalam direktori `app/Modules/`. Setiap modul memiliki siklus hidup, model, route, controller, request, migrasi, dan kebijakan (*policy*) masing-masing.

```
app/
├── Modules/                 # === 16 DOMAIN MODULES ===
│   ├── Core/                # Multi-tenancy, Company, Branch, Settings
│   ├── Identity/            # Auth, User, Role, Permission, RBAC
│   ├── MasterData/          # COA, Kontak, Pelanggan, Pemasok, Produk, Gudang
│   ├── Accounting/          # Jurnal, Buku Besar, Neraca Saldo, Periode Fiskal
│   ├── Sales/               # Penjualan, Faktur Penjualan, Surat Jalan, Penerimaan
│   ├── Purchase/            # Pembelian, PO, Penerimaan Barang, Pembayaran Pemasok
│   ├── Inventory/           # Pergerakan Stok, Mutasi, Stok Opname, Valuasi FIFO/Avg
│   ├── Finance/             # Kas & Bank, Rekonsiliasi, Transfer, Petty Cash
│   ├── Tax/                 # PPN, PPh 21/23/4(2), Tax Engine, Ekspor Coretax
│   ├── Document/            # Manajemen Berkas, Lampiran Bukti Transaksi
│   ├── Workflow/            # Approval Engine, Kebijakan Bertingkat, Antrean Kerja
│   ├── Audit/               # Append-only Audit Log, Jejak Perubahan Field
│   ├── Notification/        # Notifikasi Sistem, Email, & Notifikasi Persetujuan
│   ├── Reporting/           # Laporan Keuangan (Laba Rugi, Neraca, Arus Kas)
│   ├── Subscription/        # Paket Langganan, Kuota, Billing SaaS
│   └── Partner/             # Konsol Kantor Akuntan / Konsultan Pajak Mitra
│
├── Services/                # === 9 SHARED ENGINES ===
│   ├── Posting/             # PostingService: Pintu tunggal pembentukan jurnal
│   ├── TaxEngine/           # Kalkulasi tarif pajak, aturan efektif, pembulatan
│   ├── AuditEngine/         # Pencatatan audit trail otomatis & tamper-evident
│   ├── ApprovalEngine/      # Evaluasi kebijakan approval & validasi SoD
│   ├── DocumentEngine/      # Penanganan upload, validasi berkas, checksum
│   ├── ImportEngine/        # Import Excel bertahap (preview, validate, commit)
│   ├── ExportEngine/        # Ekspor laporan (Excel, PDF resmi ber-watermark)
│   ├── SyncEngine/          # Sinkronisasi transaksi offline ke server & idempoten
│   └── SequenceEngine/      # Penomoran otomatis dokumen bisnis unik & urut
│
├── Support/                 # === SUPPORT & KERNEL LAYER ===
│   ├── Enums/               # Status dokumen, status posting, jenis pajak, dll.
│   ├── ValueObjects/        # Money, Quantity (presisi tinggi)
│   └── Traits/              # HasCompanyScope, HasAuditTrail, Postable
```

### 16 Domain Modules

1. **Core**: Manajemen multi-entitas, struktur cabang, onboarding wizard, dan preferensi global.
2. **Identity**: Otentikasi, manajemen pengguna multi-organisasi, hak akses RBAC granular.
3. **MasterData**: Bagan Akun Standar (COA), Rekening Bank, Pelanggan, Pemasok, Gudang, Satuan, dan Katalog Produk/Jasa.
4. **Accounting**: Pemrosesan periode akuntansi, jurnal penutup, laporan neraca saldo, rekonsiliasi GL.
5. **Sales**: Siklus penjualan terpadu: Penawaran, Order Penjualan, Pengiriman, Faktur Penjualan (*Sales Invoice*), dan Retur.
6. **Purchase**: Siklus pengadaan: Permintaan Pembelian (PR), PO, Penerimaan Barang (GRN), Faktur Pemasok, dan Pembayaran Hutang.
7. **Inventory**: Pencatatan mutasi stok (*StockMovement*) yang tidak dapat dihapus, multi-gudang, penyesuaian (*stock opname*), dan kartu stok.
8. **Finance**: Arus kas harian, kas kecil (*petty cash*), mutasi antar-bank, dan rekonsiliasi rekening koran.
9. **Tax**: Perhitungan pajak otomatis, penomoran faktur pajak, rekonsiliasi fiskal, serta ekspor format Coretax DJP.
10. **Document**: Pengarsipan berkas digital, verifikasi integritas SHA-256 dokumen bukti transaksi.
11. **Workflow**: Mesin *approval* bertingkat berbasis nilai transaksi (*threshold*), delegasi, dan pemisahan wewenang.
12. **Audit**: Perekaman aktivitas sistem secara *append-only* untuk kebutuhan audit forensik dan kepatuhan.
13. **Notification**: Pusat notifikasi antarmuka dan notifikasi email untuk tugas approval serta peringatan jatuh tempo.
14. **Reporting**: Laporan Keuangan Standar SAK (Laba Rugi, Neraca, Perubahan Modal, Arus Kas) dan rasio bisnis.
15. **Subscription**: Pengaturan paket langganan tenant, kuota transaksi, dan modul aktif.
16. **Partner**: Portal khusus konsultan akuntan/pajak mitra untuk mengelola portofolio banyak klien.

### 9 Shared Engines

- **`PostingService`**: Gerbang tunggal mutlak yang memvalidasi keseimbangan Debit = Kredit sebelum menulis ke tabel jurnal.
- **`TaxCalculator`**: Menghitung PPh dan PPN sesuai ketentuan perundang-undangan perpajakan Indonesia terbaru.
- **`AuditLogger`**: Mencatat siapa, kapan, IP, user-agent, dan perubahan data (*before vs after*).
- **`ApprovalEngine`**: Memastikan alur persetujuan dipatuhi dan mencegah pengguna menyetujui transaksi buatannya sendiri (*Anti-Self Approval*).
- **`SequenceGenerator`**: Menghasilkan nomor transaksi urut dan anti-duplikat (contoh: `INV/2026/09/0001`).
- **`SyncManager`**: Memproses antrean transaksi yang dikirim dari klien PWA saat kembali online dengan kunci idempoten (*idempotency key*).
- **`DocumentEngine`**, **`ImportEngine`**, **`ExportEngine`**: Menangani berkas, validasi impor bertahap, dan pembuatan dokumen cetak.

---

## Struktur Direktori

```
d:/AKRU/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/         # CorrelationId, TenantResolver, CompanyScope
│   ├── Models/                 # User model dasar
│   ├── Modules/                # 16 Domain Modules
│   ├── Providers/              # AppServiceProvider, ModuleServiceProvider
│   ├── Services/               # 9 Shared Engines
│   └── Support/                # Enums, ValueObjects, Traits
├── bootstrap/
│   ├── app.php                 # Routing & Middleware bootstrap
│   └── providers.php           # Service provider registration
├── config/
│   ├── akru.php                # Konfigurasi spesifik platform AKRU
│   └── modules.php             # Registri daftar modul aktif
├── public/
│   ├── build/                  # Aset hasil kompilasi Vite
│   ├── icons/                  # PWA icons (192x192, 512x512)
│   ├── manifest.json           # PWA Web App Manifest
│   └── sw.js                   # Service Worker (offline cache)
├── resources/
│   ├── css/
│   │   └── app.css             # Tailwind CSS v4 source
│   ├── js/
│   │   ├── app.js              # Entrypoint Alpine.js & scripts
│   │   └── pwa/                # IndexedDB & offline sync queue handler
│   ├── lang/
│   │   └── id/                 # Bahasa Indonesia (akru.php)
│   └── views/
│       ├── layouts/            # app.blade.php, auth.blade.php, pdf.blade.php
│       ├── components/         # sidebar, topbar, modal, table, card
│       ├── dashboard/          # Dashboard peranan
│       ├── auth/               # Halaman otentikasi
│       ├── onboarding/         # Setup awal perusahaan
│       └── settings/           # Pengaturan entitas & cabang
├── routes/
│   ├── api.php                 # API v1 (Health, sync, dsb.)
│   ├── web.php                 # Web core routes
│   └── console.php             # Artisan console commands
└── tests/
    ├── Feature/
    └── Unit/
```

---

## Panduan Instalasi & Menjalankan

### 1. Persyaratan Sistem
- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `bcmath`, `mbstring`, `intl`, `gd`)
- Composer >= 2.x
- Node.js >= 20.x & npm >= 10.x
- MySQL >= 8.0 atau MariaDB >= 10.5

### 2. Langkah Instalasi

```bash
# 1. Masuk ke direktori project
cd d:/AKRU

# 2. Salin environment configuration
cp .env.example .env

# 3. Generate Application Key
php artisan key:generate

# 4. Install dependensi PHP (jika belum)
composer install

# 5. Install dependensi JavaScript (jika belum)
npm install

# 6. Kompilasi aset frontend
npm run build
# Atau untuk mode development (hot reload):
# npm run dev

# 7. Jalankan migrasi database
php artisan migrate

# 8. Jalankan local development server
php artisan serve
```

Akses aplikasi di browser: `http://localhost:8000`

### 3. Akun Demo Bawaan (Seed Data)

Database telah dilengkapi dengan data demo siap pakai (`php artisan migrate:fresh --seed`):

| Peran (Role) | Email | Password | Hak Akses Utama |
|---|---|---|---|
| **Pemilik Usaha (Owner)** | `owner@akru.id` | `password` | Akses Penuh Seluruh Modul & Otorisasi |
| **Staf Keuangan (Finance)** | `finance@akru.id` | `password` | Kas & Bank, Pembayaran, Penerimaan |
| **Akuntan (Accountant)** | `accountant@akru.id` | `password` | Jurnal, Buku Besar, Neraca Saldo, Laporan |
| **Spesialis Pajak (Tax)** | `tax@akru.id` | `password` | PPN Masa 1111, PPh Withholding, Coretax |

---

## Aturan Arsitektur & Konvensi Kode

1. **Semua UI Menggunakan Bahasa Indonesia**: Sesuai Blueprint §1.1A, antarmuka pengguna, pesan kesalahan, dan validasi menggunakan Bahasa Indonesia yang baku dan ramah pengguna bisnis.
2. **Trait `HasCompanyScope` Wajib**: Setiap model yang menyimpan data entitas wajib menggunakan trait `App\Support\Traits\HasCompanyScope` agar otomatis memfilter query berdasarkan `company_id` aktif.
3. **Pencatatan Audit Otomatis**: Gunakan trait `App\Support\Traits\HasAuditTrail` pada model transaksi penting.
4. **Idempotency Key pada Mutasi**: Transaksi yang dibuat melalui API / offline sync wajib menyertakan `Idempotency-Key` (UUIDv4) untuk mencegah duplikasi data transaksi saat koneksi tidak stabil.
5. **No Direct Journal Write**: Jangan pernah menulis langsung ke tabel `journals` atau `journal_entries` dari controller/action apa pun; selalu gunakan `PostingService`.

---

## PWA & Offline-First Capability

Aplikasi AKRU dilengkapi dengan:
- **Service Worker (`public/sw.js`)**: Melayani aset antarmuka secara instan melalui cache lokal.
- **IndexedDB Store (`resources/js/pwa/indexeddb.js`)**: Menyimpan draf transaksi lokal saat offline.
- **Sync Queue (`resources/js/pwa/sync-queue.js`)**: Otomatis mengirimkan transaksi tertunda begitu perangkat kembali terhubung dengan internet.
- **Offline Indicator (`resources/js/pwa/offline-status.js`)**: Bar indikator visual status koneksi (*Online / Offline / Menyinkronkan*).

---

## Lisensi

Hak Cipta © 2026 **AKRU**. Seluruh hak cipta dilindungi undang-undang.
