<script setup>
defineProps({ title: String })
</script>

<template>
  <section class="rounded-xl border border-gray-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
      <h2 class="text-lg font-semibold">{{ title }}</h2>
      <slot name="actions" />
    </div>
    <div class="p-6"><slot /></div>
  </section>
</template>