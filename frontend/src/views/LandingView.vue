<script setup>
import { ref } from 'vue'
import {
  Menu, X, ArrowRight, Tags, ArrowLeftRight, FileBarChart,
  FileSpreadsheet, LayoutDashboard, ShieldCheck,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import RevealOnScroll from '@/components/ui/RevealOnScroll.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const auth = useAuthStore()
const menuOpen = ref(false)

const links = [
  { label: 'Fitur', href: '#fitur' },
  { label: 'Cara Kerja', href: '#cara-kerja' },
]

const features = [
  { icon: Tags, title: 'Kategori & COA', text: 'Susun akun keuangan dengan kode yang rapi, dikelompokkan ke kategori income dan expense.' },
  { icon: ArrowLeftRight, title: 'Pencatatan Transaksi', text: 'Catat pemasukan dan pengeluaran. Sistem menentukan debit atau credit sesuai jenis akunnya.' },
  { icon: FileBarChart, title: 'Laporan Profit/Loss', text: 'Lihat laba rugi per bulan dalam satu tahun lengkap dengan total dan net income.' },
  { icon: FileSpreadsheet, title: 'Export Excel', text: 'Unduh laporan ke file Excel dengan format yang siap dibagikan atau dicetak.' },
  { icon: LayoutDashboard, title: 'Dashboard & Grafik', text: 'Pantau ringkasan, tren bulanan, dan komposisi pengeluaran dalam sekali pandang.' },
  { icon: ShieldCheck, title: 'Aman & Terproteksi', text: 'Data hanya bisa diakses setelah login, dilindungi autentikasi token.' },
]

const steps = [
  { n: '01', title: 'Atur kategori dan COA', text: 'Tentukan kelompok dan akun keuangan yang Anda butuhkan.' },
  { n: '02', title: 'Catat transaksi', text: 'Masukkan transaksi harian, cukup isi tanggal, akun, dan jumlah.' },
  { n: '03', title: 'Baca laporan', text: 'Dapatkan laporan Profit/Loss dan dashboard yang selalu terbarui.' },
]

// Tinggi batang pada mockup dashboard (persen)
const income = [40, 55, 35, 70, 60, 85]
const expense = [20, 30, 25, 40, 35, 45]
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <!-- Navbar -->
    <header class="fixed inset-x-0 top-0 z-30 border-b border-gray-100 dark:border-gray-800 bg-white/80 dark:bg-gray-950/80 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <RouterLink to="/" class="text-xl font-bold tracking-tight">
          Finance<span class="text-brand-500">App</span>
        </RouterLink>

        <nav class="hidden items-center gap-8 text-sm font-medium text-gray-600 dark:text-gray-300 md:flex">
          <a v-for="l in links" :key="l.href" :href="l.href" class="transition hover:text-gray-900 dark:hover:text-white">{{ l.label }}</a>
        </nav>

        <div class="flex items-center gap-2">
          <ThemeToggle />

          <div class="hidden md:block">
            <RouterLink
                :to="auth.isLoggedIn ? '/dashboard' : '/login'"
                class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
            >
                {{ auth.isLoggedIn ? 'Buka Dashboard' : 'Masuk' }}
            </RouterLink>
            </div>

            <button class="rounded-lg p-2 hover:bg-gray-100 dark:hover:bg-gray-800 md:hidden" @click="menuOpen = !menuOpen">
            <component :is="menuOpen ? X : Menu" :size="22" />
            </button>
        </div>
      </div>

      <div v-if="menuOpen" class="space-y-1 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 md:hidden">
        <a
          v-for="l in links" :key="l.href" :href="l.href" @click="menuOpen = false"
          class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
        >{{ l.label }}</a>
        <RouterLink
          :to="auth.isLoggedIn ? '/dashboard' : '/login'"
          class="mt-2 block rounded-xl bg-brand-500 px-3 py-2.5 text-center text-sm font-medium text-white"
        >
          {{ auth.isLoggedIn ? 'Buka Dashboard' : 'Masuk' }}
        </RouterLink>
      </div>
    </header>

    <main>
      <!-- Hero -->
      <section class="relative overflow-hidden px-4 pb-20 pt-32 sm:px-6 sm:pt-40">
        <!-- Cahaya latar -->
        <div class="pointer-events-none absolute left-1/2 top-20 z-10 h-80 w-[40rem] -translate-x-1/2 rounded-full bg-brand-500/20 blur-3xl" />

        <div class=" relative z-10">
            <div class="mx-auto max-w-3xl text-center">
                <span class="animate-fade-up inline-block rounded-full border border-brand-500/20 bg-brand-50 dark:bg-brand-500/15 px-4 py-1.5 text-xs font-semibold text-brand-500 motion-reduce:animate-none">
                    Berbasis Chart of Account
                </span>
                <h1 class="animate-fade-up mt-6 text-4xl font-bold leading-tight tracking-tight motion-reduce:animate-none sm:text-6xl" style="animation-delay: 100ms">
                    Kelola keuangan Anda dengan lebih rapi
                </h1>
                <p class="animate-fade-up mx-auto mt-6 max-w-xl text-lg text-gray-600 dark:text-gray-300 motion-reduce:animate-none" style="animation-delay: 200ms">
                    Catat transaksi, kelompokkan per kategori, dan dapatkan laporan Profit/Loss
                    bulanan yang bisa diunduh ke Excel.
                </p>
                <div class="animate-fade-up mt-10 flex flex-col items-center justify-center gap-3 motion-reduce:animate-none sm:flex-row" style="animation-delay: 300ms">
                    <RouterLink
                    :to="auth.isLoggedIn ? '/dashboard' : '/login'"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-3 font-medium text-white shadow-lg shadow-brand-500/25 transition hover:bg-brand-600"
                    >
                    Mulai Sekarang <ArrowRight :size="18" />
                    </RouterLink>
                    <a href="#fitur" class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-6 py-3 font-medium text-gray-700 dark:text-gray-200 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                    Lihat Fitur
                    </a>
                </div>
            </div>
        </div>

        <!-- Mockup dashboard -->
        <div class="animate-fade-up mx-auto mt-16 max-w-4xl motion-reduce:animate-none" style="animation-delay: 450ms">
          <div class="animate-float rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-2xl shadow-gray-200/60 motion-reduce:animate-none sm:p-6">
            <div class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-xl bg-emerald-50 dark:bg-emerald-500/15 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Income</p>
                <p class="mt-1 text-lg font-semibold text-emerald-700 dark:text-emerald-400">Rp 51.000.000</p>
              </div>
              <div class="rounded-xl bg-orange-50 dark:bg-orange-500/15 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Expense</p>
                <p class="mt-1 text-lg font-semibold text-orange-700 dark:text-orange-400">Rp 9.800.000</p>
              </div>
              <div class="rounded-xl bg-brand-50 dark:bg-brand-500/15 p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">Net Income</p>
                <p class="mt-1 text-lg font-semibold text-brand-500">Rp 41.200.000</p>
              </div>
            </div>

            <div class="mt-4 flex h-40 items-end gap-3 rounded-xl border border-gray-100 dark:border-gray-800 p-4 sm:h-52 sm:gap-5">
              <div v-for="(v, i) in income" :key="i" class="flex h-full flex-1 items-end gap-1">
                <div class="w-1/2 rounded-t bg-emerald-400" :style="{ height: v + '%' }" />
                <div class="w-1/2 rounded-t bg-orange-400" :style="{ height: expense[i] + '%' }" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Fitur -->
      <section id="fitur" class="scroll-mt-16 bg-gray-50 dark:bg-gray-950 px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-6xl">
          <RevealOnScroll>
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Semua yang Anda butuhkan</h2>
              <p class="mt-4 text-gray-600 dark:text-gray-300">Fitur inti pengelolaan keuangan, dibuat sederhana dan terhubung satu sama lain.</p>
            </div>
          </RevealOnScroll>

          <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <RevealOnScroll v-for="(f, i) in features" :key="f.title" :delay="(i % 3) * 100" class="h-full">
              <div class="h-full rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <div class="inline-flex rounded-xl bg-brand-50 dark:bg-brand-500/15 p-3 text-brand-500">
                  <component :is="f.icon" :size="22" />
                </div>
                <h3 class="mt-5 text-lg font-semibold">{{ f.title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ f.text }}</p>
              </div>
            </RevealOnScroll>
          </div>
        </div>
      </section>

      <!-- Cara kerja -->
      <section id="cara-kerja" class="scroll-mt-16 px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-6xl">
          <RevealOnScroll>
            <div class="mx-auto max-w-2xl text-center">
              <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Cara kerjanya</h2>
              <p class="mt-4 text-gray-600 dark:text-gray-300">Tiga langkah dari pencatatan sampai laporan.</p>
            </div>
          </RevealOnScroll>

          <div class="mt-14 grid gap-6 md:grid-cols-3">
            <RevealOnScroll v-for="(s, i) in steps" :key="s.n" :delay="i * 120" class="h-full">
              <div class="h-full rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
                <span class="text-4xl font-bold text-brand-500/30">{{ s.n }}</span>
                <h3 class="mt-4 text-lg font-semibold">{{ s.title }}</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ s.text }}</p>
              </div>
            </RevealOnScroll>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="px-4 pb-20 sm:px-6">
        <RevealOnScroll>
          <div class="mx-auto max-w-4xl rounded-3xl bg-sidebar px-6 py-14 text-center text-white sm:px-12">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Siap merapikan keuangan Anda?</h2>
            <p class="mx-auto mt-4 max-w-xl text-gray-300">Masuk dan mulai mencatat transaksi pertama Anda.</p>
            <RouterLink
              :to="auth.isLoggedIn ? '/dashboard' : '/login'"
              class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-medium text-gray-900 transition hover:bg-gray-100"
            >
              {{ auth.isLoggedIn ? 'Buka Dashboard' : 'Masuk ke Aplikasi' }} <ArrowRight :size="18" />
            </RouterLink>
          </div>
        </RevealOnScroll>
      </section>
    </main>

    <footer class="border-t border-gray-100 dark:border-gray-800 px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400 sm:px-6">
      © {{ new Date().getFullYear() }} FinanceApp.
    </footer>
  </div>
</template>