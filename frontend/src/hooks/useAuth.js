import { useCallback } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/authStore'
import { useNotificationStore } from '@/stores/notificationStore'

export const useAuth = () => {
  const token = useAuthStore((state) => state.token)
  const user = useAuthStore((state) => state.user)
  const setUser = useAuthStore((state) => state.setUser)
  const isAuthenticated = Boolean(token)

  return {
    token,
    user,
    setUser,
    isAuthenticated,
    isAdmin: user?.role === 'admin',
    isRealtor: user?.role === 'realtor',
    isEnterprise: user?.role === 'enterprise',
  }
}

export const useLogout = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const clear = useAuthStore((state) => state.clear)
  const resetNotifications = useNotificationStore((state) => state.reset)

  return useCallback(
    async ({ everywhere = false } = {}) => {
      try {
        if (everywhere) await authApi.logoutAll()
        else await authApi.logout()
      } catch {
        // The local session is cleared even if the token was already invalid.
      }
      clear()
      resetNotifications()
      queryClient.clear()
      navigate('/login', { replace: true })
    },
    [clear, navigate, queryClient, resetNotifications],
  )
}
