import { Link } from 'react-router-dom'
import { Alert } from '@/components/ui/Alert'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { DashboardLayout } from '@/components/layout/DashboardLayout'
import { useEnterprise } from '@/hooks/useEnterprise'
import { daysLeft } from '@/utils/enterprise'
import { enterpriseNavigation } from '@/utils/enterpriseNavigation'
import { formatDate } from '@/utils/formatters'

export const EnterpriseLayout = () => {
  const {
    enterprise,
    membership,
    role,
    isAdmin,
    canViewHierarchy,
    hasReports,
    isLoading,
    isError,
    error,
    refetch,
  } = useEnterprise()

  if (isLoading && !role) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  if (!role) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background px-4">
        <Alert variant="danger" title="Workspace unavailable">
          Your enterprise membership is not active. Contact your company administrator.
        </Alert>
      </div>
    )
  }

  const remaining = enterprise?.is_on_trial ? daysLeft(enterprise.trial_ends_at) : null

  return (
    <DashboardLayout
      sections={enterpriseNavigation(role, { canViewHierarchy, hasReports })}
      homePath="/enterprise/dashboard"
      banner={
        enterprise?.is_on_trial ? (
          <Alert variant="info" title="Enterprise trial" className="mb-5">
            {remaining === 0
              ? 'Your trial ends today.'
              : `${remaining} day${remaining === 1 ? '' : 's'} left in your trial`}
            {enterprise.trial_ends_at ? ` · ends ${formatDate(enterprise.trial_ends_at)}` : ''}.
            {isAdmin ? (
              <>
                {' '}
                <Link to="/enterprise/billing" className="font-semibold underline underline-offset-2">
                  Review plans
                </Link>
              </>
            ) : null}
          </Alert>
        ) : null
      }
    />
  )
}

