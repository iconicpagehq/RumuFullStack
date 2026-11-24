import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ArrowDownTrayIcon, ArrowLeftIcon, PaperAirplaneIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { useAuth } from '@/hooks/useAuth'
import { Brand } from '@/components/shared/Brand'
import { DetailItem } from '@/components/shared/DetailItem'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { PageLoader } from '@/components/ui/Spinner'
import {
  filenameFromResponse,
  formatCurrency,
  formatDate,
  formatDateTime,
  saveBlob,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const InvoiceDetailPage = () => {
  const { id } = useParams()
  const { user } = useAuth()

  const { data: invoice, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'invoices', id],
    queryFn: async () => {
      const response = await adminApi.billing.invoices.get(id)
      return response.data?.data
    },
  })

  const downloadMutation = useMutation({
    mutationFn: () => adminApi.billing.invoices.download(id),
    onSuccess: (response) => {
      saveBlob(
        response.data,
        filenameFromResponse(response, `Invoice-${invoice?.invoice_number || id}.pdf`),
      )
      toast.success('Invoice download started')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not download the invoice.')),
  })

  const resendMutation = useMutation({
    mutationFn: () => adminApi.billing.invoices.resend(id),
    onSuccess: () => {
      toast.success('Invoice email queued')
      refetch()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not resend the invoice email.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const items = invoice?.items || []

  return (
    <div>
      <PageHeader
        title={invoice?.invoice_number}
        breadcrumbs={[
          { label: 'Invoices', to: '/admin/billing/invoices' },
          { label: invoice?.invoice_number || 'Invoice' },
        ]}
        actions={
          <>
            <Link to="/admin/billing/invoices">
              <Button variant="outline" size="sm">
                <ArrowLeftIcon className="h-4 w-4" />
                Back
              </Button>
            </Link>
            <Button
              size="sm"
              variant="outline"
              onClick={() => downloadMutation.mutate()}
              loading={downloadMutation.isPending}
            >
              <ArrowDownTrayIcon className="h-4 w-4" />
              Download PDF
            </Button>
            <Button size="sm" onClick={() => resendMutation.mutate()} loading={resendMutation.isPending}>
              <PaperAirplaneIcon className="h-4 w-4" />
              Resend email
            </Button>
          </>
        }
      />

      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap items-start justify-between gap-6">
            <div>
              <Brand />
              <p className="mt-4 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Billed to</p>
              <p className="mt-1 text-sm font-semibold text-foreground">{invoice?.user?.name || '—'}</p>
              <p className="text-sm text-muted-foreground">{invoice?.user?.email || ''}</p>
              {invoice?.user?.company && (
                <p className="text-sm text-muted-foreground">{invoice.user.company}</p>
              )}
            </div>
            <div className="text-right">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Invoice</p>
              <p className="mt-1 text-lg font-bold text-foreground">{invoice?.invoice_number}</p>
              <div className="mt-2 flex justify-end">
                <StatusBadge status={invoice?.status} />
              </div>
              <p className="mt-2 text-xs text-muted-foreground">
                Issued {formatDate(invoice?.created_at)}
              </p>
              {invoice?.emailed_at && (
                <p className="text-xs text-muted-foreground">Emailed {formatDateTime(invoice.emailed_at)}</p>
              )}
            </div>
          </div>

          <div className="mt-8 overflow-x-auto">
            <table className="min-w-full divide-y divide-border">
              <thead>
                <tr>
                  <th className="table-th">Description</th>
                  <th className="table-th text-right">Qty</th>
                  <th className="table-th text-right">Unit price</th>
                  <th className="table-th text-right">Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {items.length ? (
                  items.map((item) => (
                    <tr key={item.id}>
                      <td className="table-td text-sm text-foreground">{item.description}</td>
                      <td className="table-td text-right text-sm">{item.quantity}</td>
                      <td className="table-td text-right text-sm">{formatCurrency(item.unit_price, { precise: true })}</td>
                      <td className="table-td text-right text-sm font-medium">
                        {formatCurrency(item.total, { precise: true })}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td className="table-td text-sm text-muted-foreground" colSpan={4}>
                      {invoice?.subscription?.plan_name || 'Subscription'} ·{' '}
                      {invoice?.subscription?.duration_months || 0} months
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          <div className="mt-6 flex justify-end">
            <dl className="w-full max-w-xs space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <dt className="text-muted-foreground">Subtotal</dt>
                <dd className="font-medium text-foreground">{formatCurrency(invoice?.subtotal, { precise: true })}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-muted-foreground">Tax</dt>
                <dd className="font-medium text-foreground">{formatCurrency(invoice?.tax, { precise: true })}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-muted-foreground">Discount</dt>
                <dd className="font-medium text-foreground">−{formatCurrency(invoice?.discount, { precise: true })}</dd>
              </div>
              <div className="flex items-center justify-between border-t border-border pt-2">
                <dt className="font-semibold text-foreground">Total</dt>
                <dd className="text-lg font-bold text-foreground">{formatCurrency(invoice?.total, { precise: true })}</dd>
              </div>
            </dl>
          </div>

          <dl className="mt-8 grid gap-4 border-t border-border pt-5 sm:grid-cols-3">
            <DetailItem label="Plan">{invoice?.subscription?.plan_name || '—'}</DetailItem>
            <DetailItem label="Payment method">{titleCase(invoice?.payment?.payment_method || '—')}</DetailItem>
            <DetailItem label="Transaction">
              {invoice?.payment?.transaction_id || invoice?.payment?.gateway_payment_id || '—'}
            </DetailItem>
            <DetailItem label="Payment status">
              {invoice?.payment ? <StatusBadge status={invoice.payment.payment_status} /> : '—'}
            </DetailItem>
            <DetailItem label="Payment date">{formatDateTime(invoice?.payment?.payment_date)}</DetailItem>
            <DetailItem label="Prepared by">{user?.name || 'Administrator'}</DetailItem>
          </dl>
        </CardContent>
      </Card>
    </div>
  )
}
