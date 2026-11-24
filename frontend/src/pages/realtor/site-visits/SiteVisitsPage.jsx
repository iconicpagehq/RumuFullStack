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
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { Switch } from '@/components/ui/Switch'
import { Tabs } from '@/components/ui/Tabs'
import { cn } from '@/utils/cn'
import { VISIT_STATUSES } from '@/utils/enterprise'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber, formatPhone } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const STATUS_TABS = [{ key: '', label: 'All visits' }, ...VISIT_STATUSES]

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

const VisitFormModal = ({ open, onClose, visit = null }) => {
  const queryClient = useQueryClient()
  const isEdit = Boolean(visit?.id)
  const [form, setForm] = useState({ lead_id: '', inventory_project_id: '', inventory_unit_id: '', scheduled_at: '' })
  const [error, setError] = useState('')

  useEffect(() => {
    if (!open) return
    setForm({
      lead_id: visit?.lead?.id ? String(visit.lead.id) : '',
      inventory_project_id: visit?.project?.id ? String(visit.project.id) : '',
      inventory_unit_id: visit?.unit?.id ? String(visit.unit.id) : '',
      scheduled_at: toDateTimeInput(visit?.scheduled_at),
    })
    setError('')
  }, [open, visit])

  const leadsQuery = useQuery({
    queryKey: ['realtor', 'lead-options'],
    queryFn: async () => {
      const response = await realtorApi.leads.all({ per_page: 200 })
      return response.data?.data || []
    },
    enabled: open,
    staleTime: 30_000,
  })

  const projectsQuery = useQuery({
    queryKey: ['realtor', 'inventory-projects'],
    queryFn: async () => {
      const response = await realtorApi.inventory.projects()
      return response.data?.data || []
    },
    enabled: open,
    staleTime: 60_000,
  })

  const unitsQuery = useQuery({
    queryKey: ['realtor', 'visit-units', form.inventory_project_id],
    queryFn: async () => {
      const response = await realtorApi.inventory.searchUnits({
        project_id: Number(form.inventory_project_id),
      })
      return response.data?.data || []
    },
    enabled: open && Boolean(form.inventory_project_id),
    staleTime: 30_000,
  })

  const mutation = useMutation({
    mutationFn: (payload) =>
      isEdit ? realtorApi.siteVisits.update(visit.id, payload) : realtorApi.siteVisits.create(payload),
    onSuccess: () => {
      toast.success(isEdit ? 'Site visit updated' : 'Site visit scheduled')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'site-visits'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      onClose()
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not save this site visit.')),
  })

  const update = (key) => (event) => {
    const value = event.target.value
    setForm((previous) => ({
      ...previous,
      [key]: value,
      ...(key === 'inventory_project_id' ? { inventory_unit_id: '' } : {}),
    }))
  }

  const submit = (event) => {
    event.preventDefault()
    if (!form.lead_id) {
      setError('Choose the lead this visit is for.')
      return
    }
    if (!form.scheduled_at) {
      setError('Choose the date and time for this visit.')
      return
    }
    setError('')
    mutation.mutate({
      lead_id: Number(form.lead_id),
      inventory_project_id: form.inventory_project_id ? Number(form.inventory_project_id) : null,
      inventory_unit_id: form.inventory_unit_id ? Number(form.inventory_unit_id) : null,
      scheduled_at: new Date(form.scheduled_at).toISOString(),
    })
  }

  const unitOptions = unitsQuery.data || []
  const currentUnitMissing =
    visit?.unit?.id && !unitOptions.some((unit) => unit.id === visit.unit.id)

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Reschedule site visit' : 'Schedule site visit'}
      description="Pick the lead, project and time for the visit."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={submit} loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Schedule visit'}
          </Button>
        </>
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {error && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </p>
        )}
        <FormField label="Lead" htmlFor="realtor-visit-lead" required>
          <Select id="realtor-visit-lead" value={form.lead_id} onChange={update('lead_id')} autoFocus>
            <option value="">Select a lead</option>
            {(leadsQuery.data || []).map((lead) => (
              <option key={lead.id} value={lead.id}>
                {lead.name}
                {lead.phone ? ` · ${formatPhone(lead.phone)}` : ''}
              </option>
            ))}
          </Select>
        </FormField>
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Project" htmlFor="realtor-visit-project">
            <Select id="realtor-visit-project" value={form.inventory_project_id} onChange={update('inventory_project_id')}>
              <option value="">No project</option>
              {(projectsQuery.data || []).map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Unit" htmlFor="realtor-visit-unit">
            <Select
              id="realtor-visit-unit"
              value={form.inventory_unit_id}
              onChange={update('inventory_unit_id')}
              disabled={!form.inventory_project_id}
            >
              <option value="">No specific unit</option>
              {currentUnitMissing && (
                <option value={visit.unit.id}>
                  {visit.unit.unit_number || `Unit #${visit.unit.id}`} (current)
                </option>
              )}
              {unitOptions.map((unit) => (
                <option key={unit.id} value={unit.id}>
                  {unit.label || unit.unit_number}
                </option>
              ))}
            </Select>
          </FormField>
        </div>
        <FormField label="Date & time" htmlFor="realtor-visit-scheduled" required>
          <Input
            id="realtor-visit-scheduled"
            type="datetime-local"
            value={form.scheduled_at}
            onChange={update('scheduled_at')}
          />
        </FormField>
      </form>
    </Modal>
  )
}

