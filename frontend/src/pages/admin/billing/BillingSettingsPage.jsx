import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Cog6ToothIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Switch } from '@/components/ui/Switch'
import { PageLoader } from '@/components/ui/Spinner'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { formatDateTime } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const sections = [
  {
    key: 'company',
    title: 'Company details',
    description: 'Shown on invoices and billing emails.',
    fields: [
      { key: 'company_name', label: 'Company name' },
      { key: 'company_legal_name', label: 'Legal name' },
      { key: 'company_gst', label: 'GST number' },
      { key: 'company_pan', label: 'PAN' },
      { key: 'company_email', label: 'Billing email', type: 'email' },
      { key: 'company_phone', label: 'Phone' },
      { key: 'company_address', label: 'Address', type: 'textarea', full: true },
    ],
  },
  {
    key: 'invoice',
    title: 'Invoice settings',
    description: 'Numbering and terms used for new invoices.',
    fields: [
      { key: 'invoice_prefix', label: 'Invoice prefix' },
      { key: 'invoice_start_number', label: 'Start number', type: 'number' },
      { key: 'invoice_currency', label: 'Currency' },
      { key: 'invoice_payment_terms', label: 'Payment terms' },
      { key: 'invoice_footer', label: 'Invoice footer', type: 'textarea', full: true },
    ],
  },
  {
    key: 'tax',
    title: 'Tax',
    description: 'Tax calculation applied at checkout.',
    fields: [
      { key: 'tax_enabled', label: 'Enable tax', type: 'boolean' },
      { key: 'tax_inclusive', label: 'Prices include tax', type: 'boolean' },
      { key: 'tax_default_gst_percent', label: 'Default tax percentage', type: 'number', step: '0.01' },
      { key: 'tax_label', label: 'Tax label' },
    ],
  },
  {
    key: 'subscription',
    title: 'Subscriptions',
    description: 'Trial and expiry behaviour.',
    fields: [
      { key: 'sub_default_trial_days', label: 'Default trial days', type: 'number' },
      { key: 'sub_grace_period_days', label: 'Grace period days', type: 'number' },
      { key: 'sub_auto_expire', label: 'Auto-expire subscriptions', type: 'boolean' },
    ],
  },
  {
    key: 'payment',
    title: 'Payments',
    description: 'Gateway preferences and retries.',
    fields: [
      {
        key: 'payment_default_gateway',
        label: 'Default gateway',
        type: 'select',
        options: [
          { value: 'razorpay', label: 'Razorpay' },
          { value: 'stripe', label: 'Stripe' },
          { value: 'paypal', label: 'PayPal' },
        ],
      },
      { key: 'payment_retry_attempts', label: 'Retry attempts', type: 'number' },
      { key: 'payment_test_mode', label: 'Test mode', type: 'boolean' },
    ],
  },
  {
    key: 'notification',
    title: 'Notifications',
    description: 'Automatic billing emails.',
    fields: [
      { key: 'notify_invoice_email_enabled', label: 'Invoice emails', type: 'boolean' },
      { key: 'notify_payment_success_enabled', label: 'Payment success emails', type: 'boolean' },
      { key: 'notify_renewal_reminder_enabled', label: 'Renewal reminders', type: 'boolean' },
    ],
  },
  {
    key: 'reminder',
    title: 'Reminders',
    description: 'When renewal reminders are sent.',
    fields: [{ key: 'reminder_sub_expiry_days', label: 'Days before expiry', type: 'number' }],
  },
]

