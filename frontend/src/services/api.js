import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
})

// Sisipkan token di setiap request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Jika token tidak valid (401), hapus sesi dan arahkan ke login
api.interceptors.response.use(
  (res) => res,
  (error) => {
    const url = error.config?.url || ''

    if (error.response?.status === 401 && !url.includes('/login')) {
      // Tandai sesi berakhir (bukan saat logout manual)
      if (localStorage.getItem('token') && !url.includes('/logout')) {
        sessionStorage.setItem('session_expired', '1')
      }
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default api