import { ref } from 'vue'

const KEY = 'theme'
const root = document.documentElement

// Nilai awal dibaca dari DOM (sudah diatur skrip di index.html)
const isDark = ref(root.classList.contains('dark'))

function setDark(dark, persist = true) {
  isDark.value = dark
  root.classList.toggle('dark', dark)
  if (persist) {
    try { localStorage.setItem(KEY, dark ? 'dark' : 'light') } catch {}
  }
}

// Jika pengguna belum memilih, ikuti perubahan tema sistem
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
  let saved = null
  try { saved = localStorage.getItem(KEY) } catch {}
  if (!saved) setDark(e.matches, false)
})

export function useTheme() {
  return { isDark, toggle: () => setDark(!isDark.value) }
}