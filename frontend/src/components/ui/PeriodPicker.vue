<script setup>
import { computed, onMounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import SegmentedToggle from './SegmentedToggle.vue'
import MonthPicker from './MonthPicker.vue'
import { MAX_YEAR } from '@/utils/period'
import { usePeriodRange } from '@/composables/usePeriodRange'

const mode = defineModel('mode') // 'year' | 'month'
const year = defineModel('year')
const month = defineModel('month') // 'YYYY-MM'

const modes = [
  { value: 'year', label: 'Tahunan' },
  { value: 'month', label: 'Bulanan' },
]

const { minYear, loadRange } = usePeriodRange()

const yearOptions = computed(() => Array.from({ length: MAX_YEAR - minYear.value + 1 }, (_, i) => MAX_YEAR - i))
const atMax = computed(() => year.value >= MAX_YEAR)
const atMin = computed(() => year.value <= minYear.value)

function shiftYear(d) {
  const next = year.value + d
  if (next > MAX_YEAR || next < minYear.value) return
  year.value = next
}

onMounted(() => loadRange())

const field =
  'rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100'
const arrow =
  'rounded-xl border border-gray-300 p-2 text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <SegmentedToggle v-model="mode" :options="modes" />

    <div v-if="mode === 'year'" class="flex items-center gap-2">
      <button type="button" :class="arrow" :disabled="atMin" title="Tahun sebelumnya" @click="shiftYear(-1)">
        <ChevronLeft :size="16" />
      </button>
      <select v-model="year" :class="field" aria-label="Tahun">
        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
      </select>
      <button type="button" :class="arrow" :disabled="atMax" title="Tahun berikutnya" @click="shiftYear(1)">
        <ChevronRight :size="16" />
      </button>
    </div>

    <MonthPicker v-else v-model="month" />
  </div>
</template>