import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { LeadSearchSelect } from './LeadSearchSelect'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { LEAD_PRIORITIES } from '@/utils/enterprise'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { toUtcDateTime } from '@/utils/formatters'

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

export const TaskFormModal = ({ open, onClose, task = null, defaultLead = null }) => {
  const isEdit = Boolean(task?.id)
  const queryClient = useQueryClient()
  const { isEmployee, canAssignLeads } = useEnterprise()
  const { teams, assignableMembers } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')
  const [leadId, setLeadId] = useState('')
  const [teamId, setTeamId] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: { title: '', description: '', priority: 'medium', due_at: '', assigned_to: '' } })

  useEffect(() => {
    if (!open) return
    reset({
      title: task?.title || '',
      description: task?.description || '',
      priority: task?.priority || 'medium',
      due_at: toDateTimeInput(task?.due_at),
      assigned_to: task?.assignee?.id || '',
    })
    setLeadId(defaultLead?.id ? String(defaultLead.id) : task?.lead?.id ? String(task.lead.id) : '')
    setTeamId(task?.team?.id ? String(task.team.id) : '')
    setGeneralError('')
  }, [open, task, defaultLead, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        title: values.title.trim(),
        description: values.description?.trim() || null,
        priority: values.priority,
        due_at: toUtcDateTime(values.due_at),
      }
      if (canAssignLeads) {
        payload.assigned_to = values.assigned_to ? Number(values.assigned_to) : null
      }
      if (!isEmployee) payload.team_id = teamId ? Number(teamId) : null
      if (!isEdit) payload.lead_id = leadId ? Number(leadId) : null

      return isEdit ? enterpriseApi.tasks.update(task.id, payload) : enterpriseApi.tasks.create(payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Task updated' : 'Task created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'tasks'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      if (leadId) queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', Number(leadId)] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'title',
          'description',
          'priority',
          'due_at',
          'assigned_to',
          'team_id',
          'lead_id',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this task.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit follow-up' : 'Create a follow-up'}
      description={isEdit ? 'Update the follow-up owner, priority or due date.' : 'Plan the next action for your team.'}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-task-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create follow-up'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-task-form"
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

        <FormField label="Title" htmlFor="task-title" error={errors.title?.message} required>
          <Input id="task-title" invalid={Boolean(errors.title)} {...register('title')} />
        </FormField>

        <FormField label="Description" htmlFor="task-description" error={errors.description?.message}>
          <Textarea id="task-description" rows={3} {...register('description')} />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Priority" htmlFor="task-priority" error={errors.priority?.message}>
            <Select id="task-priority" {...register('priority')}>
              {LEAD_PRIORITIES.map((priority) => (
                <option key={priority.value} value={priority.value}>
                  {priority.label}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Due" htmlFor="task-due" error={errors.due_at?.message}>
            <Input id="task-due" type="datetime-local" {...register('due_at')} />
          </FormField>
        </div>

        {(canAssignLeads || !isEmployee) && (
          <div className="grid gap-4 sm:grid-cols-2">
            {canAssignLeads && (
              <FormField label="Assign to" htmlFor="task-assignee" error={errors.assigned_to?.message}>
                <Select id="task-assignee" {...register('assigned_to')}>
                  <option value="">Me</option>
                  {assignableMembers.map((member) => (
                    <option key={member.user.id} value={member.user.id}>
                      {member.user.name} · {member.role}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
            {!isEmployee && (
              <FormField label="Team" htmlFor="task-team" error={errors.team_id?.message}>
                <Select id="task-team" value={teamId} onChange={(event) => setTeamId(event.target.value)}>
                  <option value="">Auto</option>
                  {teams.map((team) => (
                    <option key={team.id} value={team.id}>
                      {team.name}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
          </div>
        )}

        {!isEdit && (
          <FormField label="Linked lead" error={errors.lead_id?.message}>
            <LeadSearchSelect
              id="task-lead"
              value={leadId}
              onChange={(event) => setLeadId(event.target.value)}
              defaultLead={defaultLead}
            />
          </FormField>
        )}
      </form>
    </Modal>
  )
}