export const BillingSettingsPage = () => {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({})
  const [errors, setErrors] = useState({})

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'settings'],
    queryFn: async () => {
      const response = await adminApi.billing.settings.get()
      return response.data?.data
    },
  })

  useEffect(() => {
    if (!data?.settings) return
    setForm(
      Object.values(data.settings).reduce(
        (accumulator, group) => ({ ...accumulator, ...group }),
        {},
      ),
    )
  }, [data])

  const saveMutation = useMutation({
    mutationFn: () => adminApi.billing.settings.update(form),
    onSuccess: () => {
      toast.success('Billing settings saved')
      setErrors({})
      queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'settings'] })
    },
    onError: (err) => {
      setErrors(serverFieldErrors(err))
      toast.error(getErrorMessage(err, 'Could not save billing settings.'))
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const audits = data?.audits || []
  const available = new Set(Object.keys(form))
  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  return (
    <div>
      <PageHeader
        title="Billing settings"
        description="Company, invoice, tax and notification defaults."
        actions={
          <Button onClick={() => saveMutation.mutate()} loading={saveMutation.isPending}>
            Save settings
          </Button>
        }
      />

      <div className="grid gap-5 xl:grid-cols-2">
        {sections.map((section) => {
          const fields = section.fields.filter((field) => available.has(field.key))
          if (!fields.length) return null
          return (
            <Card key={section.key}>
              <CardHeader>
                <div>
                  <CardTitle>{section.title}</CardTitle>
                  <p className="mt-1 text-sm text-muted-foreground">{section.description}</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                {fields.map((field) => {
                  if (field.type === 'boolean') {
                    return (
                      <Switch
                        key={field.key}
                        checked={Boolean(form[field.key])}
                        onChange={(value) => set(field.key, value)}
                        label={field.label}
                      />
                    )
                  }
                  return (
                    <FormField
                      key={field.key}
                      label={field.label}
                      htmlFor={`billing-${field.key}`}
                      error={errors[field.key]}
                      className={field.full ? 'sm:col-span-2' : undefined}
                    >
                      {field.type === 'textarea' ? (
                        <Textarea
                          id={`billing-${field.key}`}
                          rows={3}
                          value={form[field.key] ?? ''}
                          onChange={(event) => set(field.key, event.target.value)}
                          invalid={Boolean(errors[field.key])}
                        />
                      ) : field.type === 'select' ? (
                        <Select
                          id={`billing-${field.key}`}
                          value={form[field.key] ?? ''}
                          onChange={(event) => set(field.key, event.target.value)}
                        >
                          {field.options.map((option) => (
                            <option key={option.value} value={option.value}>
                              {option.label}
                            </option>
                          ))}
                        </Select>
                      ) : (
                        <Input
                          id={`billing-${field.key}`}
                          type={field.type || 'text'}
                          step={field.step}
                          value={form[field.key] ?? ''}
                          onChange={(event) => set(field.key, event.target.value)}
                          invalid={Boolean(errors[field.key])}
                        />
                      )}
                    </FormField>
                  )
                })}
              </CardContent>
            </Card>
          )
        })}
      </div>

      <Card className="mt-5">
        <CardHeader>
          <div>
            <CardTitle>Change history</CardTitle>
            <p className="mt-1 text-sm text-muted-foreground">The 50 most recent billing setting changes.</p>
          </div>
        </CardHeader>
        <DataTable
          columns={[
            { key: 'setting_key', header: 'Setting', render: (row) => <span className="text-sm font-medium">{row.setting_key}</span> },
            {
              key: 'old_value',
              header: 'Previous',
              render: (row) => <span className="text-xs text-muted-foreground">{row.old_value ?? '—'}</span>,
            },
            {
              key: 'new_value',
              header: 'New',
              render: (row) => <span className="text-xs text-foreground">{row.new_value ?? '—'}</span>,
            },
            { key: 'user', header: 'Changed by', render: (row) => <span className="text-sm">{row.user || 'System'}</span> },
            {
              key: 'created_at',
              header: 'When',
              render: (row) => <span className="text-xs text-muted-foreground">{formatDateTime(row.created_at)}</span>,
            },
          ]}
          rows={audits}
          emptyState={
            <EmptyState
              icon={Cog6ToothIcon}
              title="No changes yet"
              description="Billing setting changes will be recorded here."
            />
          }
        />
      </Card>
    </div>
  )
}
