import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownIcon,
  ArrowUpIcon,
  CheckBadgeIcon,
  PencilSquareIcon,
  PlusIcon,
  PowerIcon,
  Squares2X2Icon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { PageLoader } from '@/components/ui/Spinner'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'
import { planHeaderStyle } from '@/utils/planColors'

const AUDIENCES = [
  { value: 'realtor', label: 'Realtor edition' },
  { value: 'enterprise', label: 'Enterprise edition' },
  { value: 'both', label: 'Both editions' },
]

const emptyForm = {
  name: '',
  key: '',
  audience: 'realtor',
  price: '',
  max_campaigns: '',
  max_leads_per_campaign: '',
  max_managers: '',
  max_employees: '',
  max_teams: '',
  max_monthly_leads: '',
  max_inventory_units: '',
  advanced_reports: false,
  meta_integrations: false,
  header_color: '#650052',
  is_active: true,
  is_default: false,
}

const toFormState = (plan) => ({
  name: plan?.name || '',
  key: plan?.key || '',
  audience: plan?.audience || 'realtor',
  price: plan?.price ?? '',
  max_campaigns: plan?.max_campaigns ?? '',
  max_leads_per_campaign: plan?.max_leads_per_campaign ?? '',
  max_managers: plan?.max_managers ?? '',
  max_employees: plan?.max_employees ?? '',
  max_teams: plan?.max_teams ?? '',
  max_monthly_leads: plan?.max_monthly_leads ?? '',
  max_inventory_units: plan?.max_inventory_units ?? '',
  advanced_reports: Boolean(plan?.advanced_reports),
  meta_integrations: Boolean(plan?.meta_integrations),
  header_color: plan?.header_color || '#650052',
  is_active: plan ? Boolean(plan.is_active) : true,
  is_default: plan ? Boolean(plan.is_default) : false,
})

const numericOrNull = (value) => (value === '' ? null : Number(value))

const QuotaField = ({ id, label, value, onChange, error, hint }) => (
  <FormField label={label} htmlFor={id} error={error} hint={hint || 'Leave blank for unlimited.'}>
    <Input
      id={id}
      type="number"
      min="0"
      value={value}
      onChange={(event) => onChange(event.target.value)}
      invalid={Boolean(error)}
    />
  </FormField>
)

