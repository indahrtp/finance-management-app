<script setup>
defineProps({
  label: String,
  error: String,
  options: { type: Array, default: () => [] }, // [{ value, label }]
  placeholder: { type: String, default: 'Pilih...' },
  clearable: Boolean,
})
const model = defineModel()
</script>

<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ label }}</label>
    <select
      v-model="model"
      class="w-full rounded-lg border bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/30"
      :class="error ? 'border-red-400' : 'border-gray-300 dark:border-gray-700 focus:border-brand-500'"
    >
      <option value="" :disabled="!clearable">{{ placeholder }}</option>
      <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>