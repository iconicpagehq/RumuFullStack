import { useState } from 'react'
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
  BanknotesIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  TrophyIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/24/outline'
import { enterpriseApi } from '@/api/enterprise'
import { CallsSummaryCard } from '@/components/calls/CallsSummaryCard'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { ErrorState } from '@/components/shared/ErrorState'
import { StageBadge } from '@/components/enterprise/StageBadge'
import { LeadFormModal } from '@/components/enterprise/LeadFormModal'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { useEnterprise } from '@/hooks/useEnterprise'
import { cn } from '@/utils/cn'
import { metricFormat, metricLabel, roleLabel } from '@/utils/enterprise'
import {
  formatCompactCurrency,
  formatCurrency,
  formatNumber,
  formatPercent,
  timeAgo,
  titleCase,
} from '@/utils/formatters'

const chartTooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const STAGE_COLORS = {
  new: '#3b82f6',
  contacted: '#6366f1',
  qualified: '#8b5cf6',
  site_visit_scheduled: '#a855f7',
  site_visit_completed: '#c084fc',
  negotiation: '#f59e0b',
  won: '#10b981',
  lost: '#f43f5e',
  on_hold: '#94a3b8',
}

const SOURCE_COLORS = ['#8a006b', '#a855f7', '#3b82f6', '#10b981', '#f59e0b', '#f43f5e', '#94a3b8']

const STAT_META = {
  total_leads: { icon: UsersIcon, tone: 'purple' },
  new_leads: { icon: UsersIcon, tone: 'info' },
  qualified: { icon: CheckCircleIcon, tone: 'success' },
  negotiation: { icon: ClockIcon, tone: 'warning' },
  won: { icon: TrophyIcon, tone: 'success' },
  deal_value: { icon: BanknotesIcon, tone: 'purple' },
  overdue_followups: { icon: ExclamationTriangleIcon, tone: 'danger' },
  overdue_tasks: { icon: ClipboardDocumentCheckIcon, tone: 'danger' },
  upcoming_visits: { icon: CalendarDaysIcon, tone: 'info' },
  lost: { icon: ExclamationTriangleIcon, tone: 'danger' },
  unassigned: { icon: UsersIcon, tone: 'warning' },
  active_teams: { icon: UserGroupIcon, tone: 'primary' },
  active_members: { icon: UsersIcon, tone: 'primary' },
  available_units: { icon: CalendarDaysIcon, tone: 'success' },
  follow_up_today: { icon: ClockIcon, tone: 'warning' },
}

const STAT_LINKS = {
  new_leads: '/enterprise/leads?stage=new',
  qualified: '/enterprise/leads?stage=qualified',
  negotiation: '/enterprise/leads?stage=negotiation',
  lost: '/enterprise/leads?stage=lost',
  overdue_followups: '/enterprise/leads?overdue=1',
  follow_up_today: '/enterprise/leads?follow_up_today=1',
  unassigned: '/enterprise/leads?unassigned=1',
  overdue_tasks: '/enterprise/tasks?overdue=1',
  upcoming_visits: '/enterprise/site-visits?upcoming=1',
  active_teams: '/enterprise/teams',
  active_members: '/enterprise/users',
  available_units: '/enterprise/inventory',
}

const formatStatValue = (stat) => {
  if (stat.format === 'currency') return formatCompactCurrency(stat.value)
  return formatNumber(stat.value)
}

const MiniStat = ({ stat }) => {
  const meta = STAT_META[stat.key] || {}
  const Icon = meta.icon || ArrowRightIcon
  const content = (
    <span className="flex items-center gap-3">
      <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
        <Icon className="h-4 w-4" />
      </span>
      <span className="min-w-0">
        <span className="block text-lg font-semibold leading-tight tabular-nums text-foreground">
          {formatStatValue(stat)}
        </span>
        <span className="block truncate text-[11px] text-muted-foreground">{stat.label}</span>
      </span>
    </span>
  )

  const to = STAT_LINKS[stat.key]
  return to ? (
    <Link to={to} className="rounded-lg px-2 py-2 transition hover:bg-muted/60">
      {content}
    </Link>
  ) : (
    <span className="px-2 py-2">{content}</span>
  )
}

