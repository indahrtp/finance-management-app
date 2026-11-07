<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { errorMessage } from '@/utils/errors'
import { angka, namaBulan } from '@/utils/format'

const props = defineProps({ month: String }) // 'YYYY-MM'
const toast = useToast()

const data = ref(null)
const loading = ref(false)
const open = reactive({}) // category_id -> terbuka

const curLabel = computed(() => (props.month ? namaBulan(props.month, 0, 'short') : ''))
const prevLabel = computed(() => (props.month ? namaBulan(props.month, -1, 'short') : ''))

async function load() {
  if (!props.month) return
  loading.value = true
  try {
    data.value = (await api.get('/reports/profit-loss/monthly', { params: { month: props.month } })).data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

const hasData = computed(() => {
  const r = data.value
  return !!r && (r.total_income.amount !== 0 || r.total_expense.amount !== 0 || r.total_income.previous !== 0 || r.total_expense.previous !== 0)
})

const allLines = computed(() => (data.value ? [...data.value.income, ...data.value.expense].filter((l) => l.accounts.length) : []))
const allOpen = computed(() => allLines.value.length > 0 && allLines.value.every((l) => open[l.category_id]))
function toggleAll() {
  const to = !allOpen.value
  allLines.value.forEach((l) => (open[l.category_id] = to))
}

const rows = computed(() => {
  const r = data.value
  if (!r) return []
  const out = []

  const addGroup = (tone, label, lines, total, totalLabel) => {
    out.push({ kind: 'section', key: `s-${tone}`, tone, label })
    for (const l of lines) {
      out.push({
        kind: 'category', key: `c${l.category_id}`, id: l.category_id, tone, label: l.category,
        amount: l.amount, previous: l.previous, change: l.change, share: l.share, expandable: l.accounts.length > 0,
      })
      if (open[l.category_id]) {
        for (const a of l.accounts) {
          out.push({ kind: 'account', key: `a${a.coa_id}`, tone, label: `${a.code} · ${a.name}`, amount: a.amount, previous: a.previous, change: a.change })
        }
      }
    }
    out.push({ kind: 'total', key: `t-${tone}`, tone, label: totalLabel, ...total })
  }

  addGroup('income', 'Pendapatan', r.income, r.total_income, 'Total Income')
  addGroup('expense', 'Pengeluaran', r.expense, r.total_expense, 'Total Expense')
  out.push({ kind: 'net', key: 'net', tone: 'net', label: 'Net Income', ...r.net_income })

  return out
})

const show = (v) => (v === 0 ? '–' : angka(v))
const showChange = (c) => (c === null ? '–' : `${c > 0 ? '+' : ''}${c}%`)

// Pengeluaran naik = buruk (merah), income dan net naik = baik (hijau)
function changeCls(r) {
  if (r.change === null || r.change === 0) return 'text-gray-400'
  const goodUp = r.tone !== 'expense'
  return r.change > 0 === goodUp ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'
}

function rowBg(r) {
  if (r.kind === 'total') return r.tone === 'income' ? 'bg-emerald-50 dark:bg-emerald-950' : 'bg-orange-50 dark:bg-orange-950'
  if (r.kind === 'net') return 'bg-gray-100 dark:bg-gray-800'
  if (r.kind === 'account') return 'bg-gray-50 dark:bg-gray-950'
  return 'bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800'
}

function amountCls(r) {
  if (r.kind === 'net') return r.amount < 0 ? 'font-bold text-red-600 dark:text-red-400' : 'font-bold text-gray-900 dark:text-white'
  if (r.kind === 'total') return r.tone === 'income' ? 'font-semibold text-emerald-700 dark:text-emerald-300' : 'font-semibold text-orange-700 dark:text-orange-300'
  if (r.kind === 'account') return 'text-gray-500 dark:text-gray-400'
  return r.amount === 0 ? 'text-gray-300 dark:text-gray-600' : 'font-medium text-gray-800 dark:text-gray-100'
}

watch(() => props.month, load)
onMounted(load)
</script>

<template>
  <div>
    <p v-if="loading && !data" class="py-8 text-center text-gray-400">Memuat...</p>

    <template v-else-if="data">
      <div class="mb-3 flex items-center justify-between gap-3">
        <p v-if="!hasData" class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          Belum ada transaksi posted pada {{ curLabel }} maupun {{ prevLabel }}.
        </p>
        <span v-else />
        <button v-if="allLines.length" type="button" class="shrink-0 text-xs font-medium text-brand-500 hover:underline" @click="toggleAll">
          {{ allOpen ? 'Tutup semua rincian' : 'Buka semua rincian' }}
        </button>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800" :class="{ 'opacity-60': loading }">
        <table class="w-full min-w-[640px] border-separate border-spacing-0 text-sm">
          <thead>
            <tr class="bg-gray-50 text-xs font-medium uppercase tracking-wider text-gray-500 dark:bg-gray-900 dark:text-gray-400">
              <th class="border-b border-gray-200 px-5 py-3 text-left dark:border-gray-800">Kategori / Akun</th>
              <th class="border-b border-gray-200 px-3 py-3 text-right dark:border-gray-800">{{ curLabel }}</th>
              <th class="border-b border-gray-200 px-3 py-3 text-right dark:border-gray-800">% dari total</th>
              <th class="border-b border-gray-200 px-3 py-3 text-right dark:border-gray-800">{{ prevLabel }}</th>
              <th class="border-b border-gray-200 px-5 py-3 text-right dark:border-gray-800">Selisih</th>
            </tr>
          </thead>

          <tbody>
            <template v-for="r in rows" :key="r.key">
              <tr v-if="r.kind === 'section'">
                <td colspan="5" class="border-b border-gray-100 bg-white px-5 pb-2 pt-5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500">
                  <span class="inline-flex items-center gap-2">
                    <span class="size-2 rounded-full" :class="r.tone === 'income' ? 'bg-emerald-500' : 'bg-orange-500'" />
                    {{ r.label }}
                  </span>
                </td>
              </tr>

              <tr
                v-else :class="[rowBg(r), r.kind === 'category' && r.expandable ? 'cursor-pointer' : '']"
                @click="r.kind === 'category' && r.expandable && (open[r.id] = !open[r.id])"
              >
                <td class="border-b border-gray-100 px-5 py-2.5 dark:border-gray-800" :class="r.kind === 'account' ? 'pl-12 text-gray-500 dark:text-gray-400' : amountCls(r)">
                  <span class="inline-flex items-center gap-1.5">
                    <ChevronRight
                      v-if="r.kind === 'category'" :size="14"
                      class="shrink-0 text-gray-400 transition-transform" :class="[open[r.id] ? 'rotate-90' : '', r.expandable ? '' : 'invisible']"
                    />
                    {{ r.label }}
                  </span>
                </td>
                <td class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums dark:border-gray-800" :class="amountCls(r)">{{ show(r.amount) }}</td>
                <td class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums text-gray-500 dark:border-gray-800 dark:text-gray-400">
                  {{ r.kind === 'category' && r.share !== null ? `${r.share}%` : '' }}
                </td>
                <td class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums text-gray-500 dark:border-gray-800 dark:text-gray-400">{{ show(r.previous) }}</td>
                <td class="border-b border-gray-100 px-5 py-2.5 text-right tabular-nums font-medium dark:border-gray-800" :class="changeCls(r)">{{ showChange(r.change) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        Hanya transaksi posted. Klik kategori untuk melihat rincian per COA. Selisih dibanding {{ prevLabel }}: pengeluaran yang naik ditandai merah.
        Tombol Export Excel mengunduh laporan setahun penuh.
      </p>
    </template>
  </div>
</template>