import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { BriefcaseIcon, PlusIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDate, formatNumber } from '@/utils/formatters'
import { RealtorFormModal } from './RealtorFormModal'

export const RealtorsPage = () => {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [planId, setPlanId] = useState('')
  const [suspended, setSuspended] = useState('')
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const debouncedSearch = useDebounce(search)

  const { data: plans = [] } = useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: async () => {
      const response = await adminApi.plans.list()
      return response.data?.data || []
    },
  })

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'realtors', { search: debouncedSearch, planId, suspended, page }],
    queryFn: async () => {
      const response = await adminApi.realtors.list({
        search: debouncedSearch || undefined,
        plan_id: planId || undefined,
        suspended: suspended === '' ? undefined : suspended,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'name',
      header: 'Realtor',
      render: (realtor) => (
        <div className="flex min-w-[220px] items-center gap-3">
          <Avatar src={realtor.avatar_url} name={realtor.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{realtor.name}</p>
            <p className="truncate text-xs text-muted-foreground">{realtor.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'company',
      header: 'Company',
      render: (realtor) => <span className="text-sm text-muted-foreground">{realtor.company || '—'}</span>,
    },
    {
      key: 'plan',
      header: 'Plan',
      render: (realtor) =>
        realtor.plan ? <Badge variant="primary">{realtor.plan.name}</Badge> : <span className="text-sm text-muted-foreground">—</span>,
    },
    {
      key: 'campaigns_count',
      header: 'Campaigns',
      render: (realtor) => <span className="text-sm">{formatNumber(realtor.campaigns_count || 0)}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (realtor) =>
        realtor.subscription_suspended ? <StatusBadge status="suspended" /> : <StatusBadge status="active" />,
    },
    {
      key: 'created_at',
      header: 'Joined',
      render: (realtor) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(realtor.created_at)}</span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Realtors"
        description="Manage realtor accounts, plans and subscriptions."
        actions={
          <Button size="sm" onClick={() => setModalOpen(true)}>
            <PlusIcon className="h-4 w-4" />
            Add realtor
          </Button>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search name, email or company…"
          className="w-full max-w-sm"
        />
        <Select
          value={planId}
          onChange={(event) => {
            setPlanId(event.target.value)
            setPage(1)
          }}
          className="w-40"
        >
          <option value="">All plans</option>
          {plans.map((plan) => (
            <option key={plan.id} value={plan.id}>
              {plan.name}
            </option>
          ))}
        </Select>
        <Select
          value={suspended}
          onChange={(event) => {
            setSuspended(event.target.value)
            setPage(1)
          }}
          className="w-40"
        >
          <option value="">All statuses</option>
          <option value="0">Active</option>
          <option value="1">Suspended</option>
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} realtors
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          onRowClick={(realtor) => navigate(`/admin/realtors/${realtor.id}`)}
          emptyState={
            <EmptyState
              icon={BriefcaseIcon}
              title="No realtors found"
              description="Try adjusting your filters, or add a realtor to get started."
            />
          }
        />
      </Card>

      <Pagination
        page={data?.meta?.current_page || 1}
        lastPage={data?.meta?.last_page || 1}
        total={data?.meta?.total || 0}
        perPage={data?.meta?.per_page || PAGE_SIZE}
        onPageChange={setPage}
      />

      {modalOpen && (
        <RealtorFormModal
          open={modalOpen}
          plans={plans}
          onClose={() => setModalOpen(false)}
          onSaved={() => {
            setModalOpen(false)
            refetch()
          }}
        />
      )}
    </div>
  )
}
