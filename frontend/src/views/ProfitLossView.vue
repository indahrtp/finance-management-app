<script setup>
import { ref, computed } from 'vue'
import { Download } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { namaBulan } from '@/utils/format'
import { saveBlob } from '@/utils/download'
import PageCard from '@/components/ui/PageCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import PeriodPicker from '@/components/ui/PeriodPicker.vue'
import ProfitLossYearly from '@/components/reports/ProfitLossYearly.vue'
import ProfitLossMonthly from '@/components/reports/ProfitLossMonthly.vue'

const toast = useToast()

const now = new Date()
const mode = ref('year')
const year = ref(now.getFullYear())
const month = ref(now.toLocaleDateString('sv-SE').slice(0, 7))
const exporting = ref(false)

// Export selalu berupa laporan setahun penuh
const exportYear = computed(() => (mode.value === 'month' ? Number(month.value.slice(0, 4)) || year.value : year.value))
const title = computed(() =>
  mode.value === 'year' ? `Laporan Profit/Loss ${year.value}` : `Laporan Profit/Loss ${month.value ? namaBulan(month.value) : ''}`,
)

async function exportExcel() {
  exporting.value = true
  try {
    const res = await api.get('/reports/profit-loss/export', { params: { year: exportYear.value }, responseType: 'blob' })
    saveBlob(res.data, `laporan-profit-loss-${exportYear.value}.xlsx`)
  } catch {
    toast.error('Gagal mengunduh file Excel.')
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <PageCard :title="title">
    <template #actions>
      <div class="flex flex-wrap items-center gap-2">
        <PeriodPicker v-model:mode="mode" v-model:year="year" v-model:month="month" />
        <BaseButton :loading="exporting" @click="exportExcel">
          <Download :size="16" /> {{ exporting ? 'Mengunduh...' : `Export Excel ${exportYear}` }}
        </BaseButton>
      </div>
    </template>

    <ProfitLossYearly v-if="mode === 'year'" :year="year" />
    <ProfitLossMonthly v-else :month="month" />
  </PageCard>
</template>