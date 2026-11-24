import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { UserGroupIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { LEAD_STATUSES, PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatPhone, formatNumber } from '@/utils/formatters'

const TENANTS = [
  { value: 'all', label: 'All tenants' },
  { value: 'realtor', label: 'Realtors' },
  { value: 'enterprise', label: 'Enterprise' },
]

export const LeadsPage = () => {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [tenant, setTenant] = useState('all')
  const [page, setPage] = useState(1)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'leads', { search, status, tenant, page }],
    queryFn: async () => {
      const response = await adminApi.leads({
        search: search || undefined,
        status: status || undefined,
        tenant,
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
      header: 'Lead',
      render: (lead) => (
        <div className="min-w-[180px]">
          <p className="text-sm font-semibold text-foreground">{lead.name}</p>
          <p className="text-xs text-muted-foreground">{lead.email || formatPhone(lead.phone) || '—'}</p>
        </div>
      ),
    },
    {
      key: 'campaign',
      header: 'Campaign',
      render: (lead) =>
        lead.campaign ? (
          <span className="text-sm font-medium text-foreground">{lead.campaign.name}</span>
        ) : (
          <span className="text-sm text-muted-foreground">—</span>
        ),
    },
    {
      key: 'enterprise',
      header: 'Enterprise',
      render: (lead) =>
        lead.enterprise ? (
          <span className="text-sm font-medium text-foreground">{lead.enterprise.name}</span>
        ) : (
          <Badge variant="neutral">Realtor</Badge>
        ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (lead) => <span className="text-sm">{formatPhone(lead.phone)}</span>,
    },
    {
      key: 'source',
      header: 'Source',
      render: (lead) => (
        <span className="text-xs font-medium capitalize text-muted-foreground">
          {lead.source?.replace(/_/g, ' ') || '—'}
        </span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (lead) => <StatusBadge status={lead.status} />,
    },
    {
      key: 'created_at',
      header: 'Received',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDateTime(lead.created_at)}</span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Leads"
        description="Every lead captured across realtor and enterprise campaigns."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search name, email or phone…"
          className="w-full max-w-sm"
        />
        <Select
          value={tenant}
          onChange={(event) => {
            setTenant(event.target.value)
            setPage(1)
          }}
          className="w-40"
        >
          {TENANTS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <Select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="w-44"
        >
          <option value="">All statuses</option>
          {LEAD_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} leads
        </span>
      </div>

      <DataTable
        columns={columns}
        rows={data?.rows || []}
        loading={isLoading}
        emptyState={
          <EmptyState
            icon={UserGroupIcon}
            title="No leads found"
            description={
              search || status || tenant !== 'all'
                ? 'Try adjusting your filters.'
                : 'Leads will appear here as campaigns collect them.'
            }
          />
        }
      />

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
