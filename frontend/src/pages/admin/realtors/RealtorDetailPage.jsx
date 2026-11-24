import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { addMonths, format } from 'date-fns'
import {
  ArrowLeftIcon,
  BriefcaseIcon,
  CreditCardIcon,
  MegaphoneIcon,
  PencilSquareIcon,
  PlusIcon,
  ShieldExclamationIcon,
  TrashIcon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import { DataTable } from '@/components/shared/DataTable'
import { DetailItem } from '@/components/shared/DetailItem'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { SUBSCRIPTION_STATUSES } from '@/utils/constants'
import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
  formatPhone,
} from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'
import { RealtorFormModal } from './RealtorFormModal'

const today = () => format(new Date(), 'yyyy-MM-dd')

const AssignPlanModal = ({ realtorId, plans, onClose, onSaved }) => {
  const [form, setForm] = useState(() => ({
    plan_id: plans[0]?.id || '',
    duration_months: 1,
    start_date: today(),
    expiry_date: '',
    price: plans[0]?.price ?? 0,
    gst: 0,
    discount: 0,
    currency: 'INR',
    payment_method: 'manual',
    payment_status: 'completed',
    subscription_status: 'active',
    transaction_id: '',
    internal_notes: '',
  }))
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const selectPlan = (planId) => {
    const plan = plans.find((item) => String(item.id) === String(planId))
    setForm((current) => ({
      ...current,
      plan_id: planId,
      price: plan ? Number(plan.price) * Number(current.duration_months || 1) : current.price,
    }))
  }

  const setDuration = (value) => {
    const plan = plans.find((item) => String(item.id) === String(form.plan_id))
    setForm((current) => ({
      ...current,
      duration_months: value,
      price: plan ? Number(plan.price) * Number(value || 1) : current.price,
    }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    const nextErrors = {}
    const plan = plans.find((item) => String(item.id) === String(form.plan_id))
    if (!plan) nextErrors.plan_id = 'Choose a plan.'
    if (!form.duration_months || Number(form.duration_months) < 0) {
      nextErrors.duration_months = 'Enter a valid duration.'
    }
    if (!form.start_date) nextErrors.start_date = 'Start date is required.'
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    setSubmitting(true)
    try {
      await adminApi.realtors.assignPlan(realtorId, {
        plan_name: plan.name,
        duration_months: Number(form.duration_months),
        start_date: form.start_date,
        expiry_date: form.expiry_date || null,
        price: Number(form.price) || 0,
        gst: Number(form.gst) || 0,
        discount: Number(form.discount) || 0,
        currency: form.currency || 'INR',
        payment_method: form.payment_method,
        payment_status: form.payment_status,
        transaction_id: form.transaction_id.trim() || null,
        subscription_status: form.subscription_status,
        internal_notes: form.internal_notes.trim() || null,
      })
      toast.success('Plan assigned and invoice generated')
      onSaved()
    } catch (error) {
      setErrors(serverFieldErrors(error))
      toast.error(getErrorMessage(error, 'Could not assign the plan.'))
    } finally {
      setSubmitting(false)
    }
  }

  const handleStartChange = (value) => {
    set('start_date', value)
    if (value && form.duration_months && !form.expiry_date) {
      set('expiry_date', format(addMonths(new Date(value), Number(form.duration_months)), 'yyyy-MM-dd'))
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="lg"
      title="Assign plan"
      description="Create a subscription, payment and invoice for this realtor."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button type="submit" form="assign-plan-form" loading={submitting}>
            Assign plan
          </Button>
        </>
      }
    >
      <form id="assign-plan-form" onSubmit={handleSubmit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Plan" htmlFor="assign-plan" required error={errors.plan_id}>
            <Select id="assign-plan" value={form.plan_id} onChange={(event) => selectPlan(event.target.value)}>
              {plans.map((plan) => (
                <option key={plan.id} value={plan.id}>
                  {plan.name} · {formatCurrency(plan.price)}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Duration (months)" htmlFor="assign-duration" required error={errors.duration_months}>
            <Input
              id="assign-duration"
              type="number"
              min="0"
              value={form.duration_months}
              onChange={(event) => setDuration(event.target.value)}
              invalid={Boolean(errors.duration_months)}
            />
          </FormField>
          <FormField label="Start date" htmlFor="assign-start" required error={errors.start_date}>
            <Input
              id="assign-start"
              type="date"
              value={form.start_date}
              onChange={(event) => handleStartChange(event.target.value)}
              invalid={Boolean(errors.start_date)}
            />
          </FormField>
          <FormField label="Expiry date" htmlFor="assign-expiry" error={errors.expiry_date}>
            <Input
              id="assign-expiry"
              type="date"
              value={form.expiry_date}
              onChange={(event) => set('expiry_date', event.target.value)}
              invalid={Boolean(errors.expiry_date)}
            />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <FormField label="Price (₹)" htmlFor="assign-price" required error={errors.price}>
            <Input
              id="assign-price"
              type="number"
              min="0"
              step="0.01"
              value={form.price}
              onChange={(event) => set('price', event.target.value)}
              invalid={Boolean(errors.price)}
            />
          </FormField>
          <FormField label="GST (₹)" htmlFor="assign-gst" error={errors.gst}>
            <Input
              id="assign-gst"
              type="number"
              min="0"
              step="0.01"
              value={form.gst}
              onChange={(event) => set('gst', event.target.value)}
              invalid={Boolean(errors.gst)}
            />
          </FormField>
          <FormField label="Discount (₹)" htmlFor="assign-discount" error={errors.discount}>
            <Input
              id="assign-discount"
              type="number"
              min="0"
              step="0.01"
              value={form.discount}
              onChange={(event) => set('discount', event.target.value)}
              invalid={Boolean(errors.discount)}
            />
          </FormField>
        </div>

        <div className="rounded-lg border border-border bg-muted/30 px-3 py-2.5 text-sm">
          <span className="text-muted-foreground">Invoice total: </span>
          <span className="font-semibold text-foreground">
            {formatCurrency(
              (Number(form.price) || 0) + (Number(form.gst) || 0) - (Number(form.discount) || 0),
            )}
          </span>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Subscription status" htmlFor="assign-status" error={errors.subscription_status}>
            <Select
              id="assign-status"
              value={form.subscription_status}
              onChange={(event) => set('subscription_status', event.target.value)}
            >
              {SUBSCRIPTION_STATUSES.map((status) => (
                <option key={status.value} value={status.value}>
                  {status.label}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Payment status" htmlFor="assign-payment-status" error={errors.payment_status}>
            <Select
              id="assign-payment-status"
              value={form.payment_status}
              onChange={(event) => set('payment_status', event.target.value)}
            >
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </Select>
          </FormField>
          <FormField label="Payment method" htmlFor="assign-method" error={errors.payment_method}>
            <Select
              id="assign-method"
              value={form.payment_method}
              onChange={(event) => set('payment_method', event.target.value)}
            >
              <option value="manual">Manual</option>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank transfer</option>
              <option value="cheque">Cheque</option>
              <option value="online">Online</option>
            </Select>
          </FormField>
          <FormField label="Transaction ID" htmlFor="assign-transaction" error={errors.transaction_id}>
            <Input
              id="assign-transaction"
              value={form.transaction_id}
              onChange={(event) => set('transaction_id', event.target.value)}
              invalid={Boolean(errors.transaction_id)}
              placeholder="Optional reference"
            />
          </FormField>
        </div>

        <FormField label="Internal notes" htmlFor="assign-notes" error={errors.internal_notes}>
          <Textarea
            id="assign-notes"
            rows={3}
            value={form.internal_notes}
            onChange={(event) => set('internal_notes', event.target.value)}
            placeholder="Visible to admins only."
          />
        </FormField>
      </form>
    </Modal>
  )
}

export const RealtorDetailPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [tab, setTab] = useState('overview')
  const [editOpen, setEditOpen] = useState(false)
  const [assignOpen, setAssignOpen] = useState(false)
  const [suspendOpen, setSuspendOpen] = useState(false)
  const [suspendReason, setSuspendReason] = useState('')
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [billingPage, setBillingPage] = useState({ subscriptions: 1, payments: 1, invoices: 1 })

  const { data: realtor, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'realtors', id],
    queryFn: async () => {
      const response = await adminApi.realtors.get(id)
      return response.data?.data
    },
  })

  const { data: plans = [] } = useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: async () => {
      const response = await adminApi.plans.list()
      return response.data?.data || []
    },
  })

  const {
    data: billing,
    isLoading: billingLoading,
    isError: billingError,
    error: billingQueryError,
    refetch: refetchBilling,
  } = useQuery({
    queryKey: ['admin', 'realtors', id, 'billing'],
    queryFn: async () => {
      const response = await adminApi.realtors.billing(id)
      return response.data?.data
    },
    enabled: tab === 'billing',
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin', 'realtors'] })
    queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['admin', 'analytics'] })
  }

  const suspendMutation = useMutation({
    mutationFn: () => adminApi.realtors.suspend(id, { reason: suspendReason.trim() || undefined }),
    onSuccess: () => {
      toast.success('Subscription suspended')
      setSuspendOpen(false)
      setSuspendReason('')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not suspend the subscription.')),
  })

  const resumeMutation = useMutation({
    mutationFn: () => adminApi.realtors.resume(id),
    onSuccess: () => {
      toast.success('Subscription resumed')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not resume the subscription.')),
  })

  const deleteMutation = useMutation({
    mutationFn: () => adminApi.realtors.remove(id),
    onSuccess: () => {
      toast.success('Realtor deleted')
      invalidate()
      navigate('/admin/realtors', { replace: true })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the realtor.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const campaigns = realtor?.campaigns || []
  const subscriptions = billing?.subscriptions || []
  const payments = billing?.payments || []
  const invoices = billing?.invoices || []

  const paged = (rows, pageKey) => {
    const perPage = 10
    const page = billingPage[pageKey]
    return {
      rows: rows.slice((page - 1) * perPage, page * perPage),
      page,
      lastPage: Math.max(1, Math.ceil(rows.length / perPage)),
      total: rows.length,
    }
  }

  const subscriptionPager = paged(subscriptions, 'subscriptions')
  const paymentPager = paged(payments, 'payments')
  const invoicePager = paged(invoices, 'invoices')

  const subscriptionColumns = [
    { key: 'plan_name', header: 'Plan', render: (row) => <span className="text-sm font-semibold">{row.plan_name}</span> },
    { key: 'duration_months', header: 'Duration', render: (row) => `${row.duration_months} mo` },
    { key: 'start_date', header: 'Start', render: (row) => formatDate(row.start_date) },
    { key: 'expiry_date', header: 'Expires', render: (row) => formatDate(row.expiry_date) },
    { key: 'total', header: 'Total', render: (row) => formatCurrency(row.total) },
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
  ]

  const paymentColumns = [
    {
      key: 'transaction_id',
      header: 'Reference',
      render: (row) => <span className="text-xs font-medium">{row.transaction_id || row.gateway_payment_id || `#${row.id}`}</span>,
    },
    { key: 'payment_method', header: 'Method', render: (row) => <span className="text-sm capitalize">{row.payment_method?.replace(/_/g, ' ')}</span> },
    { key: 'amount', header: 'Amount', render: (row) => formatCurrency(row.amount) },
    { key: 'payment_date', header: 'Date', render: (row) => formatDate(row.payment_date || row.created_at) },
    { key: 'payment_status', header: 'Status', render: (row) => <StatusBadge status={row.payment_status} /> },
  ]

  const invoiceColumns = [
    {
      key: 'invoice_number',
      header: 'Invoice',
      render: (row) => (
        <Link to={`/admin/billing/invoices/${row.id}`} className="text-sm font-semibold text-highlight hover:underline">
          {row.invoice_number}
        </Link>
      ),
    },
    { key: 'created_at', header: 'Date', render: (row) => formatDate(row.created_at) },
    { key: 'total', header: 'Total', render: (row) => formatCurrency(row.total) },
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
  ]

  const campaignColumns = [
    { key: 'name', header: 'Campaign', render: (row) => <span className="text-sm font-semibold">{row.name}</span> },
    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    { key: 'leads_count', header: 'Leads', render: (row) => formatNumber(row.leads_count || 0) },
    { key: 'created_at', header: 'Created', render: (row) => formatDate(row.created_at) },
  ]

  return (
    <div>
      <PageHeader
        title={realtor?.name}
        description={realtor?.company || realtor?.email}
        breadcrumbs={[{ label: 'Realtors', to: '/admin/realtors' }, { label: realtor?.name || 'Detail' }]}
        actions={
          <>
            <Link to="/admin/realtors">
              <Button variant="outline" size="sm">
                <ArrowLeftIcon className="h-4 w-4" />
                Back
              </Button>
            </Link>
            <Button size="sm" variant="outline" onClick={() => setEditOpen(true)}>
              <PencilSquareIcon className="h-4 w-4" />
              Edit
            </Button>
            {realtor?.subscription_suspended ? (
              <Button
                size="sm"
                onClick={() => resumeMutation.mutate()}
                loading={resumeMutation.isPending}
              >
                Resume access
              </Button>
            ) : (
              <Button size="sm" variant="danger-outline" onClick={() => setSuspendOpen(true)}>
                <ShieldExclamationIcon className="h-4 w-4" />
                Suspend
              </Button>
            )}
          </>
        }
      />

      {realtor?.subscription_suspended && (
        <div className="mb-5 rounded-xl border border-destructive/20 bg-destructive/5 p-4">
          <p className="text-sm font-semibold text-destructive">Subscription suspended</p>
          <p className="mt-0.5 text-sm text-muted-foreground">
            Since {formatDateTime(realtor.subscription_suspended_at)}
            {realtor.subscription_suspend_reason ? ` · ${realtor.subscription_suspend_reason}` : ''}
          </p>
        </div>
      )}

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Campaigns"
          value={formatNumber(realtor?.campaigns_count || campaigns.length)}
          icon={MegaphoneIcon}
          hint="Total workspaces"
          tone="info"
        />
        <StatsCard
          label="Current plan"
          value={realtor?.plan?.name || 'None'}
          icon={BriefcaseIcon}
          hint={realtor?.plan ? `Max ${realtor.plan.max_campaigns ?? '∞'} campaigns` : 'No plan assigned'}
        />
        <StatsCard
          label="Subscriptions"
          value={formatNumber(realtor?.subscriptions?.length || 0)}
          icon={CreditCardIcon}
          hint="Payment history available in billing"
          tone="purple"
        />
        <StatsCard
          label="Member since"
          value={formatDate(realtor?.created_at)}
          icon={BriefcaseIcon}
          hint={formatPhone(realtor?.phone)}
          tone="success"
          valueClassName="text-[22px]"
        />
      </div>

      <Tabs
        className="mt-6"
        value={tab}
        onChange={setTab}
        tabs={[
          { key: 'overview', label: 'Overview' },
          { key: 'campaigns', label: 'Campaigns', count: campaigns.length },
          { key: 'billing', label: 'Billing' },
        ]}
      />

      {tab === 'overview' && (
        <div className="mt-5 grid gap-5 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Account details</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap items-start gap-4">
                <Avatar src={realtor?.avatar_url} name={realtor?.name} size="lg" />
                <div className="min-w-0 flex-1">
                  <p className="text-base font-semibold text-foreground">{realtor?.name}</p>
                  <p className="text-sm text-muted-foreground">{realtor?.email}</p>
                  <div className="mt-2 flex flex-wrap items-center gap-1.5">
                    <Badge variant="info">Realtor</Badge>
                    {realtor?.plan && <Badge variant="primary">{realtor.plan.name}</Badge>}
                    {realtor?.subscription_suspended ? (
                      <StatusBadge status="suspended" />
                    ) : (
                      <StatusBadge status="active" />
                    )}
                  </div>
                </div>
              </div>

              <dl className="mt-6 grid gap-5 sm:grid-cols-2">
                <DetailItem label="Email">{realtor?.email}</DetailItem>
                <DetailItem label="Phone">{formatPhone(realtor?.phone)}</DetailItem>
                <DetailItem label="Company">{realtor?.company || '—'}</DetailItem>
                <DetailItem label="Joined">{formatDate(realtor?.created_at)}</DetailItem>
                <DetailItem label="Suspended plan">{realtor?.suspended_plan?.name || '—'}</DetailItem>
                <DetailItem label="Suspended at">
                  {realtor?.subscription_suspended_at ? formatDateTime(realtor.subscription_suspended_at) : '—'}
                </DetailItem>
              </dl>

              {realtor?.subscription_suspend_reason && (
                <div className="mt-5 rounded-lg border border-border bg-muted/30 p-3">
                  <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                    Suspension reason
                  </p>
                  <p className="mt-1 text-sm text-foreground">{realtor.subscription_suspend_reason}</p>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Danger zone</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-sm text-muted-foreground">
                Suspending blocks campaign, lead and inventory access. Deleting removes the account and its data
                permanently.
              </p>
              <div className="flex flex-col gap-2">
                {realtor?.subscription_suspended ? (
                  <Button
                    variant="outline"
                    onClick={() => resumeMutation.mutate()}
                    loading={resumeMutation.isPending}
                  >
                    Resume subscription
                  </Button>
                ) : (
                  <Button variant="outline" onClick={() => setSuspendOpen(true)}>
                    Suspend subscription
                  </Button>
                )}
                <Button variant="danger-outline" onClick={() => setDeleteOpen(true)}>
                  <TrashIcon className="h-4 w-4" />
                  Delete realtor
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'campaigns' && (
        <Card className="mt-5">
          <DataTable
            columns={campaignColumns}
            rows={campaigns}
            emptyState={
              <EmptyState
                icon={MegaphoneIcon}
                title="No campaigns"
                description="This realtor has not created any campaigns yet."
              />
            }
          />
        </Card>
      )}

      {tab === 'billing' && (
        <div className="mt-5 space-y-5">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
              {formatNumber(subscriptions.length)} subscriptions · {formatNumber(payments.length)} payments ·{' '}
              {formatNumber(invoices.length)} invoices
            </p>
            <Button size="sm" onClick={() => setAssignOpen(true)} disabled={!plans.length}>
              <PlusIcon className="h-4 w-4" />
              Assign plan
            </Button>
          </div>

          {billingLoading ? (
            <PageLoader />
          ) : billingError ? (
            <ErrorState error={billingQueryError} onRetry={refetchBilling} />
          ) : (
            <>
              <Card>
                <CardHeader>
                  <CardTitle>Subscriptions</CardTitle>
                </CardHeader>
                <DataTable columns={subscriptionColumns} rows={subscriptionPager.rows} />
                <Pagination
                  page={subscriptionPager.page}
                  lastPage={subscriptionPager.lastPage}
                  total={subscriptionPager.total}
                  perPage={10}
                  onPageChange={(page) => setBillingPage((current) => ({ ...current, subscriptions: page }))}
                />
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Payments</CardTitle>
                </CardHeader>
                <DataTable columns={paymentColumns} rows={paymentPager.rows} />
                <Pagination
                  page={paymentPager.page}
                  lastPage={paymentPager.lastPage}
                  total={paymentPager.total}
                  perPage={10}
                  onPageChange={(page) => setBillingPage((current) => ({ ...current, payments: page }))}
                />
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Invoices</CardTitle>
                </CardHeader>
                <DataTable columns={invoiceColumns} rows={invoicePager.rows} />
                <Pagination
                  page={invoicePager.page}
                  lastPage={invoicePager.lastPage}
                  total={invoicePager.total}
                  perPage={10}
                  onPageChange={(page) => setBillingPage((current) => ({ ...current, invoices: page }))}
                />
              </Card>
            </>
          )}
        </div>
      )}

      {editOpen && (
        <RealtorFormModal
          open={editOpen}
          realtor={realtor}
          plans={plans}
          onClose={() => setEditOpen(false)}
          onSaved={() => {
            setEditOpen(false)
            refetch()
          }}
        />
      )}

      {assignOpen && (
        <AssignPlanModal
          realtorId={id}
          plans={plans}
          onClose={() => setAssignOpen(false)}
          onSaved={() => {
            setAssignOpen(false)
            invalidate()
            queryClient.invalidateQueries({ queryKey: ['admin', 'realtors', id] })
            queryClient.invalidateQueries({ queryKey: ['admin', 'realtors', id, 'billing'] })
          }}
        />
      )}

      <Modal
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        title="Suspend subscription"
        description={`${realtor?.name} will lose access to campaigns, leads and inventory.`}
        footer={
          <>
            <Button variant="outline" onClick={() => setSuspendOpen(false)} disabled={suspendMutation.isPending}>
              Cancel
            </Button>
            <Button
              variant="danger"
              onClick={() => suspendMutation.mutate()}
              loading={suspendMutation.isPending}
            >
              Suspend access
            </Button>
          </>
        }
      >
        <FormField label="Reason (optional)" htmlFor="suspend-reason">
          <Textarea
            id="suspend-reason"
            rows={3}
            value={suspendReason}
            onChange={(event) => setSuspendReason(event.target.value)}
            placeholder="Shown to the realtor in their dashboard."
          />
        </FormField>
      </Modal>

      <ConfirmDialog
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete ${realtor?.name}?`}
        description="This permanently removes the realtor, their tokens and associated data. This cannot be undone."
        confirmLabel="Delete realtor"
      />
    </div>
  )
}
