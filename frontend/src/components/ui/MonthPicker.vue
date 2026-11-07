<script setup>
import { computed, watch, onMounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { MAX_MONTH, MAX_YEAR } from '@/utils/period'
import { usePeriodRange } from '@/composables/usePeriodRange'

const model = defineModel() // 'YYYY-MM'
const { minMonth, minYear, loadRange } = usePeriodRange()

const NAMA = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const pad = (n) => String(n).padStart(2, '0')

const current = computed(() => {
  const [y, m] = (model.value || MAX_MONTH).split('-').map(Number)
  return { y, m }
})

const years = computed(() => Array.from({ length: MAX_YEAR - minYear.value + 1 }, (_, i) => MAX_YEAR - i))
const outOfRange = (ym) => ym > MAX_MONTH || ym < minMonth.value
const monthDisabled = (m) => outOfRange(`${current.value.y}-${pad(m)}`)

function set(y, m) {
  let v = `${y}-${pad(m)}`
  if (v > MAX_MONTH) v = MAX_MONTH
  if (v < minMonth.value) v = minMonth.value
  model.value = v
}

function shift(d) {
  const dt = new Date(current.value.y, current.value.m - 1 + d, 1)
  set(dt.getFullYear(), dt.getMonth() + 1)
}

const monthSel = computed({
  get: () => current.value.m,
  set: (m) => set(current.value.y, Number(m)),
})
const yearSel = computed({
  get: () => current.value.y,
  set: (y) => set(Number(y), current.value.m),
})

const atMax = computed(() => (model.value || MAX_MONTH) >= MAX_MONTH)
const atMin = computed(() => (model.value || MAX_MONTH) <= minMonth.value)

// Nilai awal dan perubahan batas selalu dijaga tetap valid
watch(
  [minMonth, () => model.value],
  () => {
    if (model.value && outOfRange(model.value)) set(current.value.y, current.value.m)
  },
  { immediate: true },
)

onMounted(() => loadRange())

const field =
  'rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100'
const arrow =
  'rounded-xl border border-gray-300 p-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'
</script>

<template>
  <div class="flex items-center gap-2">
    <button type="button" :class="arrow" :disabled="atMin" title="Bulan sebelumnya" @click="shift(-1)">
      <ChevronLeft :size="16" />
    </button>

    <select v-model="monthSel" :class="field" aria-label="Bulan">
      <option v-for="(n, i) in NAMA" :key="i" :value="i + 1" :disabled="monthDisabled(i + 1)">{{ n }}</option>
    </select>

    <select v-model="yearSel" :class="field" aria-label="Tahun">
      <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
    </select>

    <button type="button" :class="arrow" :disabled="atMax" title="Bulan berikutnya" @click="shift(1)">
      <ChevronRight :size="16" />
    </button>
  </div>
</template>