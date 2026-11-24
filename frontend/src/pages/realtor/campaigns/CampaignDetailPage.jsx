import { useMemo, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowUpTrayIcon,
  EllipsisVerticalIcon,
  PencilSquareIcon,
  PlusIcon,
  SignalIcon,
  TrashIcon,
  TrophyIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { SearchInput } from '@/components/shared/SearchInput'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown'
import { EmptyState } from '@/components/ui/EmptyState'
import { Tabs } from '@/components/ui/Tabs'
import { LEAD_STATUSES, LEAD_SOURCES } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const sourceLabel = (source) =>
  LEAD_SOURCES.find((item) => item.value === source)?.label || source || '—'

export const CampaignDetailPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('all')
  const [confirmDelete, setConfirmDelete] = useState(false)

  const { data: campaign, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'campaign', id],
    queryFn: async () => {
      const response = await realtorApi.campaigns.get(id)
      return response.data?.data
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => realtorApi.campaigns.remove(id),
    onSuccess: () => {
      toast.success('Campaign deleted')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'campaigns'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      navigate('/realtor/campaigns', { replace: true })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the campaign.')),
  })

  const leads = campaign?.leads || []
  const statusCounts = useMemo(
    () =>
      LEAD_STATUSES.reduce(
        (counts, item) => ({
          ...counts,
          [item.value]: leads.filter((lead) => lead.status === item.value).length,
        }),
        {},
      ),
    [leads],
  )

  const filtered = useMemo(() => {
    const query = search.trim().toLowerCase()
    return leads.filter((lead) => {
      const matchesStatus = status === 'all' || lead.status === status
      const matchesSearch =
        !query ||
        lead.name?.toLowerCase().includes(query) ||
        lead.email?.toLowerCase().includes(query) ||
        lead.phone?.toLowerCase().includes(query) ||
        lead.company?.toLowerCase().includes(query)
      return matchesStatus && matchesSearch
    })
  }, [leads, search, status])

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-foreground">{lead.name}</p>
          <p className="truncate text-xs text-muted-foreground">{lead.email || lead.company || 'No email'}</p>
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (lead) => <span className="text-sm">{lead.phone || '—'}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (lead) => <StatusBadge status={lead.status} />,
    },
    {
      key: 'source',
      header: 'Source',
      render: (lead) => (
        <span className="text-xs font-medium text-muted-foreground">{sourceLabel(lead.source)}</span>
      ),
    },
    {
      key: 'created_at',
      header: 'Added',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(lead.created_at)}
        </span>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'w-10 text-right',
      stopPropagation: true,
      render: (lead) => (
        <Button
          variant="ghost"
          size="icon-sm"
          aria-label={`Open ${lead.name}`}
          onClick={() => navigate(`/realtor/campaigns/${id}/leads/${lead.id}`)}
        >
          <EllipsisVerticalIcon className="h-4 w-4" />
        </Button>
      ),
    },
  ]

  const tabs = [
    { key: 'all', label: 'All', count: leads.length },
    ...LEAD_STATUSES.map((item) => ({
      key: item.value,
      label: item.label,
      count: statusCounts[item.value] || 0,
    })),
  ]

  return (
    <div>
      <PageHeader
        breadcrumbs={[{ label: 'Campaigns', to: '/realtor/campaigns' }, { label: campaign?.name || 'Campaign' }]}
        title={
          <span className="flex flex-wrap items-center gap-3">
            {campaign?.name || (isLoading ? 'Loading…' : 'Campaign')}
            {campaign && <StatusBadge status={campaign.status} />}
          </span>
        }
        description={campaign?.description || 'Manage the leads captured by this campaign.'}
        actions={
          <>
            <Link to={`/realtor/campaigns/${id}/leads/new`}>
              <Button size="sm">
                <PlusIcon className="h-4 w-4" />
                Add lead
              </Button>
            </Link>
            <Link to={`/realtor/campaigns/${id}/meta`}>
              <Button size="sm" variant="outline">
                <SignalIcon className="h-4 w-4" />
                Meta integration
              </Button>
            </Link>
            <Link to={`/realtor/campaigns/${id}/import`}>
              <Button size="sm" variant="outline">
                <ArrowUpTrayIcon className="h-4 w-4" />
                Import
              </Button>
            </Link>
            <Dropdown
              trigger={
                <Button variant="outline" size="icon" aria-label="More campaign actions">
                  <EllipsisVerticalIcon className="h-5 w-5" />
                </Button>
              }
            >
              <DropdownItem to={`/realtor/campaigns/${id}/edit`} icon={PencilSquareIcon}>
                Edit campaign
              </DropdownItem>
              <DropdownItem danger icon={TrashIcon} onClick={() => setConfirmDelete(true)}>
                Delete campaign
              </DropdownItem>
            </Dropdown>
          </>
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total leads"
          value={formatNumber(leads.length)}
          icon={UserGroupIcon}
          loading={isLoading}
        />
        <StatsCard
          label="New"
          value={formatNumber(statusCounts.new || 0)}
          icon={UsersIcon}
          tone="info"
          hint="Awaiting first contact"
          loading={isLoading}
        />
        <StatsCard
          label="Qualified"
          value={formatNumber((statusCounts.qualified || 0) + (statusCounts.proposal || 0))}
          icon={UsersIcon}
          tone="purple"
          hint="Qualified + proposal"
          loading={isLoading}
        />
        <StatsCard
          label="Won"
          value={formatNumber(statusCounts.won || 0)}
          icon={TrophyIcon}
          tone="success"
          hint={
            leads.length
              ? `${Math.round(((statusCounts.won || 0) / leads.length) * 100)}% conversion`
              : 'No leads yet'
          }
          loading={isLoading}
        />
      </div>

      <Card className="mt-5">
        <CardHeader className="flex-col gap-4 sm:flex-row sm:items-center">
          <div className="min-w-0 flex-1">
            <CardTitle>Leads</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">
              {formatNumber(filtered.length)} of {formatNumber(leads.length)} leads
            </p>
          </div>
          <SearchInput
            value={search}
            onChange={setSearch}
            placeholder="Search leads…"
            className="w-full sm:w-72"
          />
        </CardHeader>
        <CardContent className="px-0 pb-0">
          <Tabs tabs={tabs} value={status} onChange={setStatus} className="px-5" />
          <div className="mt-4 overflow-hidden border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={filtered}
              loading={isLoading}
              onRowClick={(lead) => navigate(`/realtor/campaigns/${id}/leads/${lead.id}`)}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={UserGroupIcon}
                  title={search || status !== 'all' ? 'No matching leads' : 'No leads yet'}
                  description={
                    search || status !== 'all'
                      ? 'Try a different search or pipeline stage.'
                      : 'Add leads manually or import them from CSV or Meta.'
                  }
                  action={
                    !search && status === 'all' ? (
                      <div className="flex flex-wrap justify-center gap-2">
                        <Link to={`/realtor/campaigns/${id}/leads/new`}>
                          <Button size="sm">
                            <PlusIcon className="h-4 w-4" />
                            Add lead
                          </Button>
                        </Link>
                        <Link to={`/realtor/campaigns/${id}/import`}>
                          <Button size="sm" variant="outline">
                            <ArrowUpTrayIcon className="h-4 w-4" />
                            Import leads
                          </Button>
                        </Link>
                      </div>
                    ) : null
                  }
                />
              }
            />
          </div>
        </CardContent>
      </Card>

      <ConfirmDialog
        open={confirmDelete}
        onClose={() => setConfirmDelete(false)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete “${campaign?.name}”?`}
        description="All leads and notes in this campaign will be permanently removed. This cannot be undone."
        confirmLabel="Delete campaign"
      />
    </div>
  )
}
