import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { CheckCircleIcon, SparklesIcon } from '@heroicons/react/20/solid'
import { ClockIcon } from '@heroicons/react/24/outline'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { PageLoader } from '@/components/ui/Spinner'
import { cn } from '@/utils/cn'
import { formatCurrency, formatDate, formatNumber, timeAgo } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'
import { planAccentColor } from '@/utils/planColors'

const PlanFeature = ({ children }) => (
  <li className="flex items-start gap-2">
    <CheckCircleIcon className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
    <span className="text-sm text-muted-foreground">{children}</span>
  </li>
)

export const PlansPage = ({ hideHeader = false }) => {
  const queryClient = useQueryClient()

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'plans'],
    queryFn: async () => {
      const response = await realtorApi.plans.list()
      return response.data?.data
    },
  })

  const upgradeMutation = useMutation({
    mutationFn: (planId) => realtorApi.plans.requestUpgrade(planId),
    onSuccess: () => {
      toast.success('Upgrade request sent. Our team will review it shortly.')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'plans'] })
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not send the upgrade request.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const plans = [...(data?.plans || [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
  const currentPlan = data?.current_plan
  const campaignsCount = data?.campaigns_count || 0
  const pendingUpgrade = data?.pending_upgrade
  const expiry = data?.expiry_info
  const currentSort = currentPlan?.sort_order ?? -1

  return (
    <div>
      {!hideHeader && (
        <PageHeader
          title="Plans & upgrade"
          description="Compare available plans, track your usage and move to a higher tier when you need more."
        />
      )}

      {pendingUpgrade && (
        <Alert variant="warning" className="mb-5" title="Upgrade request pending">
          You requested the <strong>{pendingUpgrade.requested_plan?.name}</strong> plan{' '}
          {timeAgo(pendingUpgrade.created_at)}. Our team will review it shortly.
        </Alert>
      )}

      {currentPlan && (
        <Card className="mb-6">
          <div className="flex flex-wrap items-start justify-between gap-5 p-5 sm:p-6">
            <div className="flex items-start gap-4">
              <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-highlight/10 dark:text-highlight">
                <SparklesIcon className="h-5 w-5" />
              </span>
              <div>
                <div className="flex flex-wrap items-center gap-2">
                  <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                    Current plan
                  </p>
                  <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">
                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    Active
                  </span>
                </div>
                <h2 className="mt-1 text-xl font-semibold tracking-tight text-foreground">
                  {currentPlan.name}
                </h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  {formatNumber(campaignsCount)} of{' '}
                  {currentPlan.max_campaigns ? formatNumber(currentPlan.max_campaigns) : 'unlimited'}{' '}
                  campaigns used
                </p>
              </div>
            </div>
            <div className="rounded-xl border border-border bg-muted/40 px-4 py-3">
              <p className="text-[11px] font-medium uppercase tracking-[0.12em] text-muted-foreground">
                Plan price
              </p>
              <p className="mt-1 text-lg font-semibold tracking-tight text-foreground">
                {Number(currentPlan.price) > 0 ? formatCurrency(currentPlan.price) : 'Free'}
                {Number(currentPlan.price) > 0 && (
                  <span className="ml-0.5 text-xs font-medium text-muted-foreground">/mo</span>
                )}
              </p>
            </div>
          </div>

          <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-5 py-3.5 sm:px-6">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-muted-foreground">
              <span className="inline-flex items-center gap-1.5">
                <ClockIcon className="h-3.5 w-3.5" />
                {expiry
                  ? `Expires ${expiry.expires_formatted || formatDate(expiry.expires_at)} · ${expiry.days_remaining}d remaining`
                  : 'No expiry date on record'}
              </span>
              <span className="hidden text-border sm:inline">·</span>
              <span>
                Leads per campaign:{' '}
                <span className="font-medium text-foreground">
                  {currentPlan.max_leads_per_campaign
                    ? formatNumber(currentPlan.max_leads_per_campaign)
                    : 'Unlimited'}
                </span>
              </span>
            </div>
            <Link to="/realtor/billing">
              <Button size="sm" variant="outline">
                View billing history
              </Button>
            </Link>
          </div>
        </Card>
      )}

      <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        {plans.map((plan) => {
          const isCurrent = currentPlan?.id === plan.id
          const isPending = pendingUpgrade?.requested_plan?.id === plan.id
          const canUpgrade = !isCurrent && (plan.sort_order ?? 0) > currentSort
          const isPaid = Number(plan.price) > 0

          return (
            <Card
              key={plan.id}
              className={cn(
                'relative flex flex-col overflow-hidden transition',
                isCurrent
                  ? 'ring-1 ring-primary/25 dark:ring-highlight/30'
                  : 'hover:border-border hover:shadow-card',
              )}
            >
              {isCurrent && (
                <span className="absolute inset-x-0 top-0 h-0.5 bg-primary dark:bg-highlight" />
              )}

              <div className="p-5">
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <span
                      className="h-2 w-2 rounded-full"
                      style={{ backgroundColor: planAccentColor(plan.header_color) }}
                    />
                    <h3 className="text-sm font-semibold text-foreground">{plan.name}</h3>
                  </div>
                  {isCurrent && (
                    <span className="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary dark:bg-highlight/10 dark:text-highlight">
                      Current
                    </span>
                  )}
                </div>

                <div className="mt-4 flex items-baseline gap-1">
                  <span className="text-[32px] font-semibold leading-none tracking-[-0.03em] text-foreground">
                    {isPaid ? formatCurrency(plan.price) : 'Free'}
                  </span>
                  {isPaid && (
                    <span className="text-xs font-medium text-muted-foreground">/month</span>
                  )}
                </div>
                <p className="mt-2 text-xs text-muted-foreground">
                  {isPaid ? 'Billed monthly' : 'No payment required'}
                </p>
              </div>

              <div className="flex flex-1 flex-col border-t border-border p-5">
                <ul className="space-y-2.5">
                  <PlanFeature>
                    {plan.max_campaigns
                      ? `${formatNumber(plan.max_campaigns)} campaigns`
                      : 'Unlimited campaigns'}
                  </PlanFeature>
                  <PlanFeature>
                    {plan.max_leads_per_campaign
                      ? `${formatNumber(plan.max_leads_per_campaign)} leads per campaign`
                      : 'Unlimited leads per campaign'}
                  </PlanFeature>
                  <PlanFeature>Full lead management & notes</PlanFeature>
                  <PlanFeature>Inventory & customer tracking</PlanFeature>
                  <PlanFeature>Meta leads integration</PlanFeature>
                </ul>

                <div className="mt-5 flex flex-1 flex-col justify-end gap-2">
                  {isCurrent ? (
                    <Button variant="outline" disabled className="w-full">
                      Current plan
                    </Button>
                  ) : isPending ? (
                    <Button variant="outline" disabled className="w-full">
                      Upgrade requested
                    </Button>
                  ) : canUpgrade ? (
                    <>
                      {isPaid && (
                        <Link to={`/checkout?plan=${plan.id}`} className="w-full">
                          <Button className="w-full">
                            <SparklesIcon className="h-4 w-4" />
                            Buy now
                          </Button>
                        </Link>
                      )}
                      <Button
                        variant={isPaid ? 'outline' : 'primary'}
                        className="w-full"
                        onClick={() => upgradeMutation.mutate(plan.id)}
                        loading={upgradeMutation.isPending && upgradeMutation.variables === plan.id}
                        disabled={Boolean(pendingUpgrade) || upgradeMutation.isPending}
                      >
                        Request upgrade
                      </Button>
                    </>
                  ) : (
                    <Button variant="outline" disabled className="w-full">
                      Included in your plan
                    </Button>
                  )}
                </div>
              </div>
            </Card>
          )
        })}
      </div>

      <p className="mt-5 text-xs text-muted-foreground">
        Upgrade requests are reviewed by the Rumu team. Buying a paid plan activates it immediately
        after successful payment.
      </p>
    </div>
  )
}
