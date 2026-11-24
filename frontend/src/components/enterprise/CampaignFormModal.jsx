import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = {
  name: '',
  description: '',
  status: 'active',
  team_id: '',
  budget: '',
  start_date: '',
  end_date: '',
}

export const CampaignFormModal = ({ open, onClose, campaign = null }) => {
  const isEdit = Boolean(campaign?.id)
  const queryClient = useQueryClient()
  const { isManager } = useEnterprise()
  const { teams } = useEnterpriseReference()
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
    const fallbackTeam = isManager ? teams[0]?.id : ''
    reset({
      name: campaign?.name || '',
      description: campaign?.description || '',
      status: campaign?.status || 'active',
      team_id: campaign?.team_id ? String(campaign.team_id) : fallbackTeam ? String(fallbackTeam) : '',
      budget: campaign?.budget !== null && campaign?.budget !== undefined ? String(campaign.budget) : '',
      start_date: campaign?.start_date || '',
      end_date: campaign?.end_date || '',
    })
    setGeneralError('')
  }, [open, campaign, isManager, teams, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        name: values.name.trim(),
        description: values.description?.trim() || null,
        status: values.status,
        team_id: values.team_id ? Number(values.team_id) : null,
        budget: values.budget === '' ? null : Number(values.budget),
        start_date: values.start_date || null,
        end_date: values.end_date || null,
      }
      return isEdit
        ? enterpriseApi.campaigns.update(campaign.id, payload)
        : enterpriseApi.campaigns.create(payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Campaign updated' : 'Campaign created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'campaigns'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'name',
          'description',
          'status',
          'team_id',
          'budget',
          'start_date',
          'end_date',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this campaign.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit campaign' : 'Create a campaign'}
      description={
        isManager
          ? 'Managers create campaigns for their own team.'
          : 'Group leads by the marketing activity that generated them.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button
            type="submit"
            form="enterprise-campaign-form"
            loading={mutation.isPending}
            disabled={isManager && teams.length === 0}
          >
            {isEdit ? 'Save changes' : 'Create campaign'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-campaign-form"
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

        {isManager && teams.length === 0 && (
          <Alert variant="warning">
            You are not managing a team yet. Ask your company administrator to assign you as a team
            manager before creating campaigns.
          </Alert>
        )}

        <FormField label="Campaign name" htmlFor="campaign-name" error={errors.name?.message} required>
          <Input
            id="campaign-name"
            invalid={Boolean(errors.name)}
            {...register('name', { required: 'Enter a campaign name.' })}
          />
        </FormField>

        <FormField label="Description" htmlFor="campaign-description" error={errors.description?.message}>
          <Textarea id="campaign-description" rows={3} {...register('description')} />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Status" htmlFor="campaign-status" error={errors.status?.message}>
            <Select id="campaign-status" {...register('status')}>
              <option value="active">Active</option>
              <option value="paused">Paused</option>
              <option value="completed">Completed</option>
            </Select>
          </FormField>
          <FormField
            label="Team"
            htmlFor="campaign-team"
            error={errors.team_id?.message}
            hint={isManager ? 'Campaigns must belong to one of your teams.' : 'Optional. Leave empty for company-wide.'}
          >
            <Select
              id="campaign-team"
              invalid={Boolean(errors.team_id)}
              {...register('team_id', {
                required: isManager ? 'Select one of your teams.' : false,
              })}
            >
              <option value="">{isManager ? 'Select a team' : 'Company-wide'}</option>
              {teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Budget (₹)" htmlFor="campaign-budget" error={errors.budget?.message}>
            <Input id="campaign-budget" type="number" min="0" step="1000" {...register('budget')} />
          </FormField>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="Start date" htmlFor="campaign-start" error={errors.start_date?.message}>
              <Input id="campaign-start" type="date" {...register('start_date')} />
            </FormField>
            <FormField label="End date" htmlFor="campaign-end" error={errors.end_date?.message}>
              <Input
                id="campaign-end"
                type="date"
                {...register('end_date', {
                  validate: (value, values) =>
                    !value || !values.start_date || value >= values.start_date
                      ? true
                      : 'End date must be on or after the start date.',
                })}
              />
            </FormField>
          </div>
        </div>
      </form>
    </Modal>
  )
}
