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
frontend/
├── public/
├── src/
│   ├── assets/
│   │   └── main.css
│   ├── components/
│   │   ├── ui/
│   │   │   ├── BaseButton.vue
│   │   │   ├── BaseInput.vue
│   │   │   ├── BaseModal.vue
│   │   │   ├── BaseSelect.vue
│   │   │   ├── ConfirmModal.vue
│   │   │   ├── MonthPicker.vue
│   │   │   ├── PageCard.vue
│   │   │   ├── PaginationBar.vue
│   │   │   ├── PeriodPicker.vue
│   │   │   ├── RevealOnScroll.vue
│   │   │   ├── SegmentedToggle.vue
│   │   │   ├── StatCard.vue
│   │   │   ├── ThemeToggle.vue
│   │   │   ├── ToastHost.vue
│   │   │   └── TypeBadge.vue
│   │   └── reports/
│   │       ├── ProfitLossMonthly.vue
│   │       └── ProfitLossYearly.vue
│   ├── composables/
│   │   ├── usePeriodRange.js
│   │   ├── useTheme.js
│   │   └── useToast.js
│   ├── layouts/
│   │   ├── AppLayout.vue
│   │   └── menu.js
│   ├── router/
│   │   └── index.js
│   ├── services/
│   │   └── api.js
│   ├── stores/
│   │   └── auth.js
│   ├── utils/
│   │   ├── download.js
│   │   ├── errors.js
│   │   ├── format.js
│   │   └── period.js
│   ├── views/
│   │   ├── BudgetView.vue
│   │   ├── CategoryView.vue
│   │   ├── CoaView.vue
│   │   ├── DashboardView.vue
│   │   ├── LandingView.vue
│   │   ├── LoginView.vue
│   │   ├── ProfitLossView.vue
│   │   ├── TransactionView.vue
│   │   └── WalletView.vue
│   ├── App.vue
│   └── main.js
├── .env.example
├── index.html
├── package.json
└── vite.config.js
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

## Alur Data

- `services/api.js` menyisipkan token Bearer dari `localStorage` pada setiap request. Jika
  API membalas `401` (selain di halaman login), sesi dihapus dan pengguna diarahkan ke
  `/login`.
- Error validasi `422` dipetakan ke pesan di bawah masing-masing kolom lewat
  `utils/errors.js`. Error lain tampil sebagai toast (`useToast`).
- Unduhan file (Excel, lampiran) memakai Axios dengan `responseType: 'blob'` lalu
  `utils/download.js`, karena tautan biasa tidak membawa token.

## Komponen UI

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