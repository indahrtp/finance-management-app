<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { TrendingUp, TrendingDown, Scale } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { useTheme } from '@/composables/useTheme'
import { errorMessage } from '@/utils/errors'
import { rupiah, tanggal, BULAN, namaBulan } from '@/utils/format'
import PageCard from '@/components/ui/PageCard.vue'
import StatCard from '@/components/ui/StatCard.vue'
import PeriodPicker from '@/components/ui/PeriodPicker.vue'

const toast = useToast()
const { isDark } = useTheme()

const now = new Date()
const mode = ref('year')
const year = ref(now.getFullYear())
const month = ref(now.toLocaleDateString('sv-SE').slice(0, 7))

const data = ref(null)
const loading = ref(false)

const periodLabel = computed(() => (mode.value === 'year' ? String(year.value) : month.value ? namaBulan(month.value) : ''))

async function load() {
  if (mode.value === 'month' && !month.value) return
  loading.value = true
  try {
    const params = mode.value === 'month' ? { month: month.value } : { year: year.value }
    data.value = (await api.get('/dashboard', { params })).data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

const ringkas = (n) => {
  const v = Math.abs(n)
  if (v >= 1e9) return `${+(n / 1e9).toFixed(1)} M`
  if (v >= 1e6) return `${+(n / 1e6).toFixed(1)} jt`
  if (v >= 1e3) return `${+(n / 1e3).toFixed(0)} rb`
  return String(n)
}

// Grafik batang: per bulan (mode tahunan) atau per hari (mode bulanan)
const isDay = computed(() => data.value?.chart.granularity === 'day')
const barSeries = computed(() => [
  { name: 'Income', data: data.value?.chart.income ?? [] },
  { name: 'Expense', data: data.value?.chart.expense ?? [] },
])
const barOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDark.value ? 'dark' : 'light' },
  colors: ['#10b981', '#f97316'],
  plotOptions: { bar: { columnWidth: isDay.value ? '80%' : '55%', borderRadius: isDay.value ? 2 : 4 } },
  dataLabels: { enabled: false },
  stroke: { show: true, width: isDay.value ? 1 : 2, colors: ['transparent'] },
  xaxis: {
    categories: isDay.value ? (data.value?.chart.labels ?? []).map(String) : BULAN,
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { formatter: ringkas } },
  grid: { borderColor: isDark.value ? '#1f2937' : '#f3f4f6' },
  legend: { position: 'top', horizontalAlign: 'right' },
  tooltip: { y: { formatter: (v) => rupiah(v) } },
}))

const breakdown = computed(() => (data.value?.expense_breakdown ?? []).filter((x) => x.total > 0))
const donutSeries = computed(() => breakdown.value.map((x) => x.total))
const donutOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDark.value ? 'dark' : 'light' },
  labels: breakdown.value.map((x) => x.category),
  colors: ['#465fff', '#f97316', '#10b981', '#eab308', '#ef4444', '#8b5cf6'],
  stroke: { colors: [isDark.value ? '#111827' : '#ffffff'] },
  legend: { position: 'bottom' },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '68%' } } },
  tooltip: { y: { formatter: (v) => rupiah(v) } },
}))

watch([mode, year, month], load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ periodLabel }}</p>
      </div>
      <PeriodPicker v-model:mode="mode" v-model:year="year" v-model:month="month" />
    </div>

    <p v-if="loading && !data" class="py-12 text-center text-gray-400">Memuat...</p>

    <template v-else-if="data">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" :class="{ 'opacity-60': loading }">
        <StatCard
          title="Total Income" :value="rupiah(data.summary.total_income)" :hint="periodLabel"
          :change="data.comparison.income" :change-label="data.comparison.label" good-when="up" tone="green"
        >
          <template #icon><TrendingUp :size="22" /></template>
        </StatCard>
        <StatCard
          title="Total Expense" :value="rupiah(data.summary.total_expense)" :hint="periodLabel"
          :change="data.comparison.expense" :change-label="data.comparison.label" good-when="down" tone="orange"
        >
          <template #icon><TrendingDown :size="22" /></template>
        </StatCard>
        <StatCard
          title="Net Income" :value="rupiah(data.summary.balance)" :hint="periodLabel"
          :change="data.comparison.balance" :change-label="data.comparison.label" good-when="up" tone="brand"
        >
          <template #icon><Scale :size="22" /></template>
        </StatCard>
      </div>

      <div class="grid gap-6 xl:grid-cols-3" :class="{ 'opacity-60': loading }">
        <PageCard :title="`Income vs Expense ${isDay ? 'per Hari' : 'per Bulan'}`" class="xl:col-span-2">
          <VueApexCharts :key="`bar-${isDark}-${data.mode}`" type="bar" height="320" :options="barOptions" :series="barSeries" />
        </PageCard>

        <PageCard title="Komposisi Pengeluaran">
          <VueApexCharts
            v-if="donutSeries.length" :key="`donut-${isDark}-${data.mode}`"
            type="donut" height="320" :options="donutOptions" :series="donutSeries"
          />
          <p v-else class="flex h-[320px] items-center justify-center text-sm text-gray-400">Belum ada pengeluaran.</p>
        </PageCard>
      </div>

      <PageCard title="Transaksi Terbaru">
        <template #actions>
          <RouterLink to="/transactions" class="text-sm font-medium text-brand-500 hover:underline">Lihat semua</RouterLink>
        </template>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
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
              <tr v-for="t in data.recent_transactions" :key="t.id" class="border-b border-gray-100 last:border-0 dark:border-gray-800">
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