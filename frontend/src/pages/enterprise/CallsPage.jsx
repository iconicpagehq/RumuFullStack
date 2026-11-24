import { enterpriseCallsApi } from '@/api/calls'
import { enterpriseApi } from '@/api/enterprise'
import { CallsWorkspace } from '@/components/calls/CallsWorkspace'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useAuthStore } from '@/stores/authStore'

export const CallsPage = ({ view = 'logs' }) => {
  const { isAdmin } = useEnterprise()
  const user = useAuthStore((state) => state.user)

  const canDeleteCall = (call) =>
    isAdmin || (user?.id != null && Number(call?.agent?.id) === Number(user.id))

  return (
    <CallsWorkspace
      edition="enterprise"
      api={enterpriseCallsApi}
      loadLeads={(params) => enterpriseApi.leads.list(params)}
      canDeleteCall={canDeleteCall}
      view={view}
    />
  )
}

export const EnterpriseCallsLogsPage = () => <CallsPage view="logs" />
export const EnterpriseCallsAnalyticsPage = () => <CallsPage view="analytics" />
