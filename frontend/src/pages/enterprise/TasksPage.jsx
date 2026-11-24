import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { PriorityBadge } from '@/components/enterprise/StageBadge'
import { TaskFormModal } from '@/components/enterprise/TaskFormModal'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { Tabs } from '@/components/ui/Tabs'
import { useDebounce } from '@/hooks/useDebounce'
import { useAuth } from '@/hooks/useAuth'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { LEAD_PRIORITIES, TASK_STATUSES } from '@/utils/enterprise'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const STATUS_TABS = [{ key: '', label: 'All follow-ups' }, ...TASK_STATUSES]

export const TasksPage = ({ view = 'list' }) => {
  const queryClient = useQueryClient()
  const { user } = useAuth()
  const { isEmployee, canAssignLeads } = useEnterprise()
  const { teams, assignableMembers } = useEnterpriseReference()
  const [searchParams, setSearchParams] = useSearchParams()

  const isAnalytics = view === 'analytics'
  const basePath = '/enterprise/tasks'

  const status = searchParams.get('status') || ''
  const overdue = searchParams.get('overdue') === '1'
  const due = searchParams.get('due') || ''

  const [search, setSearch] = useState('')
  const [priority, setPriority] = useState('')
  const [assigneeId, setAssigneeId] = useState('')
  const [teamId, setTeamId] = useState('')
  const [page, setPage] = useState(1)
  const [formOpen, setFormOpen] = useState(false)
  const [editingTask, setEditingTask] = useState(null)
  const [deletingTask, setDeletingTask] = useState(null)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, priority, assigneeId, teamId, status, overdue, due])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    next.delete('page')
    setSearchParams(next, { replace: true })
  }

  const clearFilters = () => {
    setSearch('')
    setPriority('')
    setAssigneeId('')
    setTeamId('')
    setSearchParams({}, { replace: true })
  }

  const hasFilters = Boolean(search || priority || assigneeId || teamId || status || overdue || due)

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'tasks', { search: debouncedSearch, status, priority, assigneeId, teamId, overdue, due, page }],
    queryFn: async () => {
      const response = await enterpriseApi.tasks.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        priority: priority || undefined,
        assigned_to: assigneeId || undefined,
        team_id: teamId || undefined,
        overdue: overdue || undefined,
        due: due || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'tasks'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead'] })
  }

  const statusMutation = useMutation({
    mutationFn: ({ task, nextStatus }) => enterpriseApi.tasks.updateStatus(task.id, { status: nextStatus }),
    onSuccess: () => {
      toast.success('Follow-up status updated')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the follow-up status.')),
  })

  const deleteMutation = useMutation({
    mutationFn: (task) => enterpriseApi.tasks.remove(task.id),
    onSuccess: () => {
      toast.success('Follow-up deleted')
      setDeletingTask(null)
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete this follow-up.')),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const tasks = data?.rows || []
  const meta = data?.meta || {}
  const stats = meta.stats || {}

  const pendingCount = Number(stats.pending || 0)
  const inProgressCount = Number(stats.in_progress || 0)
  const completedCount = Number(stats.completed || 0)
  const cancelledCount = Number(stats.cancelled || 0)
  const overdueCount = Number(stats.overdue || 0)

  const totalTasks = pendingCount + inProgressCount + completedCount + cancelledCount || Number(meta.total || 0)
  const activeCount = pendingCount + inProgressCount
  const completionRate = totalTasks > 0 ? Math.round((completedCount / totalTasks) * 100) : 0

  // Priority counts with fallbacks
  const urgentCount = Number(stats.urgent ?? tasks.filter((t) => t.priority === 'urgent').length)
  const highCount = Number(stats.high ?? tasks.filter((t) => t.priority === 'high').length)
  const mediumCount = Number(stats.medium ?? tasks.filter((t) => t.priority === 'medium').length)
  const lowCount = Number(stats.low ?? tasks.filter((t) => t.priority === 'low').length)
  const totalPriorities = urgentCount + highCount + mediumCount + lowCount || 1

  const tabs = STATUS_TABS.map((tab) => ({
    ...tab,
    count: tab.key ? stats[tab.key] : undefined,
  }))

  const columns = [
    {
      key: 'title',
      header: 'Follow up',
      render: (task) => (
        <div className="min-w-0">
          <p
            className={cn(
              'max-w-[320px] truncate text-sm font-medium',
              task.status === 'completed' ? 'text-muted-foreground line-through' : 'text-foreground',
            )}
          >
            {task.title}
          </p>
          <p className="max-w-[320px] truncate text-xs text-muted-foreground">
            {task.description || task.team?.name || 'No description'}
          </p>
        </div>
      ),
    },
    {
      key: 'lead',
      header: 'Lead',
      render: (task) =>
        task.lead ? (
          <Link
            to={`/enterprise/leads/${task.lead.id}`}
            onClick={(event) => event.stopPropagation()}
            className="block max-w-[170px] truncate text-xs font-medium text-highlight hover:underline"
          >
            {task.lead.name}
          </Link>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'assignee',
      header: 'Owner',
      render: (task) => (
        <div className="min-w-0 text-xs">
          <p className="truncate font-medium text-foreground">{task.assignee?.name || 'Unassigned'}</p>
          <p className="truncate text-muted-foreground">{task.team?.name || 'No team'}</p>
        </div>
      ),
    },
    {
      key: 'priority',
      header: 'Priority',
      render: (task) => <PriorityBadge priority={task.priority} />,
    },
    {
      key: 'due_at',
      header: 'Due',
      render: (task) =>
        task.due_at ? (
          <span
            className={cn(
              'whitespace-nowrap text-xs',
              task.is_overdue ? 'font-medium text-red-600 dark:text-red-400' : 'text-muted-foreground',
            )}
          >
            {task.is_overdue && task.status !== 'completed' ? 'Overdue · ' : ''}
            {formatDateTime(task.due_at)}
          </span>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (task) => (
        <Select
          value={task.status}
          onChange={(event) => statusMutation.mutate({ task, nextStatus: event.target.value })}
          disabled={statusMutation.isPending && statusMutation.variables?.task?.id === task.id}
          className="h-8 min-w-[130px] py-0 text-xs"
          aria-label={`Change status of ${task.title}`}
        >
          {TASK_STATUSES.map((item) => (
            <option key={item.value} value={item.value}>
              {item.label}
            </option>
          ))}
        </Select>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (task) => {
        const canDelete = !isEmployee || task.creator?.id === user?.id
        return (
          <div className="flex items-center justify-end gap-1">
            {!['completed', 'cancelled'].includes(task.status) && (
              <Button
                size="icon-sm"
                variant="ghost"
                title="Mark complete"
                aria-label={`Complete ${task.title}`}
                onClick={() => statusMutation.mutate({ task, nextStatus: 'completed' })}
              >
                <CheckCircleIcon className="h-4 w-4" />
              </Button>
            )}
            <Button
              size="icon-sm"
              variant="ghost"
              title="Edit follow-up"
              aria-label={`Edit ${task.title}`}
              onClick={() => {
                setEditingTask(task)
                setFormOpen(true)
              }}
            >
              <PencilSquareIcon className="h-4 w-4" />
            </Button>
            {canDelete && (
              <Button
                size="icon-sm"
                variant="ghost"
                title="Delete follow-up"
                aria-label={`Delete ${task.title}`}
                onClick={() => setDeletingTask(task)}
              >
                <TrashIcon className="h-4 w-4" />
              </Button>
            )}
          </div>
        )
      },
    },
  ]

  const filterChips = [
    overdue && { key: 'overdue', label: 'Overdue only' },
    due === 'today' && { key: 'due', label: 'Due today' },
    due === 'upcoming' && { key: 'due', label: 'Upcoming' },
  ].filter(Boolean)

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Follow up Analytics' : 'Follow ups'}
        description={
          isAnalytics
            ? 'Performance metrics, completion rates, and team follow-up overview.'
            : 'Follow-ups and to-dos across your leads and team.'
        }
        actions={
          <Button
            size="sm"
            onClick={() => {
              setEditingTask(null)
              setFormOpen(true)
            }}
          >
            <PlusIcon className="h-4 w-4" />
            Add follow up
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
          Follow ups
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
          {overdueCount > 0 && (
            <div className="flex items-center justify-between rounded-xl border border-red-200 bg-red-50 p-4 text-xs dark:border-red-900/40 dark:bg-red-950/20">
              <div className="flex items-center gap-2 font-medium text-red-700 dark:text-red-300">
                <ExclamationTriangleIcon className="h-4 w-4 text-red-600" />
                <span>
                  {formatNumber(overdueCount)} follow-up{overdueCount === 1 ? '' : 's'} past due date requiring attention.
                </span>
              </div>
              <Link
                to={`${basePath}/list?overdue=1`}
                className="font-semibold text-red-700 underline hover:text-red-800 dark:text-red-300"
              >
                View overdue follow-ups &rarr;
              </Link>
            </div>
          )}

          <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
              label="Pending"
              value={formatNumber(pendingCount)}
              icon={ClockIcon}
              tone="warning"
              hint="Awaiting action"
              loading={isLoading}
            />
            <StatsCard
              label="In progress"
              value={formatNumber(inProgressCount)}
              icon={ClipboardDocumentCheckIcon}
              tone="info"
              hint="Actively in work"
              loading={isLoading}
            />
            <StatsCard
              label="Completed"
              value={formatNumber(completedCount)}
              icon={CheckCircleIcon}
              tone="success"
              hint="Resolved follow-ups"
              loading={isLoading}
            />
            <StatsCard
              label="Overdue"
              value={formatNumber(overdueCount)}
              icon={ClockIcon}
              tone="danger"
              hint="Past scheduled date"
              loading={isLoading}
            />
          </div>

          <div className="grid gap-5 lg:grid-cols-2">
            {/* Completion Rate & Performance */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between gap-4 pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Completion Rate & Health</h3>
                  <p className="text-xs text-muted-foreground">Follow-up execution efficiency</p>
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
                    <p className="text-base font-bold text-foreground">{formatNumber(totalTasks)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Completed</p>
                    <p className="text-base font-bold text-emerald-600 dark:text-emerald-400">{formatNumber(completedCount)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Active</p>
                    <p className="text-base font-bold text-amber-600 dark:text-amber-400">{formatNumber(activeCount)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Follow-ups by Priority */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Follow-ups by Priority</h3>
                  <p className="text-xs text-muted-foreground">Workload split across urgency levels</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-red-600 dark:text-red-400">Urgent</span>
                    <span className="text-muted-foreground">{urgentCount} ({Math.round((urgentCount / totalPriorities) * 100)}%)</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-full bg-red-500 rounded-full" style={{ width: `${(urgentCount / totalPriorities) * 100}%` }} />
                  </div>
                </div>

                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-orange-600 dark:text-orange-400">High</span>
                    <span className="text-muted-foreground">{highCount} ({Math.round((highCount / totalPriorities) * 100)}%)</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-full bg-orange-500 rounded-full" style={{ width: `${(highCount / totalPriorities) * 100}%` }} />
                  </div>
                </div>

                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-blue-600 dark:text-blue-400">Medium</span>
                    <span className="text-muted-foreground">{mediumCount} ({Math.round((mediumCount / totalPriorities) * 100)}%)</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-full bg-blue-500 rounded-full" style={{ width: `${(mediumCount / totalPriorities) * 100}%` }} />
                  </div>
                </div>

                <div className="space-y-1">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-muted-foreground">Low</span>
                    <span className="text-muted-foreground">{lowCount} ({Math.round((lowCount / totalPriorities) * 100)}%)</span>
                  </div>
                  <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div className="h-full bg-muted-foreground/50 rounded-full" style={{ width: `${(lowCount / totalPriorities) * 100}%` }} />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Status Distribution */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Status Breakdown</h3>
                  <p className="text-xs text-muted-foreground">Overall follow-up status lifecycle</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-amber-500" />
                    Pending
                  </span>
                  <span className="font-semibold">{formatNumber(pendingCount)}</span>
                </div>
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-blue-500" />
                    In Progress
                  </span>
                  <span className="font-semibold">{formatNumber(inProgressCount)}</span>
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
                    <span className="h-2 w-2 rounded-full bg-zinc-400" />
                    Cancelled
                  </span>
                  <span className="font-semibold">{formatNumber(cancelledCount)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Quick Actions & Navigation */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Quick Jump</h3>
                  <p className="text-xs text-muted-foreground">Jump directly into filtered lists</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-2.5">
                <Link
                  to={`${basePath}/list`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span>All Follow-ups</span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list?status=pending`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-amber-500" />
                    Pending Follow-ups ({pendingCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list?overdue=1`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-red-500" />
                    Overdue Follow-ups ({overdueCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Button
                  size="sm"
                  className="w-full mt-2"
                  onClick={() => {
                    setEditingTask(null)
                    setFormOpen(true)
                  }}
                >
                  <PlusIcon className="h-4 w-4" />
                  Add new follow-up
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      ) : (
        /* Follow ups List View (Pic 3 style) */
        <Card>
          <CardContent className="px-0 pb-0 pt-4">
            <div className="px-5">
              <Tabs tabs={tabs} value={status} onChange={(value) => updateParam('status', value)} />

              <div className="flex flex-col gap-3 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex flex-1 flex-wrap items-center gap-2">
                  <SearchInput
                    value={search}
                    onChange={setSearch}
                    placeholder="Search follow-ups…"
                    className="w-full sm:w-72"
                  />
                  <Select value={priority} onChange={(event) => setPriority(event.target.value)} className="w-auto min-w-[130px]">
                    <option value="">All priorities</option>
                    {LEAD_PRIORITIES.map((item) => (
                      <option key={item.value} value={item.value}>
                        {item.label}
                      </option>
                    ))}
                  </Select>
                  <Select value={due} onChange={(event) => updateParam('due', event.target.value)} className="w-auto min-w-[130px]">
                    <option value="">Any due date</option>
                    <option value="today">Due today</option>
                    <option value="upcoming">Upcoming</option>
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
                </div>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => updateParam('overdue', overdue ? '' : '1')}
                    className={cn(
                      'rounded-full border px-3 py-1 text-[11px] font-medium transition',
                      overdue
                        ? 'border-destructive/30 bg-destructive/10 text-destructive'
                        : 'border-border text-muted-foreground hover:text-foreground',
                    )}
                  >
                    Overdue only
                  </button>
                  {hasFilters && (
                    <Button variant="ghost" size="sm" onClick={clearFilters}>
                      <XMarkIcon className="h-4 w-4" />
                      Clear
                    </Button>
                  )}
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-2 pb-4">
                {filterChips.map((chip) => (
                  <button
                    key={chip.key}
                    type="button"
                    onClick={() => updateParam(chip.key, '')}
                    className="inline-flex items-center gap-1 rounded-full border border-border bg-muted/50 px-2.5 py-1 text-[11px] font-medium text-foreground transition hover:bg-muted"
                  >
                    {chip.label}
                    <XMarkIcon className="h-3 w-3" />
                  </button>
                ))}
                <p className="text-xs text-muted-foreground">
                  {formatNumber(meta.total || 0)} follow-up{meta.total === 1 ? '' : 's'}
                  {isFetching && <span className="ml-2">Refreshing…</span>}
                </p>
              </div>
            </div>

            <div className="border-t border-border">
              <DataTable
                className="rounded-none border-0"
                columns={columns}
                rows={tasks}
                loading={isLoading}
                rowKey="id"
                emptyState={
                  <EmptyState
                    icon={ClipboardDocumentCheckIcon}
                    title={hasFilters ? 'No matching follow-ups' : 'No follow-ups yet'}
                    description={
                      hasFilters
                        ? 'Clear a filter or search for a different follow-up.'
                        : 'Create a follow-up so every task has an owner and a due date.'
                    }
                    action={
                      hasFilters ? (
                        <Button variant="outline" size="sm" onClick={clearFilters}>
                          Clear filters
                        </Button>
                      ) : (
                        <Button size="sm" onClick={() => setFormOpen(true)}>
                          <PlusIcon className="h-4 w-4" />
                          Add follow up
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

      <TaskFormModal
        open={formOpen}
        onClose={() => {
          setFormOpen(false)
          setEditingTask(null)
        }}
        task={editingTask}
      />

      <ConfirmDialog
        open={Boolean(deletingTask)}
        onClose={() => setDeletingTask(null)}
        onConfirm={() => deleteMutation.mutate(deletingTask)}
        title="Delete this follow-up?"
        description={`"${deletingTask?.title || 'This follow-up'}" will be permanently removed.`}
        confirmLabel="Delete follow-up"
        loading={deleteMutation.isPending}
      />
    </div>
  )
}
