import { reactive } from 'vue'

export const toasts = reactive([])
let seq = 0

function push(message, type) {
  const t = { id: ++seq, message, type }
  toasts.push(t)
  setTimeout(() => {
    const i = toasts.findIndex((x) => x.id === t.id)
    if (i > -1) toasts.splice(i, 1)
  }, 3500)
}

export function useToast() {
  return {
    success: (m) => push(m, 'success'),
    error: (m) => push(m, 'error'),
  }
}