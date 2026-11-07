import { LayoutDashboard, Tags, BookOpen, Wallet, ArrowLeftRight, PiggyBank, FileBarChart } from 'lucide-vue-next'

export const menu = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Kategori', to: '/categories', icon: Tags },
  { label: 'COA', to: '/coa', icon: BookOpen },
  { label: 'Rekening & Dompet', to: '/wallets', icon: Wallet },
  { label: 'Transaksi', to: '/transactions', icon: ArrowLeftRight },
  { label: 'Anggaran', to: '/budgets', icon: PiggyBank },
  { label: 'Laporan P/L', to: '/reports/profit-loss', icon: FileBarChart },
]