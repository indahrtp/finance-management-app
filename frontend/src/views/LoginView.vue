<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowLeft, Eye, EyeOff } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

function fillDemo() {
  email.value = 'admin@example.com'
  password.value = 'password'
}

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    if (e.response?.status === 429) {
      error.value = 'Terlalu banyak percobaan. Coba lagi sebentar lagi.'
    } else {
      error.value = e.response?.data?.errors?.email?.[0] || 'Gagal login. Coba lagi.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="relative flex min-h-screen flex-col overflow-hidden bg-white dark:bg-gray-950">
    <!-- <div class="pointer-events-none absolute left-1/2 top-0 h-80 w-[40rem] -translate-x-1/2 rounded-full bg-brand-500/20 blur-3xl dark:bg-brand-500/10" /> -->

    <!-- Bar atas -->
    <div class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
      <RouterLink
        to="/"
        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
      >
        <ArrowLeft :size="16" /> Kembali 
      </RouterLink>
      <ThemeToggle />
    </div>

    <!-- Form -->
    <main class="relative z-10 flex flex-1 items-center justify-center px-4 pb-16">
      <div class="animate-fade-up w-full max-w-md motion-reduce:animate-none">
        <div class="mb-8 text-center">
          <h1><RouterLink to="/" class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            Finance<span class="text-brand-500">App</span>
          </RouterLink></h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Masuk untuk mengelola keuangan Anda.</p>
        </div>

        <form
          class="space-y-5 rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-200/50 dark:border-gray-800 dark:bg-gray-900 dark:shadow-none"
          @submit.prevent="submit"
        >
          <p v-if="error" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600 dark:bg-red-500/15 dark:text-red-400">
            {{ error }}
          </p>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
            <input
              v-model="email" type="email" required autocomplete="email" placeholder="nama@email.com"
              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>
            <div class="relative">
              <input
                v-model="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" placeholder="Masukkan password"
                class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-4 pr-11 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500"
              />
              <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                @click="showPassword = !showPassword"
              >
                <component :is="showPassword ? EyeOff : Eye" :size="18" />
              </button>
            </div>
          </div>

          <button
            :disabled="loading"
            class="w-full rounded-xl bg-brand-500 py-3 font-medium text-white shadow-lg shadow-brand-500/25 transition hover:bg-brand-600 disabled:opacity-60"
          >
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>

          <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Hanya mencoba?
            <button type="button" class="font-medium text-brand-500 hover:underline" @click="fillDemo">
              Gunakan akun demo
            </button>
          </p>
        </form>
      </div>
    </main>
  </div>
</template>