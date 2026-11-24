import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Switch } from '@/components/ui/Switch'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatDateTime, toUtcDateTime } from '@/utils/formatters'

const clean = (value) => (value === '' || value === undefined ? null : value)

const emptyValues = {
  status: 'completed',
  outcome: '',
  feedback: '',
  next_action: '',
  cancellation_reason: '',
  follow_up_title: '',
  follow_up_due_at: '',
}

export const VisitCloseModal = ({ open, onClose, visit = null }) => {
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')
  const [attended, setAttended] = useState(true)
  const [createFollowUp, setCreateFollowUp] = useState(false)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    watch,
    formState: { errors },
  } = useForm({ defaultValues: emptyValues })

  const status = watch('status')

  useEffect(() => {
    if (!open) return
    reset({
      ...emptyValues,
      follow_up_title: visit?.lead?.name ? `Follow up after site visit · ${visit.lead.name}` : '',
    })
    setAttended(true)
    setCreateFollowUp(false)
    setGeneralError('')
  }, [open, visit, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        status: values.status,
        customer_attended: values.status === 'completed' ? Boolean(attended) : false,
        feedback: clean(values.feedback?.trim()),
        next_action: clean(values.next_action?.trim()),
      }

      if (values.status === 'completed') {
        payload.outcome = values.outcome?.trim()
        payload.create_follow_up = Boolean(createFollowUp)
        if (createFollowUp) {
          payload.follow_up_title = clean(values.follow_up_title?.trim())
          payload.follow_up_due_at = toUtcDateTime(values.follow_up_due_at)
        }
      }

      if (values.status === 'cancelled') {
        payload.cancellation_reason = values.cancellation_reason?.trim()
      }

      return enterpriseApi.siteVisits.updateStatus(visit.id, payload)
    },
    onSuccess: () => {
      toast.success(visit?.status === 'scheduled' ? 'Site visit closed' : 'Site visit updated')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'site-visits'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'tasks'] })
      if (visit?.lead?.id) {
        queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', visit.lead.id] })
      }
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'status',
          'customer_attended',
          'outcome',
          'feedback',
          'next_action',
          'cancellation_reason',
          'follow_up_title',
          'follow_up_due_at',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not close this site visit.'))
      }
    },
  })

  if (!visit) return null

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Close site visit"
      description={
        visit.lead?.name
          ? `${visit.lead.name} · ${formatDateTime(visit.scheduled_at)}`
          : undefined
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-visit-close-form" loading={mutation.isPending}>
            Save outcome
          </Button>
        </>
      }
    >
      <form
        id="enterprise-visit-close-form"
        className="space-y-5"
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

        <FormField label="Outcome" htmlFor="visit-status" error={errors.status?.message} required>
          <Select id="visit-status" {...register('status')}>
            <option value="completed">Visit completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="no_show">Customer did not show</option>
          </Select>
        </FormField>

        {status === 'completed' && (
          <>
            <Switch
              checked={attended}
              onChange={setAttended}
              label="Customer attended"
              description="Turn off if the customer did not turn up for the visit."
            />

            <FormField label="Visit outcome" htmlFor="visit-outcome" error={errors.outcome?.message} required>
              <Textarea
                id="visit-outcome"
                rows={3}
                placeholder="What did the customer like or dislike? Which unit are they considering?"
                invalid={Boolean(errors.outcome)}
                {...register('outcome')}
              />
            </FormField>

            <FormField label="Customer feedback" htmlFor="visit-feedback" error={errors.feedback?.message}>
              <Textarea id="visit-feedback" rows={2} {...register('feedback')} />
            </FormField>

            <FormField label="Next action" htmlFor="visit-next-action" error={errors.next_action?.message}>
              <Input id="visit-next-action" placeholder="e.g. Send cost sheet for Tower B" {...register('next_action')} />
            </FormField>

            <div className="rounded-xl border border-border bg-muted/30 p-4">
              <Switch
                checked={createFollowUp}
                onChange={setCreateFollowUp}
                label="Create a follow-up task"
                description="A task is created for the visit owner so the next action is not missed."
              />
              {createFollowUp && (
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                  <FormField label="Task title" htmlFor="visit-follow-title" error={errors.follow_up_title?.message}>
                    <Input id="visit-follow-title" {...register('follow_up_title')} />
                  </FormField>
                  <FormField label="Due" htmlFor="visit-follow-due" error={errors.follow_up_due_at?.message}>
                    <Input id="visit-follow-due" type="datetime-local" {...register('follow_up_due_at')} />
                  </FormField>
                </div>
              )}
            </div>
          </>
        )}

        {status === 'cancelled' && (
          <FormField
            label="Cancellation reason"
            htmlFor="visit-cancellation"
            error={errors.cancellation_reason?.message}
            required
          >
            <Textarea id="visit-cancellation" rows={3} invalid={Boolean(errors.cancellation_reason)} {...register('cancellation_reason')} />
          </FormField>
        )}

        {status === 'no_show' && (
          <>
            <FormField label="What happened?" htmlFor="visit-no-show-note" error={errors.feedback?.message}>
              <Textarea id="visit-no-show-note" rows={2} placeholder="Record any follow-up context." {...register('feedback')} />
            </FormField>
            <FormField label="Next action" htmlFor="visit-next-action" error={errors.next_action?.message}>
              <Input id="visit-next-action" placeholder="e.g. Call again tomorrow" {...register('next_action')} />
            </FormField>
          </>
        )}
      </form>
    </Modal>
  )
}
