<script setup>
defineProps({
  title: String,
  value: String,
  hint: String,
  tone: { type: String, default: 'brand' }, // brand | green | orange
})

const tones = {
  brand: 'bg-brand-50 text-brand-500',
  green: 'bg-emerald-50 text-emerald-600',
  orange: 'bg-orange-50 text-orange-600',
}
</script>

<template>
  <div class="flex items-start justify-between rounded-xl border border-gray-200 bg-white p-5">
    <div class="min-w-0">
      <p class="text-sm text-gray-500">{{ title }}</p>
      <p class="mt-2 truncate text-2xl font-semibold">{{ value }}</p>
      <p v-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
    </div>
    <div class="shrink-0 rounded-xl p-3" :class="tones[tone]">
      <slot name="icon" />
    </div>
  </div>
</template>