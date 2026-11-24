import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownTrayIcon,
  ArrowTrendingUpIcon,
  CheckBadgeIcon,
  CreditCardIcon,
  DocumentTextIcon,
  LockClosedIcon,
  SparklesIcon,
  UserPlusIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { useEnterprise } from '@/hooks/useEnterprise'
import { cn } from '@/utils/cn'
import { daysLeft } from '@/utils/enterprise'
import {
  filenameFromResponse,
  formatCurrency,
  formatDate,
  formatNumber,
  saveBlob,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const SEAT_OPTIONS = [
  { value: 'managers', label: 'Manager seats' },
  { value: 'employees', label: 'Employee seats' },
]

const USAGE_ORDER = ['managers', 'employees', 'teams', 'monthly_leads', 'inventory_units', 'campaigns']

const usageTone = (percentage) => {
  if (percentage === null) return 'bg-primary'
  if (percentage >= 90) return 'bg-rose-500'
  if (percentage >= 70) return 'bg-primary'
  return 'bg-emerald-500'
}

const planLimits = (plan) => [
  ['Managers', plan.max_managers],
  ['Employees', plan.max_employees],
  ['Teams', plan.max_teams],
  ['Leads / month', plan.max_monthly_leads],
  ['Inventory units', plan.max_inventory_units],
  ['Campaigns', plan.max_campaigns],
]

const formatLimit = (value) =>
  value === null || value === undefined ? 'Unlimited' : formatNumber(value)

export const BillingPage = () => {
  const queryClient = useQueryClient()
  const { isAdmin } = useEnterprise()
  const [upgradeTarget, setUpgradeTarget] = useState(null)
  const [seatForm, setSeatForm] = useState({ seat_type: 'managers', quantity: 1, note: '' })
  const [seatFormError, setSeatFormError] = useState('')

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'billing'],
    queryFn: async () => {
      const response = await enterpriseApi.billing.get()
      return response.data?.data
    },
    enabled: isAdmin,
  })

  const downloadMutation = useMutation({
    mutationFn: async (invoice) => {
      const response = await enterpriseApi.billing.downloadInvoice(invoice.id)
      const filename = filenameFromResponse(response, `${invoice.invoice_number || 'invoice'}.pdf`)
      saveBlob(response.data, filename)
      return filename
    },
    onSuccess: (filename) => toast.success(`Invoice downloaded · ${filename}`),
    onError: (downloadError) => {
      toast.error(getErrorMessage(downloadError, 'Could not download this invoice.'))
    },
  })

  const seatRequestMutation = useMutation({
    mutationFn: () =>
      enterpriseApi.billing.requestSeat({
        seat_type: seatForm.seat_type,
        quantity: Number(seatForm.quantity),
        note: seatForm.note.trim() || null,
      }),
    onSuccess: () => {
      toast.success('Seat add-on request sent to the Rumu platform team')
      setSeatForm({ seat_type: 'managers', quantity: 1, note: '' })
      setSeatFormError('')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'billing'] })
    },
    onError: (seatError) => {
      setSeatFormError(getErrorMessage(seatError, 'Could not request additional seats.'))
    },
  })

  const upgradeMutation = useMutation({
    mutationFn: (planId) => enterpriseApi.billing.upgrade(planId),
    onSuccess: () => {
      toast.success('Upgrade request sent to the Rumu platform team')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'billing'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'settings'] })
      setUpgradeTarget(null)
    },
    onError: (upgradeError) => {
      toast.error(getErrorMessage(upgradeError, 'Could not request this plan upgrade.'))
      setUpgradeTarget(null)
    },
  })

  if (!isAdmin) {
    return (
      <div className="mx-auto max-w-xl">
        <PageHeader title="Billing" description="Enterprise billing is managed by your company administrator." />
        <Card>
          <CardContent className="flex flex-col items-center gap-3 py-10 text-center">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
              <LockClosedIcon className="h-6 w-6" />
            </span>
            <div>
              <p className="text-sm font-semibold text-foreground">Administrator access required</p>
              <p className="mt-1 text-xs text-muted-foreground">
                Only a company administrator can view invoices, plan usage and request seat add-ons.
                Contact your administrator if you need more seats.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    )
  }

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const plan = data?.plan
  const usage = data?.usage || {}
  const features = data?.features || {}
  const subscription = data?.subscription
  const invoices = data?.invoices || []
  const pendingUpgrade = data?.pending_upgrade
  const availablePlans = data?.available_plans || []
  const currentSort = plan?.sort_order ?? -1
  const trialRemaining = data?.is_on_trial ? daysLeft(data?.trial_ends_at) : null
  const seatRequests = data?.seat_requests || []
  const pendingSeatTypes = seatRequests
    .filter((request) => request.status === 'pending')
    .map((request) => request.seat_type)
  const seatUsage = [
    { key: 'managers', label: 'Manager seats', extra: data?.extra_managers || 0 },
    { key: 'employees', label: 'Employee seats', extra: data?.extra_employees || 0 },
  ]
  const seatRequestColumns = [
    {
      key: 'seat',
      header: 'Seats',
      render: (request) => (
        <div>
          <p className="text-sm font-medium text-foreground">
            +{request.quantity} {request.seat_label}
          </p>
          <p className="text-[11px] text-muted-foreground">
            Requested {formatDate(request.created_at)}
            {request.requested_by ? ` · ${request.requested_by}` : ''}
          </p>
        </div>
      ),
    },
    { key: 'status', header: 'Status', render: (request) => <StatusBadge status={request.status} /> },
    {
      key: 'note',
      header: 'Notes',
      render: (request) => (
        <span className="text-xs text-muted-foreground">
          {request.admin_notes || request.note || '—'}
        </span>
      ),
    },
  ]

  const invoiceColumns = [
    {
      key: 'invoice_number',
      header: 'Invoice',
      render: (invoice) => (
        <div className="flex items-center gap-2.5">
          <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-muted text-muted-foreground">
            <DocumentTextIcon className="h-4 w-4" />
          </span>
          <span className="text-sm font-medium text-foreground">{invoice.invoice_number}</span>
        </div>
      ),
    },
    {
      key: 'created_at',
      header: 'Date',
      render: (invoice) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(invoice.created_at)}
        </span>
      ),
    },
    {
      key: 'total',
      header: 'Amount',
      render: (invoice) => (
        <span className="text-sm font-semibold text-foreground">{formatCurrency(invoice.total)}</span>
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
      headerClassName: 'text-right',
      className: 'text-right',
      stopPropagation: true,
      render: (invoice) => (
        <Button
          variant="ghost"
          size="sm"
          loading={downloadMutation.isPending && downloadMutation.variables?.id === invoice.id}
          onClick={() => downloadMutation.mutate(invoice)}
        >
          <ArrowDownTrayIcon className="h-4 w-4" />
          PDF
        </Button>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Billing"
        description="Your enterprise plan, usage against limits and platform invoices."
      />

      {data?.is_on_trial && (
        <Alert variant="info" title="Enterprise trial" className="mb-5">
          {trialRemaining === 0
            ? 'Your trial ends today.'
            : `${trialRemaining} day${trialRemaining === 1 ? '' : 's'} left in your trial`}
          {data?.trial_ends_at ? ` (ends ${formatDate(data.trial_ends_at)})` : ''}.
          {isAdmin ? ' Request a plan below to continue after the trial.' : ' Ask your administrator about upgrading.'}
        </Alert>
      )}

      {pendingUpgrade && (
        <Alert variant="warning" title="Upgrade request pending" className="mb-5">
          Your request for <span className="font-semibold">{pendingUpgrade.requested_plan?.name}</span> was
          sent on {formatDate(pendingUpgrade.created_at)}. The Rumu platform team will contact the
          company administrator. No self-serve payment is taken for enterprise plans.
        </Alert>
      )}

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle>Current plan</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">
                Enterprise plans are activated by the Rumu platform team.
              </p>
            </div>
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-highlight">
              <CreditCardIcon className="h-5 w-5" />
            </span>
          </CardHeader>
          <CardContent>
            {plan ? (
              <>
                <div className="flex flex-wrap items-end justify-between gap-4">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="text-2xl font-semibold text-foreground">{plan.name}</p>
                      {data?.is_on_trial && <Badge variant="info">Trial</Badge>}
                      {subscription && <StatusBadge status={subscription.status} />}
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {Number(plan.price) > 0
                        ? `${formatCurrency(plan.price)} per month · billed by the Rumu team`
                        : 'Free plan'}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <Badge variant={features.advanced_reports ? 'success' : 'neutral'}>
                      {features.advanced_reports ? 'Advanced reports' : 'Core reports'}
                    </Badge>
                    <Badge variant={features.meta_integrations ? 'success' : 'neutral'}>
                      {features.meta_integrations ? 'Meta integrations' : 'No Meta integrations'}
                    </Badge>
                  </div>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                  {planLimits(plan).map(([label, value]) => (
                    <div key={label} className="rounded-xl border border-border bg-muted/20 px-3 py-2.5">
                      <p className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</p>
                      <p className="mt-0.5 text-sm font-semibold text-foreground">{formatLimit(value)}</p>
                    </div>
                  ))}
                </div>
              </>
            ) : (
              <EmptyState
                icon={CreditCardIcon}
                title="No plan assigned"
                description="The Rumu platform team will assign an enterprise plan to this workspace."
              />
            )}

            {subscription && (
              <div className="mt-5 grid gap-4 border-t border-border pt-4 sm:grid-cols-2">
                <Info label="Subscription" value={`${subscription.plan_name || plan?.name || 'Enterprise'} · ${subscription.status}`} />
                <Info label="Price" value={formatCurrency(subscription.price)} />
                <Info label="Started" value={formatDate(subscription.start_date)} />
                <Info label="Renews / expires" value={formatDate(subscription.expiry_date)} />
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Usage this month</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {USAGE_ORDER.map((key) => {
              const item = usage[key]
              if (!item) return null
              return (
                <div key={key}>
                  <div className="flex items-center justify-between gap-2 text-xs">
                    <span className="font-medium text-foreground">{item.label}</span>
                    <span className="text-muted-foreground">
                      {formatNumber(item.used)} / {formatLimit(item.limit)}
                    </span>
                  </div>
                  {item.percentage === null ? (
                    <p className="mt-1 text-[11px] text-muted-foreground">Unlimited on your plan.</p>
                  ) : (
                    <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                      <div
                        className={cn('h-full rounded-full transition-all', usageTone(item.percentage))}
                        style={{ width: `${Math.min(item.percentage, 100)}%` }}
                      />
                    </div>
                  )}
                  {item.percentage !== null && item.percentage >= 90 && (
                    <p className="mt-1 text-[11px] text-rose-600 dark:text-rose-400">
                      Close to the plan limit. Upgrade to add more.
                    </p>
                  )}
                </div>
              )
            })}
            {Object.keys(usage).length === 0 && (
              <p className="text-sm text-muted-foreground">Usage data is not available yet.</p>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex items-center justify-between gap-3">
            <div>
              <CardTitle>Seat capacity</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">
                Request additional seats when your team grows. Add-ons are approved by the Rumu
                platform team.
              </p>
            </div>
            <UserPlusIcon className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2">
              {seatUsage.map((seat) => {
                const item = usage[seat.key]
                const limit = item?.limit
                const used = item?.used || 0
                return (
                  <div key={seat.key} className="rounded-xl border border-border bg-muted/20 p-3">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-medium text-foreground">{seat.label}</span>
                      <span className="text-muted-foreground">
                        {formatNumber(used)} / {formatLimit(limit)}
                      </span>
                    </div>
                    <p className="mt-1 text-[11px] text-muted-foreground">
                      {seat.extra > 0 ? `Includes ${seat.extra} add-on seat${seat.extra === 1 ? '' : 's'}.` : 'No add-on seats on this plan.'}
                    </p>
                    {limit !== null && limit !== undefined && (
                      <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                          className={cn('h-full rounded-full', used >= limit ? 'bg-rose-500' : 'bg-primary')}
                          style={{ width: `${Math.min(limit === 0 ? 100 : (used / limit) * 100, 100)}%` }}
                        />
                      </div>
                    )}
                  </div>
                )
              })}
            </div>

            <form
              className="space-y-3 rounded-xl border border-border p-3"
              onSubmit={(event) => {
                event.preventDefault()
                setSeatFormError('')
                seatRequestMutation.mutate()
              }}
            >
              {seatFormError && (
                <Alert variant="danger" onDismiss={() => setSeatFormError('')}>
                  {seatFormError}
                </Alert>
              )}
              <div className="grid gap-3 sm:grid-cols-[1fr_100px]">
                <FormField label="Seat type" htmlFor="seat-type">
                  <Select
                    id="seat-type"
                    value={seatForm.seat_type}
                    onChange={(event) => setSeatForm({ ...seatForm, seat_type: event.target.value })}
                  >
                    {SEAT_OPTIONS.map((option) => (
                      <option
                        key={option.value}
                        value={option.value}
                        disabled={pendingSeatTypes.includes(option.value)}
                      >
                        {option.label}
                        {pendingSeatTypes.includes(option.value) ? ' (request pending)' : ''}
                      </option>
                    ))}
                  </Select>
                </FormField>
                <FormField label="Quantity" htmlFor="seat-quantity">
                  <Input
                    id="seat-quantity"
                    type="number"
                    min={1}
                    max={50}
                    value={seatForm.quantity}
                    onChange={(event) => setSeatForm({ ...seatForm, quantity: event.target.value })}
                  />
                </FormField>
              </div>
              <FormField label="Note for the Rumu team" htmlFor="seat-note">
                <Textarea
                  id="seat-note"
                  rows={2}
                  value={seatForm.note}
                  placeholder="Optional context, e.g. two new sales hires starting next month."
                  onChange={(event) => setSeatForm({ ...seatForm, note: event.target.value })}
                />
              </FormField>
              <div className="flex items-center justify-between gap-3">
                <p className="text-[11px] text-muted-foreground">
                  No online payment is taken for seat add-ons.
                </p>
                <Button
                  type="submit"
                  size="sm"
                  loading={seatRequestMutation.isPending}
                  disabled={pendingSeatTypes.includes(seatForm.seat_type)}
                >
                  Request seats
                </Button>
              </div>
            </form>

            <DataTable
              className="rounded-xl border border-border"
              columns={seatRequestColumns}
              rows={seatRequests}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={UserPlusIcon}
                  title="No seat requests"
                  description="Seat add-on requests and their approval status will appear here."
                />
              }
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Plan features</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex items-center justify-between rounded-xl border border-border px-3 py-2.5">
              <div>
                <p className="text-sm font-medium text-foreground">Advanced reports</p>
                <p className="text-[11px] text-muted-foreground">CSV exports for managers and admins.</p>
              </div>
              <Badge variant={features.advanced_reports ? 'success' : 'neutral'}>
                {features.advanced_reports ? 'Included' : 'Locked'}
              </Badge>
            </div>
            <div className="flex items-center justify-between rounded-xl border border-border px-3 py-2.5">
              <div>
                <p className="text-sm font-medium text-foreground">Meta integrations</p>
                <p className="text-[11px] text-muted-foreground">Lead ads, form mapping and CAPI.</p>
              </div>
              <Badge variant={features.meta_integrations ? 'success' : 'neutral'}>
                {features.meta_integrations ? 'Included' : 'Locked'}
              </Badge>
            </div>
            <p className="text-[11px] text-muted-foreground">
              Feature access follows your current plan and is enforced by the platform.
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Available enterprise plans</CardTitle>
          </CardHeader>
          <CardContent>
            {availablePlans.length === 0 ? (
              <EmptyState
                icon={SparklesIcon}
                title="No plans available"
                description="Contact the Rumu platform team to discuss enterprise pricing."
              />
            ) : (
              <div className="grid gap-4 md:grid-cols-2">
                {availablePlans.map((item) => {
                  const isCurrent = item.id === plan?.id
                  const isLower = item.sort_order <= currentSort && !isCurrent
                  const disabled = isCurrent || isLower || Boolean(pendingUpgrade) || !isAdmin
                  return (
                    <div
                      key={item.id}
                      className={cn(
                        'rounded-xl border p-4 transition',
                        isCurrent ? 'border-highlight/40 bg-primary/[0.04]' : 'border-border bg-card',
                      )}
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <div className="flex flex-wrap items-center gap-2">
                            <p className="text-sm font-semibold text-foreground">{item.name}</p>
                            {isCurrent && <Badge variant="primary">Current</Badge>}
                            {item.is_default && !isCurrent && <Badge variant="info">Recommended</Badge>}
                          </div>
                          <p className="mt-1 text-lg font-semibold text-foreground">
                            {Number(item.price) > 0 ? formatCurrency(item.price) : 'Free'}
                            {Number(item.price) > 0 && (
                              <span className="text-xs font-normal text-muted-foreground"> / month</span>
                            )}
                          </p>
                        </div>
                        <CheckBadgeIcon className="h-5 w-5 shrink-0 text-muted-foreground" />
                      </div>

                      <ul className="mt-3 space-y-1.5 text-xs text-muted-foreground">
                        <li>{formatLimit(item.max_managers)} managers</li>
                        <li>{formatLimit(item.max_employees)} employees</li>
                        <li>{formatLimit(item.max_teams)} teams</li>
                        <li>{formatLimit(item.max_monthly_leads)} leads / month</li>
                        <li>{formatLimit(item.max_inventory_units)} inventory units</li>
                        <li>{formatLimit(item.max_campaigns)} campaigns</li>
                      </ul>

                      <div className="mt-3 flex flex-wrap gap-1.5">
                        {item.advanced_reports && <Badge variant="outline">Advanced reports</Badge>}
                        {item.meta_integrations && <Badge variant="outline">Meta integrations</Badge>}
                      </div>

                      <Button
                        className="mt-4 w-full"
                        variant={isCurrent ? 'outline' : 'primary'}
                        disabled={disabled}
                        onClick={() => setUpgradeTarget(item)}
                      >
                        {isCurrent
                          ? 'Current plan'
                          : isLower
                            ? 'Higher plan required'
                            : pendingUpgrade
                              ? 'Request pending'
                              : !isAdmin
                                ? 'Admin approval required'
                                : 'Request upgrade'}
                      </Button>
                    </div>
                  )
                })}
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex items-center justify-between gap-3">
            <CardTitle>Invoices</CardTitle>
            <ArrowTrendingUpIcon className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={invoiceColumns}
              rows={invoices}
              emptyState={
                <EmptyState
                  icon={DocumentTextIcon}
                  title="No invoices"
                  description="Invoices issued by the Rumu platform team will appear here."
                />
              }
            />
            <p className="border-t border-border px-5 py-3 text-[11px] text-muted-foreground">
              Enterprise payments are handled by the Rumu platform team. Invoice downloads are emailed
              after payment confirmation.
            </p>
          </CardContent>
        </Card>
      </div>

      <ConfirmDialog
        open={Boolean(upgradeTarget)}
        onClose={() => setUpgradeTarget(null)}
        onConfirm={() => upgradeMutation.mutate(upgradeTarget.id)}
        loading={upgradeMutation.isPending}
        title={`Request the ${upgradeTarget?.name || ''} plan?`}
        description="This sends an upgrade request to the Rumu platform team. Your plan does not change until they confirm it, and no payment is taken here."
        confirmLabel="Send upgrade request"
      />
    </div>
  )
}

const Info = ({ label, value }) => (
  <div>
    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">{label}</p>
    <p className="mt-0.5 text-sm text-foreground">{value}</p>
  </div>
)
