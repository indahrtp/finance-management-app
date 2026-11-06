<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Download, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { errorMessage } from '@/utils/errors'
import { angka, BULAN } from '@/utils/format'
import { saveBlob } from '@/utils/download'
import PageCard from '@/components/ui/PageCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const toast = useToast()

const thisYear = new Date().getFullYear()
const year = ref(thisYear)
const report = ref(null)
const loading = ref(false)
const exporting = ref(false)

// 2020 sampai tahun depan, terbaru di atas
const yearOptions = Array.from({ length: thisYear + 1 - 2020 + 1 }, (_, i) => thisYear + 1 - i)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/reports/profit-loss', { params: { year: year.value } })
    report.value = data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

async function exportExcel() {
  exporting.value = true

  try {
    const res = await api.get('/reports/profit-loss/export', {
      params: { year: year.value },
      responseType: 'blob',
    })

    console.log('Export status:', res.status)
    console.log('Content-Type:', res.headers['content-type'])
    console.log('File size:', res.data.size)

    const contentType = res.headers['content-type']

    if (
      !contentType?.includes(
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      )
    ) {
      const errorText = await res.data.text()
      throw new Error(errorText || 'Response bukan file Excel.')
    }

    saveBlob(
      res.data,
      `laporan-profit-loss-${year.value}.xlsx`
    )

    toast.success('File Excel berhasil diunduh.')
  } catch (e) {
    console.error('Export error:', e)

    let message = e.message || 'Gagal mengunduh file Excel.'

    if (e.response?.data instanceof Blob) {
      const errorText = await e.response.data.text()

      try {
        const errorJson = JSON.parse(errorText)
        message = errorJson.message || message
      } catch {
        message = errorText || message
      }
    }

    console.error('Error detail:', message)
    toast.error(message)
  } finally {
    exporting.value = false
  }
}

const hasData = computed(() => {
  const r = report.value
  return !!r && (r.total_income.total !== 0 || r.total_expense.total !== 0)
})

// Warna negatif untuk net income
const negative = (n) => (n < 0 ? 'text-red-600' : '')

watch(year, load)
onMounted(load)
</script>

<template>
  <PageCard :title="`Laporan Profit/Loss ${year}`">
    <template #actions>
      <div class="flex flex-wrap items-center gap-2">
        <button class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50" title="Tahun sebelumnya" @click="year--">
          <ChevronLeft :size="16" />
        </button>
        <select
          v-model="year"
          class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50" title="Tahun berikutnya" @click="year++">
          <ChevronRight :size="16" />
        </button>

        <BaseButton :loading="exporting" @click="exportExcel">
          <Download :size="16" /> {{ exporting ? 'Mengunduh...' : 'Export Excel' }}
        </BaseButton>
      </div>
    </template>

    <p v-if="loading && !report" class="py-8 text-center text-gray-400">Memuat...</p>

    <template v-else-if="report">
      <p v-if="!hasData" class="mb-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-500">
        Belum ada transaksi pada tahun {{ year }}.
      </p>

      <div class="overflow-x-auto rounded-lg border border-gray-200" :class="{ 'opacity-60': loading }">
        <table class="w-full min-w-[1100px] border-collapse text-sm">
          <thead>
            <tr class="bg-yellow-300 text-left">
              <th class="sticky left-0 z-10 bg-yellow-300 px-4 py-3 font-semibold">Kategori</th>
              <th v-for="m in report.months" :key="m" class="px-3 py-3 text-right font-semibold">
                {{ BULAN[m - 1] }}
              </th>
              <th class="px-4 py-3 text-right font-semibold">Total</th>
            </tr>
          </thead>

          <tbody>
            <!-- Income -->
            <tr v-for="l in report.income" :key="'i' + l.category_id" class="bg-green-100">
              <td class="sticky left-0 bg-green-100 px-4 py-2.5">{{ l.category }}</td>
              <td v-for="(v, i) in l.months" :key="i" class="px-3 py-2.5 text-right tabular-nums" :class="{ 'text-gray-400': v === 0 }">
                {{ angka(v) }}
              </td>
              <td class="px-4 py-2.5 text-right font-medium tabular-nums">{{ angka(l.total) }}</td>
            </tr>
            <tr class="bg-green-300 font-semibold">
              <td class="sticky left-0 bg-green-300 px-4 py-2.5">Total Income</td>
              <td v-for="(v, i) in report.total_income.months" :key="i" class="px-3 py-2.5 text-right tabular-nums">
                {{ angka(v) }}
              </td>
              <td class="px-4 py-2.5 text-right tabular-nums">{{ angka(report.total_income.total) }}</td>
            </tr>

            <!-- Expense -->
            <tr v-for="l in report.expense" :key="'e' + l.category_id" class="bg-orange-100">
              <td class="sticky left-0 bg-orange-100 px-4 py-2.5">{{ l.category }}</td>
              <td v-for="(v, i) in l.months" :key="i" class="px-3 py-2.5 text-right tabular-nums" :class="{ 'text-gray-400': v === 0 }">
                {{ angka(v) }}
              </td>
              <td class="px-4 py-2.5 text-right font-medium tabular-nums">{{ angka(l.total) }}</td>
            </tr>
            <tr class="bg-orange-300 font-semibold">
              <td class="sticky left-0 bg-orange-300 px-4 py-2.5">Total Expense</td>
              <td v-for="(v, i) in report.total_expense.months" :key="i" class="px-3 py-2.5 text-right tabular-nums">
                {{ angka(v) }}
              </td>
              <td class="px-4 py-2.5 text-right tabular-nums">{{ angka(report.total_expense.total) }}</td>
            </tr>

            <!-- Net income -->
            <tr class="border-t-2 border-gray-300 bg-white font-bold">
              <td class="sticky left-0 bg-white px-4 py-3">Net Income</td>
              <td v-for="(v, i) in report.net_income.months" :key="i" class="px-3 py-3 text-right tabular-nums" :class="negative(v)">
                {{ angka(v) }}
              </td>
              <td class="px-4 py-3 text-right tabular-nums" :class="negative(report.net_income.total)">
                {{ angka(report.net_income.total) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="mt-3 text-xs text-gray-500">
        Income = credit − debit, Expense = debit − credit. Digeser ke samping untuk melihat semua bulan.
      </p>
    </template>
  </PageCard>
</template>