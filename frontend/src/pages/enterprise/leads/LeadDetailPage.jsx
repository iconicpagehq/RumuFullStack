import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowPathIcon,
  CalendarDaysIcon,
  ChatBubbleLeftIcon,
  CheckCircleIcon,
  ClockIcon,
  EnvelopeIcon,
  PencilSquareIcon,
  PhoneIcon,
  PlusIcon,
  SparklesIcon,
  TrashIcon,
  UserPlusIcon,
} from '@heroicons/react/24/outline'
import { enterpriseCallsApi } from '@/api/calls'
import { enterpriseApi } from '@/api/enterprise'
import { LeadCallsPanel } from '@/components/calls/LeadCallsPanel'
import { LeadAbout, LeadActionTiles, LeadNoteComposer, LeadStageRail, LeadTimeline } from '@/components/leads/LeadDetailUI'
import { StageBadge, PriorityBadge } from '@/components/enterprise/StageBadge'
import { LeadFormModal } from '@/components/enterprise/LeadFormModal'
import { StageUpdateModal } from '@/components/enterprise/StageUpdateModal'
import { AssignLeadModal } from '@/components/enterprise/AssignLeadModal'
import { TaskFormModal } from '@/components/enterprise/TaskFormModal'
import { VisitFormModal } from '@/components/enterprise/VisitFormModal'
import { VisitCloseModal } from '@/components/enterprise/VisitCloseModal'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Tabs } from '@/components/ui/Tabs'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useAuthStore } from '@/stores/authStore'
import { cn } from '@/utils/cn'
import { ENTERPRISE_STAGES, STAGE_TRANSITIONS, stageLabel } from '@/utils/enterprise'
import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
  formatPhone,
  timeAgo,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const activityMeta = {
  created: { icon: SparklesIcon, tone: 'success' },
  updated: { icon: PencilSquareIcon, tone: 'neutral' },
  assigned: { icon: UserPlusIcon, tone: 'info' },
  reassigned: { icon: UserPlusIcon, tone: 'info' },
  status_changed: { icon: ArrowPathIcon, tone: 'warning' },
  note: { icon: ChatBubbleLeftIcon, tone: 'primary' },
  note_added: { icon: ChatBubbleLeftIcon, tone: 'primary' },
  site_visit_scheduled: { icon: CalendarDaysIcon, tone: 'purple' },
  site_visit_completed: { icon: CalendarDaysIcon, tone: 'success' },
  site_visit_cancelled: { icon: CalendarDaysIcon, tone: 'danger' },
}

const taskIsOpen = (task) => !['completed', 'cancelled'].includes(task.status)

const isStatusActivity = (activity) => activity.type === 'status_changed'

const isNoteActivity = (activity) => ['note', 'note_added'].includes(activity.type)

