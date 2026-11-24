import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  BanknotesIcon,
  CreditCardIcon,
  DocumentTextIcon,
} from '@heroicons/react/20/solid'
import { BellIcon } from '@heroicons/react/24/outline'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { DataTable } from '@/components/shared/DataTable'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import {
  filenameFromResponse,
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
  saveBlob,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const BillingPage = ({ hideHeader = false }) => {
  const [tab, setTab] = useState('invoices')
  const [invoicesPage, setInvoicesPage] = useState(1)
  const [paymentsPage, setPaymentsPage] = useState(1)
  const [subscriptionsPage, setSubscriptionsPage] = useState(1)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'billing', { invoicesPage, paymentsPage, subscriptionsPage }],
    queryFn: async () => {
      const response = await realtorApi.billing.summary({
        invoices_page: invoicesPage,
        payments_page: paymentsPage,
        subs_page: subscriptionsPage,
      })
      return response.data?.data
    },
    placeholderData: (previous) => previous,
  })

  const downloadMutation = useMutation({
    mutationFn: (invoice) => realtorApi.billing.downloadInvoice(invoice.id),
    onSuccess: (response, invoice) => {
      saveBlob(
        response.data,
        filenameFromResponse(response, `Invoice-${invoice.invoice_number}.pdf`),
      )
      toast.success('Invoice download started')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not download the invoice.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const subscription = data?.current_subscription
  const summary = data?.summary || {}
  const invoices = data?.invoices?.data || []
  const invoiceMeta = data?.invoices?.meta || {}
  const payments = data?.payments?.data || []
  const paymentMeta = data?.payments?.meta || {}
  const subscriptions = data?.subscriptions?.data || []
  const subscriptionMeta = data?.subscriptions?.meta || {}

  const invoiceColumns = [
    {
      key: 'invoice_number',
      header: 'Invoice',
      render: (invoice) => (
        <Link
          to={`/realtor/billing/invoices/${invoice.id}`}
          className="font-semibold text-highlight hover:underline"
        >
          {invoice.invoice_number}
        </Link>
      ),
    },
    {
      key: 'created_at',
      header: 'Date',
      render: (invoice) => (
        <span className="whitespace-nowrap text-sm text-muted-foreground">
          {formatDate(invoice.created_at)}
        </span>
      ),
    },
    {
      key: 'total',
      header: 'Amount',
      render: (invoice) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{formatCurrency(invoice.total)}</p>
          <p className="text-[11px] text-muted-foreground">
            {invoice.status === 'paid' ? 'Paid' : 'Pending'} · tax {formatCurrency(invoice.tax)}
          </p>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (invoice) => <StatusBadge status={invoice.status} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (invoice) => (
        <div className="flex items-center justify-end gap-1">
          <Link to={`/realtor/billing/invoices/${invoice.id}`}>
            <Button size="icon-sm" variant="ghost" aria-label={`Open invoice ${invoice.invoice_number}`}>
              <ArrowTopRightOnSquareIcon className="h-4 w-4" />
            </Button>
          </Link>
          <Button
            size="icon-sm"
            variant="ghost"
            onClick={() => downloadMutation.mutate(invoice)}
            loading={downloadMutation.isPending && downloadMutation.variables?.id === invoice.id}
            aria-label={`Download invoice ${invoice.invoice_number}`}
          >
            <ArrowDownTrayIcon className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ]

  const paymentColumns = [
    {
      key: 'payment_date',
      header: 'Date',
      render: (payment) => (
        <span className="whitespace-nowrap text-sm text-muted-foreground">
          {formatDateTime(payment.payment_date || payment.created_at)}
        </span>
      ),
    },
    {
      key: 'reference',
      header: 'Reference',
      render: (payment) => (
        <div>
          <p className="text-sm font-medium text-foreground">
            {payment.transaction_id || payment.gateway_payment_id || '—'}
          </p>
          <p className="text-[11px] text-muted-foreground">
            {titleCase(payment.payment_method || payment.gateway || 'Other')}
          </p>
        </div>
      ),
    },
    {
      key: 'amount',
      header: 'Amount',
      render: (payment) => (
        <span className="text-sm font-semibold text-foreground">
          {formatCurrency(payment.amount)}
        </span>
      ),
    },
    {
      key: 'payment_status',
      header: 'Status',
      render: (payment) => <StatusBadge status={payment.payment_status} />,
    },
  ]

  const subscriptionColumns = [
    {
      key: 'plan_name',
      header: 'Plan',
      render: (item) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{item.plan_name}</p>
          <p className="text-[11px] text-muted-foreground">
            {item.duration_months} month{item.duration_months === 1 ? '' : 's'}
          </p>
        </div>
      ),
    },
    {
      key: 'period',
      header: 'Period',
      render: (item) => (
        <span className="whitespace-nowrap text-sm text-muted-foreground">
          {formatDate(item.start_date)} — {formatDate(item.expiry_date)}
        </span>
      ),
    },
    {
      key: 'total',
      header: 'Total',
      render: (item) => (
        <span className="text-sm font-semibold text-foreground">{formatCurrency(item.total)}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (item) => <StatusBadge status={item.status} />,
    },
  ]

  return (
    <div>
      {!hideHeader && (
        <PageHeader
          title="Billing"
          description="Review your subscription, invoices and payment history."
          actions={
            <>
              <Link to="/realtor/billing/notifications">
                <Button variant="outline" size="sm">
                  <BellIcon className="h-4 w-4" />
                  Notifications
                </Button>
              </Link>
              <Link to="/realtor/plans">
                <Button size="sm">Plans & upgrade</Button>
              </Link>
            </>
          }
        />
      )}

      <div className="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total paid"
          value={formatCurrency(summary.total_paid || 0)}
          icon={BanknotesIcon}
          tone="success"
          hint="Lifetime payments"
        />
        <StatsCard
          label="Pending payments"
          value={formatNumber(summary.pending_payments || 0)}
          icon={CreditCardIcon}
          tone="warning"
          hint="Awaiting completion"
        />
        <StatsCard
          label="Invoices"
          value={formatNumber(summary.total_invoices || 0)}
          icon={DocumentTextIcon}
          tone="info"
          hint="All time"
        />
        <StatsCard
          label="Last payment"
          value={summary.last_payment_at ? formatDate(summary.last_payment_at) : '—'}
          icon={BanknotesIcon}
          tone="primary"
          hint="Most recent transaction"
          valueClassName="text-[22px]"
        />
      </div>

      {subscription && (
        <Card className="mb-6">
          <CardHeader className="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle>{subscription.plan_name} subscription</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">
                {formatDate(subscription.start_date)} — {formatDate(subscription.expiry_date)}
              </p>
            </div>
            <StatusBadge status={subscription.status} />
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-3">
              <div>
                <p className="text-xs text-muted-foreground">Plan value</p>
                <p className="mt-0.5 text-sm font-semibold text-foreground">
                  {formatCurrency(subscription.price)}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">GST</p>
                <p className="mt-0.5 text-sm font-semibold text-foreground">
                  {formatCurrency(subscription.gst)}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Total paid</p>
                <p className="mt-0.5 text-sm font-semibold text-foreground">
                  {formatCurrency(subscription.total)}
                </p>
              </div>
            </div>
            {subscription.internal_notes && (
              <p className="mt-4 rounded-lg bg-muted px-3 py-2 text-xs text-muted-foreground">
                {subscription.internal_notes}
              </p>
            )}
          </CardContent>
        </Card>
      )}

      <Tabs
        tabs={[
          { key: 'invoices', label: 'Invoices', count: invoiceMeta.total },
          { key: 'payments', label: 'Payments', count: paymentMeta.total },
          { key: 'subscriptions', label: 'Subscriptions', count: subscriptionMeta.total },
        ]}
        value={tab}
        onChange={setTab}
        className="mb-4"
      />

      <Card>
        {tab === 'invoices' && (
          <>
            <DataTable
              columns={invoiceColumns}
              rows={invoices}
              rowKey="id"
              loading={isLoading}
              emptyState={
                <EmptyState
                  icon={DocumentTextIcon}
                  title="No invoices yet"
                  description="Invoices appear here after a plan purchase or renewal."
                  className="py-10"
                />
              }
            />
            <div className="px-4">
              <Pagination
                page={invoiceMeta.current_page || 1}
                lastPage={invoiceMeta.last_page || 1}
                total={invoiceMeta.total || 0}
                perPage={invoiceMeta.per_page || 10}
                onPageChange={setInvoicesPage}
              />
            </div>
          </>
        )}

        {tab === 'payments' && (
          <>
            <DataTable
              columns={paymentColumns}
              rows={payments}
              rowKey="id"
              loading={isLoading}
              emptyState={
                <EmptyState
                  icon={CreditCardIcon}
                  title="No payments recorded"
                  description="Your payment history will show up here."
                  className="py-10"
                />
              }
            />
            <div className="px-4">
              <Pagination
                page={paymentMeta.current_page || 1}
                lastPage={paymentMeta.last_page || 1}
                total={paymentMeta.total || 0}
                perPage={paymentMeta.per_page || 10}
                onPageChange={setPaymentsPage}
              />
            </div>
          </>
        )}

        {tab === 'subscriptions' && (
          <>
            <DataTable
              columns={subscriptionColumns}
              rows={subscriptions}
              rowKey="id"
              loading={isLoading}
              emptyState={
                <EmptyState
                  icon={BanknotesIcon}
                  title="No subscriptions yet"
                  description="Choose a plan to start your subscription."
                  className="py-10"
                  action={
                    <Link to="/realtor/plans">
                      <Button size="sm">View plans</Button>
                    </Link>
                  }
                />
              }
            />
            <div className="px-4">
              <Pagination
                page={subscriptionMeta.current_page || 1}
                lastPage={subscriptionMeta.last_page || 1}
                total={subscriptionMeta.total || 0}
                perPage={subscriptionMeta.per_page || 10}
                onPageChange={setSubscriptionsPage}
              />
            </div>
          </>
        )}
      </Card>
    </div>
  )
}
