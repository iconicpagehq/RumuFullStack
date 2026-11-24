import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

const baseURL = import.meta.env.VITE_API_BASE_URL || '/api/v1'

export const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const url = error.config?.url || ''
    const isAuthAttempt = url.includes('/auth/login') || url.includes('/auth/social/')

    if (status === 401 && !isAuthAttempt) {
      useAuthStore.getState().clear()
      if (window.location.pathname !== '/login') {
        window.location.assign('/login?expired=1')
      }
    }

    error.userMessage =
      error.response?.data?.message ||
      error.response?.data?.errors?.email?.[0] ||
      (error.code === 'ECONNABORTED'
        ? 'The request timed out. Please try again.'
        : 'Something went wrong. Please try again.')

    return Promise.reject(error)
  },
)

export const ASSET_BASE = (import.meta.env.VITE_STORAGE_BASE_URL || '/storage').replace(/\/$/, '')

export const assetUrl = (path) => {
  if (!path) return null
  if (/^https?:\/\//i.test(path)) return path
  if (path.startsWith('blob:') || path.startsWith('data:')) return path
  return `${ASSET_BASE}/${String(path).replace(/^\/+/, '')}`
}

export const apiData = (response) => response.data?.data ?? null
