import { Navigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { PageLoader } from '@/components/ui/Spinner'
import { homePathForRole } from '@/utils/enterprise'

export const HomeRedirect = () => {
  const { isAuthenticated, user } = useAuth()

  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (!user) return <PageLoader />

  return <Navigate to={homePathForRole(user.role)} replace />
}
