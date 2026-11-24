import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
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
import { useDebounce } from '@/hooks/useDebounce'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatCurrency, toUtcDateTime } from '@/utils/formatters'

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

const clean = (value) => (value === '' || value === undefined ? null : value)

export const VisitFormModal = ({ open, onClose, visit = null, defaultLead = null }) => {
  const isEdit = Boolean(visit?.id)
  const queryClient = useQueryClient()
  const { canAssignLeads } = useEnterprise()
  const { projects, options } = useEnterpriseReference()

  const [generalError, setGeneralError] = useState('')
  const [leadId, setLeadId] = useState('')
  const [projectId, setProjectId] = useState('')
  const [unitId, setUnitId] = useState('')
  const [unitSearch, setUnitSearch] = useState('')
  const debouncedUnitSearch = useDebounce(unitSearch, 300)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: { scheduled_at: '', assigned_to: '', notes: '' },
  })

  useEffect(() => {
    if (!open) return
    reset({
      scheduled_at: toDateTimeInput(visit?.scheduled_at),
      assigned_to: visit?.assignee?.id || '',
      notes: visit?.notes || '',
    })
    setLeadId(
      defaultLead?.id ? String(defaultLead.id) : visit?.lead?.id ? String(visit.lead.id) : '',
    )
    setProjectId(
      visit?.project?.id
        ? String(visit.project.id)
        : defaultLead?.inventory_project_id
          ? String(defaultLead.inventory_project_id)
          : '',
    )
    setUnitId(visit?.unit?.id ? String(visit.unit.id) : '')
    setUnitSearch('')
    setGeneralError('')
  }, [open, visit, defaultLead, reset])

  const unitsQuery = useQuery({
    queryKey: ['enterprise', 'unit-picker', { projectId, search: debouncedUnitSearch }],
    queryFn: async () => {
      const response = await enterpriseApi.inventory.searchUnits({
        project_id: Number(projectId),
        search: debouncedUnitSearch || undefined,
        status: 'available',
      })
      return response.data?.data || []
    },
    enabled: open && Boolean(projectId),
    staleTime: 20000,
  })

  const unitOptions = unitsQuery.data || []
  const currentUnit = visit?.unit
  const showCurrentUnit =
    currentUnit && !unitOptions.some((unit) => String(unit.id) === String(currentUnit.id))

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        inventory_project_id: Number(projectId),
        inventory_unit_id: unitId ? Number(unitId) : null,
        scheduled_at: toUtcDateTime(values.scheduled_at),
        notes: clean(values.notes?.trim()),
      }
      if (canAssignLeads && values.assigned_to) payload.assigned_to = Number(values.assigned_to)

      if (isEdit) return enterpriseApi.siteVisits.update(visit.id, payload)

      payload.lead_id = Number(leadId)
      return enterpriseApi.siteVisits.create(payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Site visit rescheduled' : 'Site visit scheduled')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'site-visits'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      if (leadId) queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', Number(leadId)] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'lead_id',
          'inventory_project_id',
          'inventory_unit_id',
          'assigned_to',
          'scheduled_at',
          'notes',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this site visit.'))
      }
    },
  })

  const assigneeOptions = options()

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Reschedule site visit' : 'Schedule a site visit'}
      description={
        isEdit
          ? 'Update the project, unit, owner or time. Only scheduled visits can be changed.'
          : 'Book a site visit and assign a team member to accompany the customer.'
      }
      size="lg"
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-visit-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Schedule visit'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-visit-form"
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

        <FormField label="Lead" error={errors.lead_id?.message} required>
          {isEdit && visit?.lead ? (
            <Select id="visit-lead" value={visit.lead.id} disabled>
              <option value={visit.lead.id}>{visit.lead.name}</option>
            </Select>
          ) : (
            <LeadSearchSelect
              id="visit-lead"
              value={leadId}
              onChange={(event) => setLeadId(event.target.value)}
              defaultLead={defaultLead}
              invalid={Boolean(errors.lead_id)}
            />
          )}
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Project" htmlFor="visit-project" error={errors.inventory_project_id?.message} required>
            <Select
              id="visit-project"
              value={projectId}
              onChange={(event) => {
                setProjectId(event.target.value)
                setUnitId('')
                setUnitSearch('')
              }}
            >
              <option value="">Select a project</option>
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </Select>
          </FormField>

          <FormField label="Date & time" htmlFor="visit-scheduled" error={errors.scheduled_at?.message} required>
            <Input id="visit-scheduled" type="datetime-local" invalid={Boolean(errors.scheduled_at)} {...register('scheduled_at')} />
          </FormField>
        </div>

        <FormField label="Unit (optional)" error={errors.inventory_unit_id?.message}>
          <div className="space-y-2">
            <Input
              value={unitSearch}
              onChange={(event) => setUnitSearch(event.target.value)}
              placeholder={projectId ? 'Search available units…' : 'Select a project first'}
              aria-label="Search available units"
              disabled={!projectId}
            />
            <Select
              value={unitId}
              onChange={(event) => setUnitId(event.target.value)}
              disabled={!projectId}
              invalid={Boolean(errors.inventory_unit_id)}
            >
              <option value="">{unitsQuery.isFetching ? 'Searching…' : 'No specific unit'}</option>
              {showCurrentUnit && (
                <option value={currentUnit.id}>
                  {currentUnit.full_path || `Unit ${currentUnit.unit_number}`}
                </option>
              )}
              {unitOptions.map((unit) => (
                <option key={unit.id} value={unit.id}>
                  {unit.full_path || `Unit ${unit.unit_number}`}
                  {unit.layout ? ` · ${unit.layout}` : ''}
                  {unit.base_price ? ` · ${formatCurrency(unit.base_price)}` : ''}
                </option>
              ))}
            </Select>
          </div>
        </FormField>

        {canAssignLeads && (
          <FormField
            label="Assigned to"
            htmlFor="visit-assignee"
            error={errors.assigned_to?.message}
            hint="Leave blank to assign yourself."
          >
            <Select id="visit-assignee" {...register('assigned_to')}>
              <option value="">Me</option>
              {assigneeOptions.map((member) => (
                <option key={member.id} value={member.id}>
                  {member.name} · {member.role}
                  {member.team ? ` · ${member.team}` : ''}
                </option>
              ))}
            </Select>
          </FormField>
        )}

        <FormField label="Notes" htmlFor="visit-notes" error={errors.notes?.message}>
          <Textarea id="visit-notes" rows={3} placeholder="Pickup point, customer preferences, access details…" {...register('notes')} />
        </FormField>
      </form>
    </Modal>
  )
}
