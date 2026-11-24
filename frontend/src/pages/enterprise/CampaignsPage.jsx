import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  PencilSquareIcon,
  PlusIcon,
  RectangleStackIcon,
  TrashIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { CampaignFormModal } from '@/components/enterprise/CampaignFormModal'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const TABS = [
  { key: '', label: 'All' },
  { key: 'active', label: 'Active' },
  { key: 'paused', label: 'Paused' },
  { key: 'completed', label: 'Completed' },
]

export const CampaignsPage = () => {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const { isAdmin, isManager } = useEnterprise()
  const canManage = isAdmin || isManager

  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'campaigns', { search: debouncedSearch, status, page }],
    queryFn: async () => {
      const response = await enterpriseApi.campaigns.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const deleteMutation = useMutation({
    mutationFn: (campaign) => enterpriseApi.campaigns.remove(campaign.id),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Campaign deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'campaigns'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      setDeleteTarget(null)
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this campaign.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const campaigns = data?.rows || []
  const meta = data?.meta || {}

  const columns = [
    {
      key: 'name',
      header: 'Campaign',
      render: (campaign) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-foreground">{campaign.name}</p>
          <p className="truncate text-xs text-muted-foreground">
            {campaign.description || 'No description'}
          </p>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (campaign) => <StatusBadge status={campaign.status} />,
    },
    {
      key: 'team',
      header: 'Team',
      render: (campaign) => (
        <span className="text-xs text-foreground">{campaign.team?.name || 'Company-wide'}</span>
      ),
    },
    {
      key: 'budget',
      header: 'Budget',
      render: (campaign) => (
        <span className="whitespace-nowrap text-xs text-foreground">
          {campaign.budget ? formatCurrency(campaign.budget) : '—'}
        </span>
      ),
    },
    {
      key: 'leads',
      header: 'Leads',
      render: (campaign) => (
        <div className="whitespace-nowrap text-xs">
          <span className="font-semibold text-foreground">{formatNumber(campaign.leads_count || 0)}</span>
          <span className="text-muted-foreground"> · {formatNumber(campaign.won_deals || 0)} won</span>
        </div>
      ),
    },
    {
      key: 'meta',
      header: 'Meta',
      render: (campaign) => (
        <Badge variant={campaign.meta_connected ? 'success' : 'neutral'} dot>
          {campaign.meta_connected ? 'Connected' : 'Not connected'}
        </Badge>
      ),
    },
    {
      key: 'deal_value',
      header: 'Won value',
      render: (campaign) => (
        <span className="whitespace-nowrap text-sm font-medium text-foreground">
          {campaign.deal_value ? formatCurrency(campaign.deal_value) : '—'}
        </span>
      ),
    },
    {
      key: 'dates',
      header: 'Dates',
      render: (campaign) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {campaign.start_date || campaign.end_date
            ? `${formatDate(campaign.start_date)} → ${formatDate(campaign.end_date)}`
            : 'Not scheduled'}
        </span>
      ),
    },
    ...(canManage
      ? [
          {
            key: 'actions',
            header: '',
            headerClassName: 'text-right',
            className: 'text-right',
            stopPropagation: true,
            render: (campaign) => (
              <div className="flex items-center justify-end gap-1">
                <button
                  type="button"
                  onClick={() => {
                    setEditing(campaign)
                    setFormOpen(true)
                  }}
                  className="rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                  aria-label={`Edit ${campaign.name}`}
                >
                  <PencilSquareIcon className="h-4 w-4" />
                </button>
                {isAdmin && (
                  <button
                    type="button"
                    onClick={() => setDeleteTarget(campaign)}
                    className="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                    aria-label={`Delete ${campaign.name}`}
                  >
                    <TrashIcon className="h-4 w-4" />
                  </button>
                )}
              </div>
            ),
          },
        ]
      : []),
  ]

  return (
    <div>
      <PageHeader
        title="Campaigns"
        description={
          isManager
            ? 'Marketing activities for your team and the leads they generated.'
            : 'Marketing activities and the leads and revenue attributed to them.'
        }
        actions={
          canManage ? (
            <Button
              onClick={() => {
                setEditing(null)
                setFormOpen(true)
              }}
            >
              <PlusIcon className="h-4 w-4" />
              New campaign
            </Button>
          ) : null
        }
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="px-5">
            <Tabs tabs={TABS} value={status} onChange={setStatus} />
            <div className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
              <p className="text-xs text-muted-foreground">
                {formatNumber(meta.total || 0)} campaign{meta.total === 1 ? '' : 's'}
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
              <SearchInput
                value={search}
                onChange={setSearch}
                placeholder="Search campaigns…"
                className="w-full sm:w-72"
              />
            </div>
          </div>

          <div className="border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={campaigns}
              loading={isLoading}
              rowKey="id"
              onRowClick={(campaign) => navigate(`/enterprise/campaigns/${campaign.id}`)}
              emptyState={
                <EmptyState
                  icon={RectangleStackIcon}
                  title={search || status ? 'No matching campaigns' : 'No campaigns yet'}
                  description={
                    search || status
                      ? 'Try a different search or status filter.'
                      : canManage
                        ? 'Create a campaign to track where your leads come from.'
                        : 'No campaigns are available for your team yet.'
                  }
                  action={
                    canManage && !search && !status ? (
                      <Button
                        onClick={() => {
                          setEditing(null)
                          setFormOpen(true)
                        }}
                      >
                        <PlusIcon className="h-4 w-4" />
                        New campaign
                      </Button>
                    ) : null
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

      <div className="mt-4 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
        <Badge variant="neutral">Attribution</Badge>
        Leads link to a campaign when created with one. Deleting a campaign with leads archives it
        instead so attribution is preserved.
      </div>

      <CampaignFormModal open={formOpen} onClose={() => setFormOpen(false)} campaign={editing} />

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        onConfirm={() => deleteMutation.mutate(deleteTarget)}
        loading={deleteMutation.isPending}
        title={`Delete ${deleteTarget?.name || 'campaign'}?`}
        description={
          deleteTarget?.leads_count
            ? 'This campaign has attributed leads, so it will be archived as completed instead of deleted.'
            : 'This campaign has no attributed leads and will be permanently deleted.'
        }
        confirmLabel={deleteTarget?.leads_count ? 'Archive campaign' : 'Delete campaign'}
      />
    </div>
  )
}
