import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = { name: '', location: '', city: '', status: 'active', description: '' }

export const ProjectFormModal = ({ open, onClose, project = null }) => {
  const isEdit = Boolean(project?.id)
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open) return
    reset({
      name: project?.name || '',
      location: project?.location || '',
      city: project?.city || '',
      status: project?.status || 'active',
      description: project?.description || '',
    })
    setGeneralError('')
  }, [open, project, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        name: values.name.trim(),
        location: values.location?.trim() || null,
        city: values.city?.trim() || null,
        status: values.status,
        description: values.description?.trim() || null,
      }
      return isEdit
        ? enterpriseApi.inventory.updateProject(project.id, payload)
        : enterpriseApi.inventory.createProject(payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Project updated' : 'Project created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      if (isEdit) queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', project.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (!applyServerErrors(error, setError, ['name', 'location', 'city', 'status', 'description'])) {
        setGeneralError(getErrorMessage(error, 'Could not save this project.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit project' : 'Create a project'}
      description={
        isEdit
          ? 'Update project details. Towers, floors and units are managed from the project page.'
          : 'Start with project details, then add towers, wings, floors and units.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-project-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create project'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-project-form"
        className="space-y-4"
        onSubmit={handleSubmit((values) => {
          setGeneralError('')
          mutation.mutate(values)
        })}
        noValidate
      >
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        <FormField label="Project name" htmlFor="project-name" error={errors.name?.message} required>
          <Input id="project-name" invalid={Boolean(errors.name)} {...register('name')} />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Location" htmlFor="project-location" error={errors.location?.message}>
            <Input id="project-location" placeholder="e.g. Baner" {...register('location')} />
          </FormField>
          <FormField label="City" htmlFor="project-city" error={errors.city?.message}>
            <Input id="project-city" placeholder="e.g. Pune" {...register('city')} />
          </FormField>
        </div>

        <FormField
          label="Status"
          htmlFor="project-status"
          error={errors.status?.message}
          hint="Inactive projects stay visible but are excluded from lead and visit pickers."
        >
          <Select id="project-status" {...register('status')}>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </Select>
        </FormField>

        <FormField label="Description" htmlFor="project-description" error={errors.description?.message}>
          <Textarea id="project-description" rows={3} {...register('description')} />
        </FormField>
      </form>
    </Modal>
  )
}
