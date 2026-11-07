<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: String,
  hint: String,
  tone: { type: String, default: 'brand' }, // brand | green | orange
  change: { type: Number, default: null }, // persen perubahan; null = tidak ada pembanding
  changeLabel: String,
  goodWhen: { type: String, default: 'up' }, // 'up' = naik itu baik, 'down' = turun itu baik
})

const tones = {
  brand: 'bg-brand-50 text-brand-500 dark:bg-brand-500/15',
  green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
  orange: 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
}

const delta = computed(() => {
  if (props.change === null || props.change === undefined) return null
  const flat = props.change === 0
  const up = props.change > 0
  const good = up === (props.goodWhen === 'up')

  return {
    text: `${flat ? '' : up ? '▲ ' : '▼ '}${Math.abs(props.change)}%`,
    cls: flat ? 'text-gray-500' : good ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400',
  }
})
</script>

<template>
  <div class="flex items-start justify-between rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
    <div class="min-w-0">
      <p class="text-sm text-gray-500 dark:text-gray-400">{{ title }}</p>
      <p class="mt-2 truncate text-2xl font-semibold text-gray-900 dark:text-white">{{ value }}</p>
      <p v-if="delta" class="mt-1 text-xs">
        <span class="font-medium" :class="delta.cls">{{ delta.text }}</span>
        <span class="text-gray-400"> {{ changeLabel }}</span>
      </p>
      <p v-else-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
    </div>
    <div class="shrink-0 rounded-xl p-3" :class="tones[tone]">
      <slot name="icon" />
    </div>
  </div>
</template>