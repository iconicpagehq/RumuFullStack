import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import toast from 'react-hot-toast'
import { ArrowLeftIcon } from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { CUSTOM_FIELD_LABELS, CUSTOM_FIELD_OPTIONS, LEAD_STATUSES } from '@/utils/constants'
import { titleCase } from '@/utils/formatters'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { leadSchema } from '@/utils/validators'

const SELECT_FIELDS = [
  'client_type',
  'property_type',
  'configuration',
  'budget_range',
  'purpose',
  'family_size',
  'construction_status',
  'site_visit_required',
  'lead_source',
  'lead_priority',
  'buying_timeline',
  'funding_type',
]

const TEXT_FIELDS = ['preferred_location', 'project_name', 'city', 'landmarks']

export const LeadFormPage = () => {
  const { id: campaignId, leadId } = useParams()
  const isEdit = Boolean(leadId)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const { data: lead, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'lead', leadId],
    queryFn: async () => {
      const response = await realtorApi.leads.get(leadId)
      return response.data?.data
    },
    enabled: isEdit,
    retry: false,
  })

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(leadSchema),
    defaultValues: {
      name: '',
      email: '',
      phone: '',
      company: '',
      status: 'new',
      note: '',
      custom_fields: {},
    },
  })

  useEffect(() => {
    if (!lead) return
    reset({
      name: lead.name || '',
      email: lead.email || '',
      phone: lead.phone || '',
      company: lead.company || '',
      status: lead.status || 'new',
      note: '',
      custom_fields: lead.custom_fields || {},
    })
  }, [lead, reset])

  const mutation = useMutation({
    mutationFn: (payload) =>
      isEdit ? realtorApi.leads.update(leadId, payload) : realtorApi.leads.create(campaignId, payload),
    onSuccess: (response) => {
      toast.success(isEdit ? 'Lead updated' : 'Lead added')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'campaign', campaignId] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'lead', leadId] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'customers'] })
      const saved = response.data?.data
      navigate(saved?.id ? `/realtor/campaigns/${campaignId}/leads/${saved.id}` : `/realtor/campaigns/${campaignId}`)
    },
    onError: (err) => {
      if (!applyServerErrors(err, setError, ['name', 'email', 'phone', 'company', 'status', 'note'])) {
        setGeneralError(getErrorMessage(err, 'Could not save the lead.'))
      }
    },
  })

  const onSubmit = (values) => {
    setGeneralError('')
    const customFields = Object.fromEntries(
      Object.entries(values.custom_fields || {}).filter(
        ([, value]) => value !== '' && value !== null && value !== undefined,
      ),
    )
    const payload = {
      name: values.name,
      email: values.email || null,
      phone: values.phone || null,
      company: values.company || null,
      status: values.status,
    }
    if (!isEdit && values.note) payload.note = values.note
    if (Object.keys(customFields).length) payload.custom_fields = customFields
    mutation.mutate(payload)
  }

  if (isEdit && isLoading) return <PageLoader />
  if (isEdit && isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div className="mx-auto max-w-4xl">
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/realtor/campaigns' },
          { label: 'Campaign', to: `/realtor/campaigns/${campaignId}` },
          { label: isEdit ? lead?.name || 'Edit lead' : 'New lead' },
        ]}
        title={isEdit ? 'Edit lead' : 'Add a lead'}
        description={
          isEdit
            ? 'Keep contact and qualification details up to date.'
            : 'Capture a lead manually and add the details your team needs.'
        }
      />

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Contact details</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2">
            <FormField label="Full name" htmlFor="name" required error={errors.name?.message}>
              <Input
                id="name"
                placeholder="Lead name"
                invalid={Boolean(errors.name)}
                {...register('name')}
              />
            </FormField>
            <FormField label="Email" htmlFor="email" error={errors.email?.message}>
              <Input
                id="email"
                type="email"
                placeholder="lead@example.com"
                invalid={Boolean(errors.email)}
                {...register('email')}
              />
            </FormField>
            <FormField label="Phone" htmlFor="phone" error={errors.phone?.message}>
              <Input
                id="phone"
                type="tel"
                placeholder="+91 98765 43210"
                invalid={Boolean(errors.phone)}
                {...register('phone')}
              />
            </FormField>
            <FormField label="Company" htmlFor="company" error={errors.company?.message}>
              <Input
                id="company"
                placeholder="Optional"
                invalid={Boolean(errors.company)}
                {...register('company')}
              />
            </FormField>
            <FormField label="Pipeline stage" htmlFor="status" error={errors.status?.message}>
              <Select id="status" invalid={Boolean(errors.status)} {...register('status')}>
                {LEAD_STATUSES.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </Select>
            </FormField>
            {!isEdit && (
              <FormField label="Initial note" htmlFor="note" error={errors.note?.message}>
                <Input
                  id="note"
                  placeholder="Optional context for your team"
                  invalid={Boolean(errors.note)}
                  {...register('note')}
                />
              </FormField>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Qualification</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {SELECT_FIELDS.map((field) => (
              <FormField key={field} label={CUSTOM_FIELD_LABELS[field]} htmlFor={`cf-${field}`}>
                <Select id={`cf-${field}`} {...register(`custom_fields.${field}`)}>
                  <option value="">Not specified</option>
                  {CUSTOM_FIELD_OPTIONS[field].map((option) => (
                    <option key={option} value={option}>
                      {titleCase(option)}
                    </option>
                  ))}
                </Select>
              </FormField>
            ))}
            {TEXT_FIELDS.map((field) => (
              <FormField key={field} label={CUSTOM_FIELD_LABELS[field]} htmlFor={`cf-${field}`}>
                <Input
                  id={`cf-${field}`}
                  placeholder={field === 'landmarks' ? 'Nearby landmarks' : 'Optional'}
                  {...register(`custom_fields.${field}`)}
                />
              </FormField>
            ))}
            <FormField
              label={CUSTOM_FIELD_LABELS.preferred_visit_date}
              htmlFor="cf-preferred_visit_date"
            >
              <Input id="cf-preferred_visit_date" type="date" {...register('custom_fields.preferred_visit_date')} />
            </FormField>
          </CardContent>
        </Card>

        <div className="flex flex-wrap items-center justify-end gap-3">
          <Button
            variant="outline"
            onClick={() => navigate(`/realtor/campaigns/${campaignId}`)}
            disabled={mutation.isPending}
          >
            <ArrowLeftIcon className="h-4 w-4" />
            Cancel
          </Button>
          <Button type="submit" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Add lead'}
          </Button>
        </div>
      </form>
    </div>
  )
}
