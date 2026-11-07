<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, X, LogOut, PanelLeftClose, PanelLeftOpen, House } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import ToastHost from '@/components/ui/ToastHost.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import { menu } from './menu'

const KEY = 'sidebar-collapsed'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const open = ref(false) 
const collapsed = ref(false) 
try {
  collapsed.value = localStorage.getItem(KEY) === '1'
} catch {}

function toggleCollapsed() {
  collapsed.value = !collapsed.value
  try {
    localStorage.setItem(KEY, collapsed.value ? '1' : '0')
  } catch {}
}

function isActive(to) {
  return route.path.startsWith(to)
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Overlay HP -->
    <div v-if="open" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="open = false" />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 dark:border-gray-800 dark:bg-gray-900"
      :class="[
        open ? 'translate-x-0' : '-translate-x-full',
        collapsed ? 'lg:-translate-x-full' : 'lg:translate-x-0',
      ]"
    >
      <div class="flex h-16 shrink-0 items-center justify-between px-6">
        <RouterLink to="/dashboard" class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
          Finance<span class="text-brand-500">App</span>
        </RouterLink>
        <button
          class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
          aria-label="Tutup menu" @click="open = false"
        >
          <X :size="20" />
        </button>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Menu</p>
        <RouterLink
          v-for="item in menu" :key="item.to" :to="item.to" @click="open = false"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
          :class="isActive(item.to)
            ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15'
            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <div class="shrink-0 border-t border-gray-200 p-3 dark:border-gray-800">
        <RouterLink
          to="/"
          class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        >
          <House :size="18" />
          <span>Beranda</span>
        </RouterLink>
      </div>
    </aside>

    <!-- Konten -->
    <div class="transition-[padding] duration-200" :class="collapsed ? 'lg:pl-0' : 'lg:pl-64'">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/80 px-4 backdrop-blur dark:border-gray-800 dark:bg-gray-950/80 sm:px-6">
        <div class="flex items-center gap-2">
          <button
            class="rounded-xl p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 lg:hidden"
            aria-label="Buka menu" @click="open = true"
          >
            <Menu :size="22" />
          </button>

          <button
            class="hidden rounded-xl p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 lg:inline-flex"
            :title="collapsed ? 'Tampilkan menu' : 'Sembunyikan menu'"
            :aria-label="collapsed ? 'Tampilkan menu' : 'Sembunyikan menu'"
            @click="toggleCollapsed"
          >
            <component :is="collapsed ? PanelLeftOpen : PanelLeftClose" :size="20" />
          </button>

          <RouterLink
            to="/dashboard"
            class="text-lg font-bold tracking-tight text-gray-900 dark:text-white"
            :class="collapsed ? '' : 'lg:hidden'"
          >
            Finance<span class="text-brand-500">App</span>
          </RouterLink>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
          <ThemeToggle />
          <div class="hidden text-right text-sm leading-tight sm:block">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth.user?.email }}</p>
          </div>
          <button
            class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/15"
            @click="logout"
          >
            <LogOut :size="16" /> <span class="hidden sm:inline">Keluar</span>
          </button>
        </div>
      </header>

      <main class="p-4 sm:p-6"><RouterView /></main>
    </div>

    <ToastHost />
  </div>
</template>