import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
  routes: [
    { path: '/', name: 'landing', component: () => import('@/views/LandingView.vue') },
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { guest: true } },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { auth: true },
      children: [
        { path: 'dashboard', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
        { path: 'categories', name: 'categories', component: () => import('@/views/CategoryView.vue') },
        { path: 'coa', name: 'coa', component: () => import('@/views/CoaView.vue') },
        { path: 'wallets', name: 'wallets', component: () => import('@/views/WalletView.vue') },
        { path: 'transactions', name: 'transactions', component: () => import('@/views/TransactionView.vue') },
        { path: 'budgets', name: 'budgets', component: () => import('@/views/BudgetView.vue') },
        { path: 'reports/profit-loss', name: 'profit-loss', component: () => import('@/views/ProfitLossView.vue') },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.isLoggedIn) return { name: 'login' }
  if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
})

export default router