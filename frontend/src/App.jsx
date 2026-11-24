import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { authApi } from '@/api/auth'
import { useAuthStore } from '@/stores/authStore'
import { AppRoutes } from './router'

const SessionSync = () => {
  const token = useAuthStore((state) => state.token)
  const setUser = useAuthStore((state) => state.setUser)

  const { data } = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: authApi.me,
    enabled: Boolean(token),
    retry: false,
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  })

  useEffect(() => {
    const user = data?.data?.data
    if (user?.id) setUser(user)
  }, [data, setUser])

  return null
}

const App = () => (
  <>
    <SessionSync />
    <AppRoutes />
  </>
)

export default App