const PlanModal = ({ open, plan, onClose, onSaved }) => {
  const isEdit = Boolean(plan)
  const [form, setForm] = useState(() => (plan ? toFormState(plan) : emptyForm))
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))
  const isEnterprise = form.audience === 'enterprise' || form.audience === 'both'

  const handleSubmit = async (event) => {
    event.preventDefault()
    const nextErrors = {}
    if (!form.name.trim()) nextErrors.name = 'Plan name is required.'
    if (form.price !== '' && Number(form.price) < 0) nextErrors.price = 'Price cannot be negative.'
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    setSubmitting(true)
    try {
      const payload = {
        name: form.name.trim(),
        key: form.key.trim() || null,
        audience: form.audience,
        price: numericOrNull(form.price),
        max_campaigns: numericOrNull(form.max_campaigns),
        max_leads_per_campaign: numericOrNull(form.max_leads_per_campaign),
        max_managers: numericOrNull(form.max_managers),
        max_employees: numericOrNull(form.max_employees),
        max_teams: numericOrNull(form.max_teams),
        max_monthly_leads: numericOrNull(form.max_monthly_leads),
        max_inventory_units: numericOrNull(form.max_inventory_units),
        advanced_reports: form.advanced_reports,
        meta_integrations: form.meta_integrations,
        header_color: form.header_color || null,
        is_active: form.is_active,
      }
      if (!isEdit) payload.is_default = form.is_default

      if (isEdit) await adminApi.plans.update(plan.id, payload)
      else await adminApi.plans.create(payload)

      toast.success(isEdit ? 'Plan updated' : 'Plan created')
      onSaved()
    } catch (error) {
      setErrors((current) => ({ ...current, ...serverFieldErrors(error) }))
      toast.error(getErrorMessage(error, 'Could not save the plan.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEdit ? `Edit ${plan?.name}` : 'New plan'}
      description="Set pricing, quotas and feature access per edition."
      size="lg"
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button type="submit" form="plan-form" loading={submitting}>
            {isEdit ? 'Save changes' : 'Create plan'}
          </Button>
        </>
      }
    >
      <form id="plan-form" onSubmit={handleSubmit} className="space-y-5">
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Plan name" htmlFor="plan-name" required error={errors.name}>
            <Input
              id="plan-name"
              value={form.name}
              onChange={(event) => set('name', event.target.value)}
              invalid={Boolean(errors.name)}
              placeholder="e.g. Growth"
            />
          </FormField>
          <FormField label="Plan key" htmlFor="plan-key" hint="Optional stable identifier e.g. growth.">
            <Input
              id="plan-key"
              value={form.key}
              onChange={(event) => set('key', event.target.value)}
              placeholder="growth"
            />
          </FormField>
          <FormField label="Audience" htmlFor="plan-audience" required>
            <Select
              id="plan-audience"
              value={form.audience}
              onChange={(event) => set('audience', event.target.value)}
            >
              {AUDIENCES.map((audience) => (
                <option key={audience.value} value={audience.value}>
                  {audience.label}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Price (₹)" htmlFor="plan-price" error={errors.price}>
            <Input
              id="plan-price"
              type="number"
              min="0"
              step="0.01"
              value={form.price}
              onChange={(event) => set('price', event.target.value)}
              invalid={Boolean(errors.price)}
              placeholder="0"
            />
          </FormField>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-foreground">Realtor quotas</h3>
          <div className="mt-2 grid gap-4 sm:grid-cols-2">
            <QuotaField
              id="plan-campaigns"
              label="Max campaigns"
              value={form.max_campaigns}
              onChange={(value) => set('max_campaigns', value)}
              error={errors.max_campaigns}
            />
            <QuotaField
              id="plan-leads"
              label="Max leads per campaign"
              value={form.max_leads_per_campaign}
              onChange={(value) => set('max_leads_per_campaign', value)}
              error={errors.max_leads_per_campaign}
            />
          </div>
        </div>

        {isEnterprise && (
          <div className="rounded-xl border border-border p-4">
            <h3 className="text-sm font-semibold text-foreground">Enterprise quotas</h3>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Leave any quota blank for unlimited. Extra seats approved from seat requests are added
              on top of manager and employee limits.
            </p>
            <div className="mt-3 grid gap-4 sm:grid-cols-2">
              <QuotaField
                id="plan-managers"
                label="Max managers"
                value={form.max_managers}
                onChange={(value) => set('max_managers', value)}
                error={errors.max_managers}
              />
              <QuotaField
                id="plan-employees"
                label="Max employees"
                value={form.max_employees}
                onChange={(value) => set('max_employees', value)}
                error={errors.max_employees}
              />
              <QuotaField
                id="plan-teams"
                label="Max teams"
                value={form.max_teams}
                onChange={(value) => set('max_teams', value)}
                error={errors.max_teams}
              />
              <QuotaField
                id="plan-monthly-leads"
                label="Max leads per month"
                value={form.max_monthly_leads}
                onChange={(value) => set('max_monthly_leads', value)}
                error={errors.max_monthly_leads}
              />
              <QuotaField
                id="plan-inventory-units"
                label="Max inventory units"
                value={form.max_inventory_units}
                onChange={(value) => set('max_inventory_units', value)}
                error={errors.max_inventory_units}
              />
            </div>
            <div className="mt-3 grid gap-2.5 sm:grid-cols-2">
              <label className="flex items-center gap-2.5 text-sm text-foreground">
                <input
                  type="checkbox"
                  checked={form.advanced_reports}
                  onChange={(event) => set('advanced_reports', event.target.checked)}
                  className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
                />
                Advanced reports & CSV export
              </label>
              <label className="flex items-center gap-2.5 text-sm text-foreground">
                <input
                  type="checkbox"
                  checked={form.meta_integrations}
                  onChange={(event) => set('meta_integrations', event.target.checked)}
                  className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
                />
                Meta Lead Ads & Conversions API
              </label>
            </div>
          </div>
        )}

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Header colour" htmlFor="plan-color">
            <div className="flex items-center gap-2">
              <input
                id="plan-color"
                type="color"
                value={form.header_color}
                onChange={(event) => set('header_color', event.target.value)}
                className="h-10 w-12 cursor-pointer rounded-lg border border-border bg-card p-1"
              />
              <Input
                value={form.header_color}
                onChange={(event) => set('header_color', event.target.value)}
                placeholder="#650052"
              />
            </div>
          </FormField>
        </div>

        <div className="space-y-2.5 rounded-lg border border-border bg-muted/30 p-3">
          <label className="flex items-center gap-2.5 text-sm text-foreground">
            <input
              type="checkbox"
              checked={form.is_active}
              disabled={isEdit && plan?.is_default}
              onChange={(event) => set('is_active', event.target.checked)}
              className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
            />
            Plan is available for purchase
          </label>
          {!isEdit && (
            <label className="flex items-center gap-2.5 text-sm text-foreground">
              <input
                type="checkbox"
                checked={form.is_default}
                onChange={(event) => set('is_default', event.target.checked)}
                className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
              />
              Make this the default plan for its audience
            </label>
          )}
          {isEdit && plan?.is_default && (
            <p className="text-xs text-muted-foreground">The default plan must stay active.</p>
          )}
        </div>
      </form>
    </Modal>
  )
}

const audienceLabel = (audience) =>
  AUDIENCES.find((item) => item.value === audience)?.label || 'Realtor edition'

export const PlansPage = () => {
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [toggling, setToggling] = useState(null)
  const [audienceFilter, setAudienceFilter] = useState('')

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: async () => {
      const response = await adminApi.plans.list()
      return response.data?.data || []
    },
  })

  const plans = data || []

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin', 'plans'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'plans'] })
    queryClient.invalidateQueries({ queryKey: ['checkout-plans'] })
  }

  const toggleMutation = useMutation({
    mutationFn: (plan) => adminApi.plans.toggleStatus(plan.id),
    onMutate: (plan) => setToggling(plan.id),
    onSuccess: () => {
      toast.success('Plan status updated')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the plan.')),
    onSettled: () => setToggling(null),
  })

  const reorderMutation = useMutation({
    mutationFn: (order) => adminApi.plans.reorder(order),
    onSuccess: () => {
      toast.success('Plan order updated')
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not reorder plans.')),
  })

  const move = (index, direction) => {
    const next = [...plans]
    const target = index + direction
    if (target < 0 || target >= next.length) return
    ;[next[index], next[target]] = [next[target], next[index]]
    reorderMutation.mutate(next.map((plan) => plan.id))
  }

  const openCreate = () => {
    setEditing(null)
    setModalOpen(true)
  }

  const openEdit = (plan) => {
    setEditing(plan)
    setModalOpen(true)
  }

  const columns = useMemo(
    () => [
      {
        key: 'name',
        header: 'Plan',
        render: (plan) => (
          <div className="flex min-w-[200px] items-center gap-3">
            <span
              className="flex h-9 w-9 items-center justify-center rounded-lg text-white"
              style={planHeaderStyle(plan.header_color)}
            >
              <Squares2X2Icon className="h-4 w-4" />
            </span>
            <div>
              <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                {plan.name}
                {plan.is_default && <CheckBadgeIcon className="h-4 w-4 text-highlight" title="Default plan" />}
              </p>
              <p className="text-xs text-muted-foreground">
                {plan.key ? plan.key : `Position ${plan.sort_order}`}
              </p>
            </div>
          </div>
        ),
      },
      {
        key: 'audience',
        header: 'Audience',
        render: (plan) => <Badge variant="outline">{audienceLabel(plan.audience)}</Badge>,
      },
      {
        key: 'price',
        header: 'Price',
        render: (plan) => <span className="text-sm font-semibold text-foreground">{formatCurrency(plan.price)}</span>,
      },
      {
        key: 'max_campaigns',
        header: 'Campaigns',
        render: (plan) => (
          <span className="text-sm">{plan.max_campaigns ?? 'Unlimited'}</span>
        ),
      },
      {
        key: 'quotas',
        header: 'Members / teams',
        render: (plan) => (
          <div className="whitespace-nowrap text-xs text-muted-foreground">
            {formatNumber(plan.max_managers ?? 0)} mgr · {formatNumber(plan.max_employees ?? 0)} emp ·{' '}
            {formatNumber(plan.max_teams ?? 0)} teams
          </div>
        ),
      },
      {
        key: 'features',
        header: 'Features',
        render: (plan) => (
          <div className="flex flex-wrap gap-1">
            {plan.advanced_reports && <Badge variant="success">Reports</Badge>}
            {plan.meta_integrations && <Badge variant="success">Meta</Badge>}
            {!plan.advanced_reports && !plan.meta_integrations && (
              <span className="text-xs text-muted-foreground">—</span>
            )}
          </div>
        ),
      },
      {
        key: 'users_count',
        header: 'Users',
        render: (plan) => <span className="text-sm">{formatNumber(plan.users_count || 0)}</span>,
      },
      {
        key: 'status',
        header: 'Status',
        render: (plan) => (
          <div className="flex flex-wrap items-center gap-1.5">
            {plan.is_default && <Badge variant="primary">Default</Badge>}
            <StatusBadge status={plan.is_active ? 'active' : 'inactive'} />
          </div>
        ),
      },
      {
        key: 'actions',
        header: '',
        className: 'text-right',
        stopPropagation: true,
        render: (plan) => (
          <div className="flex items-center justify-end gap-1">
            <Button
              variant="ghost"
              size="icon-sm"
              onClick={() => move(plans.findIndex((item) => item.id === plan.id), -1)}
              disabled={reorderMutation.isPending || plans[0]?.id === plan.id}
              aria-label="Move up"
            >
              <ArrowUpIcon className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon-sm"
              onClick={() => move(plans.findIndex((item) => item.id === plan.id), 1)}
              disabled={reorderMutation.isPending || plans[plans.length - 1]?.id === plan.id}
              aria-label="Move down"
            >
              <ArrowDownIcon className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="icon-sm" onClick={() => openEdit(plan)} aria-label="Edit plan">
              <PencilSquareIcon className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon-sm"
              onClick={() => toggleMutation.mutate(plan)}
              loading={toggling === plan.id}
              aria-label="Toggle plan"
            >
              {toggling !== plan.id && <PowerIcon className="h-4 w-4" />}
            </Button>
          </div>
        ),
      },
    ],
    [plans, reorderMutation.isPending, toggleMutation, toggling],
  )

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const groups = AUDIENCES.filter((audience) => !audienceFilter || audience.value === audienceFilter)
    .map((audience) => ({
      ...audience,
      plans: plans.filter((plan) => (plan.audience || 'realtor') === audience.value),
    }))
    .filter((group) => group.plans.length > 0)

  return (
    <div>
      <PageHeader
        title="Plans"
        description="Manage subscription tiers, quotas and feature access for both editions."
        actions={
          <>
            <Select
              value={audienceFilter}
              onChange={(event) => setAudienceFilter(event.target.value)}
              className="w-48"
            >
              <option value="">All audiences</option>
              {AUDIENCES.map((audience) => (
                <option key={audience.value} value={audience.value}>
                  {audience.label}
                </option>
              ))}
            </Select>
            <Button size="sm" onClick={openCreate}>
              <PlusIcon className="h-4 w-4" />
              New plan
            </Button>
          </>
        }
      />

      {groups.length === 0 ? (
        <Card>
          <EmptyState
            icon={Squares2X2Icon}
            title="No plans found"
            description="Create a plan tier to start onboarding realtors or enterprises."
            action={
              <Button size="sm" onClick={openCreate}>
                <PlusIcon className="h-4 w-4" />
                New plan
              </Button>
            }
          />
        </Card>
      ) : (
        <div className="space-y-6">
          {groups.map((group) => (
            <Card key={group.value}>
              <CardHeader>
                <CardTitle>{group.label}</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  {formatNumber(group.plans.length)} plan{group.plans.length === 1 ? '' : 's'}
                </p>
              </CardHeader>
              <DataTable
                className="rounded-none border-x-0 border-b-0"
                columns={columns}
                rows={group.plans}
                emptyState={
                  <EmptyState
                    icon={Squares2X2Icon}
                    title="No plans yet"
                    description="Create the first plan for this audience."
                  />
                }
              />
            </Card>
          ))}
        </div>
      )}

      {modalOpen && (
        <PlanModal
          open={modalOpen}
          plan={editing}
          onClose={() => setModalOpen(false)}
          onSaved={() => {
            setModalOpen(false)
            invalidate()
          }}
        />
      )}
    </div>
  )
}
