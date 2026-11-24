import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowRightIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  TrashIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { VisitFormModal } from '@/components/enterprise/VisitFormModal'
import { VisitCloseModal } from '@/components/enterprise/VisitCloseModal'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { Tabs } from '@/components/ui/Tabs'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { VISIT_STATUSES } from '@/utils/enterprise'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const STATUS_TABS = [{ key: '', label: 'All visits' }, ...VISIT_STATUSES]

export const SiteVisitsPage = ({ view = 'list' }) => {
  const queryClient = useQueryClient()
  const { canAssignLeads } = useEnterprise()
  const { teams, assignableMembers, projects } = useEnterpriseReference()
  const [searchParams, setSearchParams] = useSearchParams()

  const isAnalytics = view === 'analytics'
  const basePath = '/enterprise/site-visits'

  const status = searchParams.get('status') || ''
  const upcoming = searchParams.get('upcoming') === '1'
  const today = searchParams.get('range') === 'today'

  const [teamId, setTeamId] = useState('')
  const [assigneeId, setAssigneeId] = useState('')
  const [projectId, setProjectId] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [page, setPage] = useState(1)

  const [formOpen, setFormOpen] = useState(false)
  const [editingVisit, setEditingVisit] = useState(null)
  const [closingVisit, setClosingVisit] = useState(null)
  const [deletingVisit, setDeletingVisit] = useState(null)

  useEffect(() => {
    setPage(1)
  }, [teamId, assigneeId, projectId, from, to, status, upcoming, today])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    setSearchParams(next, { replace: true })
  }

  const clearFilters = () => {
    setTeamId('')
    setAssigneeId('')
    setProjectId('')
    setFrom('')
    setTo('')
    setSearchParams({}, { replace: true })
  }

  const hasFilters = Boolean(teamId || assigneeId || projectId || from || to || status || upcoming || today)

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'site-visits', { status, teamId, assigneeId, projectId, from, to, upcoming, today, page }],
    queryFn: async () => {
      const response = await enterpriseApi.siteVisits.list({
        status: status || undefined,
        team_id: teamId || undefined,
        assigned_to: assigneeId || undefined,
        project_id: projectId || undefined,
        from: from || undefined,
        to: to || undefined,
        upcoming: upcoming || undefined,
        range: today ? 'today' : undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'site-visits'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead'] })
  }

  const deleteMutation = useMutation({
    mutationFn: (visit) => enterpriseApi.siteVisits.remove(visit.id),
    onSuccess: () => {
      toast.success('Site visit deleted')
      setDeletingVisit(null)
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete this site visit.')),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const visits = data?.rows || []
  const meta = data?.meta || {}
  const stats = meta.stats || {}

  const tabs = STATUS_TABS.map((tab) => ({
    ...tab,
    count:
      tab.key === 'cancelled'
        ? stats.cancelled
        : tab.key === 'completed'
          ? stats.completed
          : tab.key === 'scheduled'
            ? stats.scheduled
            : undefined,
  }))

  const columns = [
    {
      key: 'lead',
      header: 'Lead',
      render: (visit) => (
        <div className="min-w-0">
          {visit.lead ? (
            <Link
              to={`/enterprise/leads/${visit.lead.id}`}
              className="block max-w-[200px] truncate text-sm font-semibold text-foreground hover:text-highlight"
            >
              {visit.lead.name}
            </Link>
          ) : (
            <p className="text-sm font-semibold text-foreground">Deleted lead</p>
          )}
          <p className="max-w-[200px] truncate text-xs text-muted-foreground">
            {visit.lead?.phone || visit.lead?.email || 'No contact details'}
          </p>
        </div>
      ),
    },
    {
      key: 'project',
      header: 'Project',
      render: (visit) => (
        <div className="min-w-0 text-xs">
          <p className="max-w-[200px] truncate font-medium text-foreground">{visit.project?.name || 'No project'}</p>
          <p className="max-w-[200px] truncate text-muted-foreground">
            {visit.unit?.full_path || visit.unit?.unit_number || 'No specific unit'}
          </p>
        </div>
      ),
    },
    {
      key: 'scheduled_at',
      header: 'Scheduled',
      render: (visit) => (
        <div className="whitespace-nowrap text-xs">
          <p className="font-medium text-foreground">{formatDateTime(visit.scheduled_at)}</p>
          <p className="text-muted-foreground">{visit.assignee?.name || 'Unassigned'}</p>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (visit) => <StatusBadge status={visit.status} />,
    },
    {
      key: 'outcome',
      header: 'Outcome',
      render: (visit) => (
        <p className="max-w-[220px] truncate text-xs text-muted-foreground">
          {visit.outcome || visit.cancellation_reason || '—'}
        </p>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (visit) => (
        <div className="flex items-center justify-end gap-1.5">
          {visit.status === 'scheduled' && (
            <>
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setEditingVisit(visit)
                  setFormOpen(true)
                }}
              >
                Reschedule
              </Button>
              <Button size="sm" onClick={() => setClosingVisit(visit)}>
                Close
              </Button>
            </>
          )}
          {visit.status !== 'completed' && (
            <Button
              size="icon-sm"
              variant="ghost"
              title="Delete visit"
              aria-label="Delete visit"
              onClick={() => setDeletingVisit(visit)}
            >
              <TrashIcon className="h-4 w-4" />
            </Button>
          )}
        </div>
      ),
    },
  ]

  const scheduledCount = Number(stats.scheduled || 0)
  const upcomingCount = Number(stats.upcoming || 0)
  const completedCount = Number(stats.completed || 0)
  const cancelledTotal = Number(stats.cancelled || 0)
  const totalVisits = scheduledCount + completedCount + cancelledTotal || Number(meta.total || 0)
  const completionRate = totalVisits > 0 ? Math.round((completedCount / totalVisits) * 100) : 0

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Site Visit Analytics' : 'Site visits'}
        description={
          isAnalytics
            ? 'Performance metrics, visit outcomes, and team scheduling overview.'
            : 'Schedule, reschedule and close customer visits across your projects.'
        }
        actions={
          <Button
            size="sm"
            onClick={() => {
              setEditingVisit(null)
              setFormOpen(true)
            }}
          >
            <PlusIcon className="h-4 w-4" />
            Schedule visit
          </Button>
        }
      />

      {/* Sub-navigation tabs */}
      <div className="mb-5 flex gap-2 border-b border-border pb-3">
        <Link
          to={`${basePath}/list`}
          className={cn(
            'rounded-lg px-3.5 py-1.5 text-xs font-medium transition',
            !isAnalytics
              ? 'bg-primary/10 font-semibold text-highlight'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
          )}
        >
          Site visits
        </Link>
        <Link
          to={`${basePath}/analytics`}
          className={cn(
            'rounded-lg px-3.5 py-1.5 text-xs font-medium transition',
            isAnalytics
              ? 'bg-primary/10 font-semibold text-highlight'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
          )}
        >
          Analytics
        </Link>
      </div>

      {isAnalytics ? (
        /* Analytics View (Pic 2 style) */
        <div className="space-y-6">
          {upcomingCount > 0 && (
            <div className="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs dark:border-blue-900/40 dark:bg-blue-950/20">
              <div className="flex items-center gap-2 font-medium text-blue-700 dark:text-blue-300">
                <ClockIcon className="h-4 w-4 text-blue-600" />
                <span>
                  You have {formatNumber(upcomingCount)} upcoming site visit{upcomingCount === 1 ? '' : 's'} scheduled across projects.
                </span>
              </div>
              <Link
                to={`${basePath}/list?upcoming=1`}
                className="font-semibold text-blue-700 underline hover:text-blue-800 dark:text-blue-300"
              >
                View upcoming visits &rarr;
              </Link>
            </div>
          )}

          <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard label="Scheduled" value={formatNumber(scheduledCount)} icon={CalendarDaysIcon} tone="info" loading={isLoading} />
            <StatsCard label="Upcoming" value={formatNumber(upcomingCount)} icon={ClockIcon} tone="warning" loading={isLoading} />
            <StatsCard label="Completed" value={formatNumber(completedCount)} icon={CheckCircleIcon} tone="success" loading={isLoading} />
            <StatsCard label="Cancelled / no-show" value={formatNumber(cancelledTotal)} icon={XCircleIcon} tone="danger" loading={isLoading} />
          </div>

          <div className="grid gap-5 lg:grid-cols-2">
            {/* Visit Completion & Success Rate */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between gap-4 pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Completion & Success Rate</h3>
                  <p className="text-xs text-muted-foreground">Percentage of planned visits completed</p>
                </div>
                <span className="text-2xl font-bold text-highlight shrink-0">{completionRate}%</span>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-emerald-500 transition-all duration-500"
                    style={{ width: `${completionRate}%` }}
                  />
                </div>

                <div className="grid grid-cols-3 gap-2 rounded-lg border border-border p-3 text-center">
                  <div>
                    <p className="text-xs text-muted-foreground">Total</p>
                    <p className="text-base font-bold text-foreground">{formatNumber(totalVisits)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Completed</p>
                    <p className="text-base font-bold text-emerald-600 dark:text-emerald-400">{formatNumber(completedCount)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Scheduled</p>
                    <p className="text-base font-bold text-blue-600 dark:text-blue-400">{formatNumber(scheduledCount)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Visit Status Lifecycle */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Status Distribution</h3>
                  <p className="text-xs text-muted-foreground">Breakdown of site visit outcomes</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-blue-500" />
                    Scheduled
                  </span>
                  <span className="font-semibold">{formatNumber(scheduledCount)}</span>
                </div>
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                    Completed
                  </span>
                  <span className="font-semibold">{formatNumber(completedCount)}</span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-red-500" />
                    Cancelled / Missed
                  </span>
                  <span className="font-semibold">{formatNumber(cancelledTotal)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Attendance & Outcomes Overview */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Attendance Overview</h3>
                  <p className="text-xs text-muted-foreground">Overall team showings ratio</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="rounded-lg border border-border p-3">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-foreground">Attended & Completed</span>
                    <span className="font-semibold text-emerald-600 dark:text-emerald-400">{completedCount}</span>
                  </div>
                  <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full bg-emerald-500 rounded-full"
                      style={{ width: `${totalVisits > 0 ? (completedCount / totalVisits) * 100 : 0}%` }}
                    />
                  </div>
                </div>
                <div className="rounded-lg border border-border p-3">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-foreground">Missed / Cancelled</span>
                    <span className="font-semibold text-red-600 dark:text-red-400">{cancelledTotal}</span>
                  </div>
                  <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full bg-red-500 rounded-full"
                      style={{ width: `${totalVisits > 0 ? (cancelledTotal / totalVisits) * 100 : 0}%` }}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Quick Actions & Navigation */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Quick Jump</h3>
                  <p className="text-xs text-muted-foreground">Directly access visit categories</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-2.5">
                <Link
                  to={`${basePath}/list`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span>All Site Visits</span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list?status=scheduled`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-blue-500" />
                    Scheduled Visits ({scheduledCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list?status=completed`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                    Completed Visits ({completedCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Button
                  size="sm"
                  className="w-full mt-2"
                  onClick={() => {
                    setEditingVisit(null)
                    setFormOpen(true)
                  }}
                >
                  <PlusIcon className="h-4 w-4" />
                  Schedule new visit
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      ) : (
        /* Site visits List View (Pic 3 style) */
        <Card>
          <CardContent className="px-0 pb-0 pt-4">
            <div className="px-5">
              <Tabs tabs={tabs} value={status} onChange={(value) => updateParam('status', value)} />

              <div className="flex flex-col gap-3 py-4">
                <div className="flex flex-wrap items-center gap-2">
                  <Select
                    value={projectId}
                    onChange={(event) => setProjectId(event.target.value)}
                    className="w-auto min-w-[170px]"
                  >
                    <option value="">All projects</option>
                    {projects.map((project) => (
                      <option key={project.id} value={project.id}>
                        {project.name}
                      </option>
                    ))}
                  </Select>
                  {canAssignLeads && (
                    <>
                      <Select value={teamId} onChange={(event) => setTeamId(event.target.value)} className="w-auto min-w-[140px]">
                        <option value="">All teams</option>
                        {teams.map((team) => (
                          <option key={team.id} value={team.id}>
                            {team.name}
                          </option>
                        ))}
                      </Select>
                      <Select value={assigneeId} onChange={(event) => setAssigneeId(event.target.value)} className="w-auto min-w-[150px]">
                        <option value="">All owners</option>
                        {assignableMembers.map((member) => (
                          <option key={member.user.id} value={member.user.id}>
                            {member.user.name}
                          </option>
                        ))}
                      </Select>
                    </>
                  )}
                  <label className="flex items-center gap-2 text-xs text-muted-foreground">
                    From
                    <input
                      type="date"
                      value={from}
                      onChange={(event) => setFrom(event.target.value)}
                      className="input-base h-9 w-36 py-0 text-xs"
                    />
                  </label>
                  <label className="flex items-center gap-2 text-xs text-muted-foreground">
                    To
                    <input
                      type="date"
                      value={to}
                      onChange={(event) => setTo(event.target.value)}
                      className="input-base h-9 w-36 py-0 text-xs"
                    />
                  </label>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    onClick={() => updateParam('upcoming', upcoming ? '' : '1')}
                    className={cn(
                      'rounded-full border px-3 py-1 text-[11px] font-medium transition',
                      upcoming
                        ? 'border-highlight/30 bg-primary/10 text-highlight'
                        : 'border-border text-muted-foreground hover:text-foreground',
                    )}
                  >
                    Upcoming only
                  </button>
                  <button
                    type="button"
                    onClick={() => updateParam('range', today ? '' : 'today')}
                    className={cn(
                      'rounded-full border px-3 py-1 text-[11px] font-medium transition',
                      today
                        ? 'border-highlight/30 bg-primary/10 text-highlight'
                        : 'border-border text-muted-foreground hover:text-foreground',
                    )}
                  >
                    Today
                  </button>
                  {hasFilters && (
                    <Button variant="ghost" size="sm" onClick={clearFilters}>
                      <XMarkIcon className="h-4 w-4" />
                      Clear
                    </Button>
                  )}
                  <p className="ml-auto text-xs text-muted-foreground">
                    {formatNumber(meta.total || 0)} visit{meta.total === 1 ? '' : 's'}
                    {isFetching && <span className="ml-2">Refreshing…</span>}
                  </p>
                </div>
              </div>
            </div>

            <div className="border-t border-border">
              <DataTable
                className="rounded-none border-0"
                columns={columns}
                rows={visits}
                loading={isLoading}
                rowKey="id"
                emptyState={
                  <EmptyState
                    icon={CalendarDaysIcon}
                    title={hasFilters ? 'No matching visits' : 'No site visits yet'}
                    description={
                      hasFilters
                        ? 'Clear a filter or choose a different date range.'
                        : 'Schedule your first customer visit and keep the team prepared.'
                    }
                    action={
                      hasFilters ? (
                        <Button variant="outline" size="sm" onClick={clearFilters}>
                          Clear filters
                        </Button>
                      ) : (
                        <Button size="sm" onClick={() => setFormOpen(true)}>
                          <PlusIcon className="h-4 w-4" />
                          Schedule visit
                        </Button>
                      )
                    }
                  />
                }
              />
            </div>

            <div className="border-t border-border px-4">
              <Pagination
                page={meta.current_page || 1}
                lastPage={meta.last_page || 1}
                total={meta.total || 0}
                perPage={meta.per_page || PAGE_SIZE}
                onPageChange={setPage}
              />
            </div>
          </CardContent>
        </Card>
      )}

      <VisitFormModal
        open={formOpen}
        onClose={() => {
          setFormOpen(false)
          setEditingVisit(null)
        }}
        visit={editingVisit}
      />
      <VisitCloseModal open={Boolean(closingVisit)} onClose={() => setClosingVisit(null)} visit={closingVisit} />

      <ConfirmDialog
        open={Boolean(deletingVisit)}
        onClose={() => setDeletingVisit(null)}
        onConfirm={() => deleteMutation.mutate(deletingVisit)}
        title="Delete this site visit?"
        description="The visit will be removed from schedules and lead history."
        confirmLabel="Delete visit"
        loading={deleteMutation.isPending}
      />
    </div>
  )
}
