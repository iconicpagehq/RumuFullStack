import { Link, useLocation } from 'react-router-dom'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  HomeIcon,
} from '@heroicons/react/20/solid'
import { useAuth } from '@/hooks/useAuth'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { formatCurrency, formatDate } from '@/utils/formatters'

const DetailRow = ({ label, children }) => (
  <div className="flex items-start justify-between gap-4 py-2.5 text-sm">
    <span className="text-muted-foreground">{label}</span>
    <span className="text-right font-medium text-foreground">{children}</span>
  </div>
)

export const CheckoutSuccessPage = () => {
  const { user } = useAuth()
  const location = useLocation()
  const result = location.state || {}
  const invoice = result.invoice
  const subscription = result.subscription
  const planName = subscription?.plan_name || result.plan_name

  const amount =
    invoice?.total ?? subscription?.total ?? (result.amount != null ? result.amount : null)

  return (
    <div className="mx-auto max-w-2xl">
      <div className="text-center">
        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500">
          <CheckCircleIcon className="h-9 w-9" />
        </span>
        <h1 className="mt-4 text-2xl font-bold tracking-tight text-foreground">
          Payment successful
        </h1>
        <p className="mx-auto mt-1.5 max-w-lg text-sm text-muted-foreground">
          {planName ? (
            <>
              Your <strong className="text-foreground">{planName}</strong> plan is now active. A
              receipt is on its way to {user?.email || 'your email'}.
            </>
          ) : (
            <>
              Your payment was received. Your plan is active and a receipt is on its way to{' '}
              {user?.email || 'your email'}.
            </>
          )}
        </p>
      </div>

      <Card className="mt-6 overflow-hidden">
        <div className="border-b border-border bg-muted/30 px-5 py-3">
          <div className="flex items-center justify-between gap-3">
            <span className="inline-flex items-center gap-2 text-sm font-semibold text-foreground">
              <DocumentTextIcon className="h-4 w-4 text-muted-foreground" />
              {invoice?.invoice_number
                ? `Invoice ${invoice.invoice_number}`
                : 'Payment confirmation'}
            </span>
            {subscription?.status && <StatusBadge status={subscription.status} />}
          </div>
        </div>

        <div className="divide-y divide-border px-5 py-2">
          {planName && (
            <DetailRow label="Plan">
              {planName}
              {subscription?.duration_months
                ? ` · ${subscription.duration_months} ${
                    subscription.duration_months === 1 ? 'month' : 'months'
                  }`
                : ''}
            </DetailRow>
          )}
          {amount != null && (
            <DetailRow label="Amount paid">
              {formatCurrency(amount, { precise: true })}
            </DetailRow>
          )}
          {subscription?.start_date && (
            <DetailRow label="Valid from">{formatDate(subscription.start_date)}</DetailRow>
          )}
          {subscription?.expiry_date && (
            <DetailRow label="Valid until">{formatDate(subscription.expiry_date)}</DetailRow>
          )}
          {result.paymentId && (
            <DetailRow label="Payment reference">
              <span className="break-all font-mono text-xs">{result.paymentId}</span>
            </DetailRow>
          )}
          {!invoice && !subscription && (
            <DetailRow label="Status">
              Payment received — your invoice may take a moment to appear in billing.
            </DetailRow>
          )}
        </div>
      </Card>

      <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-center">
        <Link to="/realtor/dashboard">
          <Button className="w-full sm:w-auto">
            <HomeIcon className="h-4 w-4" />
            Go to dashboard
          </Button>
        </Link>
        {invoice?.id ? (
          <Link to={`/realtor/billing/invoices/${invoice.id}`}>
            <Button variant="outline" className="w-full sm:w-auto">
              <DocumentTextIcon className="h-4 w-4" />
              View invoice
            </Button>
          </Link>
        ) : (
          <Link to="/realtor/billing">
            <Button variant="outline" className="w-full sm:w-auto">
              <DocumentTextIcon className="h-4 w-4" />
              Go to billing
            </Button>
          </Link>
        )}
      </div>

      <p className="mt-5 text-center text-xs text-muted-foreground">
        Need to change or renew your plan later?{' '}
        <Link to="/realtor/plans" className="inline-flex items-center gap-0.5 font-semibold text-highlight hover:underline">
          Manage plans
          <ArrowRightIcon className="h-3 w-3" />
        </Link>
      </p>
    </div>
  )
}