const VisitCloseModal = ({ open, onClose, visit = null }) => {
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('completed')
  const [attended, setAttended] = useState(true)
  const [outcome, setOutcome] = useState('')
  const [feedback, setFeedback] = useState('')
  const [nextAction, setNextAction] = useState('')
  const [cancellationReason, setCancellationReason] = useState('')
  const [error, setError] = useState('')

  useEffect(() => {
    if (!open) return
    setStatus('completed')
    setAttended(true)
    setOutcome('')
    setFeedback('')
    setNextAction('')
    setCancellationReason('')
    setError('')
  }, [open, visit])

  const mutation = useMutation({
    mutationFn: (payload) => realtorApi.siteVisits.updateStatus(visit.id, payload),
    onSuccess: () => {
      toast.success('Site visit closed')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'site-visits'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      onClose()
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not close this site visit.')),
  })

  if (!visit) return null

  const submit = (event) => {
    event.preventDefault()
    if (status === 'completed' && !outcome.trim()) {
      setError('Add the visit outcome before closing.')
      return
    }
    if (status === 'cancelled' && !cancellationReason.trim()) {
      setError('Add a cancellation reason.')
      return
    }
    setError('')
    mutation.mutate({
      status,
      customer_attended: status === 'completed' ? attended : false,
      outcome: status === 'completed' ? outcome.trim() : null,
      feedback: feedback.trim() || null,
      next_action: nextAction.trim() || null,
      cancellation_reason: status === 'cancelled' ? cancellationReason.trim() : null,
    })
  }

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Close site visit"
      description={visit.lead?.name ? `${visit.lead.name} · ${formatDateTime(visit.scheduled_at)}` : undefined}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={submit} loading={mutation.isPending}>
            Save outcome
          </Button>
        </>
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {error && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </p>
        )}
        <FormField label="Outcome" htmlFor="realtor-visit-outcome-status" required>
          <Select id="realtor-visit-outcome-status" value={status} onChange={(event) => setStatus(event.target.value)}>
            <option value="completed">Visit completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="no_show">Customer did not show</option>
          </Select>
        </FormField>

        {status === 'completed' && (
          <>
            <Switch
              checked={attended}
              onChange={setAttended}
              label="Customer attended"
              description="Turn off if the customer did not turn up."
            />
            <FormField label="Visit outcome" htmlFor="realtor-visit-outcome" required>
              <Textarea
                id="realtor-visit-outcome"
                rows={3}
                value={outcome}
                onChange={(event) => setOutcome(event.target.value)}
                placeholder="What did the customer like? Which unit are they considering?"
              />
            </FormField>
            <FormField label="Customer feedback" htmlFor="realtor-visit-feedback">
              <Textarea
                id="realtor-visit-feedback"
                rows={2}
                value={feedback}
                onChange={(event) => setFeedback(event.target.value)}
              />
            </FormField>
            <FormField label="Next action" htmlFor="realtor-visit-next-action">
              <Input
                id="realtor-visit-next-action"
                value={nextAction}
                onChange={(event) => setNextAction(event.target.value)}
                placeholder="e.g. Send cost sheet for Tower B"
              />
            </FormField>
          </>
        )}

        {status === 'cancelled' && (
          <FormField label="Cancellation reason" htmlFor="realtor-visit-cancellation" required>
            <Textarea
              id="realtor-visit-cancellation"
              rows={3}
              value={cancellationReason}
              onChange={(event) => setCancellationReason(event.target.value)}
            />
          </FormField>
        )}

        {status === 'no_show' && (
          <>
            <FormField label="What happened?" htmlFor="realtor-visit-no-show">
              <Textarea
                id="realtor-visit-no-show"
                rows={2}
                value={feedback}
                onChange={(event) => setFeedback(event.target.value)}
                placeholder="Record any follow-up context."
              />
            </FormField>
            <FormField label="Next action" htmlFor="realtor-visit-no-show-next">
              <Input
                id="realtor-visit-no-show-next"
                value={nextAction}
                onChange={(event) => setNextAction(event.target.value)}
                placeholder="e.g. Call again tomorrow"
              />
            </FormField>
          </>
        )}
      </form>
    </Modal>
  )
}

