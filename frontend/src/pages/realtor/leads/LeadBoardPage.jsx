import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  Squares2X2Icon,
  TableCellsIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { LeadImportModal } from '@/components/shared/LeadImportModal'
import { SearchInput } from '@/components/shared/SearchInput'
import { PriorityBadge } from '@/components/enterprise/StageBadge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { LEAD_STATUSES, PAGE_SIZE } from '@/utils/constants'
import { LEAD_PRIORITIES } from '@/utils/enterprise'
import { cn } from '@/utils/cn'
import {
  filenameFromResponse,
  formatCurrency,
  formatDateTime,
  formatNumber,
  formatPhone,
  saveBlob,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const statusDot = {
  new: 'bg-blue-500',
  contacted: 'bg-amber-500',
  qualified: 'bg-violet-500',
  proposal: 'bg-indigo-500',
  negotiation: 'bg-orange-500',
  won: 'bg-emerald-500',
  lost: 'bg-red-500',
}

const BOARD_PAGE_SIZE = 200

export const LeadBoardPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [view, setView] = useState('board')
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [campaignId, setCampaignId] = useState('')
  const [priority, setPriority] = useState('')
  const [page, setPage] = useState(1)
  const [importOpen, setImportOpen] = useState(false)

  const perPage = view === 'board' ? BOARD_PAGE_SIZE : PAGE_SIZE
  const filters = {
    search: search || undefined,
    status: view === 'table' && status ? status : undefined,
    campaign_id: campaignId || undefined,
    priority: priority || undefined,
  }

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['realtor', 'leads', { ...filters, view, page }],
    queryFn: async () => {
      const response = await realtorApi.leads.all({ ...filters, page, per_page: perPage })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const campaignsQuery = useQuery({
    queryKey: ['realtor', 'campaigns'],
    queryFn: async () => {
      const response = await realtorApi.campaigns.list()
      return response.data?.data || []
    },
  })

  const statusMutation = useMutation({
    mutationFn: ({ lead, nextStatus }) => realtorApi.leads.updateStatus(lead.id, nextStatus),
    onSuccess: () => {
      toast.success('Lead stage updated')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the lead stage.')),
  })

  const exportMutation = useMutation({
    mutationFn: () => realtorApi.leads.export(filters),
    onSuccess: (response) => {
      saveBlob(response.data, filenameFromResponse(response, 'my-leads.csv'))
      toast.success('Leads exported')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not export your leads.')),
  })

  const leadsByStatus = useMemo(() => {
    const groups = Object.fromEntries(LEAD_STATUSES.map((item) => [item.value, []]))
    ;(data?.rows || []).forEach((lead) => {
      if (groups[lead.status]) groups[lead.status].push(lead)
    })
    return groups
  }, [data?.rows])

  const openLead = (lead) => navigate(`/realtor/campaigns/${lead.campaign_id}/leads/${lead.id}`)

  const resetPage = () => setPage(1)

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const campaigns = campaignsQuery.data || []
  const leads = data?.rows || []
  const meta = data?.meta || {}
  const statusCounts = meta.status_counts || {}

  const stageSelect = (lead) => (
    <div onClick={(event) => event.stopPropagation()}>
      <Select
        value={lead.status}
        onChange={(event) => statusMutation.mutate({ lead, nextStatus: event.target.value })}
        disabled={statusMutation.isPending && statusMutation.variables?.lead?.id === lead.id}
        className="h-8 w-full py-0 text-xs"
        aria-label={`Change stage of ${lead.name}`}
      >
        {LEAD_STATUSES.map((item) => (
          <option key={item.value} value={item.value}>
            {item.label}
          </option>
        ))}
      </Select>
    </div>
  )

  const columns = [
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="min-w-[180px]">
          <p className="text-sm font-semibold text-foreground">{lead.name}</p>
          <p className="text-xs text-muted-foreground">
            {lead.company || lead.email || formatPhone(lead.phone) || '—'}
          </p>
        </div>
      ),
    },
    {
      key: 'campaign',
      header: 'Campaign',
      render: (lead) =>
        lead.campaign ? (
          <span className="text-sm text-foreground">{lead.campaign.name}</span>
        ) : (
          <span className="text-sm text-muted-foreground">—</span>
        ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (lead) => <span className="text-sm">{formatPhone(lead.phone) || '—'}</span>,
    },
    {
      key: 'priority',
      header: 'Priority',
      render: (lead) => <PriorityBadge priority={lead.priority} />,
    },
    {
      key: 'status',
      header: 'Stage',
      stopPropagation: true,
      render: (lead) => <div className="w-40">{stageSelect(lead)}</div>,
    },
    {
      key: 'deal_value',
      header: 'Deal value',
      render: (lead) => (
        <span className="whitespace-nowrap text-sm text-foreground">
          {lead.deal_value ? formatCurrency(lead.deal_value) : '—'}
        </span>
      ),
    },
    {
      key: 'next_follow_up_at',
      header: 'Follow-up',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {lead.next_follow_up_at ? formatDateTime(lead.next_follow_up_at) : '—'}
        </span>
      ),
    },
    {
      key: 'created_at',
      header: 'Created',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(lead.created_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Leads"
        description="Every lead you own, across all campaigns."
        actions={
          <>
            <div className="flex items-center rounded-lg border border-border bg-card p-0.5">
              <button
                type="button"
                onClick={() => {
                  setView('board')
                  resetPage()
                }}
                className={cn(
                  'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition',
                  view === 'board'
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
                )}
                aria-pressed={view === 'board'}
              >
                <Squares2X2Icon className="h-4 w-4" />
                Board
              </button>
              <button
                type="button"
                onClick={() => {
                  setView('table')
                  resetPage()
                }}
                className={cn(
                  'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition',
                  view === 'table'
                    ? 'bg-primary text-primary-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
                )}
                aria-pressed={view === 'table'}
              >
                <TableCellsIcon className="h-4 w-4" />
                Table
              </button>
            </div>
            <Button size="sm" variant="outline" onClick={() => setImportOpen(true)}>
              <ArrowUpTrayIcon className="h-4 w-4" />
              Import leads
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => exportMutation.mutate()}
              loading={exportMutation.isPending}
            >
              <ArrowDownTrayIcon className="h-4 w-4" />
              Export CSV
            </Button>
          </>
        }
      />

      <LeadImportModal
        open={importOpen}
        onClose={() => setImportOpen(false)}
        campaigns={campaigns}
        campaignRequired
        defaultCampaignId={campaignId}
        onImport={({ file, campaignId: targetCampaignId }) =>
          realtorApi.imports.csv(targetCampaignId, file)
        }
        onSample={({ campaignId: targetCampaignId, format }) =>
          realtorApi.imports.sample(targetCampaignId, { format })
        }
        onImported={() => {
          queryClient.invalidateQueries({ queryKey: ['realtor', 'leads'] })
          queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
          queryClient.invalidateQueries({ queryKey: ['realtor', 'campaigns'] })
        }}
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            resetPage()
          }}
          placeholder="Search name, email, phone or company…"
          className="w-full max-w-sm"
        />
        <Select
          value={campaignId}
          onChange={(event) => {
            setCampaignId(event.target.value)
            resetPage()
          }}
          className="w-48"
        >
          <option value="">All campaigns</option>
          {campaigns.map((campaign) => (
            <option key={campaign.id} value={campaign.id}>
              {campaign.name}
            </option>
          ))}
        </Select>
        <Select
          value={priority}
          onChange={(event) => {
            setPriority(event.target.value)
            resetPage()
          }}
          className="w-40"
        >
          <option value="">All priorities</option>
          {LEAD_PRIORITIES.map((item) => (
            <option key={item.value} value={item.value}>
              {item.label}
            </option>
          ))}
        </Select>
        {view === 'table' && (
          <Select
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              resetPage()
            }}
            className="w-40"
          >
            <option value="">All stages</option>
            {LEAD_STATUSES.map((item) => (
              <option key={item.value} value={item.value}>
                {item.label}
              </option>
            ))}
          </Select>
        )}
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(meta.total || 0)} lead{meta.total === 1 ? '' : 's'}
          {isFetching && <span className="ml-2">Refreshing…</span>}
        </span>
      </div>

      {view === 'board' ? (
        <>
          <div className="flex gap-4 overflow-x-auto pb-3">
            {LEAD_STATUSES.map((stage) => {
              const stageLeads = leadsByStatus[stage.value] || []
              return (
                <div key={stage.value} className="w-[280px] shrink-0">
                  <div className="flex items-center justify-between px-1 pb-2">
                    <span className="flex items-center gap-2 text-sm font-semibold text-foreground">
                      <span className={cn('h-2 w-2 rounded-full', statusDot[stage.value])} />
                      {stage.label}
                    </span>
                    <span className="rounded-full bg-muted px-2 py-0.5 text-[11px] font-semibold text-muted-foreground">
                      {formatNumber(statusCounts[stage.value] ?? stageLeads.length)}
                    </span>
                  </div>
                  <div className="min-h-[220px] space-y-2.5 rounded-xl border border-dashed border-border bg-muted/30 p-2">
                    {isLoading ? (
                      <div className="space-y-2.5">
                        <div className="skeleton h-24 w-full rounded-lg" />
                        <div className="skeleton h-24 w-full rounded-lg" />
                      </div>
                    ) : stageLeads.length === 0 ? (
                      <p className="px-2 py-8 text-center text-xs text-muted-foreground">No leads here.</p>
                    ) : (
                      stageLeads.map((lead) => (
                        <div
                          key={lead.id}
                          role="button"
                          tabIndex={0}
                          onClick={() => openLead(lead)}
                          onKeyDown={(event) => {
                            if (event.key === 'Enter' || event.key === ' ') {
                              event.preventDefault()
                              openLead(lead)
                            }
                          }}
                          className="cursor-pointer rounded-lg border border-border bg-card p-3 shadow-sm transition hover:border-highlight/40 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        >
                          <div className="flex items-start justify-between gap-2">
                            <p className="text-sm font-semibold text-foreground">{lead.name}</p>
                            <PriorityBadge priority={lead.priority} />
                          </div>
                          <p className="mt-0.5 truncate text-xs text-muted-foreground">
                            {lead.company || lead.email || formatPhone(lead.phone) || 'No contact details'}
                          </p>
                          <div className="mt-2 space-y-1 text-[11px] text-muted-foreground">
                            {lead.campaign && <p className="truncate">Campaign · {lead.campaign.name}</p>}
                            {lead.deal_value ? <p>Deal value · {formatCurrency(lead.deal_value)}</p> : null}
                            {lead.next_follow_up_at && <p>Follow-up · {formatDateTime(lead.next_follow_up_at)}</p>}
                          </div>
                          <div className="mt-2.5">{stageSelect(lead)}</div>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              )
            })}
          </div>
          {meta.last_page > 1 && (
            <Card className="mt-2">
              <Pagination
                page={meta.current_page || 1}
                lastPage={meta.last_page || 1}
                total={meta.total || 0}
                perPage={meta.per_page || perPage}
                onPageChange={setPage}
              />
            </Card>
          )}
        </>
      ) : (
        <>
          <DataTable
            columns={columns}
            rows={leads}
            loading={isLoading}
            onRowClick={openLead}
            emptyState={
              <EmptyState
                icon={UserGroupIcon}
                title="No leads found"
                description={
                  search || status || campaignId || priority
                    ? 'Try adjusting your filters.'
                    : 'Leads captured by your campaigns will appear here.'
                }
              />
            }
          />
          <Pagination
            page={meta.current_page || 1}
            lastPage={meta.last_page || 1}
            total={meta.total || 0}
            perPage={meta.per_page || PAGE_SIZE}
            onPageChange={setPage}
          />
        </>
      )}
    </div>
  )
}
