<script setup>
defineProps({
  title: String,
  value: String,
  hint: String,
  tone: { type: String, default: 'brand' }, // brand | green | orange
})

const tones = {
  brand: 'bg-brand-50 dark:bg-brand-500/15 text-brand-500',
  green: 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
  orange: 'bg-orange-50 dark:bg-orange-500/15 text-orange-600 dark:text-orange-400',
}
</script>

<template>
  <div class="flex items-start justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
    <div class="min-w-0">
      <p class="text-sm text-gray-500 dark:text-gray-400">{{ title }}</p>
      <p class="mt-2 truncate text-2xl font-semibold">{{ value }}</p>
      <p v-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
    </div>
    <div class="shrink-0 rounded-xl p-3" :class="tones[tone]">
      <slot name="icon" />
    </div>
  </div>
</template>