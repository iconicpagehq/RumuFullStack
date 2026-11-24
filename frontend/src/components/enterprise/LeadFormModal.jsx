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
import { ENTERPRISE_SOURCES, LEAD_PRIORITIES } from '@/utils/enterprise'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { toUtcDateTime } from '@/utils/formatters'

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

const emptyValues = {
  name: '',
  email: '',
  phone: '',
  company: '',
  source: 'manual',
  priority: 'medium',
  team_id: '',
  assigned_to: '',
  campaign_id: '',
  inventory_project_id: '',
  budget_min: '',
  budget_max: '',
  preferred_location: '',
  preferred_configuration: '',
  next_follow_up_at: '',
  note: '',
}

const valuesForLead = (lead) => ({
  name: lead?.name || '',
  email: lead?.email || '',
  phone: lead?.phone || '',
  company: lead?.company || '',
  source: lead?.source || 'manual',
  priority: lead?.priority || 'medium',
  team_id: lead?.team_id || '',
  assigned_to: lead?.assigned_to || '',
  campaign_id: lead?.campaign_id || '',
  inventory_project_id: lead?.inventory_project_id || '',
  budget_min: lead?.budget_min ?? '',
  budget_max: lead?.budget_max ?? '',
  preferred_location: lead?.preferred_location || '',
  preferred_configuration: lead?.preferred_configuration || '',
  next_follow_up_at: toDateTimeInput(lead?.next_follow_up_at),
  note: '',
})

const clean = (value) => (value === '' || value === undefined ? null : value)
const numberOrNull = (value) => (value === '' || value === undefined || value === null ? null : Number(value))

