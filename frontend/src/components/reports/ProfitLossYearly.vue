<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { errorMessage } from '@/utils/errors'
import { angka, BULAN } from '@/utils/format'

const props = defineProps({ year: Number })
const toast = useToast()

const report = ref(null)
const loading = ref(false)

async function load() {
  loading.value = true
  try {
    report.value = (await api.get('/reports/profit-loss', { params: { year: props.year } })).data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

const hasData = computed(() => {
  const r = report.value
  return !!r && (r.total_income.total !== 0 || r.total_expense.total !== 0)
})

const rows = computed(() => {
  const r = report.value
  if (!r) return []
  const line = (l, p) => ({ kind: 'line', key: p + l.category_id, label: l.category, months: l.months, total: l.total })
  return [
    { kind: 'section', tone: 'income', key: 's-income', label: 'Pendapatan' },
    ...r.income.map((l) => line(l, 'i')),
    { kind: 'total', tone: 'income', key: 't-income', label: 'Total Income', ...r.total_income },
    { kind: 'section', tone: 'expense', key: 's-expense', label: 'Pengeluaran' },
    ...r.expense.map((l) => line(l, 'e')),
    { kind: 'total', tone: 'expense', key: 't-expense', label: 'Total Expense', ...r.total_expense },
    { kind: 'net', key: 'net', label: 'Net Income', ...r.net_income },
  ]
})

// Latar harus opaque karena kolom pertama bersifat sticky
function cellBg(r) {
  if (r.kind === 'total') return r.tone === 'income' ? 'bg-emerald-50 dark:bg-emerald-950' : 'bg-orange-50 dark:bg-orange-950'
  if (r.kind === 'net') return 'bg-gray-100 dark:bg-gray-800'
  return 'bg-white group-hover:bg-gray-50 dark:bg-gray-900 dark:group-hover:bg-gray-800'
}

function cellText(r, v) {
  if (r.kind === 'net') return v < 0 ? 'font-bold text-red-600 dark:text-red-400' : 'font-bold text-gray-900 dark:text-white'
  if (r.kind === 'total') {
    return r.tone === 'income' ? 'font-semibold text-emerald-700 dark:text-emerald-300' : 'font-semibold text-orange-700 dark:text-orange-300'
  }
  return v === 0 ? 'text-gray-300 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300'
}

const show = (v) => (v === 0 ? '–' : angka(v))

watch(() => props.year, load)
onMounted(load)
</script>

<template>
  <div>
    <p v-if="loading && !report" class="py-8 text-center text-gray-400">Memuat...</p>

    <template v-else-if="report">
      <p v-if="!hasData" class="mb-4 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
        Belum ada transaksi posted pada tahun {{ year }}.
      </p>

      <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800" :class="{ 'opacity-60': loading }">
        <table class="w-full min-w-[1100px] border-separate border-spacing-0 text-sm">
          <thead>
            <tr>
              <th class="sticky left-0 z-10 border-b border-gray-200 bg-gray-50 px-5 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">Kategori</th>
              <th
                v-for="m in report.months" :key="m"
                class="border-b border-gray-200 bg-gray-50 px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400"
              >{{ BULAN[m - 1] }}</th>
              <th class="border-b border-gray-200 bg-gray-50 px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">Total</th>
            </tr>
          </thead>

          <tbody>
            <template v-for="r in rows" :key="r.key">
              <tr v-if="r.kind === 'section'">
                <td colspan="14" class="border-b border-gray-100 bg-white px-5 pb-2 pt-5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500">
                  <span class="sticky left-5 inline-flex items-center gap-2">
                    <span class="size-2 rounded-full" :class="r.tone === 'income' ? 'bg-emerald-500' : 'bg-orange-500'" />
                    {{ r.label }}
                  </span>
                </td>
              </tr>

              <tr v-else class="group">
                <td
                  class="sticky left-0 z-[1] border-b border-gray-100 px-5 py-2.5 dark:border-gray-800"
                  :class="[cellBg(r), r.kind === 'line' ? 'text-gray-800 dark:text-gray-100' : cellText(r, 1)]"
                >{{ r.label }}</td>
                <td
                  v-for="(v, i) in r.months" :key="i"
                  class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums dark:border-gray-800"
                  :class="[cellBg(r), cellText(r, v)]"
                >{{ show(v) }}</td>
                <td
                  class="border-b border-gray-100 px-5 py-2.5 text-right tabular-nums dark:border-gray-800"
                  :class="[cellBg(r), cellText(r, r.total), r.kind === 'line' ? 'font-medium' : '']"
                >{{ show(r.total) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        Hanya transaksi berstatus posted. Income = credit − debit, Expense = debit − credit. Geser ke samping untuk melihat semua bulan.
      </p>
    </template>
  </div>
</template>