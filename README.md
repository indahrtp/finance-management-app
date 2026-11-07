# FinanceApp - Aplikasi Pengelolaan Keuangan

Aplikasi pengelolaan keuangan pribadi berbasis Chart of Account (COA):
mencatat transaksi, mengelompokkannya per kategori, dan menghasilkan
laporan Profit/Loss bulanan yang dapat diexport ke Excel.

Dibuat sebagai mini proyek skill test.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.3, Laravel 12, Laravel Sanctum (token) |
| Frontend | Vue 3, Vite, Pinia, Vue Router, Tailwind CSS, ApexCharts |
| Database | PostgreSQL 16 |
| Export | maatwebsite/excel |

## Fitur

- Login dan logout (token Sanctum), seluruh API dilindungi autentikasi
- CRUD Kategori (income / expense), COA, dan Transaksi
- Validasi aturan akuntansi: COA income hanya credit, COA expense hanya debit
- Laporan Profit/Loss per tahun (12 kolom bulan + total, Net Income)
- Export laporan ke Excel (.xlsx)
- Dashboard: ringkasan, grafik income vs expense, komposisi pengeluaran, transaksi terbaru
- Filter, pencarian, dan paginasi transaksi
- Tampilan responsif (desktop dan mobile)

## Struktur Repository

```
finance-management-app/
├── backend/    Laravel (REST API)
├── frontend/   Vue 3 (SPA)
├── docs/       ERD dan analisis
└── README.md
```

## Instalasi

### Prasyarat
PHP 8.2+ (ekstensi pdo_pgsql, gd, zip), Composer, Node.js 20+, PostgreSQL 13+

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```
Buat database `finance_app` di PostgreSQL, lalu isi `DB_USERNAME` dan
`DB_PASSWORD` di `.env`. Kemudian:
```bash
php artisan migrate --seed
php artisan serve
```
API berjalan di `http://127.0.0.1:8000`.

### Frontend
```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```
Aplikasi berjalan di `http://localhost:5173`.

## Akun Demo

| Email | Password |
|---|---|
| admin@example.com | password |

Seeder mengisi data contoh tahun 2022 (Januari sampai Maret) yang
menghasilkan laporan seperti contoh pada soal.

## Dokumentasi

- [Analisis permasalahan](docs/analisis.md)
- [Entity Relationship Diagram](docs/erd.md)

## Ringkasan Endpoint API

Semua endpoint selain login memerlukan header `Authorization: Bearer <token>`.

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | /api/login | Login, mengembalikan token |
| POST | /api/logout | Logout |
| GET | /api/me | Data user login |
| GET/POST/PUT/DELETE | /api/categories | CRUD kategori |
| GET/POST/PUT/DELETE | /api/coa | CRUD COA |
| GET/POST/PUT/DELETE | /api/transactions | CRUD transaksi (filter, paginasi) |
| GET | /api/reports/profit-loss?year= | Laporan Profit/Loss |
| GET | /api/reports/profit-loss/export?year= | Unduh laporan (.xlsx) |
| GET | /api/dashboard?year= | Data dashboard |

## Aturan Bisnis

- Income = credit - debit, Expense = debit - credit
- Satu transaksi hanya mengisi debit atau credit (dijaga validasi dan CHECK constraint database)
- COA bertipe income hanya boleh memakai credit, bertipe expense hanya debit
- Kategori yang punya COA, dan COA yang sudah dipakai transaksi, tidak dapat dihapus
- Nominal disimpan sebagai decimal(15,2)