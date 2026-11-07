# FinanceApp - Frontend (Vue 3)

Antarmuka web (SPA) untuk aplikasi pengelolaan keuangan FinanceApp. Gambaran proyek ada di
[README utama](../README.md), dan API-nya di [backend/README.md](../backend/README.md).

## Teknologi

- Vue 3 (Composition API, `<script setup>`), **JavaScript**
- Vite, Vue Router, Pinia
- Tailwind CSS v4 (plugin `@tailwindcss/vite`, tanpa file konfigurasi)
- Axios, ApexCharts (`vue3-apexcharts`), lucide-vue-next (ikon)
- Font Urbanist (Google Fonts)

## Persyaratan

Node.js 20.19+ atau 22.12+. Backend harus berjalan di `http://127.0.0.1:8000`.

## Instalasi

```bash
npm install
cp .env.example .env        # Windows CMD: copy .env.example .env
npm run dev
```

Aplikasi berjalan di `http://localhost:5173`. Restart `npm run dev` setiap mengubah `.env`.

| Variabel | Contoh | Keterangan |
|---|---|---|
| `VITE_API_URL` | `http://127.0.0.1:8000/api` | Alamat API backend |

## Skrip

| Perintah | Fungsi |
|---|---|
| `npm run dev` | Server pengembangan |
| `npm run build` | Build produksi ke `dist/` |
| `npm run preview` | Pratinjau hasil build |

## Struktur Folder

```
src/
├── assets/main.css        Tailwind, tema (warna, font, animasi), dark mode
├── components/
│   ├── ui/                Komponen dasar yang dipakai ulang
│   └── reports/           Tabel laporan Profit/Loss (tahunan, bulanan)
├── composables/           useToast, useTheme, usePeriodRange
├── layouts/               AppLayout (sidebar dan navbar), menu.js
├── router/index.js        Rute dan penjaga halaman
├── services/api.js        Instance Axios
├── stores/auth.js         Pinia: sesi pengguna
├── utils/                 format, errors, download, period
└── views/                 Satu file per halaman
```

## Halaman dan Rute

| Rute | Halaman | Login |
|---|---|---|
| `/` | Landing page | Tidak |
| `/login` | Masuk | Tidak (tamu saja) |
| `/dashboard` | Dashboard tahunan atau bulanan | Ya |
| `/categories` | Kategori | Ya |
| `/coa` | Chart of Account | Ya |
| `/wallets` | Rekening dan Dompet | Ya |
| `/transactions` | Transaksi | Ya |
| `/budgets` | Anggaran | Ya |
| `/reports/profit-loss` | Laporan Profit/Loss (tahunan atau bulanan) | Ya |

Penjaga halaman ada di `router/index.js`: halaman bertanda login mengarahkan ke `/login`
jika belum masuk, dan `/login` mengarahkan ke dashboard jika sudah masuk.

## Alur Data

- `services/api.js` menyisipkan token Bearer dari `localStorage` pada setiap request. Jika
  API membalas `401` (selain di halaman login), sesi dihapus dan pengguna diarahkan ke
  `/login`.
- Error validasi `422` dipetakan ke pesan di bawah masing-masing kolom lewat
  `utils/errors.js`. Error lain tampil sebagai toast (`useToast`).
- Unduhan file (Excel, lampiran) memakai Axios dengan `responseType: 'blob'` lalu
  `utils/download.js`, karena tautan biasa tidak membawa token.

## Komponen UI (`components/ui/`)

| Komponen | Fungsi |
|---|---|
| `BaseButton`, `BaseInput`, `BaseSelect`, `BaseModal` | Elemen form dan dialog dasar |
| `PageCard`, `StatCard` | Kartu halaman dan kartu ringkasan (dengan indikator perubahan) |
| `ConfirmModal` | Konfirmasi hapus atau batalkan |
| `ToastHost` | Notifikasi |
| `PaginationBar` | Paginasi daftar |
| `SegmentedToggle`, `PeriodPicker`, `MonthPicker` | Pemilih periode Tahunan/Bulanan |
| `ThemeToggle` | Tombol mode terang/gelap |
| `TypeBadge` | Lencana income atau expense |
| `RevealOnScroll` | Animasi muncul saat digulir (landing page) |

## Tema dan Tampilan

- Warna, font, dan animasi didefinisikan di blok `@theme` pada `assets/main.css`
- **Dark mode** berbasis class `dark` pada `<html>`, pilihan disimpan di `localStorage`.
  Skrip kecil di `index.html` menerapkan tema sebelum halaman digambar agar tidak berkedip
- Sidebar dapat disembunyikan di layar lebar (pilihan tersimpan), dan menjadi drawer di HP
- Animasi menghormati pengaturan sistem "kurangi gerakan"

## Aturan Periode

Pemilih periode (Dashboard, Laporan, Anggaran) tidak dapat memilih bulan atau tahun yang
belum tiba. Batas awalnya diambil dari data paling awal lewat `GET /api/period-range`
(composable `usePeriodRange`), dan dimuat ulang setelah menyimpan transaksi atau anggaran.
Tanggal pada form dan filter transaksi dibatasi sampai hari ini (`utils/period.js`).

## Troubleshooting

| Masalah | Penyebab dan solusi |
|---|---|
| Request ke `undefined/login` | `.env` belum dibuat, atau dev server belum di-restart |
| Error CORS | Backend belum di-restart setelah mengubah `config/cors.php` |
| `Network Error` | Backend belum berjalan di port 8000 |
| Gaya Tailwind tidak muncul | `@import "tailwindcss"` belum ada di `main.css` atau plugin belum ada di `vite.config.js` |
| `Failed to resolve component: X` | Komponen atau ikon `X` dipakai di template tetapi belum diimpor |
| Mode gelap tidak berubah | `@custom-variant dark` belum ada di `main.css` |
| Unduhan Excel atau lampiran rusak | `responseType: 'blob'` tidak terpasang pada request |