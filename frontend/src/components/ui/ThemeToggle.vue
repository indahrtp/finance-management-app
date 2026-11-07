<script setup>
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const { isDark, toggle } = useTheme()
</script>

<template>
  <button
    type="button"
    class="rounded-lg p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-800"
    :title="isDark ? 'Mode terang' : 'Mode gelap'"
    :aria-label="isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'"
    @click="toggle"
  >
    <Sun v-if="isDark" :size="20" />
    <Moon v-else :size="20" />
  </button>
</template>