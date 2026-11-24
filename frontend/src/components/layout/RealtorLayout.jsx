import { Link } from 'react-router-dom'
import { Alert } from '@/components/ui/Alert'
import { useAuth } from '@/hooks/useAuth'
import { realtorNavigation } from '@/utils/navigation'
import { DashboardLayout } from './DashboardLayout'

export const RealtorLayout = () => {
  const { user } = useAuth()
  const suspended = Boolean(user?.subscription_suspended)

  return (
    <DashboardLayout
      sections={realtorNavigation}
      homePath="/realtor/dashboard"
      banner={
        suspended ? (
          <Alert variant="warning" title="Your subscription is suspended" className="mb-5">
            {user?.subscription_suspend_reason || 'Renew your subscription to regain full access.'}{' '}
            <Link to="/realtor/plans" className="font-semibold underline underline-offset-2">
              View plans
            </Link>
          </Alert>
        ) : null
      }
    />
  )
}
