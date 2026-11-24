import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { useAuthStore } from '@/stores/authStore'
import { Brand } from '@/components/shared/Brand'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { PageLoader } from '@/components/ui/Spinner'
import {
  filenameFromResponse,
  formatCurrency,
  formatDate,
  saveBlob,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const InvoiceDetailPage = () => {
  const { id } = useParams()
  const user = useAuthStore((state) => state.user)

  const { data: invoice, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'billing', 'invoice', id],
    queryFn: async () => {
      const response = await realtorApi.billing.invoice(id)
      return response.data?.data
    },
    enabled: Boolean(id),
    retry: false,
  })

  const downloadMutation = useMutation({
    mutationFn: () => realtorApi.billing.downloadInvoice(id),
    onSuccess: (response) => {
      saveBlob(
        response.data,
        filenameFromResponse(response, `Invoice-${invoice?.invoice_number || id}.pdf`),
      )
      toast.success('Invoice download started')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not download the invoice.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const items = invoice.items || []
  const payment = invoice.payment
  const subscription = invoice.subscription
  const hasDiscount = Number(invoice.discount) > 0

  return (
    <div className="mx-auto max-w-4xl">
      <PageHeader
        breadcrumbs={[
          { label: 'Billing', to: '/realtor/billing' },
          { label: invoice.invoice_number },
        ]}
        title={invoice.invoice_number}
        description={`Issued ${formatDate(invoice.created_at)}`}
        actions={
          <>
            <Link to="/realtor/billing">
              <Button variant="outline" size="sm">
                <ArrowLeftIcon className="h-4 w-4" />
                Back to billing
              </Button>
            </Link>
            <Button
              size="sm"
              onClick={() => downloadMutation.mutate()}
              loading={downloadMutation.isPending}
            >
              <ArrowDownTrayIcon className="h-4 w-4" />
              Download PDF
            </Button>
          </>
        }
      />

      <Card className="overflow-hidden">
        <div className="border-b border-border bg-muted/40 p-6 sm:p-8">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <Brand to="/realtor/dashboard" />
              <p className="mt-3 text-sm text-muted-foreground">Rumu CRM · Billing</p>
            </div>
            <div className="text-right">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Invoice
              </p>
              <p className="mt-1 text-lg font-bold text-foreground">{invoice.invoice_number}</p>
              <div className="mt-2 flex justify-end">
                <StatusBadge status={invoice.status} />
              </div>
            </div>
          </div>
        </div>

        <div className="grid gap-6 border-b border-border p-6 sm:grid-cols-2 sm:p-8">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              Billed to
            </p>
            <p className="mt-2 text-sm font-semibold text-foreground">{user?.name || '—'}</p>
            <p className="mt-0.5 text-sm text-muted-foreground">{user?.email}</p>
            {user?.company && <p className="mt-0.5 text-sm text-muted-foreground">{user.company}</p>}
            {user?.phone && <p className="mt-0.5 text-sm text-muted-foreground">{user.phone}</p>}
          </div>
          <div className="sm:text-right">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              Details
            </p>
            <div className="mt-2 space-y-1 text-sm">
              <p className="text-muted-foreground">
                Invoice date: <span className="text-foreground">{formatDate(invoice.created_at)}</span>
              </p>
              {subscription && (
                <p className="text-muted-foreground">
                  Plan:{' '}
                  <span className="text-foreground">
                    {subscription.plan_name} · {subscription.duration_months} months
                  </span>
                </p>
              )}
              {payment && (
                <p className="text-muted-foreground">
                  Payment:{' '}
                  <span className="text-foreground">
                    {titleCase(payment.payment_method || payment.gateway || 'Other')}
                  </span>
                </p>
              )}
              {invoice.emailed_at && (
                <p className="text-muted-foreground">
                  Emailed: <span className="text-foreground">{formatDate(invoice.emailed_at)}</span>
                </p>
              )}
            </div>
          </div>
        </div>

        <div className="p-6 sm:p-8">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[32rem] text-sm">
              <thead>
                <tr className="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                  <th className="pb-2.5 font-semibold">Description</th>
                  <th className="pb-2.5 text-center font-semibold">Qty</th>
                  <th className="pb-2.5 text-right font-semibold">Unit price</th>
                  <th className="pb-2.5 text-right font-semibold">Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={4} className="py-6 text-center text-muted-foreground">
                      No line items on this invoice.
                    </td>
                  </tr>
                ) : (
                  items.map((item) => (
                    <tr key={item.id}>
                      <td className="py-3 pr-3 font-medium text-foreground">{item.description}</td>
                      <td className="py-3 text-center text-muted-foreground">{item.quantity}</td>
                      <td className="py-3 text-right text-muted-foreground">
                        {formatCurrency(item.unit_price)}
                      </td>
                      <td className="py-3 text-right font-semibold text-foreground">
                        {formatCurrency(item.total)}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          <div className="mt-6 ml-auto w-full max-w-xs space-y-2 text-sm">
            <div className="flex items-center justify-between text-muted-foreground">
              <span>Subtotal</span>
              <span className="font-medium text-foreground">{formatCurrency(invoice.subtotal)}</span>
            </div>
            <div className="flex items-center justify-between text-muted-foreground">
              <span>Tax</span>
              <span className="font-medium text-foreground">{formatCurrency(invoice.tax)}</span>
            </div>
            {hasDiscount && (
              <div className="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                <span>Discount</span>
                <span className="font-medium">−{formatCurrency(invoice.discount)}</span>
              </div>
            )}
            <div className="flex items-center justify-between border-t border-border pt-2.5 text-base">
              <span className="font-semibold text-foreground">Total</span>
              <span className="font-bold text-foreground">{formatCurrency(invoice.total)}</span>
            </div>
          </div>
        </div>

        {payment && (
          <div className="border-t border-border bg-muted/30 p-6 sm:p-8">
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              Payment information
            </p>
            <div className="mt-2 grid gap-3 text-sm sm:grid-cols-3">
              <div>
                <p className="text-xs text-muted-foreground">Status</p>
                <div className="mt-1">
                  <StatusBadge status={payment.payment_status} />
                </div>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Transaction</p>
                <p className="mt-1 truncate font-mono text-xs text-foreground">
                  {payment.transaction_id || payment.gateway_payment_id || '—'}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Paid on</p>
                <p className="mt-1 font-medium text-foreground">
                  {formatDate(payment.payment_date || payment.created_at)}
                </p>
              </div>
            </div>
          </div>
        )}

        <div className="border-t border-border p-6 text-center text-xs text-muted-foreground sm:p-8">
          Thank you for your business. For billing questions, contact support from your workspace.
        </div>
      </Card>
    </div>
  )
}
