<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

defineProps({
  page: Number,
  lastPage: Number,
  total: Number,
  from: Number,
  to: Number,
})
defineEmits(['change'])
</script>

<template>
  <div v-if="total" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600 dark:text-gray-300">
    <p>Menampilkan {{ from }}–{{ to }} dari {{ total }} data</p>
    <div class="flex items-center gap-2">
      <button
        class="rounded-lg border border-gray-300 dark:border-gray-700 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40"
        :disabled="page <= 1" @click="$emit('change', page - 1)"
      >
        <ChevronLeft :size="16" />
      </button>
      <span>Halaman {{ page }} / {{ lastPage }}</span>
      <button
        class="rounded-lg border border-gray-300 dark:border-gray-700 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40"
        :disabled="page >= lastPage" @click="$emit('change', page + 1)"
      >
        <ChevronRight :size="16" />
      </button>
    </div>
  </div>
</template>