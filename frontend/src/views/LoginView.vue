<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.errors?.email?.[0] || 'Gagal login. Coba lagi.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="relative flex min-h-screen items-center justify-center bg-gray-100 dark:bg-gray-800 px-4">
    <div class="absolute right-4 top-4"><ThemeToggle /></div>
    <form @submit.prevent="submit" class="w-full max-w-sm space-y-4 rounded-xl bg-white dark:bg-gray-900 p-8 shadow">
      <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Masuk</h1>

      <p v-if="error" class="rounded bg-red-50 dark:bg-red-500/15 px-3 py-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

      <div>
        <label class="mb-1 block text-sm text-gray-600 dark:text-gray-300">Email</label>
        <input v-model="email" type="email" required
          class="w-full rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 focus:border-blue-500 focus:outline-none" />
      </div>
      <div>
        <label class="mb-1 block text-sm text-gray-600 dark:text-gray-300">Password</label>
        <input v-model="password" type="password" required
          class="w-full rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 focus:border-blue-500 focus:outline-none" />
      </div>

      <button :disabled="loading"
        class="w-full rounded-lg bg-blue-600 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-60">
        {{ loading ? 'Memproses...' : 'Masuk' }}
      </button>
    </form>
  </div>
</template>