const TrendChart = ({ data = [], loading }) => {
  if (loading) return <div className="skeleton mt-4 h-64 w-full" />

  return (
    <div className="mt-4 h-64 w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 8, right: 8, left: -18, bottom: 0 }}>
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
          <Bar dataKey="leads" name="Leads" fill="#8a006b" radius={[4, 4, 0, 0]} maxBarSize={22} />
          <Bar dataKey="won" name="Deals won" fill="#e9b8df" radius={[4, 4, 0, 0]} maxBarSize={22} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}

const SourcesDonut = ({ sources = [], loading }) => {
  const total = sources.reduce((sum, item) => sum + Number(item.count || 0), 0)
  const colors = sources.map((_, index) => SOURCE_COLORS[index % SOURCE_COLORS.length])

  if (loading) return <div className="skeleton h-44 w-full" />

  if (!total) {
    return (
      <div className="flex h-44 flex-col items-center justify-center gap-1 text-center">
        <p className="text-2xl font-semibold text-foreground">0</p>
        <p className="text-xs text-muted-foreground">No leads captured yet</p>
      </div>
    )
  }

  return (
    <div className="flex flex-col items-center gap-4 sm:flex-row">
      <div className="relative h-40 w-40 shrink-0">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Tooltip contentStyle={chartTooltipStyle} />
            <Pie
              data={sources}
              dataKey="count"
              nameKey="source"
              innerRadius="62%"
              outerRadius="88%"
              paddingAngle={2}
              cornerRadius={4}
              stroke="none"
              isAnimationActive={false}
            >
              {sources.map((entry, index) => (
                <Cell key={entry.source} fill={colors[index]} />
              ))}
            </Pie>
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-xl font-semibold tabular-nums text-foreground">{formatNumber(total)}</span>
          <span className="text-[10px] uppercase tracking-wide text-muted-foreground">Leads</span>
        </div>
      </div>
      <div className="min-w-0 flex-1 space-y-1.5">
        {sources.map((source, index) => (
          <div key={source.source} className="flex items-center justify-between gap-2 text-xs">
            <span className="flex min-w-0 items-center gap-1.5 text-muted-foreground">
              <span className="h-2 w-2 shrink-0 rounded-[2px]" style={{ backgroundColor: colors[index] }} />
              <span className="truncate">{titleCase(source.source)}</span>
            </span>
            <span className="font-medium tabular-nums text-foreground">{formatNumber(source.count)}</span>
          </div>
        ))}
      </div>
    </div>
  )
}

const PerformanceTable = ({ title, description, rows, columns }) => (
  <Card>
    <CardHeader>
      <div>
        <CardTitle>{title}</CardTitle>
        <p className="mt-1 text-xs text-muted-foreground">{description}</p>
      </div>
    </CardHeader>
    <CardContent className="px-0 pb-0">
      {rows.length === 0 ? (
        <div className="px-5 pb-5">
          <EmptyState title="No data yet" description="Performance will appear as your team works leads." />
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-border text-sm">
            <thead className="bg-muted/40">
              <tr>
                {columns.map((column) => (
                  <th key={column.key} className="table-th">
                    {column.label}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {rows.map((row, index) => (
                <tr key={row.id || row.user_id || index}>
                  {columns.map((column) => (
                    <td key={column.key} className="table-td">
                      {column.render ? column.render(row) : row[column.key]}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </CardContent>
  </Card>
)

export const DashboardPage = () => {
  const [addLeadOpen, setAddLeadOpen] = useState(false)
  const { role, isEmployee } = useEnterprise()

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'dashboard'],
    queryFn: async () => {
      const response = await enterpriseApi.dashboard()
      return response.data?.data
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const stats = data?.stats || []
  const statByKey = Object.fromEntries(stats.map((stat) => [stat.key, stat]))
  const primaryKeys = isEmployee
    ? ['total_leads', 'won', 'deal_value', 'follow_up_today']
    : ['total_leads', 'won', 'deal_value', 'upcoming_visits']
  const primary = primaryKeys.map((key) => statByKey[key]).filter(Boolean)
  const secondary = stats.filter((stat) => !primaryKeys.includes(stat.key))

  const funnel = data?.funnel || []
  const funnelTotal = funnel.reduce((sum, item) => sum + Number(item.count || 0), 0)
  const maxStageCount = Math.max(1, ...funnel.map((item) => Number(item.count || 0)))

  const recentLeads = data?.recent_leads || []
  const targets = data?.targets || []
  const teamPerformance = data?.team_performance || []
  const employeePerformance = data?.employee_performance || []
  const campaignPerformance = data?.campaign_performance || []
  const activity = data?.activity || []
  const taskSummary = data?.tasks || {}
  const visitSummary = data?.visits || {}

  return (
    <div>
      <PageHeader
        title="Overview"
        description={
          isEmployee
            ? 'Your leads, follow-ups and visits for this month.'
            : `Company-wide pipeline and team performance · signed in as ${roleLabel(role).toLowerCase()}.`
        }
        actions={
          <Button size="sm" onClick={() => setAddLeadOpen(true)}>
            <PlusIcon className="h-4 w-4" />
            Add lead
          </Button>
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        {isLoading
          ? [1, 2, 3, 4].map((item) => (
              <StatsCard key={item} label="Loading" value="" loading />
            ))
          : primary.map((stat) => {
              const meta = STAT_META[stat.key] || {}
              return (
                <StatsCard
                  key={stat.key}
                  label={stat.label}
                  value={formatStatValue(stat)}
                  icon={meta.icon}
                  tone={meta.tone}
                />
              )
            })}
      </div>

      {secondary.length > 0 && (
        <Card className="mt-5 p-3">
          <div className="grid grid-cols-2 gap-1 sm:grid-cols-3 xl:grid-cols-5">
            {secondary.map((stat) => (
              <MiniStat key={stat.key} stat={stat} />
            ))}
          </div>
        </Card>
      )}

      <CallsSummaryCard calls={data?.calls} isLoading={isLoading} to="/enterprise/calls" />

      <div className="mt-5 grid gap-5 lg:grid-cols-5">
        <Card className="lg:col-span-3">
          <CardHeader>
            <div>
              <CardTitle>Pipeline</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">
                {formatNumber(funnelTotal)} leads across all stages.
              </p>
            </div>
            <Link to="/enterprise/leads" className="text-xs font-semibold text-highlight hover:underline">
              View leads
            </Link>
          </CardHeader>
          <CardContent className="space-y-3">
            {funnel.map((item) => {
              const percentage = funnelTotal ? Math.round((Number(item.count) / funnelTotal) * 100) : 0
              return (
                <Link
                  key={item.stage}
                  to={`/enterprise/leads?stage=${item.stage}`}
                  className="group grid grid-cols-[110px_1fr_52px] items-center gap-3 rounded-md px-1 py-1 transition hover:bg-muted/40"
                >
                  <span className="text-xs font-medium text-muted-foreground group-hover:text-foreground">
                    {item.label}
                  </span>
                  <span className="h-2.5 overflow-hidden rounded-full bg-muted">
                    <span
                      className="block h-full rounded-full transition-all"
                      style={{
                        width: `${Math.max(2, (Number(item.count) / maxStageCount) * 100)}%`,
                        backgroundColor: STAGE_COLORS[item.stage],
                      }}
                    />
                  </span>
                  <span className="text-right text-xs tabular-nums text-muted-foreground">
                    <span className="font-semibold text-foreground">{formatNumber(item.count)}</span> · {percentage}%
                  </span>
                </Link>
              )
            })}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <div>
              <CardTitle>Lead sources</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">Where enquiries come from.</p>
            </div>
          </CardHeader>
          <CardContent>
            <SourcesDonut sources={data?.sources} loading={isLoading} />
          </CardContent>
        </Card>
      </div>

      <Card className="mt-5">
        <CardHeader>
          <div>
            <CardTitle>Six-month trend</CardTitle>
            <p className="mt-1 text-xs text-muted-foreground">Leads created and deals won each month.</p>
          </div>
          <Link to="/enterprise/reports" className="text-xs font-semibold text-highlight hover:underline">
            Open reports
          </Link>
        </CardHeader>
        <CardContent>
          <TrendChart data={data?.trend} loading={isLoading} />
        </CardContent>
      </Card>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <div>
              <CardTitle>Recent leads</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">The newest enquiries in your workspace.</p>
            </div>
            <Link to="/enterprise/leads" className="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline">
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
            ) : recentLeads.length === 0 ? (
              <EmptyState
                icon={UsersIcon}
                title="No leads yet"
                description="Add your first lead or share your intake form with the team."
                action={
                  <Button size="sm" onClick={() => setAddLeadOpen(true)}>
                    <PlusIcon className="h-4 w-4" />
                    Add lead
                  </Button>
                }
              />
            ) : (
              <div className="-mx-2 space-y-1">
                {recentLeads.map((lead) => (
                  <Link
                    key={lead.id}
                    to={`/enterprise/leads/${lead.id}`}
                    className="flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-muted/60"
                  >
                    <Avatar name={lead.name} size="sm" />
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm font-medium text-foreground">{lead.name}</span>
                      <span className="block truncate text-xs text-muted-foreground">
                        {lead.company || lead.phone || lead.email || 'No contact details'}
                        {lead.assignee?.name ? ` · ${lead.assignee.name}` : ' · Unassigned'}
                      </span>
                    </span>
                    <StageBadge stage={lead.stage} />
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Tasks</CardTitle>
              <Link to="/enterprise/tasks" className="text-xs font-semibold text-highlight hover:underline">
                Open
              </Link>
            </CardHeader>
            <CardContent className="space-y-2.5">
              {[
                ['Overdue', taskSummary.overdue, '/enterprise/tasks?overdue=1', 'text-red-600 dark:text-red-400'],
                ['Due today', taskSummary.today, '/enterprise/tasks?due=today', 'text-amber-600 dark:text-amber-300'],
                ['Pending', taskSummary.pending, '/enterprise/tasks?status=pending', 'text-foreground'],
                ['Completed this month', taskSummary.completed_this_month, '/enterprise/tasks?status=completed', 'text-emerald-600 dark:text-emerald-400'],
              ].map(([label, value, to, tone]) => (
                <Link key={label} to={to} className="flex items-center justify-between rounded-md px-1 py-1 text-sm transition hover:bg-muted/40">
                  <span className="text-muted-foreground">{label}</span>
                  <span className={cn('font-semibold tabular-nums', tone)}>{formatNumber(value || 0)}</span>
                </Link>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Site visits</CardTitle>
              <Link to="/enterprise/site-visits" className="text-xs font-semibold text-highlight hover:underline">
                Open
              </Link>
            </CardHeader>
            <CardContent className="space-y-2.5">
              {[
                ['Upcoming', visitSummary.upcoming],
                ['Today', visitSummary.today],
                ['Completed this month', visitSummary.completed_this_month],
              ].map(([label, value]) => (
                <div key={label} className="flex items-center justify-between px-1 text-sm">
                  <span className="text-muted-foreground">{label}</span>
                  <span className="font-semibold tabular-nums text-foreground">{formatNumber(value || 0)}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          {!isEmployee && (
            <Card>
              <CardHeader>
                <CardTitle>Attention</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2.5">
                <Link to="/enterprise/leads?unassigned=1" className="flex items-center justify-between rounded-md px-1 py-1 text-sm transition hover:bg-muted/40">
                  <span className="text-muted-foreground">Unassigned leads</span>
                  <span className="font-semibold tabular-nums text-foreground">
                    {formatNumber(data?.pending_assignments || 0)}
                  </span>
                </Link>
                <Link to="/enterprise/leads?overdue=1" className="flex items-center justify-between rounded-md px-1 py-1 text-sm transition hover:bg-muted/40">
                  <span className="text-muted-foreground">Overdue follow-ups</span>
                  <span className="font-semibold tabular-nums text-foreground">
                    {formatNumber(statByKey.overdue_followups?.value || 0)}
                  </span>
                </Link>
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {targets.length > 0 && (
        <Card className="mt-5">
          <CardHeader>
            <div>
              <CardTitle>This month's targets</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">Progress against assigned targets.</p>
            </div>
            <Link to="/enterprise/targets" className="text-xs font-semibold text-highlight hover:underline">
              Manage targets
            </Link>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {targets.map((target) => {
              const percent = target.achievement_percentage ?? 0
              const isCurrency = metricFormat(target.metric) === 'currency'
              return (
                <div key={target.id} className="rounded-lg border border-border p-3">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <p className="truncate text-xs font-semibold text-foreground">{metricLabel(target.metric)}</p>
                      <p className="truncate text-[11px] text-muted-foreground">{target.scope}</p>
                    </div>
                    <span className="text-xs font-semibold tabular-nums text-foreground">
                      {formatPercent(percent)}
                    </span>
                  </div>
                  <div className="mt-2.5 h-1.5 overflow-hidden rounded-full bg-muted">
                    <span
                      className="block h-full rounded-full bg-primary"
                      style={{ width: `${Math.min(100, Math.max(0, percent))}%` }}
                    />
                  </div>
                  <p className="mt-1.5 text-[11px] text-muted-foreground">
                    {isCurrency ? formatCurrency(target.actual_value) : formatNumber(target.actual_value)} of{' '}
                    {isCurrency ? formatCurrency(target.target_value) : formatNumber(target.target_value)}
                  </p>
                </div>
              )
            })}
          </CardContent>
        </Card>
      )}

      {!isEmployee && (
        <div className="mt-5 grid gap-5 lg:grid-cols-2">
          <PerformanceTable
            title="Team performance"
            description="Open leads and closed business by team."
            rows={teamPerformance}
            columns={[
              {
                key: 'team',
                label: 'Team',
                render: (row) => (
                  <div className="min-w-0">
                    <Link to={`/enterprise/teams/${row.id}`} className="block truncate font-medium text-foreground hover:text-highlight">
                      {row.name}
                    </Link>
                    <span className="block truncate text-[11px] text-muted-foreground">
                      {row.manager || 'No manager'} · {formatNumber(row.members)} members
                    </span>
                  </div>
                ),
              },
              {
                key: 'open_leads',
                label: 'Open',
                render: (row) => <span className="tabular-nums">{formatNumber(row.open_leads)}</span>,
              },
              {
                key: 'won_deals',
                label: 'Won',
                render: (row) => <span className="tabular-nums">{formatNumber(row.won_deals)}</span>,
              },
              {
                key: 'deal_value',
                label: 'Value',
                render: (row) => (
                  <span className="whitespace-nowrap font-medium tabular-nums">{formatCompactCurrency(row.deal_value)}</span>
                ),
              },
            ]}
          />

          <PerformanceTable
            title="People performance"
            description="Top performers by won deal value."
            rows={employeePerformance}
            columns={[
              {
                key: 'person',
                label: 'Member',
                render: (row) => (
                  <div className="flex min-w-0 items-center gap-2">
                    <Avatar name={row.name} size="xs" />
                    <div className="min-w-0">
                      <p className="truncate font-medium text-foreground">{row.name}</p>
                      <p className="text-[11px] capitalize text-muted-foreground">{row.role}</p>
                    </div>
                  </div>
                ),
              },
              {
                key: 'open_leads',
                label: 'Open',
                render: (row) => <span className="tabular-nums">{formatNumber(row.open_leads)}</span>,
              },
              {
                key: 'won_deals',
                label: 'Won',
                render: (row) => <span className="tabular-nums">{formatNumber(row.won_deals)}</span>,
              },
              {
                key: 'deal_value',
                label: 'Value',
                render: (row) => (
                  <span className="whitespace-nowrap font-medium tabular-nums">{formatCompactCurrency(row.deal_value)}</span>
                ),
              },
            ]}
          />
        </div>
      )}

      {campaignPerformance.length > 0 && (
        <PerformanceTable
          title="Campaign performance"
          description="Lead volume and conversion by campaign."
          rows={[...campaignPerformance]}
          columns={[
            {
              key: 'name',
              label: 'Campaign',
              render: (row) => <span className="font-medium text-foreground">{row.name}</span>,
            },
            {
              key: 'status',
              label: 'Status',
              render: (row) => <span className="capitalize text-muted-foreground">{row.status}</span>,
            },
            {
              key: 'leads',
              label: 'Leads',
              render: (row) => <span className="tabular-nums">{formatNumber(row.leads)}</span>,
            },
            {
              key: 'won_deals',
              label: 'Won',
              render: (row) => <span className="tabular-nums">{formatNumber(row.won_deals)}</span>,
            },
            {
              key: 'deal_value',
              label: 'Value',
              render: (row) => (
                <span className="whitespace-nowrap font-medium tabular-nums">{formatCompactCurrency(row.deal_value)}</span>
              ),
            },
          ]}
        />
      )}

      <Card className="mt-5">
        <CardHeader>
          <div>
            <CardTitle>Recent activity</CardTitle>
            <p className="mt-1 text-xs text-muted-foreground">Latest updates across your visible leads.</p>
          </div>
        </CardHeader>
        <CardContent>
          {activity.length === 0 ? (
            <EmptyState title="No activity yet" description="Lead updates, notes and stage changes will appear here." />
          ) : (
            <ol className="relative space-y-4 border-l border-border pl-5">
              {activity.map((item) => (
                <li key={item.id} className="relative">
                  <span className="absolute -left-[25px] top-1.5 h-2 w-2 rounded-full bg-highlight ring-4 ring-card" />
                  <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <p className="text-sm font-medium text-foreground">{item.title}</p>
                    <span className="text-[11px] text-muted-foreground">{timeAgo(item.created_at)}</span>
                  </div>
                  {item.description && <p className="mt-0.5 text-xs text-muted-foreground">{item.description}</p>}
                  <p className="mt-0.5 text-[11px] text-muted-foreground">
                    {item.user || 'System'} · {titleCase(item.type)}
                  </p>
                </li>
              ))}
            </ol>
          )}
        </CardContent>
      </Card>

      <LeadFormModal open={addLeadOpen} onClose={() => setAddLeadOpen(false)} />
    </div>
  )
}
