<script setup>
defineProps({ type: String })
</script>

<template>
  <span
    class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-700'"
  >
    {{ type === 'income' ? 'Income' : 'Expense' }}
  </span>
</template>