export const LeadDetailPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isEmployee, isAdmin, isManager, canAssignLeads } = useEnterprise()
  const currentUser = useAuthStore((state) => state.user)

  const [tab, setTab] = useState('activity')
  const [note, setNote] = useState('')
  const [aboutOpen, setAboutOpen] = useState(true)
  const [editOpen, setEditOpen] = useState(false)
  const [stageOpen, setStageOpen] = useState(false)
  const [stageInitial, setStageInitial] = useState('')
  const [assignOpen, setAssignOpen] = useState(false)
  const [taskOpen, setTaskOpen] = useState(false)
  const [editingTask, setEditingTask] = useState(null)
  const [visitOpen, setVisitOpen] = useState(false)
  const [editingVisit, setEditingVisit] = useState(null)
  const [closingVisit, setClosingVisit] = useState(null)
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [leadCallCount, setLeadCallCount] = useState(null)

  const { data: lead, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'lead', Number(id)],
    queryFn: async () => {
      const response = await enterpriseApi.leads.get(id)
      return response.data?.data
    },
    enabled: Boolean(id),
  })

  const noteMutation = useMutation({
    mutationFn: () => enterpriseApi.leads.addNote(lead.id, note.trim()),
    onSuccess: () => {
      toast.success('Note added')
      setNote('')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', lead.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not add the note.')),
  })

  const taskStatusMutation = useMutation({
    mutationFn: (task) => enterpriseApi.tasks.updateStatus(task.id, { status: 'completed' }),
    onSuccess: () => {
      toast.success('Follow-up completed')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', Number(id)] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'tasks'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update this task.')),
  })

  const canDeleteCall = (call) =>
    isAdmin || (currentUser?.id != null && Number(call?.agent?.id) === Number(currentUser.id))

  const deleteMutation = useMutation({
    mutationFn: () => enterpriseApi.leads.remove(id),
    onSuccess: () => {
      toast.success('Lead deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'customers'] })
      navigate('/enterprise/leads')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete this lead.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />
  if (!lead) return <ErrorState error={{ message: 'Lead not found.' }} onRetry={refetch} />

  const activities = lead.activities || []
  const notes = activities.filter(isNoteActivity)
  const statusActivities = activities.filter(isStatusActivity)
  const tasks = lead.tasks || []
  const visits = lead.site_visits || []
  const openTasks = tasks.filter(taskIsOpen)
  const terminal = ['won', 'lost'].includes(lead.stage)
  const allowedStages = STAGE_TRANSITIONS[lead.stage] || []
  const canConvert = allowedStages.includes('won')
  const isCustomer = lead.stage === 'won'

  const openStageModal = (stage = '') => {
    setStageInitial(stage)
    setStageOpen(true)
  }

  const tabs = [
    { key: 'activity', label: 'Activity Timeline', count: activities.length },
    { key: 'notes', label: 'Notes', count: notes.length },
    { key: 'followups', label: 'Follow Ups', count: openTasks.length },
    { key: 'calls', label: 'Calls', count: leadCallCount },
    { key: 'audit', label: 'Status Audit Trail', count: statusActivities.length },
    { key: 'visits', label: 'Site Visits', count: visits.length },
  ]

  const buildItem = (activity) => {
    const meta = activityMeta[activity.type] || { icon: ClockIcon, tone: 'neutral' }
    const from = activity.meta?.from
    const to = activity.meta?.to

    return {
      id: `activity-${activity.id}`,
      icon: meta.icon,
      tone: meta.tone,
      title: activity.title,
      description: activity.description,
      user: activity.user?.name || 'System',
      time: activity.created_at,
      transition:
        isStatusActivity(activity) && from && to
          ? { fromLabel: stageLabel(from), toLabel: stageLabel(to) }
          : null,
    }
  }

  const activityItems = activities.map(buildItem)
  const noteItems = notes.map((activity) => ({
    id: `note-${activity.id}`,
    icon: ChatBubbleLeftIcon,
    tone: 'primary',
    title: 'Note added',
    description: activity.description,
    user: activity.user?.name || 'Team',
    time: activity.created_at,
  }))
  const auditItems = statusActivities.map((activity) => {
    const item = buildItem(activity)
    return { ...item, id: `audit-${activity.id}`, title: 'Stage changed' }
  })

  const budget =
    lead.budget_min || lead.budget_max
      ? `${lead.budget_min ? formatCurrency(lead.budget_min) : 'Any'} – ${
          lead.budget_max ? formatCurrency(lead.budget_max) : 'Any'
        }`
      : null

  const aboutItems = [
    { label: 'Company', value: lead.company || 'Individual buyer' },
    { label: 'Source', value: titleCase(lead.source || '') },
    { label: 'Budget', value: budget },
    { label: 'Configuration', value: lead.preferred_configuration },
    { label: 'Location', value: lead.preferred_location },
    { label: 'Project', value: lead.project?.name },
    { label: 'Interested unit', value: lead.unit?.full_path || lead.unit?.unit_number },
    { label: 'Campaign', value: lead.campaign?.name },
    { label: 'Created', value: formatDateTime(lead.created_at) },
    {
      label: 'Last contacted',
      value: lead.last_contacted_at ? formatDateTime(lead.last_contacted_at) : null,
    },
  ]

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <nav className="flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
          <Link to="/enterprise/leads" className="transition hover:text-foreground">
            {isEmployee ? 'My leads' : 'Leads'}
          </Link>
          <span aria-hidden="true">/</span>
          <span className="font-medium text-foreground">{lead.name}</span>
        </nav>
        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => setEditOpen(true)}>
            <PencilSquareIcon className="h-4 w-4" />
            Edit
          </Button>
          {canAssignLeads && (
            <Button variant="outline" size="sm" onClick={() => setAssignOpen(true)}>
              <UserPlusIcon className="h-4 w-4" />
              Assign
            </Button>
          )}
          {!terminal && (
            <Button size="sm" onClick={() => openStageModal()}>
              <ArrowPathIcon className="h-4 w-4" />
              Move stage
            </Button>
          )}
        </div>
      </div>

      {terminal && (
        <Alert variant={lead.stage === 'won' ? 'success' : 'danger'}>
          {lead.stage === 'won'
            ? `Won${lead.deal_value ? ` · ${formatCurrency(lead.deal_value)}` : ''}${
                lead.won_at ? ` on ${formatDate(lead.won_at)}` : ''
              }. This lead is closed.`
            : `Lost${lead.lost_reason ? ` · ${lead.lost_reason}` : ''}. This lead is closed.`}
        </Alert>
      )}

      <div className="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
        <aside className="space-y-4">
          <Card className="p-5">
            <div className="flex flex-wrap items-center gap-2">
              <StageBadge stage={lead.stage} />
              <PriorityBadge priority={lead.priority} />
              {isCustomer && <StatusBadge status="won" label="Customer" variant="success" />}
            </div>

            <h1 className="mt-3 text-xl font-semibold leading-tight text-foreground">{lead.name}</h1>
            {lead.company && <p className="mt-1 text-sm text-muted-foreground">{lead.company}</p>}

            <div className="mt-4 space-y-2 text-sm">
              {lead.email ? (
                <a
                  href={`mailto:${lead.email}`}
                  className="flex items-center gap-2 break-all text-muted-foreground transition hover:text-highlight"
                >
                  <EnvelopeIcon className="h-4 w-4 shrink-0" />
                  {lead.email}
                </a>
              ) : null}
              {lead.phone ? (
                <span className="flex items-center gap-2 text-muted-foreground">
                  <PhoneIcon className="h-4 w-4 shrink-0" />
                  {formatPhone(lead.phone)}
                </span>
              ) : null}
            </div>

            <div className="mt-5 rounded-xl border border-border bg-muted/30 p-3">
              <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                Assigned salesperson
              </p>
              {lead.assignee ? (
                <div className="mt-2 flex items-center gap-2.5">
                  <Avatar name={lead.assignee.name} size="sm" />
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-foreground">
                      {lead.assignee.name}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                      {lead.team?.name || 'No team'}
                    </p>
                  </div>
                </div>
              ) : (
                <p className="mt-1.5 text-xs italic text-muted-foreground">
                  No sales employee assigned yet.
                </p>
              )}
              {canAssignLeads && (
                <button
                  type="button"
                  onClick={() => setAssignOpen(true)}
                  className="mt-2 text-xs font-semibold text-highlight hover:underline"
                >
                  {lead.assignee ? 'Reassign lead' : 'Assign lead'}
                </button>
              )}
            </div>

            <div className="mt-4">
              <LeadActionTiles
                phone={lead.phone}
                onNote={() => {
                  setTab('activity')
                  document.getElementById('lead-note-input')?.focus()
                }}
                onActivity={() => setTab('activity')}
              />
            </div>

            <div className="mt-4">
              <LeadAbout items={aboutItems} open={aboutOpen} onToggle={() => setAboutOpen((value) => !value)} />
            </div>

            <div className="mt-4 space-y-2">
              <Button
                className="w-full bg-success text-success-foreground hover:bg-emerald-700"
                onClick={() => openStageModal('won')}
                disabled={!canConvert}
                title={
                  canConvert
                    ? undefined
                    : isCustomer
                      ? 'This lead is already a customer'
                      : 'Qualify or negotiate the lead before converting'
                }
              >
                <CheckCircleIcon className="h-4 w-4" />
                {isCustomer ? 'Converted to customer' : 'Convert to Customer'}
              </Button>
              {(isAdmin || isManager) && (
                <Button
                  variant="ghost"
                  className="w-full justify-center text-destructive hover:bg-destructive/10 hover:text-destructive"
                  onClick={() => setDeleteOpen(true)}
                >
                  <TrashIcon className="h-4 w-4" />
                  Delete Lead
                </Button>
              )}
            </div>
          </Card>
        </aside>

        <section className="min-w-0 space-y-4">
          <LeadStageRail
            steps={ENTERPRISE_STAGES}
            value={lead.stage}
            onSelect={(stage) => openStageModal(stage)}
            canSelect={(stage) => allowedStages.includes(stage)}
            pending={stageOpen}
          />

          <Card className="overflow-hidden">
            <div className="px-4 pt-3">
              <Tabs tabs={tabs} value={tab} onChange={setTab} />
            </div>

            {tab === 'activity' && (
              <div className="space-y-4 px-4 py-4">
                <LeadNoteComposer
                  id="lead-note-input"
                  value={note}
                  onChange={setNote}
                  onSubmit={() => noteMutation.mutate()}
                  pending={noteMutation.isPending}
                  placeholder="Write a note for the team…"
                />
                <LeadTimeline
                  items={activityItems}
                  empty={
                    <EmptyState
                      icon={ClockIcon}
                      title="No activity yet"
                      description="Notes, stage changes and assignments will appear here."
                    />
                  }
                />
              </div>
            )}

            {tab === 'notes' && (
              <div className="space-y-4 px-4 py-4">
                <LeadNoteComposer
                  value={note}
                  onChange={setNote}
                  onSubmit={() => noteMutation.mutate()}
                  pending={noteMutation.isPending}
                  placeholder="Write a note for the team…"
                />
                <LeadTimeline
                  items={noteItems}
                  empty={
                    <EmptyState
                      icon={ChatBubbleLeftIcon}
                      title="No notes yet"
                      description="Add a note so the team has the latest context."
                    />
                  }
                />
              </div>
            )}

            {tab === 'followups' && (
              <div className="space-y-4 px-4 py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p className="text-sm font-semibold text-foreground">Follow ups</p>
                    <p className="text-xs text-muted-foreground">
                      {formatNumber(openTasks.length)} open of {formatNumber(tasks.length)} follow-up
                      {tasks.length === 1 ? '' : 's'}
                    </p>
                  </div>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => {
                      setEditingTask(null)
                      setTaskOpen(true)
                    }}
                  >
                    <PlusIcon className="h-4 w-4" />
                    Add follow-up
                  </Button>
                </div>

                {lead.next_follow_up_at && (
                  <div className="flex items-center gap-3 rounded-xl border border-border bg-muted/30 p-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-highlight">
                      <ClockIcon className="h-4 w-4" />
                    </span>
                    <div>
                      <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        Next follow-up
                      </p>
                      <p className="text-sm font-medium text-foreground">
                        {formatDateTime(lead.next_follow_up_at)}
                      </p>
                    </div>
                  </div>
                )}

                {tasks.length === 0 ? (
                  <EmptyState
                    icon={ClockIcon}
                    title="No follow-ups for this lead"
                    description="Create a follow-up so the next touchpoint does not slip."
                    action={
                      <Button size="sm" onClick={() => setTaskOpen(true)}>
                        <PlusIcon className="h-4 w-4" />
                        Add follow-up
                      </Button>
                    }
                  />
                ) : (
                  <div className="space-y-2">
                    {tasks.map((task) => (
                      <div key={task.id} className="rounded-xl border border-border p-3">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                          <div className="min-w-0 flex-1">
                            <p
                              className={cn(
                                'text-sm font-medium',
                                task.status === 'completed'
                                  ? 'text-muted-foreground line-through'
                                  : 'text-foreground',
                              )}
                            >
                              {task.title}
                            </p>
                            <div className="mt-1.5 flex flex-wrap items-center gap-2">
                              <StatusBadge status={task.status} />
                              <PriorityBadge priority={task.priority} />
                              {task.due_at && (
                                <span
                                  className={cn(
                                    'text-[11px]',
                                    task.is_overdue && taskIsOpen(task)
                                      ? 'font-medium text-red-600 dark:text-red-400'
                                      : 'text-muted-foreground',
                                  )}
                                >
                                  {task.is_overdue && taskIsOpen(task) ? 'Overdue · ' : 'Due '}
                                  {formatDateTime(task.due_at)}
                                </span>
                              )}
                              <span className="text-[11px] text-muted-foreground">
                                {task.assignee?.name || 'Unassigned'}
                              </span>
                            </div>
                            {task.description && (
                              <p className="mt-1.5 text-xs text-muted-foreground">{task.description}</p>
                            )}
                          </div>
                          <div className="flex items-center gap-1">
                            {taskIsOpen(task) && (
                              <Button
                                size="icon-sm"
                                variant="ghost"
                                title="Mark complete"
                                aria-label={`Complete ${task.title}`}
                                loading={
                                  taskStatusMutation.isPending &&
                                  taskStatusMutation.variables?.id === task.id
                                }
                                onClick={() => taskStatusMutation.mutate(task)}
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
                                setTaskOpen(true)
                              }}
                            >
                              <PencilSquareIcon className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {tab === 'calls' && (
              <LeadCallsPanel
                edition="enterprise"
                api={enterpriseCallsApi}
                lead={lead}
                canDeleteCall={canDeleteCall}
                onCount={setLeadCallCount}
              />
            )}

            {tab === 'audit' && (
              <div className="px-4 py-4">
                <LeadTimeline
                  items={auditItems}
                  empty={
                    <EmptyState
                      icon={ArrowPathIcon}
                      title="No stage changes yet"
                      description="Every pipeline stage change is recorded here with who made it and when."
                    />
                  }
                />
              </div>
            )}

            {tab === 'visits' && (
              <div className="space-y-4 px-4 py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <p className="text-xs text-muted-foreground">
                    {formatNumber(visits.filter((visit) => visit.status === 'scheduled').length)} scheduled
                  </p>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => {
                      setEditingVisit(null)
                      setVisitOpen(true)
                    }}
                  >
                    <PlusIcon className="h-4 w-4" />
                    Schedule visit
                  </Button>
                </div>

                {visits.length === 0 ? (
                  <EmptyState
                    icon={CalendarDaysIcon}
                    title="No site visits yet"
                    description="Schedule a visit when the customer is ready to see a project."
                    action={
                      <Button size="sm" onClick={() => setVisitOpen(true)}>
                        <PlusIcon className="h-4 w-4" />
                        Schedule visit
                      </Button>
                    }
                  />
                ) : (
                  <div className="space-y-2">
                    {visits.map((visit) => (
                      <div key={visit.id} className="rounded-xl border border-border p-3">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                          <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                              <p className="text-sm font-medium text-foreground">
                                {formatDateTime(visit.scheduled_at)}
                              </p>
                              <StatusBadge status={visit.status} />
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                              {visit.project?.name || 'No project'}
                              {visit.unit ? ` · ${visit.unit.full_path || visit.unit.unit_number}` : ''}
                              {visit.assignee?.name ? ` · ${visit.assignee.name}` : ''}
                            </p>
                            {visit.outcome && (
                              <p className="mt-1 text-xs text-muted-foreground">Outcome: {visit.outcome}</p>
                            )}
                            {visit.cancellation_reason && (
                              <p className="mt-1 text-xs text-muted-foreground">
                                Reason: {visit.cancellation_reason}
                              </p>
                            )}
                          </div>
                          {visit.status === 'scheduled' && (
                            <div className="flex items-center gap-2">
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => {
                                  setEditingVisit(visit)
                                  setVisitOpen(true)
                                }}
                              >
                                Reschedule
                              </Button>
                              <Button size="sm" onClick={() => setClosingVisit(visit)}>
                                Close visit
                              </Button>
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </Card>
        </section>
      </div>

      <LeadFormModal open={editOpen} onClose={() => setEditOpen(false)} lead={lead} />
      <StageUpdateModal
        open={stageOpen}
        onClose={() => setStageOpen(false)}
        lead={lead}
        initialStage={stageInitial}
      />
      {canAssignLeads && (
        <AssignLeadModal open={assignOpen} onClose={() => setAssignOpen(false)} lead={lead} />
      )}
      <TaskFormModal
        open={taskOpen}
        onClose={() => {
          setTaskOpen(false)
          setEditingTask(null)
        }}
        task={editingTask}
        defaultLead={lead}
      />
      <VisitFormModal
        open={visitOpen}
        onClose={() => {
          setVisitOpen(false)
          setEditingVisit(null)
        }}
        visit={editingVisit}
        defaultLead={editingVisit ? null : lead}
      />
      <VisitCloseModal
        open={Boolean(closingVisit)}
        onClose={() => setClosingVisit(null)}
        visit={closingVisit}
      />

      <ConfirmDialog
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title="Delete this lead?"
        description={`${lead.name} will be removed from the workspace for everyone. Reports and customer lists will stop including this lead.`}
        confirmLabel="Delete lead"
      />
    </div>
  )
}
