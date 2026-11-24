import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { PriorityBadge } from '@/components/enterprise/StageBadge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { TaskFormModal } from '@/components/realtor/TaskFormModal'
import { useDebounce } from '@/hooks/useDebounce'
import { TASK_STATUSES } from '@/utils/enterprise'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber, formatPhone } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const STATUS_TABS = [{ key: '', label: 'All follow-ups' }, ...TASK_STATUSES]

export const TasksPage = ({ view = 'list' }) => {
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const isAnalytics = view === 'analytics'
  const basePath = '/realtor/tasks'

  const status = searchParams.get('status') || ''
  const overdue = searchParams.get('overdue') === '1'

  const [search, setSearch] = useState('')
  const [leadId, setLeadId] = useState('')
  const [page, setPage] = useState(1)
  const [formOpen, setFormOpen] = useState(false)
  const [editingTask, setEditingTask] = useState(null)
  const [deletingTask, setDeletingTask] = useState(null)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, leadId, status, overdue])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    next.delete('page')
    setSearchParams(next, { replace: true })
  }

  const clearFilters = () => {
    setSearch('')
    setLeadId('')
    setSearchParams({}, { replace: true })
  }

  const hasFilters = Boolean(search || leadId || status || overdue)

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['realtor', 'tasks', { search: debouncedSearch, status, leadId, overdue, page }],
    queryFn: async () => {
      const response = await realtorApi.tasks.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        lead_id: leadId || undefined,
        overdue: overdue || undefined,
        page,
        per_page: PAGE_SIZE,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const leadsQuery = useQuery({
    queryKey: ['realtor', 'lead-options'],
    queryFn: async () => {
      const response = await realtorApi.leads.all({ per_page: 200 })
      return response.data?.data || []
    },
    staleTime: 30_000,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['realtor', 'tasks'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
  }

  const statusMutation = useMutation({
    mutationFn: ({ task, nextStatus }) => realtorApi.tasks.updateStatus(task.id, { status: nextStatus }),
    onSuccess: () => {
      toast.success('Follow-up status updated')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the follow-up status.')),
  })

  const deleteMutation = useMutation({
    mutationFn: (task) => realtorApi.tasks.remove(task.id),
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
  const statusCounts = meta.status_counts || {}
  const priorityCounts = meta.priority_counts || {}

  const pendingCount = Number(statusCounts.pending || 0)
  const inProgressCount = Number(statusCounts.in_progress || 0)
  const completedCount = Number(statusCounts.completed || 0)
  const cancelledCount = Number(statusCounts.cancelled || 0)
  const overdueCount = Number(meta.overdue_count || 0)

  const totalTasks = pendingCount + inProgressCount + completedCount + cancelledCount || Number(meta.total || 0)
  const activeCount = pendingCount + inProgressCount
  const completionRate = totalTasks > 0 ? Math.round((completedCount / totalTasks) * 100) : 0

  // Priority counts with fallbacks
  const urgentCount = Number(priorityCounts.urgent ?? tasks.filter((t) => t.priority === 'urgent').length)
  const highCount = Number(priorityCounts.high ?? tasks.filter((t) => t.priority === 'high').length)
  const mediumCount = Number(priorityCounts.medium ?? tasks.filter((t) => t.priority === 'medium').length)
  const lowCount = Number(priorityCounts.low ?? tasks.filter((t) => t.priority === 'low').length)
  const totalPriorities = urgentCount + highCount + mediumCount + lowCount || 1

  const tabs = STATUS_TABS.map((tab) => ({
    ...tab,
    count: tab.key ? statusCounts[tab.key] : undefined,
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
            {task.description || 'No description'}
          </p>
        </div>
      ),
    },
    {
      key: 'lead',
      header: 'Lead',
      render: (task) =>
        task.lead ? (
          <div className="min-w-0">
            <p className="max-w-[180px] truncate text-xs font-medium text-foreground">{task.lead.name}</p>
            <p className="text-xs text-muted-foreground">{formatPhone(task.lead.phone) || '—'}</p>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
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
              task.is_overdue && task.status !== 'completed'
                ? 'font-medium text-red-600 dark:text-red-400'
                : 'text-muted-foreground',
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
      render: (task) => (
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
          <Button
            size="icon-sm"
            variant="ghost"
            title="Delete follow-up"
            aria-label={`Delete ${task.title}`}
            onClick={() => setDeletingTask(task)}
          >
            <TrashIcon className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Follow up Analytics' : 'Follow ups'}
        description={
          isAnalytics
            ? 'Performance metrics, completion rates, and follow-up workload overview.'
            : 'Your personal follow-ups and client touchpoints.'
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
                  <Select
                    value={leadId}
                    onChange={(event) => setLeadId(event.target.value)}
                    className="w-auto min-w-[180px]"
                  >
                    <option value="">All leads</option>
                    {(leadsQuery.data || []).map((lead) => (
                      <option key={lead.id} value={lead.id}>
                        {lead.name}
                      </option>
                    ))}
                  </Select>
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

              <p className="pb-4 text-xs text-muted-foreground">
                {formatNumber(meta.total || 0)} follow-up{meta.total === 1 ? '' : 's'}
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
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
                        : 'Create a follow-up so every task has a due date.'
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
