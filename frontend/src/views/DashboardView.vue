<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { TrendingUp, TrendingDown, Wallet, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useTheme } from '@/composables/useTheme'
import { errorMessage } from '@/utils/errors'
import { rupiah, tanggal, BULAN } from '@/utils/format'
import PageCard from '@/components/ui/PageCard.vue'
import StatCard from '@/components/ui/StatCard.vue'

const { isDark } = useTheme()
const toast = useToast()

const thisYear = new Date().getFullYear()
const year = ref(thisYear)
const data = ref(null)
const loading = ref(false)

const yearOptions = Array.from({ length: thisYear + 1 - 2020 + 1 }, (_, i) => thisYear + 1 - i)

async function load() {
  loading.value = true
  try {
    const res = await api.get('/dashboard', { params: { year: year.value } })
    data.value = res.data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

// Format sumbu: 1.500.000 -> "1,5 jt"
const ringkas = (n) => {
  const v = Math.abs(n)
  if (v >= 1e9) return `${+(n / 1e9).toFixed(1)} M`
  if (v >= 1e6) return `${+(n / 1e6).toFixed(1)} jt`
  if (v >= 1e3) return `${+(n / 1e3).toFixed(0)} rb`
  return String(n)
}

// Grafik batang: income vs expense per bulan
const barSeries = computed(() => [
  { name: 'Income', data: data.value?.monthly.income ?? [] },
  { name: 'Expense', data: data.value?.monthly.expense ?? [] },
])

const barOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDark.value ? 'dark' : 'light' },
  colors: ['#10b981', '#f97316'],
  plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
  dataLabels: { enabled: false },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  xaxis: { categories: BULAN, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { formatter: ringkas } },
  grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6' },
  legend: { position: 'top', horizontalAlign: 'right' },
  tooltip: { y: { formatter: (v) => rupiah(v) } },
}))

// Donut: komposisi pengeluaran
const breakdown = computed(() => (data.value?.expense_breakdown ?? []).filter((x) => x.total > 0))
const donutSeries = computed(() => breakdown.value.map((x) => x.total))
const donutOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDark.value ? 'dark' : 'light' },
  stroke: { colors: [isDark.value ? '#111827' : '#ffffff'] },
  labels: breakdown.value.map((x) => x.category),
  colors: ['#465fff', '#f97316', '#10b981', '#eab308', '#ef4444', '#8b5cf6'],
  legend: { position: 'bottom' },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '68%' } } },
  tooltip: { y: { formatter: (v) => rupiah(v) } },
}))

watch(year, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <!-- Judul + pemilih tahun -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">Dashboard</h1>
      <div class="flex items-center gap-2">
        <button class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 hover:bg-gray-50 dark:hover:bg-gray-800" @click="year--">
          <ChevronLeft :size="16" />
        </button>
        <select
          v-model="year"
          class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 hover:bg-gray-50 dark:hover:bg-gray-800" @click="year++">
          <ChevronRight :size="16" />
        </button>
      </div>
    </div>

    <p v-if="loading && !data" class="py-12 text-center text-gray-400">Memuat...</p>

    <template v-else-if="data">
      <!-- Kartu ringkasan -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" :class="{ 'opacity-60': loading }">
        <StatCard title="Total Income" :value="rupiah(data.summary.total_income)" :hint="`Tahun ${data.year}`" tone="green">
          <template #icon><TrendingUp :size="22" /></template>
        </StatCard>
        <StatCard title="Total Expense" :value="rupiah(data.summary.total_expense)" :hint="`Tahun ${data.year}`" tone="orange">
          <template #icon><TrendingDown :size="22" /></template>
        </StatCard>
        <StatCard title="Saldo (Net Income)" :value="rupiah(data.summary.balance)" hint="Income dikurangi expense" tone="brand">
          <template #icon><Wallet :size="22" /></template>
        </StatCard>
      </div>

      <!-- Grafik -->
      <div class="grid gap-6 xl:grid-cols-3" :class="{ 'opacity-60': loading }">
        <PageCard :title="`Income vs Expense ${data.year}`" class="xl:col-span-2">
          <VueApexCharts :key="String(isDark)" type="bar" height="320" :options="barOptions" :series="barSeries" />
        </PageCard>

        <PageCard title="Komposisi Pengeluaran">
          <VueApexCharts :key="String(isDark)" v-if="donutSeries.length" type="donut" height="320" :options="donutOptions" :series="donutSeries" />
          <p v-else class="flex h-[320px] items-center justify-center text-sm text-gray-400">
            Belum ada pengeluaran.
          </p>
        </PageCard>
      </div>

      <!-- Transaksi terbaru -->
      <PageCard title="Transaksi Terbaru">
        <template #actions>
          <RouterLink to="/transactions" class="text-sm font-medium text-brand-500 hover:underline">
            Lihat semua
          </RouterLink>
        </template>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400">
              <tr>
                <th class="py-3 pr-4 font-medium">Tanggal</th>
                <th class="py-3 pr-4 font-medium">COA</th>
                <th class="py-3 pr-4 font-medium">Deskripsi</th>
                <th class="py-3 text-right font-medium">Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!data.recent_transactions.length">
                <td colspan="4" class="py-8 text-center text-gray-400">Belum ada transaksi.</td>
              </tr>
              <tr v-for="t in data.recent_transactions" :key="t.id" class="border-b border-gray-100 dark:border-gray-800 last:border-0">
                <td class="whitespace-nowrap py-3 pr-4">{{ tanggal(t.date) }}</td>
                <td class="py-3 pr-4">{{ t.coa?.name }}</td>
                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ t.description || '-' }}</td>
                <td
                  class="whitespace-nowrap py-3 text-right font-medium"
                  :class="Number(t.credit) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'"
                >
                  {{ Number(t.credit) > 0 ? '+' : '-' }} {{ rupiah(Number(t.credit) > 0 ? t.credit : t.debit) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </PageCard>
    </template>
  </div>
</template>