import { ref, computed } from 'vue'
import api from '@/services/api'
import { MAX_MONTH } from '@/utils/period'

const minMonth = ref(MAX_MONTH)
let pending = null

export function loadRange(force = false) {
  if (pending && !force) return pending

  pending = api
    .get('/period-range')
    .then(({ data }) => {
      if (data.min) minMonth.value = data.min > MAX_MONTH ? MAX_MONTH : data.min
    })
    .catch(() => {
      pending = null 
    })

  return pending
}

export function usePeriodRange() {
  return {
    minMonth,
    minYear: computed(() => Number(minMonth.value.slice(0, 4))),
    loadRange,
  }
}