export const SiteVisitsPage = ({ view = 'list' }) => {
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const isAnalytics = view === 'analytics'
  const basePath = '/realtor/site-visits'

  const status = searchParams.get('status') || ''

  const [leadId, setLeadId] = useState('')
  const [page, setPage] = useState(1)
  const [formOpen, setFormOpen] = useState(false)
  const [editingVisit, setEditingVisit] = useState(null)
  const [closingVisit, setClosingVisit] = useState(null)
  const [deletingVisit, setDeletingVisit] = useState(null)

  useEffect(() => {
    setPage(1)
  }, [leadId, status])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    setSearchParams(next, { replace: true })
  }

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['realtor', 'site-visits', { status, leadId, page }],
    queryFn: async () => {
      const response = await realtorApi.siteVisits.list({
        status: status || undefined,
        lead_id: leadId || undefined,
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
    queryClient.invalidateQueries({ queryKey: ['realtor', 'site-visits'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
  }

  const deleteMutation = useMutation({
    mutationFn: (visit) => realtorApi.siteVisits.remove(visit.id),
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
  const statusCounts = meta.status_counts || {}

  const tabs = STATUS_TABS.map((tab) => ({
    ...tab,
    count: tab.key ? statusCounts[tab.key] : undefined,
  }))

  const columns = [
    {
      key: 'lead',
      header: 'Lead',
      render: (visit) => (
        <div className="min-w-0">
          <p className="max-w-[200px] truncate text-sm font-semibold text-foreground">
            {visit.lead?.name || 'Deleted lead'}
          </p>
          <p className="text-xs text-muted-foreground">{formatPhone(visit.lead?.phone) || '—'}</p>
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
            {visit.unit?.unit_number || 'No specific unit'}
          </p>
        </div>
      ),
    },
    {
      key: 'scheduled_at',
      header: 'Scheduled',
      render: (visit) => (
        <span className="whitespace-nowrap text-xs font-medium text-foreground">
          {formatDateTime(visit.scheduled_at)}
        </span>
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
          <Button
            size="icon-sm"
            variant="ghost"
            title="Delete visit"
            aria-label="Delete visit"
            onClick={() => setDeletingVisit(visit)}
          >
            <TrashIcon className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ]

  const scheduledCount = Number(statusCounts.scheduled || 0)
  const upcomingCount = Number(meta.upcoming_count || 0)
  const completedCount = Number(statusCounts.completed || 0)
  const cancelledOnlyCount = Number(statusCounts.cancelled || 0)
  const noShowCount = Number(statusCounts.no_show || 0)
  const cancelledTotal = cancelledOnlyCount + noShowCount
  const totalVisits = scheduledCount + completedCount + cancelledTotal || Number(meta.total || 0)
  const completionRate = totalVisits > 0 ? Math.round((completedCount / totalVisits) * 100) : 0

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Site Visit Analytics' : 'Site visits'}
        description={
          isAnalytics
            ? 'Performance metrics, visit outcomes, and scheduling overview.'
            : 'Schedule, reschedule and close your customer visits.'
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
                  You have {formatNumber(upcomingCount)} upcoming site visit{upcomingCount === 1 ? '' : 's'} scheduled.
                </span>
              </div>
              <Link
                to={`${basePath}/list`}
                className="font-semibold text-blue-700 underline hover:text-blue-800 dark:text-blue-300"
              >
                View scheduled visits &rarr;
              </Link>
            </div>
          )}

          <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
              label="Scheduled"
              value={formatNumber(scheduledCount)}
              icon={CalendarDaysIcon}
              tone="info"
              hint="Upcoming & in queue"
              loading={isLoading}
            />
            <StatsCard
              label="Upcoming"
              value={formatNumber(upcomingCount)}
              icon={ClockIcon}
              tone="warning"
              hint="Future touchpoints"
              loading={isLoading}
            />
            <StatsCard
              label="Completed"
              value={formatNumber(completedCount)}
              icon={CheckCircleIcon}
              tone="success"
              hint="Finished visits"
              loading={isLoading}
            />
            <StatsCard
              label="Cancelled / no-show"
              value={formatNumber(cancelledTotal)}
              icon={XCircleIcon}
              tone="danger"
              hint="Cancelled or missed"
              loading={isLoading}
            />
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
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-red-500" />
                    Cancelled
                  </span>
                  <span className="font-semibold">{formatNumber(cancelledOnlyCount)}</span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-amber-500" />
                    Customer No-Show
                  </span>
                  <span className="font-semibold">{formatNumber(noShowCount)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Attendance & Outcomes Overview */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Attendance Overview</h3>
                  <p className="text-xs text-muted-foreground">Customer attendance ratio</p>
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

              <div className="flex flex-wrap items-center gap-2 py-4">
                <Select
                  value={leadId}
                  onChange={(event) => setLeadId(event.target.value)}
                  className="w-auto min-w-[200px]"
                >
                  <option value="">All leads</option>
                  {(leadsQuery.data || []).map((lead) => (
                    <option key={lead.id} value={lead.id}>
                      {lead.name}
                    </option>
                  ))}
                </Select>
                <p className="ml-auto text-xs text-muted-foreground">
                  {formatNumber(meta.total || 0)} visit{meta.total === 1 ? '' : 's'}
                  {isFetching && <span className="ml-2">Refreshing…</span>}
                </p>
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
                    title={status || leadId ? 'No matching visits' : 'No site visits yet'}
                    description={
                      status || leadId
                        ? 'Clear a filter or choose a different lead.'
                        : 'Schedule your first customer visit to keep every showing on track.'
                    }
                    action={
                      status || leadId ? (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setLeadId('')
                            setSearchParams({}, { replace: true })
                          }}
                        >
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
        description="The visit will be removed from your schedule."
        confirmLabel="Delete visit"
        loading={deleteMutation.isPending}
      />
    </div>
  )
}
