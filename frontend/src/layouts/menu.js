import { LayoutDashboard, Tags, BookOpen, ArrowLeftRight, FileBarChart } from 'lucide-vue-next'

export const menu = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Kategori', to: '/categories', icon: Tags },
  { label: 'COA', to: '/coa', icon: BookOpen },
  { label: 'Transaksi', to: '/transactions', icon: ArrowLeftRight },
  { label: 'Laporan P/L', to: '/reports/profit-loss', icon: FileBarChart },
]