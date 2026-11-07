<script setup>
defineProps({ type: String })
</script>

<template>
  <span
    class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="type === 'income' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' : 'bg-orange-50 dark:bg-orange-500/15 text-orange-700 dark:text-orange-400'"
  >
    {{ type === 'income' ? 'Income' : 'Expense' }}
  </span>
</template>