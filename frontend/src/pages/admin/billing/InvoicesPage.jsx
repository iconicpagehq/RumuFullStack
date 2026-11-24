import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { DocumentTextIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Input, Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { INVOICE_STATUSES, PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'

export const InvoicesPage = () => {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'invoices', { search: debouncedSearch, status, dateFrom, dateTo, page }],
    queryFn: async () => {
      const response = await adminApi.billing.invoices.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
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
      key: 'invoice_number',
      header: 'Invoice',
      render: (row) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{row.invoice_number}</p>
          <p className="text-xs text-muted-foreground">{row.user?.email || '—'}</p>
        </div>
      ),
    },
    {
      key: 'user',
      header: 'Billed to',
      render: (row) => (
        <div className="min-w-[150px]">
          <p className="text-sm font-medium text-foreground">{row.user?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.user?.company || ''}</p>
        </div>
      ),
    },
    {
      key: 'plan',
      header: 'Plan',
      render: (row) => <span className="text-sm">{row.subscription?.plan_name || '—'}</span>,
    },
    {
      key: 'created_at',
      header: 'Date',
      render: (row) => <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(row.created_at)}</span>,
    },
    {
      key: 'total',
      header: 'Total',
      render: (row) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{formatCurrency(row.total)}</p>
          <p className="text-[11px] text-muted-foreground">tax {formatCurrency(row.tax)}</p>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: 'emailed_at',
      header: 'Emailed',
      render: (row) => (
        <span className="text-xs text-muted-foreground">{row.emailed_at ? formatDate(row.emailed_at) : '—'}</span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader title="Invoices" description="Every invoice generated for realtor subscriptions." />

      <div className="mb-4 flex flex-wrap items-end gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search invoice, realtor or transaction…"
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
          {INVOICE_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <div>
          <label className="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            From
          </label>
          <Input
            type="date"
            value={dateFrom}
            onChange={(event) => {
              setDateFrom(event.target.value)
              setPage(1)
            }}
            className="w-40"
          />
        </div>
        <div>
          <label className="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            To
          </label>
          <Input
            type="date"
            value={dateTo}
            onChange={(event) => {
              setDateTo(event.target.value)
              setPage(1)
            }}
            className="w-40"
          />
        </div>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} invoices
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          onRowClick={(row) => navigate(`/admin/billing/invoices/${row.id}`)}
          emptyState={
            <EmptyState
              icon={DocumentTextIcon}
              title="No invoices found"
              description="Invoices are created when a subscription payment is completed."
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
