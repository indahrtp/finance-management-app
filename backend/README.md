# FinanceApp - Backend (Laravel API)

REST API untuk aplikasi pengelolaan keuangan FinanceApp. Gambaran proyek ada di
[README utama](../README.md).

## Teknologi

- PHP 8.3, Laravel 12
- Laravel Sanctum (autentikasi token Bearer)
- PostgreSQL 16
- maatwebsite/excel (export Excel)

## Persyaratan

- PHP 8.2+ dengan ekstensi `pdo_pgsql`, `gd`, `zip`, `fileinfo`, `mbstring`
  (cek dengan `php -m`)
- Composer
- PostgreSQL 13+

## Instalasi

```bash
composer install
cp .env.example .env        # Windows CMD: copy .env.example .env
php artisan key:generate
```

Buat database `finance_app`, isi `.env`, lalu:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

## Konfigurasi `.env`

| Variabel | Contoh | Keterangan |
|---|---|---|
| `APP_TIMEZONE` | `Asia/Jakarta` | **Penting**: dipakai validasi "tidak boleh melebihi hari ini" |
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `5432` | |
| `DB_DATABASE` | `finance_app` | |
| `DB_USERNAME` / `DB_PASSWORD` | | Jangan di-commit |

CORS diatur di `config/cors.php`. `allowed_origins` harus memuat alamat frontend
(`http://localhost:5173`). Restart `php artisan serve` setelah mengubahnya.

## Perintah Berguna

| Perintah | Fungsi |
|---|---|
| `php artisan migrate:fresh --seed` | Hapus semua tabel, migrasi ulang, isi data contoh |
| `php artisan db:seed --class=NamaSeeder` | Jalankan satu seeder (hati-hati: `TransactionSeeder` tidak aman diulang tanpa `migrate:fresh`) |
| `php artisan route:list --path=api` | Daftar endpoint |
| `php artisan optimize:clear` | Bersihkan cache config, route, dan view |

## Struktur Folder

```
app/
├── Enums/            CategoryType, TransactionStatus, WalletType
├── Exports/          ProfitLossExport (Excel)
├── Http/
│   ├── Controllers/Api/   Auth, Category, Coa, Wallet, Transaction,
│   │                      Budget, Report, Dashboard
│   └── Requests/          TransactionRequest
├── Models/           User, Category, Coa, Wallet, Transaction, Budget
├── Rules/            UniqueInsensitive
└── Services/         ProfitLossService
database/
├── migrations/
└── seeders/          User, Category, Coa, Wallet, Transaction, Budget
routes/api.php
```

## Konvensi API

- Selalu kirim header `Accept: application/json`
- Autentikasi: `Authorization: Bearer <token>` (token dari `POST /api/login`)
- Kode status: `200/201/204` sukses, `401` belum login, `404` tidak ditemukan,
  `409` konflik aturan bisnis (contoh: menghapus data yang masih dipakai),
  `422` validasi gagal, `429` terlalu banyak percobaan login
- Format error validasi (422):

```json
{ "message": "...", "errors": { "field": ["pesan"] } }
```

- Daftar transaksi dipaginasi mengikuti format `paginate()` Laravel
  (`data`, `current_page`, `last_page`, `total`, `from`, `to`)

## Daftar Endpoint

Semua endpoint selain `POST /api/login` memerlukan token.

### Autentikasi

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/login` | Body `email`, `password`. Hasil `user` dan `token`. Dibatasi 10 percobaan per menit |
| GET | `/api/me` | Data pengguna login |
| POST | `/api/logout` | Mencabut token yang dipakai |

### Master data

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/categories` | Query `type` (income/expense). Menyertakan `coa_count` |
| POST, PUT | `/api/categories`, `/{id}` | Body `name`, `type` |
| DELETE | `/api/categories/{id}` | 409 jika masih memiliki COA |
| GET | `/api/coa` | Query `category_id`, `search` (kode atau nama) |
| GET | `/api/coa/next-code` | Query `category_id`. Hasil `{ "code": "606" }` (pratinjau) |
| POST, PUT | `/api/coa`, `/{id}` | Body `name`, `category_id`. **Kode dibuat sistem**, tidak dapat diubah |
| DELETE | `/api/coa/{id}` | 409 jika sudah dipakai transaksi |
| GET | `/api/wallets` | Menyertakan `balance` dan `transactions_count` |
| POST, PUT | `/api/wallets`, `/{id}` | Body `name`, `type` (cash/bank/ewallet), `opening_balance` |
| DELETE | `/api/wallets/{id}` | 409 jika sudah dipakai transaksi |

