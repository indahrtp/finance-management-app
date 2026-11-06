// Laravel 422: { errors: { field: ['pesan', ...] } } -> { field: 'pesan' }
export function validationErrors(e) {
  const raw = e.response?.data?.errors || {}
  return Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, v[0]]))
}

export function errorMessage(e) {
  return e.response?.data?.message || 'Terjadi kesalahan. Coba lagi.'
}