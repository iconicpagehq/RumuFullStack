import { Link, useLocation, useSearchParams } from 'react-router-dom'
import { ArrowPathIcon, CreditCardIcon, XCircleIcon } from '@heroicons/react/20/solid'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'

export const CheckoutFailurePage = () => {
  const location = useLocation()
  const [searchParams] = useSearchParams()
  const state = location.state || {}

  const reason =
    state.reason ||
    searchParams.get('reason') ||
    'Your payment was cancelled or could not be completed. No plan changes were made.'
  const planId = state.planId || searchParams.get('plan')
  const reference =
    state.paymentId ||
    state.orderId ||
    searchParams.get('payment_id') ||
    searchParams.get('order_id')

  return (
    <div className="mx-auto max-w-2xl">
      <div className="text-center">
        <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-destructive/10 text-destructive">
          <XCircleIcon className="h-9 w-9" />
        </span>
        <h1 className="mt-4 text-2xl font-bold tracking-tight text-foreground">
          Payment not completed
        </h1>
        <p className="mx-auto mt-1.5 max-w-lg text-sm text-muted-foreground">{reason}</p>
      </div>

      <Card className="mt-6 p-5">
        <Alert variant="info" title="Your plan has not been changed">
          If money was debited but your plan still shows as inactive, keep the reference below and
          contact Rumu support — our team can trace the payment for you.
        </Alert>

        {reference && (
          <div className="mt-4 rounded-lg border border-border bg-muted/40 p-3">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
              Payment reference
            </p>
            <p className="mt-1 break-all font-mono text-xs text-foreground">{reference}</p>
          </div>
        )}

        <div className="mt-5 flex flex-col gap-2 sm:flex-row">
          <Link to={planId ? `/checkout?plan=${planId}` : '/realtor/plans'} className="sm:flex-1">
            <Button className="w-full">
              <ArrowPathIcon className="h-4 w-4" />
              Try again
            </Button>
          </Link>
          <Link to="/realtor/billing" className="sm:flex-1">
            <Button variant="outline" className="w-full">
              <CreditCardIcon className="h-4 w-4" />
              Go to billing
            </Button>
          </Link>
        </div>
      </Card>

      <p className="mt-5 text-center text-xs text-muted-foreground">
        You can also go back to the{' '}
        <Link to="/realtor/plans" className="font-semibold text-highlight hover:underline">
          plans page
        </Link>{' '}
        to review your options or request an upgrade.
      </p>
    </div>
  )
}
