import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { ShareIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'

export const ReferralsPage = () => {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'referrals', { search: debouncedSearch, status, page }],
    queryFn: async () => {
      const response = await adminApi.billing.referrals({
        search: debouncedSearch || undefined,
        status: status || undefined,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
        stats: response.data?.stats || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const stats = data?.stats || {}

  const columns = [
    {
      key: 'referrer',
      header: 'Referrer',
      render: (row) => (
        <div className="min-w-[160px]">
          <p className="text-sm font-semibold text-foreground">{row.referrer?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.referrer?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'referred',
      header: 'Referred',
      render: (row) => (
        <div className="min-w-[160px]">
          <p className="text-sm font-medium text-foreground">{row.referred?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.referred?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'reward_amount',
      header: 'Reward',
      render: (row) => <span className="text-sm font-semibold text-foreground">{formatCurrency(row.reward_amount)}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: 'created_at',
      header: 'Date',
      render: (row) => <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(row.created_at)}</span>,
    },
  ]

  return (
    <div>
      <PageHeader title="Referrals" description="Realtor referral activity and rewards." />

      <div className="mb-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Total referrals" value={formatNumber(stats.total || 0)} icon={ShareIcon} />
        <StatsCard
          label="Completed"
          value={formatNumber(stats.completed || 0)}
          icon={ShareIcon}
          tone="success"
        />
        <StatsCard label="Pending" value={formatNumber(stats.pending || 0)} icon={ShareIcon} tone="warning" />
        <StatsCard
          label="Total rewards"
          value={formatCurrency(stats.total_rewards || 0)}
          icon={ShareIcon}
          tone="purple"
        />
      </div>

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search referrer or referred…"
          className="w-full max-w-sm"
        />
        <Select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="w-36"
        >
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} referrals
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          emptyState={
            <EmptyState
              icon={ShareIcon}
              title="No referrals yet"
              description="Referral records will appear here once realtors invite others."
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
    </div>
  )
}
