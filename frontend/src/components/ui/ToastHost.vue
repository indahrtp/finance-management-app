<script setup>
import { toasts } from '@/composables/useToast'
</script>

<template>
  <div class="fixed right-4 top-4 z-[60] space-y-2">
    <div
      v-for="t in toasts" :key="t.id"
      class="max-w-sm rounded-lg px-4 py-3 text-sm text-white shadow-lg"
      :class="t.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'"
    >
      {{ t.message }}
    </div>
  </div>
</template>