<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <header class="flex items-center justify-between bg-white px-6 py-3 shadow-sm">
      <span class="font-semibold">Finance App</span>
      <div class="flex items-center gap-4 text-sm">
        <span>{{ auth.user?.name }}</span>
        <button @click="logout" class="text-red-600 hover:underline">Keluar</button>
      </div>
    </header>
    <main class="p-6"><RouterView /></main>
  </div>
</template>