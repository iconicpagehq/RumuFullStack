import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { realtorApi } from '@/api/realtor'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { LEAD_PRIORITIES, TASK_STATUSES } from '@/utils/enterprise'
import { formatPhone } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const toDateTimeInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

export const TaskFormModal = ({ open, onClose, task = null, defaultLead = null, lockLead = false }) => {
  const queryClient = useQueryClient()
  const isEdit = Boolean(task?.id)
  const [form, setForm] = useState({
    title: '',
    description: '',
    priority: 'medium',
    due_at: '',
    lead_id: '',
    status: 'pending',
  })
  const [error, setError] = useState('')

  useEffect(() => {
    if (!open) return
    setForm({
      title: task?.title || '',
      description: task?.description || '',
      priority: task?.priority || 'medium',
      due_at: toDateTimeInput(task?.due_at),
      lead_id: task?.lead?.id
        ? String(task.lead.id)
        : defaultLead?.id
          ? String(defaultLead.id)
          : '',
      status: task?.status || 'pending',
    })
    setError('')
  }, [open, task, defaultLead])

  const leadsQuery = useQuery({
    queryKey: ['realtor', 'lead-options'],
    queryFn: async () => {
      const response = await realtorApi.leads.all({ per_page: 200 })
      return response.data?.data || []
    },
    enabled: open && !lockLead,
    staleTime: 30_000,
  })

  const mutation = useMutation({
    mutationFn: (payload) =>
      isEdit ? realtorApi.tasks.update(task.id, payload) : realtorApi.tasks.create(payload),
    onSuccess: () => {
      toast.success(isEdit ? 'Follow-up updated' : 'Follow-up created')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'tasks'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'lead-tasks'] })
      queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
      onClose()
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not save this follow-up.')),
  })

  const submit = (event) => {
    event.preventDefault()
    if (!form.title.trim()) {
      setError('Follow-up title is required.')
      return
    }
    setError('')
    mutation.mutate({
      title: form.title.trim(),
      description: form.description || null,
      priority: form.priority,
      due_at: form.due_at ? new Date(form.due_at).toISOString() : null,
      lead_id: lockLead ? defaultLead?.id || null : form.lead_id ? Number(form.lead_id) : null,
      status: form.status,
    })
  }

  const update = (key) => (event) => setForm((previous) => ({ ...previous, [key]: event.target.value }))

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit follow-up' : 'New follow-up'}
      description={
        isEdit
          ? 'Update this follow-up.'
          : defaultLead
            ? `Schedule the next touchpoint for ${defaultLead.name}.`
            : 'Keep every follow-up on track.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={submit} loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create follow-up'}
          </Button>
        </>
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {error && (
          <p className="rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </p>
        )}
        <FormField label="Title" htmlFor="realtor-task-title" required>
          <Input
            id="realtor-task-title"
            value={form.title}
            onChange={update('title')}
            placeholder="Call back about the 3 BHK site visit"
            maxLength={255}
            autoFocus
          />
        </FormField>
        <FormField label="Description" htmlFor="realtor-task-description">
          <Textarea
            id="realtor-task-description"
            value={form.description}
            onChange={update('description')}
            placeholder="Add context for this follow-up…"
            rows={3}
          />
        </FormField>
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Priority" htmlFor="realtor-task-priority">
            <Select id="realtor-task-priority" value={form.priority} onChange={update('priority')}>
              {LEAD_PRIORITIES.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Status" htmlFor="realtor-task-status">
            <Select id="realtor-task-status" value={form.status} onChange={update('status')}>
              {TASK_STATUSES.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Due date" htmlFor="realtor-task-due" hint="Optional deadline for this task.">
            <Input id="realtor-task-due" type="datetime-local" value={form.due_at} onChange={update('due_at')} />
          </FormField>
          {lockLead ? (
            <FormField label="Linked lead">
              <p className="flex h-10 items-center rounded-lg border border-border bg-muted/40 px-3 text-sm text-foreground">
                {defaultLead?.name || 'This lead'}
              </p>
            </FormField>
          ) : (
            <FormField label="Linked lead" htmlFor="realtor-task-lead">
              <Select id="realtor-task-lead" value={form.lead_id} onChange={update('lead_id')}>
                <option value="">No lead</option>
                {(leadsQuery.data || []).map((lead) => (
                  <option key={lead.id} value={lead.id}>
                    {lead.name}
                    {lead.phone ? ` · ${formatPhone(lead.phone)}` : ''}
                  </option>
                ))}
              </Select>
            </FormField>
          )}
        </div>
      </form>
    </Modal>
  )
}
