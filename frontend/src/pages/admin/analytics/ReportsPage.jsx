import { useState } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { format, startOfMonth } from 'date-fns'
import toast from 'react-hot-toast'
import { ArrowDownTrayIcon, DocumentChartBarIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { filenameFromResponse, saveBlob } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const ReportsPage = () => {
  const [form, setForm] = useState({
    type: 'revenue',
    format: 'csv',
    start_date: format(startOfMonth(new Date()), 'yyyy-MM-dd'),
    end_date: format(new Date(), 'yyyy-MM-dd'),
  })
  const [error, setError] = useState('')

  const { data, isLoading, isError, error: queryError, refetch } = useQuery({
    queryKey: ['admin', 'analytics', 'reports'],
    queryFn: async () => {
      const response = await adminApi.analytics.reports()
      return response.data?.data
    },
  })

  const exportMutation = useMutation({
    mutationFn: () => adminApi.analytics.export(form),
    onSuccess: (response) => {
      saveBlob(
        response.data,
        filenameFromResponse(
          response,
          `${form.type}_report_${form.start_date.replace(/-/g, '')}_${form.end_date.replace(/-/g, '')}.csv`,
        ),
      )
      toast.success('Report downloaded')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not generate the report.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={queryError} onRetry={refetch} />

  const types = data?.types || []
  const formats = data?.formats || []
  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const submit = (event) => {
    event.preventDefault()
    if (!form.start_date || !form.end_date) {
      setError('Choose a start and end date.')
      return
    }
    if (form.end_date < form.start_date) {
      setError('The end date must be after the start date.')
      return
    }
    setError('')
    exportMutation.mutate()
  }

  return (
    <div>
      <PageHeader title="Reports" description="Export revenue and customer data for any period." />

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <div>
              <CardTitle>Generate a report</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">
                Reports are generated as CSV files and downloaded immediately.
              </p>
            </div>
          </CardHeader>
          <CardContent>
            <form onSubmit={submit} className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Report type" htmlFor="report-type" required>
                  <Select id="report-type" value={form.type} onChange={(event) => set('type', event.target.value)}>
                    {types.map((type) => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </Select>
                </FormField>
                <FormField label="Format" htmlFor="report-format" required>
                  <Select id="report-format" value={form.format} onChange={(event) => set('format', event.target.value)}>
                    {formats.map((formatOption) => (
                      <option key={formatOption.value} value={formatOption.value}>
                        {formatOption.label}
                      </option>
                    ))}
                  </Select>
                </FormField>
                <FormField label="Start date" htmlFor="report-start" required error={error}>
                  <Input
                    id="report-start"
                    type="date"
                    value={form.start_date}
                    onChange={(event) => set('start_date', event.target.value)}
                    invalid={Boolean(error)}
                  />
                </FormField>
                <FormField label="End date" htmlFor="report-end" required>
                  <Input
                    id="report-end"
                    type="date"
                    value={form.end_date}
                    onChange={(event) => set('end_date', event.target.value)}
                    invalid={Boolean(error)}
                  />
                </FormField>
              </div>

              <div className="rounded-lg border border-border bg-muted/30 p-3 text-sm text-muted-foreground">
                {form.type === 'revenue'
                  ? 'Includes completed payments, plan names, realtor details and totals for the selected period.'
                  : 'Includes realtor signups, plan, status and campaign counts for the selected period.'}
              </div>

              <Button type="submit" loading={exportMutation.isPending}>
                <ArrowDownTrayIcon className="h-4 w-4" />
                Download CSV
              </Button>
            </form>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Available reports</CardTitle>
          </CardHeader>
          <CardContent>
            {types.length === 0 ? (
              <EmptyState
                icon={DocumentChartBarIcon}
                title="No report types"
                description="Report types will appear once configured."
              />
            ) : (
              <div className="space-y-3">
                {types.map((type) => (
                  <button
                    key={type.value}
                    type="button"
                    onClick={() => set('type', type.value)}
                    className={`w-full rounded-lg border p-3 text-left transition ${
                      form.type === type.value
                        ? 'border-highlight bg-primary/5'
                        : 'border-border hover:bg-muted'
                    }`}
                  >
                    <p className="text-sm font-semibold text-foreground">{type.label}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      {type.value === 'revenue'
                        ? 'Payment and revenue transactions.'
                        : 'Customer acquisition and plan details.'}
                    </p>
                  </button>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
