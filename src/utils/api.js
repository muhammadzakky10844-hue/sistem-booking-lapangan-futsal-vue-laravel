import axios from 'axios'

const envApiUrl = (import.meta.env.VITE_API_URL || '').trim().replace(/\/+$/, '')
const fallbackApiUrl = `${window.location.origin}/api`

const ensureApiSuffix = (url) => {
  const cleanUrl = (url || '').trim().replace(/\/+$/, '')
  if (!cleanUrl) return fallbackApiUrl
  return cleanUrl.endsWith('/api') ? cleanUrl : `${cleanUrl}/api`
}

// Guard against misconfigured env URL on shared hosting.
const baseURL = ensureApiSuffix(
  envApiUrl.includes('infinityfr.app.com') || !envApiUrl
    ? fallbackApiUrl
    : envApiUrl
)

const api = axios.create({
  baseURL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Otomatis sisipkan token di setiap request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
    // Shared hosting can strip Authorization; send a fallback header too.
    config.headers['X-Auth-Token'] = token
  }
  return config
})

export default api
