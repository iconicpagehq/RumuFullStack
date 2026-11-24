import { useMemo, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  CheckIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  TagIcon,
} from '@heroicons/react/20/solid'
import { checkoutApi } from '@/api/checkout'
import { useAuth } from '@/hooks/useAuth'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { Spinner, PageLoader } from '@/components/ui/Spinner'
import { cn } from '@/utils/cn'
import { getErrorMessage } from '@/utils/errors'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { loadRazorpay } from '@/utils/razorpay'

const DURATIONS = [1, 3, 6, 12]

const SummaryRow = ({ label, value, tone }) => (
  <div className="flex items-start justify-between gap-3 text-sm">
    <span className="text-muted-foreground">{label}</span>
    <span
      className={cn(
        'shrink-0 font-medium text-foreground',
        tone === 'success' && 'text-emerald-600 dark:text-emerald-400',
      )}
    >
      {value}
    </span>
  </div>
)

const planLimit = (value, plural) =>
  value ? `${formatNumber(value)} ${plural}` : `Unlimited ${plural}`

export const CheckoutPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { user } = useAuth()
  const [searchParams] = useSearchParams()
  const requestedPlanId = searchParams.get('plan') || searchParams.get('plan_id')

  const [selectedPlanId, setSelectedPlanId] = useState(null)
  const [duration, setDuration] = useState(1)
  const [couponInput, setCouponInput] = useState('')
  const [appliedCoupon, setAppliedCoupon] = useState('')
  const [stage, setStage] = useState('idle')
  const [checkoutError, setCheckoutError] = useState('')
  const starting = stage === 'starting'
  const verifying = stage === 'verifying'

  const plansQuery = useQuery({
    queryKey: ['checkout-plans'],
    queryFn: async () => {
      const response = await checkoutApi.plans()
      return response.data?.data
    },
  })

  const paidPlans = useMemo(
    () =>
      [...(plansQuery.data?.plans || [])]
        .filter((plan) => Number(plan.price) > 0)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0)),
    [plansQuery.data],
  )

  const selectedPlan =
    paidPlans.find((plan) => String(plan.id) === String(selectedPlanId)) ||
    paidPlans.find((plan) => String(plan.id) === requestedPlanId) ||
    paidPlans[0] ||
    null

  const quoteQuery = useQuery({
    queryKey: ['checkout-quote', selectedPlan?.id, duration, appliedCoupon],
    queryFn: async () => {
      const response = await checkoutApi.summary({
        plan_id: selectedPlan.id,
        duration_months: duration,
        coupon_code: appliedCoupon || undefined,
      })
      return response.data?.data
    },
    enabled: Boolean(selectedPlan?.id),
    retry: false,
  })

  const quote = quoteQuery.data
  const coupon = quote?.coupon

  const applyCoupon = useMutation({
    mutationFn: (code) =>
      checkoutApi.applyCoupon({
        plan_id: selectedPlan.id,
        code,
        duration_months: duration,
      }),
    onSuccess: (response, code) => {
      setAppliedCoupon(code)
      setCouponInput(code)
      setCheckoutError('')
      queryClient.setQueryData(
        ['checkout-quote', selectedPlan?.id, duration, code],
        response.data?.data,
      )
      toast.success('Coupon applied')
    },
    onError: (error) => toast.error(getErrorMessage(error, 'That coupon could not be applied.')),
  })

  const removeCoupon = useMutation({
    mutationFn: () =>
      checkoutApi.removeCoupon({ plan_id: selectedPlan.id, duration_months: duration }),
    onSuccess: (response) => {
      setAppliedCoupon('')
      setCouponInput('')
      queryClient.setQueryData(
        ['checkout-quote', selectedPlan?.id, duration, ''],
        response.data?.data,
      )
      toast.success('Coupon removed')
    },
    onError: (error) => toast.error(getErrorMessage(error, 'Could not remove the coupon.')),
  })

  const choosePlan = (plan) => {
    setSelectedPlanId(plan.id)
    setAppliedCoupon('')
    setCouponInput('')
    setCheckoutError('')
  }

  const chooseDuration = (months) => {
    setDuration(months)
    setAppliedCoupon('')
    setCouponInput('')
  }

  const handleApplyCoupon = () => {
    if (appliedCoupon) return
    const code = couponInput.trim()
    if (!code) {
      toast.error('Enter a coupon code first.')
      return
    }
    applyCoupon.mutate(code)
  }

  const verifyPayment = async (paymentResponse, order, plan) => {
    setStage('verifying')
    try {
      const response = await checkoutApi.verify({
        razorpay_order_id: paymentResponse.razorpay_order_id,
        razorpay_payment_id: paymentResponse.razorpay_payment_id,
        razorpay_signature: paymentResponse.razorpay_signature,
      })
      const result = response.data?.data || {}
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
      queryClient.invalidateQueries({ queryKey: ['realtor'] })
      queryClient.invalidateQueries({ queryKey: ['checkout-plans'] })
      navigate('/checkout/success', {
        replace: true,
        state: {
          ...result,
          plan_name: result.subscription?.plan_name || plan?.name,
          duration_months: result.subscription?.duration_months || duration,
          amount: order?.payment?.amount,
          paymentId: paymentResponse.razorpay_payment_id,
        },
      })
    } catch (error) {
      navigate('/checkout/failure', {
        replace: true,
        state: {
          reason: getErrorMessage(error, 'We could not verify your payment.'),
          planId: plan?.id,
          orderId: order?.payment?.gateway_order_id,
        },
      })
    } finally {
      setStage('idle')
    }
  }

  const handlePay = async () => {
    if (!selectedPlan || !quote || stage !== 'idle') return
    setCheckoutError('')
    setStage('starting')

    try {
      await loadRazorpay()

      const response = await checkoutApi.createOrder({
        plan_id: selectedPlan.id,
        duration_months: duration,
        coupon_code: appliedCoupon || undefined,
      })
      const order = response.data?.data

      if (
        !window.Razorpay ||
        order?.payment?.gateway !== 'razorpay' ||
        !order?.payment?.gateway_order_id
      ) {
        throw new Error('The payment gateway is unavailable right now. Please try again later.')
      }

      const instance = new window.Razorpay({
        key: order.razorpay_key,
        amount: order.payment.amount_in_subunits,
        currency: order.payment.currency,
        order_id: order.payment.gateway_order_id,
        name: 'Rumu',
        description: `${selectedPlan.name} plan · ${duration} ${duration === 1 ? 'month' : 'months'}`,
        prefill: order.prefill || {},
        notes: { subscription_id: String(order.subscription_id) },
        theme: { color: '#8a006b' },
        modal: {
          ondismiss: () => {
            setStage('idle')
            toast('Payment cancelled. You can try again whenever you are ready.')
          },
        },
        handler: (paymentResponse) => verifyPayment(paymentResponse, order, selectedPlan),
      })

      instance.on('payment.failed', (failure) => {
        setStage('idle')
        navigate('/checkout/failure', {
          replace: true,
          state: {
            reason:
              failure?.error?.description || 'Your payment could not be completed by the bank.',
            planId: selectedPlan.id,
            orderId: order.payment.gateway_order_id,
            paymentId: failure?.error?.metadata?.payment_id,
          },
        })
      })

      instance.open()
    } catch (error) {
      setStage('idle')
      setCheckoutError(getErrorMessage(error, 'Could not start the payment. Please try again.'))
    }
  }

  if (plansQuery.isLoading) return <PageLoader />
  if (plansQuery.isError) {
    return <ErrorState error={plansQuery.error} onRetry={plansQuery.refetch} />
  }

  if (!paidPlans.length) {
    return (
      <div className="mx-auto max-w-xl">
        <Card className="p-6 text-center">
          <h1 className="text-lg font-bold text-foreground">No paid plans available</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            Online checkout is not open right now. Please contact the Rumu team or request an
            upgrade from the plans page.
          </p>
          <Link to="/realtor/plans" className="mt-4 inline-block">
            <Button variant="outline">
              <ArrowLeftIcon className="h-4 w-4" />
              Back to plans
            </Button>
          </Link>
        </Card>
      </div>
    )
  }

  return (
    <div>
      {verifying && (
        <div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-3 bg-background/90 backdrop-blur-sm">
          <Spinner className="h-8 w-8" />
          <p className="text-sm font-semibold text-foreground">Verifying your payment…</p>
          <p className="text-xs text-muted-foreground">Please keep this page open.</p>
        </div>
      )}

      <div className="mb-6">
        <Link
          to="/realtor/plans"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground"
        >
          <ArrowLeftIcon className="h-3.5 w-3.5" />
          Back to plans
        </Link>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-foreground">
          Complete your upgrade
        </h1>
        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
          Choose your plan and billing period. Your new plan activates as soon as the payment is
          verified.
        </p>
      </div>

      <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
        <div className="space-y-5">
          <Card className="p-5">
            <div className="flex items-center justify-between gap-3">
              <h2 className="text-base font-semibold text-foreground">1. Choose your plan</h2>
              <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Monthly pricing
              </span>
            </div>

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
              {paidPlans.map((plan) => {
                const active = plan.id === selectedPlan?.id
                return (
                  <button
                    key={plan.id}
                    type="button"
                    onClick={() => choosePlan(plan)}
                    className={cn(
                      'rounded-xl border p-4 text-left transition',
                      active
                        ? 'border-highlight bg-primary/5 ring-1 ring-highlight'
                        : 'border-border hover:border-highlight/40 hover:bg-muted/40',
                    )}
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <p className="text-sm font-semibold text-foreground">{plan.name}</p>
                        <p className="mt-1 text-xl font-bold text-foreground">
                          {formatCurrency(plan.price)}
                          <span className="ml-0.5 text-xs font-medium text-muted-foreground">
                            /month
                          </span>
                        </p>
                      </div>
                      {active ? (
                        <CheckCircleIcon className="h-5 w-5 shrink-0 text-highlight" />
                      ) : (
                        <span className="mt-1 h-4 w-4 shrink-0 rounded-full border border-border" />
                      )}
                    </div>
                    <ul className="mt-3 space-y-1 text-xs text-muted-foreground">
                      <li className="flex items-center gap-1.5">
                        <CheckIcon className="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                        {planLimit(plan.max_campaigns, 'campaigns')}
                      </li>
                      <li className="flex items-center gap-1.5">
                        <CheckIcon className="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                        {planLimit(plan.max_leads_per_campaign, 'leads per campaign')}
                      </li>
                    </ul>
                  </button>
                )
              })}
            </div>

            <div className="mt-5 border-t border-border pt-4">
              <h3 className="text-sm font-semibold text-foreground">Billing period</h3>
              <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                {DURATIONS.map((months) => {
                  const active = duration === months
                  return (
                    <button
                      key={months}
                      type="button"
                      onClick={() => chooseDuration(months)}
                      className={cn(
                        'rounded-lg border px-2 py-2.5 text-center transition',
                        active
                          ? 'border-highlight bg-primary/5 ring-1 ring-highlight'
                          : 'border-border hover:bg-muted/50',
                      )}
                    >
                      <span
                        className={cn(
                          'block text-sm font-semibold',
                          active ? 'text-highlight' : 'text-foreground',
                        )}
                      >
                        {months} {months === 1 ? 'month' : 'months'}
                      </span>
                      <span className="mt-0.5 block text-[10px] text-muted-foreground">
                        {formatCurrency(Number(selectedPlan.price) * months)}
                      </span>
                    </button>
                  )
                })}
              </div>
            </div>
          </Card>

          <Card className="p-5">
            <h2 className="text-base font-semibold text-foreground">2. Coupon</h2>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Have a promotional code? Apply it before paying.
            </p>

            <div className="mt-3 flex gap-2">
              <Input
                value={couponInput}
                onChange={(event) => setCouponInput(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === 'Enter') handleApplyCoupon()
                }}
                placeholder="Enter coupon code"
                autoComplete="off"
                disabled={Boolean(appliedCoupon) || applyCoupon.isPending || removeCoupon.isPending}
              />
              {appliedCoupon ? (
                <Button
                  variant="outline"
                  onClick={() => removeCoupon.mutate()}
                  loading={removeCoupon.isPending}
                >
                  Remove
                </Button>
              ) : (
                <Button
                  variant="outline"
                  onClick={handleApplyCoupon}
                  loading={applyCoupon.isPending}
                  disabled={!couponInput.trim()}
                >
                  Apply
                </Button>
              )}
            </div>

            {coupon && (
              <p className="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <TagIcon className="h-3.5 w-3.5" />
                {coupon.code} applied — you save {formatCurrency(quote.discount, { precise: true })}
              </p>
            )}
            {!coupon && quote?.coupon_error && (
              <p className="mt-2.5 text-xs font-medium text-destructive">{quote.coupon_error}</p>
            )}
          </Card>

          <Card className="p-5">
            <div className="flex items-center justify-between gap-3">
              <h2 className="text-base font-semibold text-foreground">3. Billing details</h2>
              <Link
                to="/realtor/profile"
                className="text-xs font-semibold text-highlight hover:underline"
              >
                Edit profile
              </Link>
            </div>
            <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt className="text-xs text-muted-foreground">Name</dt>
                <dd className="mt-0.5 font-medium text-foreground">{user?.name || '—'}</dd>
              </div>
              <div>
                <dt className="text-xs text-muted-foreground">Email</dt>
                <dd className="mt-0.5 truncate font-medium text-foreground">{user?.email || '—'}</dd>
              </div>
              <div>
                <dt className="text-xs text-muted-foreground">Phone</dt>
                <dd className="mt-0.5 font-medium text-foreground">{user?.phone || '—'}</dd>
              </div>
              <div>
                <dt className="text-xs text-muted-foreground">Company</dt>
                <dd className="mt-0.5 font-medium text-foreground">{user?.company || '—'}</dd>
              </div>
            </dl>
            <p className="mt-3 text-xs text-muted-foreground">
              Your invoice and payment receipt are sent to this email address.
            </p>
          </Card>
        </div>

        <Card className="p-5 lg:sticky lg:top-5">
          <h2 className="text-base font-semibold text-foreground">Order summary</h2>

          {checkoutError && (
            <Alert variant="danger" className="mt-3" title="Payment could not start">
              {checkoutError}
            </Alert>
          )}

          {quoteQuery.isError ? (
            <div className="mt-4">
              <Alert variant="danger" title="Could not calculate the total">
                {getErrorMessage(quoteQuery.error)}
              </Alert>
              <Button
                variant="outline"
                size="sm"
                className="mt-3 w-full"
                onClick={() => quoteQuery.refetch()}
              >
                Try again
              </Button>
            </div>
          ) : !quote ? (
            <div className="flex items-center justify-center py-10">
              <Spinner className="h-6 w-6" />
            </div>
          ) : (
            <>
              <div className="mt-4 space-y-2.5">
                <SummaryRow
                  label={`${selectedPlan.name} × ${duration} ${duration === 1 ? 'month' : 'months'}`}
                  value={formatCurrency(quote.price, { precise: true })}
                />
                {Number(quote.discount) > 0 && (
                  <SummaryRow
                    label={`Discount${coupon ? ` (${coupon.code})` : ''}`}
                    value={`− ${formatCurrency(quote.discount, { precise: true })}`}
                    tone="success"
                  />
                )}
                {Number(quote.tax_percent) > 0 && (
                  <SummaryRow
                    label={`${quote.tax_label || 'GST'} (${formatNumber(quote.tax_percent)}%${
                      quote.tax_inclusive ? ', included' : ''
                    })`}
                    value={formatCurrency(quote.tax_amount, { precise: true })}
                  />
                )}
              </div>

              <div className="mt-4 flex items-center justify-between gap-3 border-t border-border pt-3">
                <span className="text-sm font-semibold text-foreground">Total due today</span>
                <span className="text-lg font-bold text-foreground">
                  {formatCurrency(quote.total, { precise: true })}
                </span>
              </div>

              <Button
                size="lg"
                className="mt-4 w-full"
                onClick={handlePay}
                loading={starting}
                disabled={starting || verifying || quoteQuery.isFetching || Boolean(quote.coupon_error)}
              >
                {!starting && <LockClosedIcon className="h-4 w-4" />}
                Pay {formatCurrency(quote.total, { precise: true })}
              </Button>

              <ul className="mt-4 space-y-2 text-[11px] leading-relaxed text-muted-foreground">
                <li className="flex items-start gap-1.5">
                  <ShieldCheckIcon className="mt-px h-3.5 w-3.5 shrink-0" />
                  Secured by Razorpay. Rumu never stores your card details.
                </li>
                <li>
                  By paying you agree to our{' '}
                  <Link to="/terms-of-service" className="font-medium text-foreground hover:underline">
                    Terms of Service
                  </Link>{' '}
                  and{' '}
                  <Link to="/privacy-policy" className="font-medium text-foreground hover:underline">
                    Privacy Policy
                  </Link>
                  .
                </li>
              </ul>
            </>
          )}
        </Card>
      </div>
    </div>
  )
}
