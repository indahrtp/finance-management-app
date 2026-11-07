<script setup>
defineProps({
  variant: { type: String, default: 'primary' }, // primary | secondary | danger
  loading: Boolean,
})
const styles = {
  primary: 'bg-brand-500 text-white hover:bg-brand-600',
  secondary: 'border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800',
  danger: 'bg-red-600 text-white hover:bg-red-700',
}
</script>

<template>
  <button
    :disabled="loading"
    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-60"
    :class="styles[variant]"
  >
    <slot />
  </button>
</template>