import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  EllipsisVerticalIcon,
  MegaphoneIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown'
import { EmptyState } from '@/components/ui/EmptyState'
import { Tabs } from '@/components/ui/Tabs'
import { CAMPAIGN_STATUSES } from '@/utils/constants'
import { formatNumber, timeAgo } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const CampaignListPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [tab, setTab] = useState('all')
  const [deleting, setDeleting] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'campaigns'],
    queryFn: async () => {
      const response = await realtorApi.campaigns.list()
      return response.data?.data || []
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => realtorApi.campaigns.remove(id),
    onSuccess: () => {
      toast.success('Campaign deleted')
      setDeleting(null)
      queryClient.invalidateQueries({ queryKey: ['realtor', 'campaigns'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the campaign.')),
  })

  const campaigns = data || []
  const filtered = useMemo(() => {
    const query = search.trim().toLowerCase()
    return campaigns.filter((campaign) => {
      const matchesTab = tab === 'all' || campaign.status === tab
      const matchesSearch =
        !query ||
        campaign.name?.toLowerCase().includes(query) ||
        campaign.description?.toLowerCase().includes(query)
      return matchesTab && matchesSearch
    })
  }, [campaigns, search, tab])

  const tabs = [
    { key: 'all', label: 'All', count: campaigns.length },
    ...CAMPAIGN_STATUSES.map((status) => ({
      key: status.value,
      label: status.label,
      count: campaigns.filter((campaign) => campaign.status === status.value).length,
    })),
  ]

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div>
      <PageHeader
        title="Campaigns"
        description="Organise lead capture around each project, offer or marketing push."
        actions={
          <Link to="/realtor/campaigns/new">
            <Button size="sm">
              <PlusIcon className="h-4 w-4" />
              New campaign
            </Button>
          </Link>
        }
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <Tabs tabs={tabs} value={tab} onChange={setTab} className="min-w-0 flex-1 sm:border-b-0" />
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Search campaigns…"
          className="sm:w-72"
        />
      </div>

      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {[1, 2, 3, 4, 5, 6].map((item) => (
            <div key={item} className="skeleton h-44 w-full rounded-xl" />
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <Card>
          <EmptyState
            icon={MegaphoneIcon}
            title={search || tab !== 'all' ? 'No matching campaigns' : 'No campaigns yet'}
            description={
              search || tab !== 'all'
                ? 'Try a different search or filter.'
                : 'Create a campaign to group leads from a project, channel or offer.'
            }
            action={
              !search && tab === 'all' ? (
                <Link to="/realtor/campaigns/new">
                  <Button size="sm">
                    <PlusIcon className="h-4 w-4" />
                    Create campaign
                  </Button>
                </Link>
              ) : null
            }
          />
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {filtered.map((campaign) => (
            <Card
              key={campaign.id}
              className="group flex flex-col p-5 transition hover:border-highlight/30 hover:shadow-card"
            >
              <div className="flex items-start justify-between gap-3">
                <Link to={`/realtor/campaigns/${campaign.id}`} className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-highlight">
                      <MegaphoneIcon className="h-4 w-4" />
                    </span>
                    <div className="min-w-0">
                      <h3 className="truncate text-sm font-semibold text-foreground group-hover:text-highlight">
                        {campaign.name}
                      </h3>
                      <p className="text-[11px] text-muted-foreground">
                        Updated {timeAgo(campaign.updated_at)}
                      </p>
                    </div>
                  </div>
                </Link>
                <Dropdown
                  trigger={
                    <Button variant="ghost" size="icon-sm" aria-label="Campaign actions">
                      <EllipsisVerticalIcon className="h-4 w-4" />
                    </Button>
                  }
                >
                  <DropdownItem to={`/realtor/campaigns/${campaign.id}`}>
                    View campaign
                  </DropdownItem>
                  <DropdownItem
                    to={`/realtor/campaigns/${campaign.id}/edit`}
                    icon={PencilSquareIcon}
                  >
                    Edit
                  </DropdownItem>
                  <DropdownItem
                    danger
                    icon={TrashIcon}
                    onClick={() => setDeleting(campaign)}
                  >
                    Delete
                  </DropdownItem>
                </Dropdown>
              </div>

              <p className="mt-3 line-clamp-2 min-h-[2.5rem] text-sm text-muted-foreground">
                {campaign.description || 'No description added yet.'}
              </p>

              <div className="mt-4 flex items-center justify-between border-t border-border pt-3">
                <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                  <UserGroupIcon className="h-3.5 w-3.5" />
                  {formatNumber(campaign.leads_count || 0)} leads
                </span>
                <StatusBadge status={campaign.status} />
              </div>
            </Card>
          ))}
        </div>
      )}

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleteMutation.mutate(deleting.id)}
        loading={deleteMutation.isPending}
        title={`Delete “${deleting?.name}”?`}
        description="All leads and notes inside this campaign will be permanently removed. This cannot be undone."
        confirmLabel="Delete campaign"
      />
    </div>
  )
}