export const LeadFormModal = ({ open, onClose, lead = null, onSaved }) => {
  const isEdit = Boolean(lead?.id)
  const queryClient = useQueryClient()
  const { canAssignLeads, isAdmin } = useEnterprise()
  const { teams, assignableMembers, projects, campaigns } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')
  const [duplicateError, setDuplicateError] = useState('')
  const [allowDuplicate, setAllowDuplicate] = useState(false)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: emptyValues })

  useEffect(() => {
    if (!open) return
    reset(valuesForLead(lead))
    setGeneralError('')
    setDuplicateError('')
    setAllowDuplicate(false)
  }, [open, lead, reset])

  const mutation = useMutation({
    mutationFn: ({ values, allow }) => {
      const payload = {
        name: values.name.trim(),
        email: clean(values.email.trim()),
        phone: clean(values.phone.trim()),
        company: clean(values.company.trim()),
        source: values.source,
        priority: values.priority,
        campaign_id: numberOrNull(values.campaign_id),
        inventory_project_id: numberOrNull(values.inventory_project_id),
        budget_min: numberOrNull(values.budget_min),
        budget_max: numberOrNull(values.budget_max),
        preferred_location: clean(values.preferred_location.trim()),
        preferred_configuration: clean(values.preferred_configuration.trim()),
        next_follow_up_at: toUtcDateTime(values.next_follow_up_at),
      }

      if (canAssignLeads) {
        payload.team_id = numberOrNull(values.team_id)
      }

      if (!isEdit) {
        if (canAssignLeads) payload.assigned_to = numberOrNull(values.assigned_to)
        if (values.note?.trim()) payload.note = values.note.trim()
        if (allow) payload.allow_duplicate = true
        return enterpriseApi.leads.create(payload)
      }

      return enterpriseApi.leads.update(lead.id, payload)
    },
    onSuccess: (response) => {
      const saved = response.data?.data
      toast.success(isEdit ? 'Lead updated' : 'Lead created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'customers'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      if (lead?.id) {
        queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', lead.id] })
      }
      onSaved?.(saved)
      onClose()
    },
    onError: (error) => {
      const duplicate = error?.response?.data?.errors?.duplicate
      if (duplicate) {
        setDuplicateError(Array.isArray(duplicate) ? duplicate[0] : String(duplicate))
        return
      }
      if (
        !applyServerErrors(error, setError, [
          'name',
          'email',
          'phone',
          'company',
          'source',
          'priority',
          'team_id',
          'assigned_to',
          'campaign_id',
          'inventory_project_id',
          'budget_min',
          'budget_max',
          'next_follow_up_at',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this lead.'))
      }
    },
  })

  const onSubmit = (values, options = {}) => {
    setGeneralError('')
    setDuplicateError('')
    mutation.mutate({ values, allow: options.allow ?? allowDuplicate })
  }

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit lead' : 'Add a lead'}
      description={
        isEdit
          ? 'Update contact details, requirements and follow-up.'
          : 'Capture a new enquiry and route it to the right team.'
      }
      size="lg"
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-lead-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create lead'}
          </Button>
        </>
      }
    >
      <form id="enterprise-lead-form" onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}
        {duplicateError && (
          <Alert variant="warning" title="Possible duplicate">
            {duplicateError}
            <div className="mt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => {
                  setAllowDuplicate(true)
                  setDuplicateError('')
                  handleSubmit((values) => onSubmit(values, { allow: true }))()
                }}
              >
                Save anyway
              </Button>
            </div>
          </Alert>
        )}

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Full name" htmlFor="lead-name" error={errors.name?.message} required>
            <Input id="lead-name" invalid={Boolean(errors.name)} {...register('name')} />
          </FormField>
          <FormField label="Company" htmlFor="lead-company" error={errors.company?.message}>
            <Input id="lead-company" {...register('company')} />
          </FormField>
          <FormField label="Email" htmlFor="lead-email" error={errors.email?.message}>
            <Input id="lead-email" type="email" invalid={Boolean(errors.email)} {...register('email')} />
          </FormField>
          <FormField
            label="Phone"
            htmlFor="lead-phone"
            error={errors.phone?.message}
            hint="Provide at least an email or phone."
          >
            <Input id="lead-phone" invalid={Boolean(errors.phone)} {...register('phone')} />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Source" htmlFor="lead-source">
            <Select id="lead-source" {...register('source')}>
              {ENTERPRISE_SOURCES.map((source) => (
                <option key={source.value} value={source.value}>
                  {source.label}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Priority" htmlFor="lead-priority">
            <Select id="lead-priority" {...register('priority')}>
              {LEAD_PRIORITIES.map((priority) => (
                <option key={priority.value} value={priority.value}>
                  {priority.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        {canAssignLeads && (
          <div className="grid gap-4 sm:grid-cols-2">
            <FormField label="Team" htmlFor="lead-team" error={errors.team_id?.message}>
              <Select id="lead-team" {...register('team_id')}>
                <option value="">No team</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </Select>
            </FormField>
            {!isEdit && (
              <FormField
                label="Assign to"
                htmlFor="lead-assignee"
                error={errors.assigned_to?.message}
                hint={isAdmin ? 'Leave blank to keep unassigned.' : 'Only your team members are listed.'}
              >
                <Select id="lead-assignee" {...register('assigned_to')}>
                  <option value="">Unassigned</option>
                  {assignableMembers.map((member) => (
                    <option key={member.user.id} value={member.user.id}>
                      {member.user.name} · {member.role}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
          </div>
        )}

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Interested project" htmlFor="lead-project">
            <Select id="lead-project" {...register('inventory_project_id')}>
              <option value="">No project</option>
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Campaign" htmlFor="lead-campaign">
            <Select id="lead-campaign" {...register('campaign_id')}>
              <option value="">No campaign</option>
              {campaigns.map((campaign) => (
                <option key={campaign.id} value={campaign.id}>
                  {campaign.name}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Budget from (₹)" htmlFor="lead-budget-min" error={errors.budget_min?.message}>
            <Input id="lead-budget-min" type="number" min="0" {...register('budget_min')} />
          </FormField>
          <FormField label="Budget up to (₹)" htmlFor="lead-budget-max" error={errors.budget_max?.message}>
            <Input id="lead-budget-max" type="number" min="0" {...register('budget_max')} />
          </FormField>
          <FormField label="Preferred location" htmlFor="lead-location">
            <Input id="lead-location" {...register('preferred_location')} />
          </FormField>
          <FormField label="Preferred configuration" htmlFor="lead-configuration">
            <Input id="lead-configuration" placeholder="e.g. 3 BHK" {...register('preferred_configuration')} />
          </FormField>
        </div>

        <FormField
          label="Next follow-up"
          htmlFor="lead-follow-up"
          error={errors.next_follow_up_at?.message}
          hint="Shown on your dashboard when it becomes due."
        >
          <Input id="lead-follow-up" type="datetime-local" {...register('next_follow_up_at')} />
        </FormField>

        {!isEdit && (
          <FormField label="First note" htmlFor="lead-note" hint="Optional context for the team.">
            <Textarea id="lead-note" rows={3} {...register('note')} />
          </FormField>
        )}
      </form>
    </Modal>
  )
}
