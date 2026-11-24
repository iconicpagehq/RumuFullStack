import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { PencilSquareIcon, PlusIcon, TicketIcon, TrashIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const emptyCoupon = {
  code: '',
  name: '',
  description: '',
  discount_type: 'percentage',
  discount_value: '',
  max_discount_amount: '',
  min_purchase_amount: '',
  applicable_plans: [],
  start_date: '',
  expiration_date: '',
  usage_limit: '',
  customer_usage_limit: 1,
  is_active: true,
  notes: '',
}

const toCouponForm = (coupon) => ({
  code: coupon?.code || '',
  name: coupon?.name || '',
  description: coupon?.description || '',
  discount_type: coupon?.discount_type || 'percentage',
  discount_value: coupon?.discount_value ?? '',
  max_discount_amount: coupon?.max_discount_amount ?? '',
  min_purchase_amount: coupon?.min_purchase_amount ?? '',
  applicable_plans: Array.isArray(coupon?.applicable_plans) ? coupon.applicable_plans : [],
  start_date: coupon?.start_date ? coupon.start_date.slice(0, 10) : '',
  expiration_date: coupon?.expiration_date ? coupon.expiration_date.slice(0, 10) : '',
  usage_limit: coupon?.usage_limit ?? '',
  customer_usage_limit: coupon?.customer_usage_limit ?? 1,
  is_active: coupon ? Boolean(coupon.is_active) : true,
  notes: coupon?.notes || '',
})

const CouponModal = ({ coupon, plans, onClose, onSaved }) => {
  const [form, setForm] = useState(() => toCouponForm(coupon))
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)
  const isEdit = Boolean(coupon)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const togglePlan = (name) => {
    setForm((current) => ({
      ...current,
      applicable_plans: current.applicable_plans.includes(name)
        ? current.applicable_plans.filter((item) => item !== name)
        : [...current.applicable_plans, name],
    }))
  }

  const submit = async (event) => {
    event.preventDefault()
    const nextErrors = {}
    if (!form.code.trim()) nextErrors.code = 'Coupon code is required.'
    if (!form.name.trim()) nextErrors.name = 'Name is required.'
    if (form.discount_value === '' || Number(form.discount_value) < 0) {
      nextErrors.discount_value = 'Enter a valid discount.'
    }
    if (form.discount_type === 'percentage' && Number(form.discount_value) > 100) {
      nextErrors.discount_value = 'Percentage cannot exceed 100.'
    }
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    setSaving(true)
    try {
      const payload = {
        code: form.code.trim().toUpperCase(),
        name: form.name.trim(),
        description: form.description.trim() || null,
        discount_type: form.discount_type,
        discount_value: Number(form.discount_value),
        max_discount_amount: form.max_discount_amount === '' ? null : Number(form.max_discount_amount),
        min_purchase_amount: form.min_purchase_amount === '' ? null : Number(form.min_purchase_amount),
        applicable_plans: form.applicable_plans.length ? form.applicable_plans : null,
        start_date: form.start_date || null,
        expiration_date: form.expiration_date || null,
        usage_limit: form.usage_limit === '' ? null : Number(form.usage_limit),
        customer_usage_limit: Number(form.customer_usage_limit) || 1,
        is_active: form.is_active,
        notes: form.notes.trim() || null,
      }
      if (isEdit) await adminApi.billing.coupons.update(coupon.id, payload)
      else await adminApi.billing.coupons.create(payload)
      toast.success(isEdit ? 'Coupon updated' : 'Coupon created')
      onSaved()
    } catch (error) {
      setErrors(serverFieldErrors(error))
      toast.error(getErrorMessage(error, 'Could not save the coupon.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="lg"
      title={isEdit ? `Edit ${coupon.code}` : 'New coupon'}
      description="Discounts are applied during checkout."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button type="submit" form="coupon-form" loading={saving}>
            {isEdit ? 'Save changes' : 'Create coupon'}
          </Button>
        </>
      }
    >
      <form id="coupon-form" onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Code" htmlFor="coupon-code" required error={errors.code}>
            <Input
              id="coupon-code"
              value={form.code}
              onChange={(event) => set('code', event.target.value.toUpperCase())}
              invalid={Boolean(errors.code)}
              placeholder="SAVE20"
            />
          </FormField>
          <FormField label="Name" htmlFor="coupon-name" required error={errors.name}>
            <Input
              id="coupon-name"
              value={form.name}
              onChange={(event) => set('name', event.target.value)}
              invalid={Boolean(errors.name)}
            />
          </FormField>
        </div>

        <FormField label="Description" htmlFor="coupon-description" error={errors.description}>
          <Textarea
            id="coupon-description"
            rows={2}
            value={form.description}
            onChange={(event) => set('description', event.target.value)}
          />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-3">
          <FormField label="Discount type" htmlFor="coupon-type" error={errors.discount_type}>
            <Select
              id="coupon-type"
              value={form.discount_type}
              onChange={(event) => set('discount_type', event.target.value)}
            >
              <option value="percentage">Percentage</option>
              <option value="fixed">Fixed amount</option>
            </Select>
          </FormField>
          <FormField
            label={form.discount_type === 'percentage' ? 'Discount (%)' : 'Discount (₹)'}
            htmlFor="coupon-value"
            required
            error={errors.discount_value}
          >
            <Input
              id="coupon-value"
              type="number"
              min="0"
              step="0.01"
              value={form.discount_value}
              onChange={(event) => set('discount_value', event.target.value)}
              invalid={Boolean(errors.discount_value)}
            />
          </FormField>
          <FormField label="Max discount (₹)" htmlFor="coupon-max" error={errors.max_discount_amount}>
            <Input
              id="coupon-max"
              type="number"
              min="0"
              step="0.01"
              value={form.max_discount_amount}
              onChange={(event) => set('max_discount_amount', event.target.value)}
              placeholder="No cap"
            />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-4">
          <FormField label="Min purchase (₹)" htmlFor="coupon-min" error={errors.min_purchase_amount}>
            <Input
              id="coupon-min"
              type="number"
              min="0"
              step="0.01"
              value={form.min_purchase_amount}
              onChange={(event) => set('min_purchase_amount', event.target.value)}
            />
          </FormField>
          <FormField label="Total uses" htmlFor="coupon-usage" hint="Blank = unlimited." error={errors.usage_limit}>
            <Input
              id="coupon-usage"
              type="number"
              min="1"
              value={form.usage_limit}
              onChange={(event) => set('usage_limit', event.target.value)}
            />
          </FormField>
          <FormField label="Uses per realtor" htmlFor="coupon-customer" error={errors.customer_usage_limit}>
            <Input
              id="coupon-customer"
              type="number"
              min="1"
              value={form.customer_usage_limit}
              onChange={(event) => set('customer_usage_limit', event.target.value)}
            />
          </FormField>
          <FormField label="Starts" htmlFor="coupon-start" error={errors.start_date}>
            <Input
              id="coupon-start"
              type="date"
              value={form.start_date}
              onChange={(event) => set('start_date', event.target.value)}
            />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Expires" htmlFor="coupon-expiry" error={errors.expiration_date}>
            <Input
              id="coupon-expiry"
              type="date"
              value={form.expiration_date}
              onChange={(event) => set('expiration_date', event.target.value)}
            />
          </FormField>
          <div className="flex items-end pb-2">
            <label className="flex items-center gap-2.5 text-sm text-foreground">
              <input
                type="checkbox"
                checked={form.is_active}
                onChange={(event) => set('is_active', event.target.checked)}
                className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
              />
              Coupon is active
            </label>
          </div>
        </div>

        <FormField label="Applicable plans" hint="Leave all unchecked to apply to every plan.">
          <div className="flex flex-wrap gap-2">
            {plans.map((plan) => {
              const active = form.applicable_plans.includes(plan.name)
              return (
                <button
                  key={plan.id}
                  type="button"
                  onClick={() => togglePlan(plan.name)}
                  className={`rounded-full border px-3 py-1.5 text-xs font-medium transition ${
                    active
                      ? 'border-highlight bg-primary/10 text-highlight'
                      : 'border-border text-muted-foreground hover:bg-muted'
                  }`}
                >
                  {plan.name}
                </button>
              )
            })}
          </div>
        </FormField>

        <FormField label="Internal notes" htmlFor="coupon-notes" error={errors.notes}>
          <Textarea
            id="coupon-notes"
            rows={2}
            value={form.notes}
            onChange={(event) => set('notes', event.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  )
}

export const CouponsPage = () => {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [active, setActive] = useState('')
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const debouncedSearch = useDebounce(search)

  const { data: plans = [] } = useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: async () => {
      const response = await adminApi.plans.list()
      return response.data?.data || []
    },
  })

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'coupons', { search: debouncedSearch, active, page }],
    queryFn: async () => {
      const response = await adminApi.billing.coupons.list({
        search: debouncedSearch || undefined,
        is_active: active === '' ? undefined : active,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const deleteMutation = useMutation({
    mutationFn: () => adminApi.billing.coupons.remove(deleting.id),
    onSuccess: () => {
      toast.success('Coupon deleted')
      setDeleting(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'coupons'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the coupon.')),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const openCreate = () => {
    setEditing(null)
    setModalOpen(true)
  }

  const columns = [
    {
      key: 'code',
      header: 'Coupon',
      render: (row) => (
        <div>
          <p className="text-sm font-bold tracking-wide text-foreground">{row.code}</p>
          <p className="text-xs text-muted-foreground">{row.name}</p>
        </div>
      ),
    },
    {
      key: 'discount',
      header: 'Discount',
      render: (row) => (
        <div>
          <p className="text-sm font-semibold text-foreground">
            {row.discount_type === 'percentage'
              ? `${row.discount_value}% off`
              : `${formatCurrency(row.discount_value)} off`}
          </p>
          {row.max_discount_amount && (
            <p className="text-[11px] text-muted-foreground">Max {formatCurrency(row.max_discount_amount)}</p>
          )}
        </div>
      ),
    },
    {
      key: 'usage',
      header: 'Usage',
      render: (row) => (
        <span className="text-sm">
          {formatNumber(row.usages_count || 0)}
          {row.usage_limit ? ` / ${formatNumber(row.usage_limit)}` : ''}
        </span>
      ),
    },
    {
      key: 'validity',
      header: 'Validity',
      render: (row) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {row.start_date ? formatDate(row.start_date) : 'Anytime'} →{' '}
          {row.expiration_date ? formatDate(row.expiration_date) : 'No expiry'}
        </span>
      ),
    },
    {
      key: 'plans',
      header: 'Plans',
      render: (row) =>
        Array.isArray(row.applicable_plans) && row.applicable_plans.length ? (
          <div className="flex max-w-[180px] flex-wrap gap-1">
            {row.applicable_plans.map((plan) => (
              <Badge key={plan} variant="neutral">
                {plan}
              </Badge>
            ))}
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">All plans</span>
        ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (row) => (
        <div className="flex justify-end gap-1">
          <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Edit coupon"
            onClick={() => {
              setEditing(row)
              setModalOpen(true)
            }}
          >
            <PencilSquareIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Delete coupon"
            onClick={() => setDeleting(row)}
          >
            <TrashIcon className="h-4 w-4 text-destructive" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Coupons"
        description="Create promo codes for checkout discounts."
        actions={
          <Button size="sm" onClick={openCreate}>
            <PlusIcon className="h-4 w-4" />
            New coupon
          </Button>
        }
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search code or name…"
          className="w-full max-w-sm"
        />
        <Select
          value={active}
          onChange={(event) => {
            setActive(event.target.value)
            setPage(1)
          }}
          className="w-36"
        >
          <option value="">All</option>
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} coupons
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          emptyState={
            <EmptyState
              icon={TicketIcon}
              title="No coupons yet"
              description="Create a coupon to run a discount campaign."
              action={
                <Button size="sm" onClick={openCreate}>
                  <PlusIcon className="h-4 w-4" />
                  New coupon
                </Button>
              }
            />
          }
        />
      </Card>

      <Pagination
        page={data?.meta?.current_page || 1}
        lastPage={data?.meta?.last_page || 1}
        total={data?.meta?.total || 0}
        perPage={data?.meta?.per_page || PAGE_SIZE}
        onPageChange={setPage}
      />

      {modalOpen && (
        <CouponModal
          coupon={editing}
          plans={plans}
          onClose={() => setModalOpen(false)}
          onSaved={() => {
            setModalOpen(false)
            queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'coupons'] })
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete ${deleting?.code || ''}?`}
        description="Existing payments keep their applied discount, but the code cannot be used again."
        confirmLabel="Delete coupon"
      />
    </div>
  )
}
