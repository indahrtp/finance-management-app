<script setup>
defineOptions({ inheritAttrs: false }) // atribut (min, max, placeholder, dll.) diteruskan ke <input>
defineProps({ label: String, error: String, type: { type: String, default: 'text' } })
const model = defineModel()
</script>

<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ label }}</label>
    <input
      v-model="model" :type="type" v-bind="$attrs"
      class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:bg-gray-900 dark:text-gray-100"
      :class="error ? 'border-red-400' : 'border-gray-300 focus:border-brand-500 dark:border-gray-700'"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>