### Transaksi

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/transactions` | Query `page`, `per_page`, `search` (deskripsi atau no. referensi), `coa_id`, `wallet_id`, `status`, `start_date`, `end_date` |
| POST | `/api/transactions` | Body `date`, `coa_id`, `wallet_id`, `debit`, `credit`, opsional `description`, `reference_no`, `notes`. **Selalu berstatus draft** |
| PUT | `/api/transactions/{id}` | Status tidak dapat diubah di sini. 409 jika void |
| DELETE | `/api/transactions/{id}` | Hanya draft. Selain itu 409 |
| POST | `/api/transactions/{id}/post` | draft menjadi posted |
| POST | `/api/transactions/{id}/void` | posted menjadi void (permanen) |
| POST | `/api/transactions/{id}/attachment` | `multipart/form-data`, field `file` (jpg, png, pdf, maks 2 MB). Mengganti lampiran lama |
| GET | `/api/transactions/{id}/attachment` | Mengunduh lampiran |
| DELETE | `/api/transactions/{id}/attachment` | Menghapus lampiran |

### Anggaran

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/budgets` | Query `month` (`YYYY-MM`). Perbandingan anggaran dan realisasi per kategori expense, dengan status `safe` (< 80%), `warning` (80-100%), `over` (> 100%), `none` |
| POST | `/api/budgets` | Body `category_id`, `month`, `amount`. Membuat atau mengubah (upsert). Hanya kategori expense |
| DELETE | `/api/budgets/{id}` | |

### Laporan dan dashboard

| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/reports/profit-loss` | Query `year`. 12 kolom bulan per kategori beserta total dan net income |
| GET | `/api/reports/profit-loss/monthly` | Query `month` (`YYYY-MM`). Rincian per kategori dan COA, persentase, perbandingan bulan lalu |
| GET | `/api/reports/profit-loss/export` | Query `year`. Mengunduh file `.xlsx` |
| GET | `/api/dashboard` | Query `year` (mode tahunan) atau `month` (mode bulanan) |
| GET | `/api/period-range` | Bulan paling awal yang memiliki data, untuk batas pemilih periode |

Tahun harus 2000 sampai tahun berjalan, dan bulan tidak boleh melebihi bulan berjalan.

## Di Mana Aturan Bisnis Ditegakkan

| Aturan | Lapisan |
|---|---|
| Debit atau credit saja, nilai > 0 | `TransactionRequest` dan CHECK constraint |
| COA income hanya credit, expense hanya debit | `TransactionRequest` |
| Tanggal transaksi maksimal hari ini | `TransactionRequest` |
| Nama unik tanpa beda huruf besar/kecil | `UniqueInsensitive` dan unique index `LOWER(name)` |
| Kode COA otomatis dan tidak berubah | `CoaController` (advisory lock PostgreSQL) |
| Tipe kategori dan tipe COA konsisten | `CategoryController`, `CoaController` |
| Tidak bisa menghapus data yang dipakai | Controller (409) dan foreign key `RESTRICT` |
| Alur status transaksi | `TransactionController` |
| Hanya transaksi posted yang dihitung | `ProfitLossService`, `BudgetController`, `Wallet::scopeWithBalance`, `DashboardController` |
| Anggaran unik per kategori per bulan | Unique index (`category_id`, `period`) |

## Laporan

`ProfitLossService` adalah satu-satunya sumber angka laporan:

- `generate($year)`: 12 kolom bulan (dipakai laporan tahunan, dashboard, export Excel)
- `generateMonthly($year, $month)`: per COA untuk bulan terpilih dan bulan sebelumnya
- `daily($year, $month)`: data harian untuk grafik dashboard bulanan

Seluruh agregasi dijalankan di PostgreSQL dengan `SUM(CASE ...)`. Export Excel
(`ProfitLossExport`) memakai service yang sama sehingga angkanya identik dengan layar.

## Seeder

`DatabaseSeeder` memanggil secara berurutan: `UserSeeder`, `CategorySeeder`, `CoaSeeder`,
`WalletSeeder`, `TransactionSeeder`, `BudgetSeeder`. Data master memakai `updateOrCreate`.
`TransactionSeeder` membuat transaksi **tahun berjalan** (dari Januari sampai hari ini)
dengan nominal deterministik, ditambah satu draft dan satu void.

Urutan migrasi penting: migrasi `wallets` harus berjalan **sebelum** `transactions`.

## Lampiran

Lampiran disimpan di `storage/app/private/transaction-attachments` (disk `local`, tidak
dapat diakses publik) dengan nama acak. Pengunduhan hanya lewat endpoint yang memerlukan
login. `storage:link` tidak diperlukan.

## Troubleshooting

| Masalah | Penyebab dan solusi |
|---|---|
| `could not find driver` | Aktifkan `extension=pdo_pgsql` di `php.ini` |
| 404 pada `/api/...` | Cek `php artisan route:list --path=api` dan `routes/api.php` |
| `relation "wallets" does not exist` | Urutan file migrasi salah (wallets harus lebih awal dari transactions) |
| `Class "App\Http\Controllers\Api\X" not found` | Lupa `use` di controller untuk class `X` |
| Transaksi hari ini ditolak "melebihi hari ini" | `APP_TIMEZONE` belum diisi `Asia/Jakarta`, lalu `php artisan config:clear` |
| Export Excel gagal | Cek ekstensi `gd`, `zip`, dan paket `maatwebsite/excel` terpasang |
| Upload lampiran gagal | Periksa `upload_max_filesize` dan `post_max_size` di `php.ini`, serta ekstensi `fileinfo` |
| Error CORS di browser | Periksa `config/cors.php`, lalu restart server |

Log aplikasi ada di `storage/logs/laravel.log`.