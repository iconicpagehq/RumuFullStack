import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  ArrowRightIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  MegaphoneIcon,
  PlusIcon,
  TrophyIcon,
  UsersIcon,
} from '@heroicons/react/24/outline'
import { realtorApi } from '@/api/realtor'
import { CallsSummaryCard } from '@/components/calls/CallsSummaryCard'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { CAMPAIGN_STATUSES, LEAD_STATUSES } from '@/utils/constants'
import { cn } from '@/utils/cn'
import { formatDateTime, formatNumber, timeAgo } from '@/utils/formatters'

const chartTooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const campaignColors = {
  active: '#10b981',
  paused: '#f59e0b',
  completed: '#94a3b8',
}

const leadColors = {
  new: '#3b82f6',
  contacted: '#6366f1',
  qualified: '#8b5cf6',
  proposal: '#f59e0b',
  negotiation: '#f97316',
  won: '#10b981',
  lost: '#f43f5e',
}

const DonutLegend = ({ items, className }) => (
  <div className={cn('flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5', className)}>
    {items.map((item) => (
      <span
        key={item.name}
        className="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground"
      >
        <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: item.color }} />
        {item.name}
      </span>
    ))}
  </div>
)

const DonutCenter = ({ total, label }) => (
  <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
    <span className="text-2xl font-semibold tabular-nums leading-none tracking-tight text-foreground">
      {formatNumber(total)}
    </span>
    <span className="mt-1 text-[10px] font-medium uppercase tracking-[0.12em] text-muted-foreground">
      {label}
    </span>
  </div>
)

const DonutChart = ({ data, total, centerLabel }) => {
  const visibleData = data.filter((entry) => Number(entry.value) > 0)

  if (!total || !visibleData.length) {
    return (
      <div className="relative flex h-full items-center justify-center">
        <div className="h-48 w-48 rounded-full border-[24px] border-muted" />
        <DonutCenter total={0} label={centerLabel} />
      </div>
    )
  }

  return (
    <div className="relative h-full w-full">
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Tooltip contentStyle={chartTooltipStyle} />
          <Pie
            data={visibleData}
            dataKey="value"
            nameKey="name"
            cx="50%"
            cy="50%"
            innerRadius="62%"
            outerRadius="84%"
            paddingAngle={visibleData.length > 1 ? 2 : 0}
            cornerRadius={4}
            stroke="none"
            isAnimationActive={false}
          >
            {visibleData.map((entry) => (
              <Cell key={entry.name} fill={entry.color} />
            ))}
          </Pie>
        </PieChart>
      </ResponsiveContainer>
      <DonutCenter total={total} label={centerLabel} />
    </div>
  )
}

