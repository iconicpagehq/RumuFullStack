import { realtorCallsApi } from '@/api/calls'
import { realtorApi } from '@/api/realtor'
import { CallsWorkspace } from '@/components/calls/CallsWorkspace'

export const CallsPage = ({ view = 'logs' }) => (
  <CallsWorkspace
    edition="realtor"
    api={realtorCallsApi}
    loadLeads={(params) => realtorApi.leads.all(params)}
    canDeleteCall={() => true}
    view={view}
  />
)

export const CallsLogsPage = () => <CallsPage view="logs" />
export const CallsAnalyticsPage = () => <CallsPage view="analytics" />

