import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { BellAlertIcon, PaperAirplaneIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
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
import {
  BILLING_NOTIFICATION_TYPES,
  NOTIFICATION_STATUSES,
  PAGE_SIZE,
} from '@/utils/constants'
import { formatDateTime, formatNumber, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const BillingNotificationsPage = () => {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [type, setType] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [resending, setResending] = useState(null)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'notifications', { search: debouncedSearch, type, status, page }],
    queryFn: async () => {
      const response = await adminApi.billing.notifications.list({
        search: debouncedSearch || undefined,
        type: type || undefined,
        status: status || undefined,
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

  const resendMutation = useMutation({
    mutationFn: (notification) => adminApi.billing.notifications.resend(notification.id),
    onMutate: (notification) => setResending(notification.id),
    onSuccess: () => {
      toast.success('Notification resent')
      queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'notifications'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not resend the notification.')),
    onSettled: () => setResending(null),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'user',
      header: 'Recipient',
      render: (row) => (
        <div className="min-w-[170px]">
          <p className="text-sm font-semibold text-foreground">{row.user?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.user?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'type',
      header: 'Notification',
      render: (row) => (
        <div>
          <p className="text-sm font-medium text-foreground">
            {BILLING_NOTIFICATION_TYPES.find((item) => item.value === row.type)?.label || titleCase(row.type || '')}
          </p>
          <p className="text-xs text-muted-foreground">
            {titleCase(row.channel || '')} · {row.subscription?.plan_name || 'No plan'}
          </p>
        </div>
      ),
    },
    {
      key: 'invoice',
      header: 'Invoice',
      render: (row) =>
        row.invoice ? (
          <Link
            to={`/admin/billing/invoices/${row.invoice.id}`}
            className="text-sm font-medium text-highlight hover:underline"
            onClick={(event) => event.stopPropagation()}
          >
            {row.invoice.invoice_number}
          </Link>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (row) => (
        <div>
          <StatusBadge status={row.status} />
          {row.error_message && (
            <p className="mt-1 max-w-[180px] truncate text-[11px] text-destructive" title={row.error_message}>
              {row.error_message}
            </p>
          )}
        </div>
      ),
    },
    {
      key: 'created_at',
      header: 'Queued',
      render: (row) => (
        <div className="whitespace-nowrap text-xs text-muted-foreground">
          <p>{formatDateTime(row.created_at)}</p>
          {row.sent_at && <p>Sent {formatDateTime(row.sent_at)}</p>}
        </div>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (row) =>
        row.status !== 'sent' ? (
          <Button
            size="sm"
            variant="outline"
            onClick={() => resendMutation.mutate(row)}
            loading={resending === row.id}
          >
            <PaperAirplaneIcon className="h-3.5 w-3.5" />
            Resend
          </Button>
        ) : null,
    },
  ]

  return (
    <div>
      <PageHeader
        title="Billing notifications"
        description="Delivery log for invoice, payment and subscription emails."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search recipient…"
          className="w-full max-w-sm"
        />
        <Select
          value={type}
          onChange={(event) => {
            setType(event.target.value)
            setPage(1)
          }}
          className="w-52"
        >
          <option value="">All types</option>
          {BILLING_NOTIFICATION_TYPES.map((option) => (
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
          className="w-36"
        >
          <option value="">All statuses</option>
          {NOTIFICATION_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} notifications
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          emptyState={
            <EmptyState
              icon={BellAlertIcon}
              title="No billing notifications"
              description="Delivery attempts will be logged here."
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
