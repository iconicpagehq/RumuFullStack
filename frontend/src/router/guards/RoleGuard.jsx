import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { PageLoader } from '@/components/ui/Spinner'
import { homePathForRole } from '@/utils/enterprise'

export const RoleGuard = ({ role }) => {
  const { user } = useAuth()

  if (!user) return <PageLoader />

  if (user.role !== role) {
    return <Navigate to={homePathForRole(user.role)} replace />
  }

  return <Outlet />
}
