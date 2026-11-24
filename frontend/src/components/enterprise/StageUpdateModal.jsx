import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { ENTERPRISE_STAGES, STAGE_TRANSITIONS, stageLabel } from '@/utils/enterprise'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { toUtcDateTime } from '@/utils/formatters'

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

export const StageUpdateModal = ({ open, onClose, lead, initialStage = '' }) => {
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')
  const allowed = STAGE_TRANSITIONS[lead?.stage] || []

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: { stage: '', note: '' } })

  useEffect(() => {
    if (!open) return
    reset({
      stage: allowed.includes(initialStage) ? initialStage : allowed[0] || '',
      note: '',
      deal_value: '',
      lost_reason: '',
      next_follow_up_at: toDateTimeInput(lead?.next_follow_up_at),
      budget_min: lead?.budget_min ?? '',
      budget_max: lead?.budget_max ?? '',
      preferred_location: lead?.preferred_location || '',
      preferred_configuration: lead?.preferred_configuration || '',
    })
    setGeneralError('')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, lead, initialStage])

  const stage = watch('stage')

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.leads.updateStage(lead.id, {
        stage: values.stage,
        note: values.note?.trim() || null,
        deal_value: values.deal_value === '' ? null : Number(values.deal_value),
        lost_reason: values.lost_reason?.trim() || null,
        next_follow_up_at: toUtcDateTime(values.next_follow_up_at),
        budget_min: values.budget_min === '' ? null : Number(values.budget_min),
        budget_max: values.budget_max === '' ? null : Number(values.budget_max),
        preferred_location: values.preferred_location?.trim() || null,
        preferred_configuration: values.preferred_configuration?.trim() || null,
      }),
    onSuccess: () => {
      toast.success('Pipeline stage updated')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', lead.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reports'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'customers'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'stage',
          'note',
          'deal_value',
          'lost_reason',
          'next_follow_up_at',
          'budget_min',
          'budget_max',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not update the pipeline stage.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Move pipeline stage"
      description={`${lead?.name || 'Lead'} is currently ${stageLabel(lead?.stage)}.`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-stage-form" loading={mutation.isPending} disabled={!allowed.length}>
            Update stage
          </Button>
        </>
      }
    >
      {!allowed.length ? (
        <Alert variant="info">
          This lead has reached a final stage and can no longer be moved.
        </Alert>
      ) : (
        <form
          id="enterprise-stage-form"
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

          <FormField label="New stage" htmlFor="stage-value" error={errors.stage?.message} required>
            <Select id="stage-value" invalid={Boolean(errors.stage)} {...register('stage')}>
              {allowed.map((value) => (
                <option key={value} value={value}>
                  {ENTERPRISE_STAGES.find((item) => item.value === value)?.label || value}
                </option>
              ))}
            </Select>
          </FormField>

          <FormField
            label="Note"
            htmlFor="stage-note"
            error={errors.note?.message}
            hint={
              ['contacted', 'qualified'].includes(stage)
                ? 'Required — describe the interaction or qualification.'
                : 'Optional context for the activity timeline.'
            }
          >
            <Textarea id="stage-note" rows={3} invalid={Boolean(errors.note)} {...register('note')} />
          </FormField>

          {stage === 'qualified' && (
            <div className="grid gap-4 sm:grid-cols-2">
              <FormField label="Budget from (₹)" htmlFor="stage-budget-min">
                <Input id="stage-budget-min" type="number" min="0" {...register('budget_min')} />
              </FormField>
              <FormField label="Budget up to (₹)" htmlFor="stage-budget-max">
                <Input id="stage-budget-max" type="number" min="0" {...register('budget_max')} />
              </FormField>
              <FormField label="Preferred location" htmlFor="stage-location">
                <Input id="stage-location" {...register('preferred_location')} />
              </FormField>
              <FormField label="Preferred configuration" htmlFor="stage-configuration">
                <Input id="stage-configuration" {...register('preferred_configuration')} />
              </FormField>
              <div className="sm:col-span-2">
                <FormField label="Next follow-up" htmlFor="stage-follow-up">
                  <Input id="stage-follow-up" type="datetime-local" {...register('next_follow_up_at')} />
                </FormField>
              </div>
            </div>
          )}

          {stage === 'won' && (
            <FormField
              label="Deal value (₹)"
              htmlFor="stage-deal-value"
              error={errors.deal_value?.message}
              required
            >
              <Input
                id="stage-deal-value"
                type="number"
                min="0"
                invalid={Boolean(errors.deal_value)}
                {...register('deal_value')}
              />
            </FormField>
          )}

          {stage === 'lost' && (
            <FormField label="Lost reason" htmlFor="stage-lost-reason" error={errors.lost_reason?.message} required>
              <Textarea
                id="stage-lost-reason"
                rows={2}
                invalid={Boolean(errors.lost_reason)}
                {...register('lost_reason')}
              />
            </FormField>
          )}

          {stage === 'on_hold' && (
            <FormField
              label="Next follow-up"
              htmlFor="stage-hold-follow-up"
              error={errors.next_follow_up_at?.message}
              required
            >
              <Input
                id="stage-hold-follow-up"
                type="datetime-local"
                invalid={Boolean(errors.next_follow_up_at)}
                {...register('next_follow_up_at')}
              />
            </FormField>
          )}

          {stage === 'site_visit_scheduled' && (
            <Alert variant="info">
              Schedule the site visit first from this lead&apos;s page, then move it here.
            </Alert>
          )}
        </form>
      )}
    </Modal>
  )
}
