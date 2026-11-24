import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowPathIcon,
  ArrowPathRoundedSquareIcon,
  CalendarDaysIcon,
  EllipsisVerticalIcon,
  XCircleIcon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Dropdown, DropdownItem, DropdownLabel } from '@/components/ui/Dropdown'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { PAGE_SIZE, SUBSCRIPTION_STATUSES } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const SubscriptionModal = ({ subscription, onClose, onSaved }) => {
  const [form, setForm] = useState({
    status: subscription.status || 'active',
    expiry_date: subscription.expiry_date ? subscription.expiry_date.slice(0, 10) : '',
    internal_notes: subscription.internal_notes || '',
  })
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const submit = async (event) => {
    event.preventDefault()
    setSaving(true)
    try {
      await adminApi.billing.subscriptions.update(subscription.id, {
        status: form.status,
        expiry_date: form.expiry_date || null,
        internal_notes: form.internal_notes || null,
      })
      toast.success('Subscription updated')
      onSaved()
    } catch (error) {
      setErrors(serverFieldErrors(error))
      toast.error(getErrorMessage(error, 'Could not update the subscription.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={`Manage subscription #${subscription.id}`}
      description={`${subscription.user?.name || 'Realtor'} · ${subscription.plan_name}`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button type="submit" form="subscription-form" loading={saving}>
            Save changes
          </Button>
        </>
      }
    >
      <form id="subscription-form" onSubmit={submit} className="space-y-4">
        <FormField label="Status" htmlFor="sub-status" error={errors.status}>
          <Select id="sub-status" value={form.status} onChange={(event) => set('status', event.target.value)}>
            {SUBSCRIPTION_STATUSES.map((status) => (
              <option key={status.value} value={status.value}>
                {status.label}
              </option>
            ))}
          </Select>
        </FormField>
        <FormField label="Expiry date" htmlFor="sub-expiry" error={errors.expiry_date}>
          <Input
            id="sub-expiry"
            type="date"
            value={form.expiry_date}
            onChange={(event) => set('expiry_date', event.target.value)}
            invalid={Boolean(errors.expiry_date)}
          />
        </FormField>
        <FormField label="Internal notes" htmlFor="sub-notes" error={errors.internal_notes}>
          <Textarea
            id="sub-notes"
            rows={3}
            value={form.internal_notes}
            onChange={(event) => set('internal_notes', event.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  )
}

const ExtendModal = ({ subscription, onClose, onSaved }) => {
  const [months, setMonths] = useState(1)
  const [saving, setSaving] = useState(false)

  const submit = async (event) => {
    event.preventDefault()
    setSaving(true)
    try {
      await adminApi.billing.subscriptions.extend(subscription.id, { months_to_add: Number(months) })
      toast.success(`Subscription extended by ${months} month${Number(months) === 1 ? '' : 's'}`)
      onSaved()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Could not extend the subscription.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title="Extend subscription"
      description={`${subscription.user?.name || 'Realtor'} · ${subscription.plan_name}`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button type="submit" form="extend-form" loading={saving}>
            Extend
          </Button>
        </>
      }
    >
      <form id="extend-form" onSubmit={submit}>
        <FormField label="Months to add" htmlFor="extend-months" required hint="Between 1 and 60 months.">
          <Input
            id="extend-months"
            type="number"
            min="1"
            max="60"
            value={months}
            onChange={(event) => setMonths(event.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  )
}

export const SubscriptionsPage = () => {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [extending, setExtending] = useState(null)
  const [cancelling, setCancelling] = useState(null)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'subscriptions', { search: debouncedSearch, status, page }],
    queryFn: async () => {
      const response = await adminApi.billing.subscriptions.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
        kpis: response.data?.kpis || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'subscriptions'] })
    queryClient.invalidateQueries({ queryKey: ['admin', 'analytics'] })
  }

  const cancelMutation = useMutation({
    mutationFn: () => adminApi.billing.subscriptions.cancel(cancelling.id),
    onSuccess: () => {
      toast.success('Subscription cancelled')
      setCancelling(null)
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not cancel the subscription.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const kpis = data?.kpis || {}

  const columns = [
    {
      key: 'user',
      header: 'Realtor',
      render: (row) => (
        <div className="min-w-[180px]">
          <p className="text-sm font-semibold text-foreground">{row.user?.name || '—'}</p>
          <p className="text-xs text-muted-foreground">{row.user?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'plan_name',
      header: 'Plan',
      render: (row) => (
        <div>
          <p className="text-sm font-medium text-foreground">{row.plan_name}</p>
          <p className="text-xs text-muted-foreground">{row.duration_months} months</p>
        </div>
      ),
    },
    {
      key: 'start_date',
      header: 'Period',
      render: (row) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(row.start_date)} → {formatDate(row.expiry_date)}
        </span>
      ),
    },
    {
      key: 'total',
      header: 'Total',
      render: (row) => <span className="text-sm font-semibold text-foreground">{formatCurrency(row.total)}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (row) => (
        <Dropdown
          trigger={
            <Button variant="ghost" size="icon-sm" aria-label="Subscription actions">
              <EllipsisVerticalIcon className="h-4 w-4" />
            </Button>
          }
        >
          <DropdownLabel>Subscription</DropdownLabel>
          <DropdownItem icon={ArrowPathIcon} onClick={() => setEditing(row)}>
            Manage details
          </DropdownItem>
          <DropdownItem icon={CalendarDaysIcon} onClick={() => setExtending(row)}>
            Extend validity
          </DropdownItem>
          {row.status !== 'cancelled' && (
            <DropdownItem icon={XCircleIcon} danger onClick={() => setCancelling(row)}>
              Cancel subscription
            </DropdownItem>
          )}
        </Dropdown>
      ),
    },
  ]

  return (
    <div>
      <PageHeader title="Subscriptions" description="Track and manage realtor subscriptions." />

      <div className="mb-5 grid gap-4 sm:grid-cols-3">
        <StatsCard
          label="Active subscriptions"
          value={formatNumber(kpis.active || 0)}
          icon={ArrowPathRoundedSquareIcon}
          tone="success"
        />
        <StatsCard
          label="Expired"
          value={formatNumber(kpis.expired || 0)}
          icon={XCircleIcon}
          tone="danger"
        />
        <StatsCard
          label="New this month"
          value={formatNumber(kpis.new_this_month || 0)}
          icon={CalendarDaysIcon}
          tone="info"
        />
      </div>

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search plan or realtor…"
          className="w-full max-w-sm"
        />
        <Select
          value={status}
          onChange={(event) => {
            setStatus(event.target.value)
            setPage(1)
          }}
          className="w-40"
        >
          <option value="">All statuses</option>
          {SUBSCRIPTION_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Select>
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} subscriptions
        </span>
      </div>

      <Card>
        <DataTable
          columns={columns}
          rows={data?.rows || []}
          loading={isLoading}
          emptyState={
            <EmptyState
              icon={ArrowPathRoundedSquareIcon}
              title="No subscriptions found"
              description="Try adjusting your search or status filter."
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

      {editing && (
        <SubscriptionModal
          subscription={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            invalidate()
          }}
        />
      )}

      {extending && (
        <ExtendModal
          subscription={extending}
          onClose={() => setExtending(null)}
          onSaved={() => {
            setExtending(null)
            invalidate()
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(cancelling)}
        onClose={() => setCancelling(null)}
        onConfirm={() => cancelMutation.mutate()}
        loading={cancelMutation.isPending}
        title={`Cancel ${cancelling?.plan_name || ''} subscription?`}
        description="The realtor will lose access at the end of the current validity. This can be reversed by managing the subscription."
        confirmLabel="Cancel subscription"
        cancelLabel="Keep active"
      />
    </div>
  )
}
