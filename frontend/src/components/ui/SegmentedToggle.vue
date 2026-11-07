<script setup>
defineProps({ options: Array }) // [{ value, label }]
const model = defineModel()
</script>

<template>
  <div class="inline-flex rounded-xl border border-gray-300 bg-white p-1 dark:border-gray-700 dark:bg-gray-900">
    <button
      v-for="o in options" :key="o.value" type="button" @click="model = o.value"
      class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
      :class="model === o.value ? 'bg-brand-500 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
    >
      {{ o.label }}
    </button>
  </div>
</template>