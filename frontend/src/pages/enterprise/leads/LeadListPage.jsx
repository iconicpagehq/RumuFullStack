import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  ArrowUpTrayIcon,
  ArrowsRightLeftIcon,
  PlusIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { LeadImportModal } from '@/components/shared/LeadImportModal'
import { SearchInput } from '@/components/shared/SearchInput'
import { StageBadge, PriorityBadge } from '@/components/enterprise/StageBadge'
import { LeadFormModal } from '@/components/enterprise/LeadFormModal'
import { Avatar } from '@/components/ui/Avatar'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { Tabs } from '@/components/ui/Tabs'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { ENTERPRISE_STAGES, LEAD_PRIORITIES, stageLabel } from '@/utils/enterprise'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCompactCurrency, formatDate, formatNumber, formatTime } from '@/utils/formatters'

const STAGE_TABS = [{ key: '', label: 'All stages' }, ...ENTERPRISE_STAGES]

const SORT_OPTIONS = [
  { value: '', label: 'Newest first' },
  { value: 'follow_up', label: 'Follow-up date' },
  { value: 'oldest', label: 'Oldest first' },
  { value: 'name', label: 'Name A–Z' },
]

const FollowUpCell = ({ lead }) => {
  if (!lead.next_follow_up_at) {
    return <span className="text-xs text-muted-foreground">—</span>
  }

  return (
    <div className={cn('whitespace-nowrap text-xs', lead.is_overdue ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground')}>
      <p className="font-medium">{formatDate(lead.next_follow_up_at)}</p>
      <p>
        {lead.is_overdue ? 'Overdue · ' : ''}
        {formatTime(lead.next_follow_up_at)}
      </p>
    </div>
  )
}

export const LeadListPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()
  const { isAdmin, isManager, isEmployee, canAssignLeads } = useEnterprise()
  const { teams, assignableMembers, campaigns } = useEnterpriseReference()

  const stage = searchParams.get('stage') || ''
  const overdue = searchParams.get('overdue') === '1'
  const unassigned = searchParams.get('unassigned') === '1'
  const followUpToday = searchParams.get('follow_up_today') === '1'
  const teamId = searchParams.get('team_id') || ''

  const [search, setSearch] = useState('')
  const [priority, setPriority] = useState('')
  const [assigneeId, setAssigneeId] = useState('')
  const [sort, setSort] = useState('')
  const [page, setPage] = useState(1)
  const [addOpen, setAddOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, priority, teamId, assigneeId, sort, stage, overdue, unassigned, followUpToday])

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
    setSort('')
    setSearchParams({}, { replace: true })
  }

  const hasFilters =
    Boolean(search || priority || teamId || assigneeId || stage || overdue || unassigned || followUpToday)

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: [
      'enterprise',
      'leads',
      { search: debouncedSearch, stage, priority, teamId, assigneeId, sort, page, overdue, unassigned, followUpToday },
    ],
    queryFn: async () => {
      const response = await enterpriseApi.leads.list({
        search: debouncedSearch || undefined,
        stage: stage || undefined,
        priority: priority || undefined,
        team_id: teamId || undefined,
        assigned_to: assigneeId || undefined,
        sort: sort || undefined,
        unassigned: unassigned || undefined,
        overdue: overdue || undefined,
        follow_up_today: followUpToday || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const leads = data?.rows || []
  const meta = data?.meta || {}

  const columns = [
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="flex min-w-0 items-center gap-3">
          <Avatar name={lead.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{lead.name}</p>
            <p className="truncate text-xs text-muted-foreground">
              {lead.company || lead.source?.replace('_', ' ') || 'No company'}
            </p>
          </div>
        </div>
      ),
    },
    {
      key: 'contact',
      header: 'Contact',
      render: (lead) => (
        <div className="min-w-0 text-xs">
          <p className="max-w-[190px] truncate text-foreground">{lead.phone || '—'}</p>
          <p className="max-w-[190px] truncate text-muted-foreground">{lead.email || '—'}</p>
        </div>
      ),
    },
    {
      key: 'stage',
      header: 'Stage',
      render: (lead) => <StageBadge stage={lead.stage} />,
    },
    {
      key: 'priority',
      header: 'Priority',
      render: (lead) => <PriorityBadge priority={lead.priority} />,
    },
    {
      key: 'owner',
      header: 'Owner',
      render: (lead) => (
        <div className="min-w-0 text-xs">
          <p className={cn('truncate font-medium', lead.assignee?.name ? 'text-foreground' : 'text-amber-600 dark:text-amber-300')}>
            {lead.assignee?.name || 'Unassigned'}
          </p>
          <p className="truncate text-muted-foreground">{lead.team?.name || 'No team'}</p>
        </div>
      ),
    },
    {
      key: 'next_follow_up_at',
      header: 'Follow-up',
      render: (lead) => <FollowUpCell lead={lead} />,
    },
    {
      key: 'value',
      header: 'Value',
      render: (lead) => {
        if (lead.stage === 'won' && lead.deal_value) {
          return <span className="whitespace-nowrap text-sm font-semibold text-foreground">{formatCompactCurrency(lead.deal_value)}</span>
        }
        if (lead.budget_min || lead.budget_max) {
          return (
            <span className="whitespace-nowrap text-xs text-muted-foreground">
              {lead.budget_min ? formatCompactCurrency(lead.budget_min) : 'Any'}
              {lead.budget_max ? ` – ${formatCompactCurrency(lead.budget_max)}` : '+'}
            </span>
          )
        }
        return <span className="text-xs text-muted-foreground">—</span>
      },
    },
    {
      key: 'open_tasks_count',
      header: 'Open tasks',
      render: (lead) =>
        lead.open_tasks_count > 0 ? (
          <span className="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-highlight">
            {formatNumber(lead.open_tasks_count)}
          </span>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'created_at',
      header: 'Created',
      render: (lead) => <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(lead.created_at)}</span>,
    },
  ]

  const filterChips = [
    overdue && { key: 'overdue', label: 'Overdue follow-ups' },
    unassigned && { key: 'unassigned', label: 'Unassigned' },
    followUpToday && { key: 'follow_up_today', label: "Today's follow-ups" },
    stage && { key: 'stage', label: stageLabel(stage) },
  ].filter(Boolean)

  return (
    <div>
      <PageHeader
        title={isEmployee ? 'My leads' : 'Leads'}
        description={
          isEmployee
            ? 'Every lead assigned to you, with follow-ups and activity in one place.'
            : 'Manage the full pipeline, assign owners and keep every follow-up on track.'
        }
        actions={
          <>
            {(isAdmin || isManager) && (
              <Button variant="outline" size="sm" onClick={() => setImportOpen(true)}>
                <ArrowUpTrayIcon className="h-4 w-4" />
                Import leads
              </Button>
            )}
            {canAssignLeads && (
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate('/enterprise/leads/distribute')}
              >
                <ArrowsRightLeftIcon className="h-4 w-4" />
                Distribute leads
              </Button>
            )}
            <Button size="sm" onClick={() => setAddOpen(true)}>
              <PlusIcon className="h-4 w-4" />
              Add lead
            </Button>
          </>
        }
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="px-5">
            <Tabs
              tabs={STAGE_TABS}
              value={stage}
              onChange={(value) => updateParam('stage', value)}
            />

            <div className="flex flex-col gap-3 py-4">
              <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex flex-1 flex-wrap items-center gap-2">
                  <SearchInput
                    value={search}
                    onChange={setSearch}
                    placeholder="Search name, email, phone or company…"
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
                  {canAssignLeads && (
                    <>
                      <Select value={teamId} onChange={(event) => updateParam('team_id', event.target.value)} className="w-auto min-w-[140px]">
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
                  <Select value={sort} onChange={(event) => setSort(event.target.value)} className="w-auto min-w-[145px]">
                    {SORT_OPTIONS.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </Select>
                </div>

                {hasFilters && (
                  <Button variant="ghost" size="sm" onClick={clearFilters}>
                    <XMarkIcon className="h-4 w-4" />
                    Clear filters
                  </Button>
                )}
              </div>

              <div className="flex flex-wrap items-center gap-2">
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
                  {formatNumber(meta.total || 0)} lead{meta.total === 1 ? '' : 's'}
                  {isFetching && <span className="ml-2">Refreshing…</span>}
                </p>
              </div>
            </div>
          </div>

          <div className="border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={leads}
              loading={isLoading}
              rowKey="id"
              onRowClick={(lead) => navigate(`/enterprise/leads/${lead.id}`)}
              emptyState={
                <EmptyState
                  icon={UsersIcon}
                  title={hasFilters ? 'No matching leads' : 'No leads yet'}
                  description={
                    hasFilters
                      ? 'Try clearing a filter or searching for a different name.'
                      : 'Capture your first enquiry and route it to the right team.'
                  }
                  action={
                    hasFilters ? (
                      <Button variant="outline" size="sm" onClick={clearFilters}>
                        Clear filters
                      </Button>
                    ) : (
                      <Button size="sm" onClick={() => setAddOpen(true)}>
                        <PlusIcon className="h-4 w-4" />
                        Add lead
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

      <LeadImportModal
        open={importOpen}
        onClose={() => setImportOpen(false)}
        teams={teams}
        campaigns={campaigns}
        description="Upload a CSV or Excel file to add many leads at once. Duplicate emails and phone numbers already in your workspace are skipped."
        onImport={(payload) => enterpriseApi.leads.importCsv(payload)}
        onSample={({ format }) => enterpriseApi.leads.importSample({ format })}
        onImported={() => {
          queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
          queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
          queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
        }}
      />

      <LeadFormModal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        onSaved={(lead) => {
          if (lead?.id) navigate(`/enterprise/leads/${lead.id}`)
        }}
      />
    </div>
  )
}
