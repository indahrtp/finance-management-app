<script setup>
import { X } from 'lucide-vue-next'
defineProps({ show: Boolean, title: String })
defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="$emit('close')">
      <div class="w-full max-w-lg rounded-xl bg-white dark:bg-gray-900 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 px-6 py-4">
          <h3 class="text-lg font-semibold">{{ title }}</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><X :size="20" /></button>
        </div>
        <div class="p-6"><slot /></div>
      </div>
    </div>
  </Teleport>
</template>