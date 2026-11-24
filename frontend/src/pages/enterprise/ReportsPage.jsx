import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
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
  BanknotesIcon,
  ChartBarIcon,
  DocumentArrowDownIcon,
  LockClosedIcon,
  TrophyIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select } from '@/components/ui/Input'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import {
  formatCurrency,
  formatNumber,
  formatPercent,
  filenameFromResponse,
  saveBlob,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const chartTooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const emptyFilters = { from: '', to: '', group_by: '', team_id: '', user_id: '' }

const cleanParams = (filters) =>
  Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null && value !== undefined))

const EXPORTS = [
  { type: 'leads', label: 'Leads' },
  { type: 'deals', label: 'Deals' },
  { type: 'visits', label: 'Visits' },
  { type: 'tasks', label: 'Tasks' },
]

export const ReportsPage = () => {
  const { isEmployee, features } = useEnterprise()
  const canExport = !isEmployee
  const exportUnlocked = Boolean(features.advanced_reports)
  const { teams, assignableMembers } = useEnterpriseReference()
  const [draft, setDraft] = useState(emptyFilters)
  const [filters, setFilters] = useState(emptyFilters)


  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'reports', filters],
    queryFn: async () => {
      const response = await enterpriseApi.reports.get(cleanParams(filters))
      return response.data?.data
    },
  })

  const exportMutation = useMutation({
    mutationFn: async (type) => {
      const response = await enterpriseApi.reports.export({ ...cleanParams(filters), type })
      const filename = filenameFromResponse(response, `enterprise_${type}_report.csv`)
      saveBlob(response.data, filename)
      return { type, filename }
    },
    onSuccess: ({ type, filename }) => {
      toast.success(`${titleCase(type)} export downloaded · ${filename}`)
    },
    onError: (exportError) => {
      if (exportError?.response?.data?.code === 'feature_locked') {
        toast.error('Report exports are not included in your current plan. Ask your administrator to upgrade.')
        return
      }
      toast.error(getErrorMessage(exportError, 'Could not export this report.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const summary = data?.summary || {}
  const stages = data?.stages || []
  const sources = data?.sources || []
  const priorities = data?.priorities || []
  const trend = data?.trend || []
  const teamsPerformance = data?.team_performance || []
  const employeePerformance = data?.employee_performance || []
  const campaignPerformance = data?.campaign_performance || []
  const activities = data?.activities || []
  const visits = data?.visits || {}
  const tasks = data?.tasks || {}
  const period = data?.period || {}

  const maxStage = Math.max(1, ...stages.map((stage) => stage.count))

  const teamColumns = [
    { key: 'name', header: 'Team', render: (row) => <span className="text-sm font-medium text-foreground">{row.name}</span> },
    { key: 'members', header: 'Members', render: (row) => formatNumber(row.members) },
    { key: 'leads', header: 'Leads', render: (row) => formatNumber(row.leads) },
    { key: 'won', header: 'Won', render: (row) => formatNumber(row.won) },
    { key: 'deal_value', header: 'Deal value', render: (row) => formatCurrency(row.deal_value) },
  ]

  const employeeColumns = [
    {
      key: 'name',
      header: 'Member',
      render: (row) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-foreground">{row.name || 'Unknown member'}</p>
          <p className="text-[11px] capitalize text-muted-foreground">{row.role}</p>
        </div>
      ),
    },
    { key: 'leads', header: 'Leads', render: (row) => formatNumber(row.leads) },
    { key: 'won', header: 'Won', render: (row) => formatNumber(row.won) },
    { key: 'completed_visits', header: 'Visits', render: (row) => formatNumber(row.completed_visits) },
    { key: 'deal_value', header: 'Deal value', render: (row) => formatCurrency(row.deal_value) },
  ]

  const campaignColumns = [
    { key: 'name', header: 'Campaign', render: (row) => <span className="text-sm font-medium text-foreground">{row.name}</span> },
    { key: 'leads', header: 'Leads', render: (row) => formatNumber(row.leads) },
    { key: 'won', header: 'Won', render: (row) => formatNumber(row.won) },
    { key: 'deal_value', header: 'Deal value', render: (row) => formatCurrency(row.deal_value) },
  ]

  const activeFilterCount = Object.values(filters).filter(Boolean).length

  return (
    <div>
      <PageHeader
        title="Reports"
        description={
          period.from
            ? `${formatNumber(summary.total_leads || 0)} leads from ${period.from} to ${period.to}.`
            : 'Performance across leads, visits, tasks and revenue for the selected period.'
        }
        actions={
          !canExport ? (
            <Badge variant="neutral">Exports are available to managers and admins</Badge>
          ) : !exportUnlocked ? (
            <Link to="/enterprise/billing">
              <Button variant="outline" size="sm">
                <LockClosedIcon className="h-4 w-4" />
                Upgrade for exports
              </Button>
            </Link>
          ) : (
            <div className="flex flex-wrap items-center gap-1.5">
              {EXPORTS.map((item) => (
                <Button
                  key={item.type}
                  variant="outline"
                  size="sm"
                  onClick={() => exportMutation.mutate(item.type)}
                  loading={exportMutation.isPending && exportMutation.variables === item.type}
                  disabled={exportMutation.isPending}
                >
                  <DocumentArrowDownIcon className="h-4 w-4" />
                  {item.label}
                </Button>
              ))}
            </div>
          )
        }
      />

      <Card>
        <CardContent>
          <form
            className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6"
            onSubmit={(event) => {
              event.preventDefault()
              setFilters(draft)
            }}
          >
            <FormField label="From" htmlFor="report-from">
              <Input
                id="report-from"
                type="date"
                value={draft.from}
                onChange={(event) => setDraft({ ...draft, from: event.target.value })}
              />
            </FormField>
            <FormField label="To" htmlFor="report-to">
              <Input
                id="report-to"
                type="date"
                value={draft.to}
                onChange={(event) => setDraft({ ...draft, to: event.target.value })}
              />
            </FormField>
            <FormField label="Group by" htmlFor="report-group">
              <Select
                id="report-group"
                value={draft.group_by}
                onChange={(event) => setDraft({ ...draft, group_by: event.target.value })}
              >
                <option value="">Auto</option>
                <option value="day">Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
              </Select>
            </FormField>
            {!isEmployee && (
              <>
                <FormField label="Team" htmlFor="report-team">
                  <Select
                    id="report-team"
                    value={draft.team_id}
                    onChange={(event) => setDraft({ ...draft, team_id: event.target.value })}
                  >
                    <option value="">All teams</option>
                    {teams.map((team) => (
                      <option key={team.id} value={team.id}>
                        {team.name}
                      </option>
                    ))}
                  </Select>
                </FormField>
                <FormField label="Member" htmlFor="report-user">
                  <Select
                    id="report-user"
                    value={draft.user_id}
                    onChange={(event) => setDraft({ ...draft, user_id: event.target.value })}
                  >
                    <option value="">All members</option>
                    {assignableMembers.map((member) => (
                      <option key={member.user.id} value={member.user.id}>
                        {member.user.name}
                      </option>
                    ))}
                  </Select>
                </FormField>
              </>
            )}
            <div className="flex items-end gap-2">
              <Button type="submit" loading={isFetching}>
                Apply
              </Button>
              <Button
                type="button"
                variant="ghost"
                onClick={() => {
                  setDraft(emptyFilters)
                  setFilters(emptyFilters)
                }}
                disabled={activeFilterCount === 0 && draft === emptyFilters}
              >
                Reset
              </Button>
            </div>
          </form>
          {activeFilterCount > 0 && (
            <div className="mt-3 flex flex-wrap items-center gap-2 border-t border-border pt-3">
              <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Active filters
              </span>
              {filters.from && <Badge variant="outline">From {filters.from}</Badge>}
              {filters.to && <Badge variant="outline">To {filters.to}</Badge>}
              {filters.group_by && <Badge variant="outline">Grouped by {filters.group_by}</Badge>}
              {filters.team_id && (
                <Badge variant="outline">
                  Team: {teams.find((team) => String(team.id) === filters.team_id)?.name || filters.team_id}
                </Badge>
              )}
              {filters.user_id && (
                <Badge variant="outline">
                  Member:{' '}
                  {assignableMembers.find((member) => String(member.user.id) === filters.user_id)?.user.name ||
                    filters.user_id}
                </Badge>
              )}
            </div>
          )}
        </CardContent>
      </Card>

      <div className="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Total leads" value={formatNumber(summary.total_leads || 0)} icon={UsersIcon} tone="purple" loading={isLoading} />
        <StatsCard label="Deals won" value={formatNumber(summary.won || 0)} icon={TrophyIcon} tone="success" loading={isLoading} />
        <StatsCard label="Deal value" value={formatCurrency(summary.deal_value || 0)} icon={BanknotesIcon} tone="primary" loading={isLoading} />
        <StatsCard
          label="Win rate"
          value={summary.win_rate === null || summary.win_rate === undefined ? '—' : formatPercent(summary.win_rate)}
          icon={ChartBarIcon}
          tone="info"
          hint={`${formatNumber(summary.lost || 0)} lost`}
          loading={isLoading}
        />
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader>
            <CardTitle>Lead and won trend</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <div className="skeleton h-[260px] rounded-xl" />
            ) : trend.length === 0 ? (
              <EmptyState
                icon={ChartBarIcon}
                title="No trend data"
                description="Leads created in the selected period will appear here."
              />
            ) : (
              <div className="h-[260px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={trend} margin={{ top: 4, right: 8, bottom: 0, left: -12 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgb(var(--border))" vertical={false} />
                    <XAxis
                      dataKey="period"
                      tick={{ fontSize: 11, fill: 'rgb(var(--muted-foreground))' }}
                      axisLine={false}
                      tickLine={false}
                    />
                    <YAxis
                      allowDecimals={false}
                      tick={{ fontSize: 11, fill: 'rgb(var(--muted-foreground))' }}
                      axisLine={false}
                      tickLine={false}
                    />
                    <Tooltip contentStyle={chartTooltipStyle} cursor={{ fill: 'rgb(var(--muted))' }} />
                    <Bar dataKey="leads" name="Leads" fill="#8a006b" radius={[4, 4, 0, 0]} maxBarSize={32} />
                    <Bar dataKey="won" name="Won" fill="#10b981" radius={[4, 4, 0, 0]} maxBarSize={32} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
            <div className="mt-3 grid gap-3 border-t border-border pt-3 sm:grid-cols-2 lg:grid-cols-4">
              <MiniStat label="Contacted" value={formatNumber(summary.contacted || 0)} />
              <MiniStat label="Qualified" value={formatNumber(summary.qualified || 0)} />
              <MiniStat label="Avg deal value" value={formatCurrency(summary.average_deal_value || 0)} />
              <MiniStat label="Activities" value={formatNumber(summary.activities || 0)} />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Pipeline by stage</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {isLoading ? (
              <div className="skeleton h-[260px] rounded-xl" />
            ) : stages.every((stage) => stage.count === 0) ? (
              <EmptyState icon={ChartBarIcon} title="No pipeline data" />
            ) : (
              stages.map((stage) => (
                <div key={stage.stage}>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-foreground">{stage.label}</span>
                    <span className="font-medium text-foreground">{formatNumber(stage.count)}</span>
                  </div>
                  <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                      className={`h-full rounded-full ${
                        stage.stage === 'won'
                          ? 'bg-emerald-500'
                          : stage.stage === 'lost'
                            ? 'bg-rose-500'
                            : 'bg-primary'
                      }`}
                      style={{ width: `${(stage.count / maxStage) * 100}%` }}
                    />
                  </div>
                </div>
              ))
            )}

            {priorities.length > 0 && (
              <div className="flex flex-wrap gap-2 border-t border-border pt-3">
                {priorities.map((priority) => (
                  <Badge key={priority.priority} variant="neutral">
                    {titleCase(priority.priority)} · {formatNumber(priority.count)}
                  </Badge>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle>Site visits</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-3">
            <MiniStat label="Scheduled" value={formatNumber(visits.scheduled || 0)} />
            <MiniStat label="Completed" value={formatNumber(visits.completed || 0)} />
            <MiniStat label="Cancelled" value={formatNumber(visits.cancelled || 0)} />
            <MiniStat label="No show" value={formatNumber(visits.no_show || 0)} />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Tasks</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-3">
            <MiniStat label="Total" value={formatNumber(tasks.total || 0)} />
            <MiniStat label="Completed" value={formatNumber(tasks.completed || 0)} />
            <MiniStat label="Pending" value={formatNumber(tasks.pending || 0)} />
            <MiniStat label="Overdue" value={formatNumber(tasks.overdue || 0)} />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Activity mix</CardTitle>
          </CardHeader>
          <CardContent>
            {activities.length === 0 ? (
              <p className="text-xs text-muted-foreground">No activities in this period.</p>
            ) : (
              <div className="space-y-2">
                {activities.slice(0, 6).map((activity) => (
                  <div key={activity.type} className="flex items-center justify-between text-xs">
                    <span className="text-muted-foreground">{titleCase(activity.type)}</span>
                    <span className="font-medium text-foreground">{formatNumber(activity.count)}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Lead sources</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={[
                { key: 'source', header: 'Source', render: (row) => <span className="text-sm capitalize text-foreground">{titleCase(row.source)}</span> },
                { key: 'count', header: 'Leads', render: (row) => formatNumber(row.count) },
                { key: 'won', header: 'Won', render: (row) => formatNumber(row.won) },
                {
                  key: 'conversion',
                  header: 'Conversion',
                  render: (row) =>
                    row.count > 0 ? formatPercent((row.won / row.count) * 100) : '—',
                },
              ]}
              rows={sources}
              loading={isLoading}
              rowKey="source"
              emptyState={<EmptyState title="No source data" description="Lead sources appear once leads are captured." />}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Team performance</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={teamColumns}
              rows={teamsPerformance}
              loading={isLoading}
              rowKey="id"
              emptyState={<EmptyState title="No team data" description="Create teams and assign leads to see performance." />}
            />
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Member performance</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={employeeColumns}
              rows={employeePerformance}
              loading={isLoading}
              rowKey="user_id"
              emptyState={<EmptyState title="No member data" description="Add active team members to see their performance." />}
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Campaign performance</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={campaignColumns}
              rows={campaignPerformance}
              loading={isLoading}
              rowKey="id"
              emptyState={<EmptyState title="No campaign data" description="Campaigns with attributed leads will appear here." />}
            />
          </CardContent>
        </Card>
      </div>

      <p className="mt-4 text-[11px] text-muted-foreground">
        Reports are scoped to your role. Exports download CSV files with formula-injection-safe values.
        {isFetching && ' Refreshing…'}
      </p>
    </div>
  )
}

const MiniStat = ({ label, value }) => (
  <div className="rounded-xl border border-border bg-muted/25 px-3 py-2.5">
    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
    <p className="mt-1 text-sm font-semibold text-foreground">{value}</p>
  </div>
)