export const DashboardPage = () => {
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'dashboard'],
    queryFn: async () => {
      const response = await realtorApi.dashboard()
      return response.data?.data
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const leadStatus = data?.lead_status || {}
  const campaignStatus = data?.campaign_status || {}
  const totalLeads = Object.values(leadStatus).reduce((sum, value) => sum + Number(value || 0), 0)
  const totalCampaigns = Object.values(campaignStatus).reduce(
    (sum, value) => sum + Number(value || 0),
    0,
  )
  const wonLeads = Number(leadStatus.won || 0)
  const conversion = totalLeads ? Math.round((wonLeads / totalLeads) * 100) : 0
  const warning = data?.plan_expiry_warning
  const chartData = (data?.months || []).map((month, index) => ({
    month,
    leads: data?.monthly_leads?.[index] || 0,
    campaigns: data?.monthly_campaigns?.[index] || 0,
  }))

  const campaignData = CAMPAIGN_STATUSES.map((status) => ({
    name: status.label,
    value: Number(campaignStatus[status.value] || 0),
    color: campaignColors[status.value],
  }))
  const leadData = LEAD_STATUSES.map((status) => ({
    name: status.label,
    value: Number(leadStatus[status.value] || 0),
    color: leadColors[status.value],
  }))
  const upcomingTasks = data?.upcoming_tasks || []
  const upcomingVisits = data?.upcoming_site_visits || []
  const followUps = data?.lead_follow_ups || []
  const overdueTasksCount = Number(data?.overdue_tasks_count || 0)

  return (
    <div>
      <PageHeader title="Dashboard" description="A live overview of your campaigns and pipeline." />

      {warning && (
        <Alert variant="warning" title="Your plan is expiring soon" className="mb-5">
          {warning.plan_name} expires {warning.expires_formatted || `in ${warning.days_remaining} day(s)`}.
          Renew to keep campaigns running.{' '}
          <Link to="/realtor/plans" className="font-semibold underline underline-offset-2">
            Renew plan
          </Link>
        </Alert>
      )}

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Active Projects"
          value={formatNumber(campaignStatus.active || 0)}
          icon={DocumentTextIcon}
          tone="purple"
          hint={`${formatNumber(campaignStatus.paused || 0)} paused`}
          loading={isLoading}
        />
        <StatsCard
          label="Completed"
          value={formatNumber(campaignStatus.completed || 0)}
          icon={CheckCircleIcon}
          tone="success"
          hint={`${formatNumber(totalCampaigns)} in total`}
          loading={isLoading}
        />
        <StatsCard
          label="Total Leads"
          value={formatNumber(totalLeads)}
          icon={UsersIcon}
          tone="warning"
          hint={`${formatNumber(leadStatus.new || 0)} new`}
          loading={isLoading}
        />
        <StatsCard
          label="Won Leads"
          value={formatNumber(wonLeads)}
          icon={TrophyIcon}
          tone="info"
          hint={`${conversion}% conversion`}
          loading={isLoading}
        />
      </div>

      <CallsSummaryCard calls={data?.calls} isLoading={isLoading} to="/realtor/calls" />

      <div className="mt-5 grid gap-5 lg:grid-cols-2">
        <Card className="flex flex-col p-5">
          <div className="flex items-baseline justify-between gap-3">
            <CardTitle>Campaign Status</CardTitle>
            <span className="text-xs text-muted-foreground">
              {formatNumber(totalCampaigns)} total
            </span>
          </div>
          <div className="mt-3 h-60 w-full">
            {isLoading ? (
              <div className="skeleton h-full w-full" />
            ) : (
              <DonutChart data={campaignData} total={totalCampaigns} centerLabel="Campaigns" />
            )}
          </div>
          <DonutLegend
            items={campaignData.map((item) => ({ name: item.name, color: item.color }))}
            className="mt-auto pt-4"
          />
        </Card>

        <Card className="flex flex-col p-5">
          <div className="flex items-baseline justify-between gap-3">
            <CardTitle>Leads by Status</CardTitle>
            <span className="text-xs text-muted-foreground">{formatNumber(totalLeads)} total</span>
          </div>
          <div className="mt-3 h-60 w-full">
            {isLoading ? (
              <div className="skeleton h-full w-full" />
            ) : (
              <DonutChart data={leadData} total={totalLeads} centerLabel="Leads" />
            )}
          </div>
          <DonutLegend
            items={leadData.map((item) => ({ name: item.name, color: item.color }))}
            className="mt-auto pt-4"
          />
        </Card>
      </div>

      <Card className="mt-5 p-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <CardTitle>Monthly Activity</CardTitle>
            <p className="mt-1 text-xs text-muted-foreground">
              Leads and campaigns created over the last 6 months.
            </p>
          </div>
          <div className="flex items-center gap-1 rounded-full border border-border px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
            <span className="inline-flex items-center gap-1.5 px-1">
              <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: '#8a006b' }} />
              Leads
            </span>
            <span className="text-border">·</span>
            <span className="inline-flex items-center gap-1.5 px-1">
              <span className="h-2 w-2 rounded-[2px]" style={{ backgroundColor: '#e9b8df' }} />
              Campaigns
            </span>
          </div>
        </div>
        {isLoading ? (
          <div className="skeleton mt-4 h-64 w-full" />
        ) : (
          <div className="mt-4 h-64 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 8, right: 8, left: -18, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgb(var(--border))" />
                <XAxis
                  dataKey="month"
                  axisLine={false}
                  tickLine={false}
                  tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                  tickMargin={8}
                />
                <YAxis
                  allowDecimals={false}
                  axisLine={false}
                  tickLine={false}
                  tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                />
                <Tooltip contentStyle={chartTooltipStyle} cursor={{ fill: 'rgb(var(--muted))' }} />
                <Bar dataKey="leads" name="Leads" fill="#8a006b" radius={[4, 4, 0, 0]} maxBarSize={20} />
                <Bar
                  dataKey="campaigns"
                  name="Campaigns"
                  fill="#e9b8df"
                  radius={[4, 4, 0, 0]}
                  maxBarSize={20}
                />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}
      </Card>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card>
          <CardHeader>
            <div>
              <CardTitle>Upcoming tasks</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Your next follow-ups.</p>
            </div>
            <Link
              to="/realtor/tasks"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              View all
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent>
            {overdueTasksCount > 0 && (
              <Link
                to="/realtor/tasks?overdue=1"
                className="mb-3 flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs font-medium text-destructive transition hover:bg-destructive/15"
              >
                <ClockIcon className="h-4 w-4 shrink-0" />
                {formatNumber(overdueTasksCount)} overdue task{overdueTasksCount === 1 ? '' : 's'}
              </Link>
            )}
            {isLoading ? (
              <div className="space-y-2">
                {[1, 2, 3].map((item) => (
                  <div key={item} className="skeleton h-12 w-full" />
                ))}
              </div>
            ) : upcomingTasks.length === 0 ? (
              <div className="flex flex-col items-center gap-1 py-6 text-center">
                <ClipboardDocumentCheckIcon className="h-7 w-7 text-muted-foreground/60" />
                <p className="text-sm font-medium text-foreground">No upcoming tasks</p>
                <p className="text-xs text-muted-foreground">Plan your next follow-up.</p>
                <Link to="/realtor/tasks" className="mt-1 text-xs font-semibold text-highlight hover:underline">
                  Add a task
                </Link>
              </div>
            ) : (
              <div className="-mx-2 space-y-1">
                {upcomingTasks.map((task) => (
                  <Link
                    key={task.id}
                    to="/realtor/tasks"
                    className="block rounded-lg px-2 py-2 transition hover:bg-muted/60"
                  >
                    <p className="truncate text-sm font-medium text-foreground">{task.title}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      {task.lead ? `${task.lead} · ` : ''}
                      {formatDateTime(task.due_at)}
                    </p>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Upcoming site visits</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Scheduled customer visits.</p>
            </div>
            <Link
              to="/realtor/site-visits"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              View all
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="space-y-2">
                {[1, 2, 3].map((item) => (
                  <div key={item} className="skeleton h-12 w-full" />
                ))}
              </div>
            ) : upcomingVisits.length === 0 ? (
              <div className="flex flex-col items-center gap-1 py-6 text-center">
                <CalendarDaysIcon className="h-7 w-7 text-muted-foreground/60" />
                <p className="text-sm font-medium text-foreground">No visits scheduled</p>
                <p className="text-xs text-muted-foreground">Set up a showing for a lead.</p>
                <Link
                  to="/realtor/site-visits"
                  className="mt-1 text-xs font-semibold text-highlight hover:underline"
                >
                  Schedule a visit
                </Link>
              </div>
            ) : (
              <div className="-mx-2 space-y-1">
                {upcomingVisits.map((visit) => (
                  <Link
                    key={visit.id}
                    to="/realtor/site-visits"
                    className="block rounded-lg px-2 py-2 transition hover:bg-muted/60"
                  >
                    <p className="truncate text-sm font-medium text-foreground">
                      {visit.lead || 'Customer visit'}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      {visit.project ? `${visit.project} · ` : ''}
                      {formatDateTime(visit.scheduled_at)}
                    </p>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Lead follow-ups</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Scheduled lead callbacks.</p>
            </div>
            <Link
              to="/realtor/leads"
              className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
            >
              View leads
              <ArrowRightIcon className="h-3.5 w-3.5" />
            </Link>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="space-y-2">
                {[1, 2, 3].map((item) => (
                  <div key={item} className="skeleton h-12 w-full" />
                ))}
              </div>
            ) : followUps.length === 0 ? (
              <div className="flex flex-col items-center gap-1 py-6 text-center">
                <UsersIcon className="h-7 w-7 text-muted-foreground/60" />
                <p className="text-sm font-medium text-foreground">No follow-ups scheduled</p>
                <p className="text-xs text-muted-foreground">Set a next callback on a lead.</p>
                <Link to="/realtor/leads" className="mt-1 text-xs font-semibold text-highlight hover:underline">
                  Open leads
                </Link>
              </div>
            ) : (
              <div className="-mx-2 space-y-1">
                {followUps.map((lead) => (
                  <Link
                    key={lead.id}
                    to="/realtor/leads"
                    className="block rounded-lg px-2 py-2 transition hover:bg-muted/60"
                  >
                    <p className="truncate text-sm font-medium text-foreground">{lead.name}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      {lead.campaign ? `${lead.campaign} · ` : ''}
                      {formatDateTime(lead.next_follow_up_at)}
                    </p>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card className="mt-5">
        <CardHeader>
          <div>
            <CardTitle>Recent campaigns</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">Your latest active workspaces.</p>
          </div>
          <Link
            to="/realtor/campaigns"
            className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
          >
            View all
            <ArrowRightIcon className="h-4 w-4" />
          </Link>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3].map((item) => (
                <div key={item} className="skeleton h-14 w-full" />
              ))}
            </div>
          ) : data?.recent_campaigns?.length ? (
            <div className="-mx-2 space-y-1">
              {data.recent_campaigns.map((campaign) => (
                <Link
                  key={campaign.id}
                  to={`/realtor/campaigns/${campaign.id}`}
                  className="group flex items-center gap-3 rounded-lg px-2 py-2.5 transition hover:bg-muted/60"
                >
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/[0.07] text-primary dark:bg-highlight/10 dark:text-highlight">
                    <MegaphoneIcon className="h-4 w-4" />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium text-foreground">
                      {campaign.name}
                    </span>
                    <span className="mt-0.5 block text-xs text-muted-foreground">
                      {formatNumber(campaign.leads_count || 0)} leads · updated{' '}
                      {timeAgo(campaign.updated_at)}
                    </span>
                  </span>
                  <StatusBadge status={campaign.status} />
                  <ArrowRightIcon className="h-4 w-4 shrink-0 text-muted-foreground/60 transition group-hover:translate-x-0.5 group-hover:text-foreground" />
                </Link>
              ))}
            </div>
          ) : (
            <EmptyState
              icon={MegaphoneIcon}
              title="No campaigns yet"
              description="Create your first campaign to start capturing leads."
              action={
                <Link to="/realtor/campaigns/new">
                  <Button size="sm">
                    <PlusIcon className="h-4 w-4" />
                    Create campaign
                  </Button>
                </Link>
              }
            />
          )}
        </CardContent>
      </Card>
    </div>
  )
}
