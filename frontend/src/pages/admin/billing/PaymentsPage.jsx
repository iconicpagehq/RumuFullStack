import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { BanknotesIcon, CreditCardIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Badge } from '@/components/ui/Badge'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader, Spinner } from '@/components/ui/Spinner'
import { DataTable } from '@/components/shared/DataTable'
import { DetailItem } from '@/components/shared/DetailItem'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { PAGE_SIZE, PAYMENT_STATUSES } from '@/utils/constants'
import { formatCurrency, formatDateTime, formatNumber, titleCase } from '@/utils/formatters'

const PaymentDetail = ({ paymentId, onClose }) => {
  const { data: payment, isLoading } = useQuery({
    queryKey: ['admin', 'billing', 'payments', paymentId],
    queryFn: async () => {
      const response = await adminApi.billing.payments.get(paymentId)
      return response.data?.data
    },
  })

  return (
    <Modal open onClose={onClose} title="Payment details" description={payment ? `Payment #${payment.id}` : undefined}>
      {isLoading ? (
        <div className="flex justify-center py-8">
          <Spinner className="h-7 w-7" />
        </div>
      ) : (
        <div className="space-y-5">
          <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-muted/40 p-4">
            <div>
              <p className="text-2xl font-bold text-foreground">{formatCurrency(payment?.amount, { precise: true })}</p>
              <p className="text-xs uppercase tracking-wide text-muted-foreground">{payment?.currency}</p>
            </div>
            <StatusBadge status={payment?.payment_status} />
          </div>

          <dl className="grid gap-4 sm:grid-cols-2">
            <DetailItem label="Realtor">{payment?.subscription?.user?.name || '—'}</DetailItem>
            <DetailItem label="Email">{payment?.subscription?.user?.email || '—'}</DetailItem>
            <DetailItem label="Plan">{payment?.subscription?.plan_name || '—'}</DetailItem>
            <DetailItem label="Method">{titleCase(payment?.payment_method || '—')}</DetailItem>
            <DetailItem label="Payment date">{formatDateTime(payment?.payment_date)}</DetailItem>
            <DetailItem label="Verified">{formatDateTime(payment?.verified_at)}</DetailItem>
            <DetailItem label="Transaction ID">{payment?.transaction_id || '—'}</DetailItem>
            <DetailItem label="Gateway">{titleCase(payment?.gateway || '—')}</DetailItem>
            <DetailItem label="Gateway order">
              <span className="break-all text-xs">{payment?.gateway_order_id || '—'}</span>
            </DetailItem>
            <DetailItem label="Gateway payment">
              <span className="break-all text-xs">{payment?.gateway_payment_id || '—'}</span>
            </DetailItem>
          </dl>

          {payment?.coupon && (
            <div className="rounded-lg border border-border bg-muted/30 p-3">
              <p className="text-xs font-semibold text-foreground">Coupon · {payment.coupon.code}</p>
              <p className="mt-0.5 text-xs text-muted-foreground">
                {payment.coupon.name} ·{' '}
                {payment.coupon.discount_type === 'percentage'
                  ? `${payment.coupon.discount_value}% off`
                  : `${formatCurrency(payment.coupon.discount_value)} off`}
              </p>
            </div>
          )}
        </div>
      )}
    </Modal>
  )
}

export const PaymentsPage = () => {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [selected, setSelected] = useState(null)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'payments', { search: debouncedSearch, status, page }],
    queryFn: async () => {
      const response = await adminApi.billing.payments.list({
        search: debouncedSearch || undefined,
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

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'reference',
      header: 'Reference',
      render: (row) => (
        <div className="min-w-[150px]">
          <p className="text-sm font-semibold text-foreground">
            {row.transaction_id || row.gateway_payment_id || `#${row.id}`}
          </p>
          <p className="text-xs capitalize text-muted-foreground">{row.gateway || row.payment_method || '—'}</p>
        </div>
      ),
    },
    {
      key: 'realtor',
      header: 'Realtor',
      render: (row) => (
        <div className="min-w-[170px]">
          <p className="text-sm font-medium text-foreground">{row.subscription?.user?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.subscription?.user?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'plan',
      header: 'Plan',
      render: (row) => <span className="text-sm">{row.subscription?.plan_name || '—'}</span>,
    },
    {
      key: 'amount',
      header: 'Amount',
      render: (row) => <span className="text-sm font-semibold text-foreground">{formatCurrency(row.amount)}</span>,
    },
    {
      key: 'payment_status',
      header: 'Status',
      render: (row) => <StatusBadge status={row.payment_status} />,
    },
    {
      key: 'payment_date',
      header: 'Date',
      render: (row) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(row.payment_date || row.created_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader title="Payments" description="All online and offline payments recorded on the platform." />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search transaction, order or realtor…"
          className="w-full max-w-sm"
        />
        <Select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="w-40"
        >
          <option value="">All statuses</option>
          {PAYMENT_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <div className="ml-auto flex items-center gap-1.5">
          <Badge variant="neutral">
            <CreditCardIcon className="h-3.5 w-3.5" />
            {formatNumber(data?.meta?.total || 0)} payments
          </Badge>
        </div>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          onRowClick={(row) => setSelected(row.id)}
          emptyState={
            <EmptyState
              icon={BanknotesIcon}
              title="No payments found"
              description="Payments will appear as realtors subscribe and renew."
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

      {selected && <PaymentDetail paymentId={selected} onClose={() => setSelected(null)} />}
    </div>
  )
}
