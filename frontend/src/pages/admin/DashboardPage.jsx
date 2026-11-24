import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { format } from 'date-fns'
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  ArrowRightIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  MegaphoneIcon,
  PlusIcon,
  UserGroupIcon,
  UserPlusIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { ErrorState } from '@/components/shared/ErrorState'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { LEAD_STATUSES, CAMPAIGN_STATUSES } from '@/utils/constants'
import { formatNumber, titleCase } from '@/utils/formatters'

const tooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const statusBar = {
  new: 'bg-blue-500',
  contacted: 'bg-amber-500',
  qualified: 'bg-violet-500',
  proposal: 'bg-indigo-500',
  negotiation: 'bg-orange-500',
  won: 'bg-emerald-500',
  lost: 'bg-red-500',
}

const campaignBar = {
  active: 'bg-emerald-500',
  paused: 'bg-amber-500',
  completed: 'bg-slate-400',
}

export const DashboardPage = () => {
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'dashboard'],
    queryFn: async () => {
      const response = await adminApi.dashboard()
      return response.data?.data
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const leadStatus = data?.leads_by_status || {}
  const campaignStatus = data?.campaigns_by_status || {}
  const totalLeads = Object.values(leadStatus).reduce((sum, value) => sum + Number(value || 0), 0)
  const totalCampaigns = Object.values(campaignStatus).reduce((sum, value) => sum + Number(value || 0), 0)
  const totalRealtors = Object.values(data?.realtors_by_plan || {}).reduce(
    (sum, value) => sum + Number(value || 0),
    0,
  )
  const chartData = (data?.months || []).map((month, index) => ({
    month,
    leads: data?.leads_monthly?.[index] || 0,
    campaigns: data?.campaigns_monthly?.[index] || 0,
    enterpriseLeads: data?.enterprise_leads_monthly?.[index] || 0,
  }))
  const planBreakdown = Object.entries(data?.realtors_by_plan || {}).map(([planId, count]) => ({
    name: data?.plan_names?.[planId] || 'No plan',
    count: Number(count || 0),
  }))
  const enterprisePlanBreakdown = Object.entries(data?.enterprise_plans || {}).map(
    ([planId, count]) => ({
      name: data?.plan_names?.[planId] || 'No plan',
      count: Number(count || 0),
    }),
  )
  const totalEnterpriseAccounts = enterprisePlanBreakdown.reduce((sum, plan) => sum + plan.count, 0)
  const enterpriseLeadsThisMonth =
    data?.enterprise_leads_monthly?.[data.enterprise_leads_monthly.length - 1] || 0

  return (
    <div>
      <PageHeader
        title="Admin dashboard"
        description={`${format(new Date(), 'EEEE, d MMMM yyyy')} · Platform health at a glance.`}
        actions={
          <>
            <Link to="/admin/analytics">
              <Button size="sm" variant="outline">
                Analytics
              </Button>
            </Link>
            <Link to="/admin/realtors">
              <Button size="sm">
                <PlusIcon className="h-4 w-4" />
                Add realtor
              </Button>
            </Link>
          </>
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total leads"
          value={formatNumber(totalLeads)}
          icon={UserGroupIcon}
          hint={`${formatNumber(leadStatus.new || 0)} new`}
          loading={isLoading}
        />
        <StatsCard
          label="Campaigns"
          value={formatNumber(totalCampaigns)}
          icon={MegaphoneIcon}
          hint={`${formatNumber(campaignStatus.active || 0)} active`}
          tone="info"
          loading={isLoading}
        />
        <StatsCard
          label="Realtors"
          value={formatNumber(totalRealtors)}
          icon={BriefcaseIcon}
          hint={`${planBreakdown.length} plan tiers in use`}
          tone="purple"
          loading={isLoading}
        />
        <StatsCard
          label="Won leads"
          value={formatNumber(leadStatus.won || 0)}
          icon={UsersIcon}
          hint={
            totalLeads
              ? `${Math.round((Number(leadStatus.won || 0) / totalLeads) * 100)}% conversion`
              : 'No leads yet'
          }
          tone="success"
          loading={isLoading}
        />
      </div>

      <div className="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Enterprise accounts"
          value={formatNumber(data?.enterprises_total || 0)}
          icon={BuildingOffice2Icon}
          hint={`${formatNumber(data?.enterprises_active || 0)} active`}
          tone="purple"
          loading={isLoading}
        />
        <StatsCard
          label="Enterprise members"
          value={formatNumber(data?.enterprise_members || 0)}
          icon={UserPlusIcon}
          hint="Active managers & employees"
          tone="info"
          loading={isLoading}
        />
        <StatsCard
          label="Enterprise leads"
          value={formatNumber(data?.enterprise_leads_total || 0)}
          icon={UserGroupIcon}
          hint={`${formatNumber(enterpriseLeadsThisMonth)} this month`}
          loading={isLoading}
        />
        <StatsCard
          label="Enterprise plans in use"
          value={formatNumber(enterprisePlanBreakdown.length)}
          icon={BriefcaseIcon}
          hint="Plan tiers assigned"
          tone="success"
          loading={isLoading}
        />
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader>
            <div>
              <CardTitle>Platform growth</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">New leads and campaigns over the last 6 months.</p>
            </div>
            <div className="flex flex-wrap items-center gap-1 rounded-full border border-border px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
              <span className="inline-flex items-center gap-1.5 px-1">
                <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: '#8a006b' }} />
                Realtor leads
              </span>
              <span className="text-border">·</span>
              <span className="inline-flex items-center gap-1.5 px-1">
                <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: '#e9b8df' }} />
                Campaigns
              </span>
              <span className="text-border">·</span>
              <span className="inline-flex items-center gap-1.5 px-1">
                <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: '#650052' }} />
                Enterprise leads
              </span>
            </div>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="skeleton h-64 w-full" />
            ) : (
              <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={chartData} margin={{ top: 8, right: 8, left: -18, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgb(var(--border))" />
                    <XAxis
                      dataKey="month"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                    />
                    <YAxis
                      allowDecimals={false}
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                    />
                    <Tooltip contentStyle={tooltipStyle} />
                    <Bar dataKey="leads" name="Realtor leads" fill="#8a006b" radius={[4, 4, 0, 0]} maxBarSize={16} />
                    <Bar
                      dataKey="campaigns"
                      name="Campaigns"
                      fill="#e9b8df"
                      radius={[4, 4, 0, 0]}
                      maxBarSize={16}
                    />
                    <Bar
                      dataKey="enterpriseLeads"
                      name="Enterprise leads"
                      fill="#650052"
                      radius={[4, 4, 0, 0]}
                      maxBarSize={16}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Lead pipeline</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">All leads by stage.</p>
            </div>
            <Link
              to="/admin/leads"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              View
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent className="space-y-3.5">
            {LEAD_STATUSES.map((status) => {
              const count = Number(leadStatus[status.value] || 0)
              const percent = totalLeads ? Math.round((count / totalLeads) * 100) : 0
              return (
                <div key={status.value}>
                  <div className="flex items-center justify-between text-xs">
                    <span className="flex items-center gap-2 font-medium text-foreground">
                      <span className={`h-2 w-2 rounded-full ${statusBar[status.value]}`} />
                      {status.label}
                    </span>
                    <span className="text-muted-foreground">
                      {formatNumber(count)} · {percent}%
                    </span>
                  </div>
                  <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                      className={`h-full rounded-full ${statusBar[status.value]}`}
                      style={{ width: `${percent}%` }}
                    />
                  </div>
                </div>
              )
            })}
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <Card>
          <CardHeader>
            <div>
              <CardTitle>Campaign status</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Campaigns by current state.</p>
            </div>
          </CardHeader>
          <CardContent className="space-y-3">
            {CAMPAIGN_STATUSES.map((status) => (
              <div key={status.value} className="flex items-center justify-between gap-3">
                <span className="flex items-center gap-2 text-sm text-foreground">
                  <span className={`h-2 w-2 rounded-full ${campaignBar[status.value]}`} />
                  {status.label}
                </span>
                <span className="text-sm font-semibold tabular-nums text-foreground">
                  {formatNumber(campaignStatus[status.value] || 0)}
                </span>
              </div>
            ))}
            <div className="flex items-center justify-between border-t border-border pt-3">
              <span className="text-sm font-medium text-muted-foreground">Total</span>
              <span className="text-sm font-bold tabular-nums text-foreground">
                {formatNumber(totalCampaigns)}
              </span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Realtors by plan</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Distribution across plan tiers.</p>
            </div>
            <Link
              to="/admin/realtors"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              Manage
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent>
            {planBreakdown.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted-foreground">No realtors registered yet.</p>
            ) : (
              <div className="space-y-3">
                {planBreakdown.map((plan) => {
                  const percent = totalRealtors ? Math.round((plan.count / totalRealtors) * 100) : 0
                  return (
                    <div key={plan.name}>
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-medium text-foreground">{titleCase(plan.name)}</span>
                        <span className="text-muted-foreground">
                          {formatNumber(plan.count)} · {percent}%
                        </span>
                      </div>
                      <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div className="h-full rounded-full bg-primary" style={{ width: `${percent}%` }} />
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Enterprise plans</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Enterprise accounts by plan tier.</p>
            </div>
            <Link
              to="/admin/enterprises"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              Manage
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent>
            {enterprisePlanBreakdown.length === 0 ? (
              <p className="py-6 text-center text-sm text-muted-foreground">No enterprise accounts yet.</p>
            ) : (
              <div className="space-y-3">
                {enterprisePlanBreakdown.map((plan) => {
                  const percent = totalEnterpriseAccounts
                    ? Math.round((plan.count / totalEnterpriseAccounts) * 100)
                    : 0
                  return (
                    <div key={plan.name}>
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-medium text-foreground">{titleCase(plan.name)}</span>
                        <span className="text-muted-foreground">
                          {formatNumber(plan.count)} · {percent}%
                        </span>
                      </div>
                      <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                          className="h-full rounded-full"
                          style={{ width: `${percent}%`, backgroundColor: '#650052' }}
                        />
                      </div>
                    </div>
                  )
                })}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
