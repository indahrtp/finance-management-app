# FinanceApp - Aplikasi Pengelolaan Keuangan

Aplikasi pengelolaan keuangan berbasis **Chart of Account (COA)**. Pengguna mencatat
transaksi, mengelompokkannya ke kategori income dan expense, memantau saldo rekening
dan anggaran, serta mendapatkan laporan **Profit/Loss** (tahunan dan bulanan) yang dapat
diunduh ke Excel.

Dibuat sebagai mini proyek skill test dengan **Laravel** (REST API), **Vue.js** (SPA),
dan **PostgreSQL**.

## Fitur

- **Autentikasi** token (Laravel Sanctum), seluruh API dilindungi, batas percobaan login
- **Dashboard** tahunan dan bulanan: ringkasan, perbandingan dengan periode sebelumnya, grafik (bulanan atau harian), komposisi pengeluaran, transaksi terbaru, saldo kas dan bank
- **Rekening dan Dompet** (tunai, bank, e-wallet) dengan saldo awal dan saldo berjalan
- CRUD **Kategori** (income / expense), **COA**, dan **Transaksi**
- **Anggaran** per kategori expense per bulan, dibandingkan dengan realisasi
- **Laporan Profit/Loss**: tabel 12 bulan per kategori dengan Total Income, Total Expense,
  dan Net Income
- **Export Excel** laporan Profit/Loss dengan tata letak siap cetak

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.3, Laravel 12, Laravel Sanctum, maatwebsite/excel |
| Frontend | Vue 3 (JavaScript), Vite, Pinia, Vue Router, Tailwind CSS v4, Axios, ApexCharts, lucide-vue-next |
| Database | PostgreSQL 16 

## Struktur Repository

```
finance-management-app/
├── backend/     Laravel (REST API)    -> lihat backend/README.md
├── frontend/    Vue 3 (SPA)           -> lihat frontend/README.md
├── docs/        ERD dan analisis masalah
└── README.md
```
## Menjalankan Proyek

### Prasyarat

- PHP 8.2+ dengan ekstensi `pdo_pgsql`, `gd`, `zip`, `fileinfo`, `mbstring`
- Composer
- Node.js 20.19+ atau 22.12+
- PostgreSQL 13+

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env        # Windows CMD: copy .env.example .env
php artisan key:generate
```

Buat database kosong bernama `finance_app` di PostgreSQL, lalu isi `DB_USERNAME` dan
`DB_PASSWORD` di `backend/.env`. Kemudian:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

API berjalan di `http://127.0.0.1:8000`.

### 2. Frontend

Di terminal lain:

```bash
cd frontend
npm install
cp .env.example .env        # Windows CMD: copy .env.example .env
npm run dev
```

Aplikasi berjalan di `http://localhost:5173`.

## Akun dan Data Demo

| Email | Password |
|---|---|
| `admin@example.com` | `password` |

## Aturan Bisnis

**Perhitungan**

- Income = credit - debit, Expense = debit - credit
- Net Income = Total Income - Total Expense
- Saldo rekening = saldo awal + (credit - debit) transaksi posted
- Nominal disimpan sebagai `decimal(15,2)`

**Transaksi**

- Satu transaksi hanya mengisi debit **atau** credit, nilainya lebih dari 0
- COA income hanya memakai credit, COA expense hanya memakai debit
- Transaksi baru selalu berstatus **draft**. Hanya transaksi **posted** yang dihitung di
  laporan, dashboard, anggaran, dan saldo rekening
- Draft boleh diubah dan dihapus. Posted boleh diubah tetapi **tidak boleh dihapus**,
  hanya dibatalkan (void). Void bersifat permanen sebagai jejak audit
- Tanggal transaksi tidak boleh melebihi hari ini
- Lampiran: JPG, PNG, atau PDF, maksimal 2 MB, disimpan di penyimpanan privat

**Master data**

- Nama kategori, COA, dan rekening unik tanpa membedakan huruf besar/kecil
- Kode COA dibuat sistem (kode terbesar pada tipe yang sama + 1) dan tidak dapat diubah.
  Celah akibat penghapusan sengaja tidak diisi ulang
- Tipe kategori tidak dapat diubah jika sudah memiliki COA. COA hanya dapat berpindah ke
  kategori bertipe sama
- Kategori yang memiliki COA, COA yang dipakai transaksi, dan rekening yang dipakai
  transaksi tidak dapat dihapus
- Anggaran hanya untuk kategori expense, satu anggaran per kategori per bulan

**Periode**

- Laporan, dashboard, dan anggaran hanya dapat dilihat sampai periode berjalan
- Batas awal pemilih periode mengikuti data paling awal yang ada

## Ringkasan Endpoint API

Semua endpoint selain login memerlukan header `Authorization: Bearer <token>`.
Detail parameter ada di [backend/README.md](backend/README.md).

| Area | Endpoint |
|---|---|
| Auth | `POST /api/login`, `POST /api/logout`, `GET /api/me` |
| Kategori | `/api/categories` (CRUD) |
| COA | `/api/coa` (CRUD), `GET /api/coa/next-code` |
| Rekening | `/api/wallets` (CRUD, dengan saldo) |
| Transaksi | `/api/transactions` (CRUD), `POST /{id}/post`, `POST /{id}/void`, `/{id}/attachment` |
| Anggaran | `GET/POST /api/budgets`, `DELETE /api/budgets/{id}` |
| Laporan | `GET /api/reports/profit-loss`, `/monthly`, `/export` |
| Dashboard | `GET /api/dashboard` |
| Lainnya | `GET /api/period-range` |