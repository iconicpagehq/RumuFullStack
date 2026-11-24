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
import { PROJECT_STATUSES } from '@/utils/constants'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { inventoryProjectSchema } from '@/utils/validators'

export const ProjectFormPage = () => {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const { data: project, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['inventory', 'project', id],
    queryFn: async () => {
      const response = await realtorApi.inventory.project(id)
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
    resolver: zodResolver(inventoryProjectSchema),
    defaultValues: { name: '', location: '', city: '', description: '', status: 'active' },
  })

  useEffect(() => {
    if (!project) return
    reset({
      name: project.name || '',
      location: project.location || '',
      city: project.city || '',
      description: project.description || '',
      status: project.status || 'active',
    })
  }, [project, reset])

  const mutation = useMutation({
    mutationFn: (values) =>
      isEdit
        ? realtorApi.inventory.updateProject(id, values)
        : realtorApi.inventory.createProject(values),
    onSuccess: (response) => {
      toast.success(isEdit ? 'Project updated' : 'Project created')
      queryClient.invalidateQueries({ queryKey: ['inventory', 'projects'] })
      queryClient.invalidateQueries({ queryKey: ['inventory', 'project', id] })
      const saved = response.data?.data
      navigate(saved?.id ? `/realtor/inventory/${saved.id}` : '/realtor/inventory')
    },
    onError: (err) => {
      if (!applyServerErrors(err, setError, ['name', 'location', 'city', 'description', 'status'])) {
        setGeneralError(getErrorMessage(err, 'Could not save the project.'))
      }
    },
  })

  if (isEdit && isLoading) return <PageLoader />
  if (isEdit && isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div className="mx-auto max-w-2xl">
      <PageHeader
        breadcrumbs={[
          { label: 'Inventory', to: '/realtor/inventory' },
          { label: isEdit ? project?.name || 'Edit project' : 'New project' },
        ]}
        title={isEdit ? 'Edit project' : 'Create a project'}
        description="Projects are the top level of your inventory structure."
      />

      <Card>
        <CardContent className="pt-5">
          {generalError && (
            <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
              {generalError}
            </Alert>
          )}

          <form
            onSubmit={handleSubmit((values) => {
              setGeneralError('')
              mutation.mutate(values)
            })}
            className="space-y-5"
            noValidate
          >
            <FormField label="Project name" htmlFor="name" required error={errors.name?.message}>
              <Input
                id="name"
                placeholder="e.g. Skyline Residency"
                invalid={Boolean(errors.name)}
                {...register('name')}
              />
            </FormField>

            <div className="grid gap-4 sm:grid-cols-2">
              <FormField label="Location" htmlFor="location" error={errors.location?.message}>
                <Input
                  id="location"
                  placeholder="e.g. Whitefield"
                  invalid={Boolean(errors.location)}
                  {...register('location')}
                />
              </FormField>
              <FormField label="City" htmlFor="city" error={errors.city?.message}>
                <Input
                  id="city"
                  placeholder="e.g. Bengaluru"
                  invalid={Boolean(errors.city)}
                  {...register('city')}
                />
              </FormField>
            </div>

            <FormField label="Status" htmlFor="status" error={errors.status?.message}>
              <Select id="status" invalid={Boolean(errors.status)} {...register('status')}>
                {PROJECT_STATUSES.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </Select>
            </FormField>

            <FormField
              label="Description"
              htmlFor="description"
              error={errors.description?.message}
              hint="Optional details about the project."
            >
              <Textarea
                id="description"
                rows={4}
                placeholder="Project highlights, location notes…"
                invalid={Boolean(errors.description)}
                {...register('description')}
              />
            </FormField>

            <div className="flex flex-wrap items-center justify-end gap-3 border-t border-border pt-4">
              <Button variant="outline" onClick={() => navigate(-1)} disabled={mutation.isPending}>
                <ArrowLeftIcon className="h-4 w-4" />
                Cancel
              </Button>
              <Button type="submit" loading={mutation.isPending}>
                {isEdit ? 'Save changes' : 'Create project'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
