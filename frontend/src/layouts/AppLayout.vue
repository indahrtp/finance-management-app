<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, X, LogOut } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { menu } from './menu'
import ToastHost from '@/components/ui/ToastHost.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const open = ref(false) // sidebar di mobile

function isActive(to) {
  return to === '/' ? route.path === '/' : route.path.startsWith(to)
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Overlay mobile -->
    <div v-if="open" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open = false" />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-64 bg-sidebar text-gray-300 transition-transform duration-200 lg:translate-x-0"
      :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-16 items-center justify-between px-6">
        <span class="text-xl font-semibold text-white">FinanceApp</span>
        <button class="lg:hidden" @click="open = false"><X :size="20" /></button>
      </div>

      <nav class="space-y-1 px-3 py-4">
        <RouterLink
          v-for="item in menu" :key="item.to" :to="item.to" @click="open = false"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="isActive(item.to) ? 'bg-brand-500 text-white' : 'hover:bg-white/10'"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </RouterLink>
      </nav>
    </aside>

    <!-- Konten -->
    <div class="lg:pl-64">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
        <button class="rounded-lg p-2 hover:bg-gray-100 lg:hidden" @click="open = true">
          <Menu :size="22" />
        </button>
        <div class="ml-auto flex items-center gap-4">
          <div class="text-right text-sm leading-tight">
            <p class="font-medium">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500">{{ auth.user?.email }}</p>
          </div>
          <button @click="logout" class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
            <LogOut :size="16" /> Keluar
          </button>
        </div>
      </header>

      <main class="p-4 sm:p-6"><RouterView /></main>
    </div>
    <ToastHost />
  </div>
</template>