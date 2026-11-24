import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = {
  name: '',
  description: '',
  manager_id: '',
  status: 'active',
  lead_distribution_mode: 'manual',
}

export const TeamFormModal = ({ open, onClose, team = null }) => {
  const isEdit = Boolean(team?.id)
  const queryClient = useQueryClient()
  const { members } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')

  const managers = members.filter((member) => member.role === 'manager' && member.status === 'active')

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
      name: team?.name || '',
      description: team?.description || '',
      manager_id: team?.manager?.id ? String(team.manager.id) : '',
      status: team?.status || 'active',
      lead_distribution_mode: team?.lead_distribution_mode || 'manual',
    })
    setGeneralError('')
  }, [open, team, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        name: values.name.trim(),
        description: values.description?.trim() || null,
        manager_id: values.manager_id ? Number(values.manager_id) : null,
        status: values.status,
        lead_distribution_mode: values.lead_distribution_mode,
      }
      return isEdit
        ? enterpriseApi.teams.update(team.id, payload)
        : enterpriseApi.teams.create(payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Team updated' : 'Team created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      if (isEdit) queryClient.invalidateQueries({ queryKey: ['enterprise', 'team', team.id] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'name',
          'description',
          'manager_id',
          'status',
          'lead_distribution_mode',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this team.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit team' : 'Create a team'}
      description={
        isEdit
          ? 'Changing the manager reassigns open leads owned by this team.'
          : 'Teams group managers and employees for lead distribution and targets.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-team-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create team'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-team-form"
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

        <FormField label="Team name" htmlFor="team-name" error={errors.name?.message} required>
          <Input
            id="team-name"
            invalid={Boolean(errors.name)}
            {...register('name', { required: 'Enter a team name.' })}
          />
        </FormField>

        <FormField label="Description" htmlFor="team-description" error={errors.description?.message}>
          <Textarea id="team-description" rows={3} {...register('description')} />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField
            label="Team manager"
            htmlFor="team-manager"
            error={errors.manager_id?.message}
            hint="Only active managers can lead a team."
          >
            <Select id="team-manager" {...register('manager_id')}>
              <option value="">No manager</option>
              {managers.map((member) => (
                <option key={member.user.id} value={member.user.id}>
                  {member.user.name}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Status" htmlFor="team-status" error={errors.status?.message}>
            <Select id="team-status" {...register('status')}>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </Select>
          </FormField>
        </div>

        <FormField
          label="Lead distribution"
          htmlFor="team-distribution"
          error={errors.lead_distribution_mode?.message}
          hint="Round robin automatically suggests the next owner when leads arrive unassigned."
        >
          <Select id="team-distribution" {...register('lead_distribution_mode')}>
            <option value="manual">Manual assignment</option>
            <option value="round_robin">Round robin</option>
          </Select>
        </FormField>
      </form>
    </Modal>
  )
}
