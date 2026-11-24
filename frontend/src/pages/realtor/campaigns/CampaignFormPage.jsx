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
import { Card, CardContent } from '@/components/ui/Card'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { CAMPAIGN_STATUSES } from '@/utils/constants'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { campaignSchema } from '@/utils/validators'

export const CampaignFormPage = () => {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const { data: campaign, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'campaign', id],
    queryFn: async () => {
      const response = await realtorApi.campaigns.get(id)
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
    resolver: zodResolver(campaignSchema),
    defaultValues: { name: '', description: '', status: 'active' },
  })

  useEffect(() => {
    if (campaign) {
      reset({
        name: campaign.name || '',
        description: campaign.description || '',
        status: campaign.status || 'active',
      })
    }
  }, [campaign, reset])

  const mutation = useMutation({
    mutationFn: (values) =>
      isEdit ? realtorApi.campaigns.update(id, values) : realtorApi.campaigns.create(values),
    onSuccess: (response) => {
      toast.success(isEdit ? 'Campaign updated' : 'Campaign created')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'campaigns'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      const saved = response.data?.data
      navigate(saved?.id ? `/realtor/campaigns/${saved.id}` : '/realtor/campaigns')
    },
    onError: (err) => {
      if (!applyServerErrors(err, setError, ['name', 'description', 'status'])) {
        setGeneralError(getErrorMessage(err, 'Could not save the campaign.'))
      }
    },
  })

  const onSubmit = (values) => {
    setGeneralError('')
    mutation.mutate(values)
  }

  if (isEdit && isLoading) return <PageLoader />
  if (isEdit && isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div className="mx-auto max-w-2xl">
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/realtor/campaigns' },
          { label: isEdit ? campaign?.name || 'Edit' : 'New campaign' },
        ]}
        title={isEdit ? 'Edit campaign' : 'Create a campaign'}
        description={
          isEdit
            ? 'Update the details of this campaign.'
            : 'Campaigns group leads by project, offer or marketing channel.'
        }
      />

      <Card>
        <CardContent className="pt-5">
          {generalError && (
            <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
              {generalError}
            </Alert>
          )}

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
            <FormField label="Campaign name" htmlFor="name" required error={errors.name?.message}>
              <Input
                id="name"
                placeholder="e.g. Skyline Residency launch"
                invalid={Boolean(errors.name)}
                {...register('name')}
              />
            </FormField>

            <FormField
              label="Description"
              htmlFor="description"
              error={errors.description?.message}
              hint="Optional context for your team."
            >
              <Textarea
                id="description"
                rows={4}
                placeholder="What is this campaign promoting?"
                invalid={Boolean(errors.description)}
                {...register('description')}
              />
            </FormField>

            <FormField label="Status" htmlFor="status" error={errors.status?.message}>
              <Select id="status" invalid={Boolean(errors.status)} {...register('status')}>
                {CAMPAIGN_STATUSES.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </Select>
            </FormField>

            <div className="flex flex-wrap items-center justify-end gap-3 border-t border-border pt-4">
              <Button variant="outline" onClick={() => navigate(-1)} disabled={mutation.isPending}>
                <ArrowLeftIcon className="h-4 w-4" />
                Cancel
              </Button>
              <Button type="submit" loading={mutation.isPending}>
                {isEdit ? 'Save changes' : 'Create campaign'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
