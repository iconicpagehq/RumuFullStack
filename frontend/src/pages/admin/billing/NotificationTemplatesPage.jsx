import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { DocumentDuplicateIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { PageLoader } from '@/components/ui/Spinner'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { BILLING_NOTIFICATION_TYPES } from '@/utils/constants'
import { formatDate } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const emptyTemplate = {
  name: '',
  type: 'subscription_activated',
  subject: '',
  body_content: '',
  is_active: true,
}

const TemplateModal = ({ template, onClose, onSaved }) => {
  const isEdit = Boolean(template)
  const [form, setForm] = useState(() => ({
    name: template?.name || '',
    type: template?.type || 'subscription_activated',
    subject: template?.subject || '',
    body_content: template?.body_content || '',
    is_active: template ? Boolean(template.is_active) : true,
  }))
  const [errors, setErrors] = useState({})
  const [saving, setSaving] = useState(false)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const submit = async (event) => {
    event.preventDefault()
    const nextErrors = {}
    if (!form.name.trim()) nextErrors.name = 'Template name is required.'
    if (!form.subject.trim()) nextErrors.subject = 'Subject is required.'
    if (!form.body_content.trim()) nextErrors.body_content = 'Body content is required.'
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    setSaving(true)
    try {
      const payload = {
        ...form,
        name: form.name.trim(),
        subject: form.subject.trim(),
      }
      if (isEdit) await adminApi.billing.notificationTemplates.update(template.id, payload)
      else await adminApi.billing.notificationTemplates.create(payload)
      toast.success(isEdit ? 'Template updated' : 'Template created')
      onSaved()
    } catch (error) {
      setErrors(serverFieldErrors(error))
      toast.error(getErrorMessage(error, 'Could not save the template.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="lg"
      title={isEdit ? `Edit ${template.name}` : 'New template'}
      description="Use placeholders like {{user_name}} and {{invoice_number}} in the content."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={saving}>
            Cancel
          </Button>
          <Button type="submit" form="template-form" loading={saving}>
            {isEdit ? 'Save changes' : 'Create template'}
          </Button>
        </>
      }
    >
      <form id="template-form" onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Template name" htmlFor="template-name" required error={errors.name}>
            <Input
              id="template-name"
              value={form.name}
              onChange={(event) => set('name', event.target.value)}
              invalid={Boolean(errors.name)}
            />
          </FormField>
          <FormField label="Notification type" htmlFor="template-type" required error={errors.type}>
            <Select id="template-type" value={form.type} onChange={(event) => set('type', event.target.value)}>
              {BILLING_NOTIFICATION_TYPES.map((type) => (
                <option key={type.value} value={type.value}>
                  {type.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <FormField label="Email subject" htmlFor="template-subject" required error={errors.subject}>
          <Input
            id="template-subject"
            value={form.subject}
            onChange={(event) => set('subject', event.target.value)}
            invalid={Boolean(errors.subject)}
          />
        </FormField>

        <FormField label="Body content" htmlFor="template-body" required error={errors.body_content}>
          <Textarea
            id="template-body"
            rows={8}
            value={form.body_content}
            onChange={(event) => set('body_content', event.target.value)}
            invalid={Boolean(errors.body_content)}
            className="font-mono text-xs"
          />
        </FormField>

        <label className="flex items-center gap-2.5 text-sm text-foreground">
          <input
            type="checkbox"
            checked={form.is_active}
            onChange={(event) => set('is_active', event.target.checked)}
            className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
          />
          Template is active
        </label>
      </form>
    </Modal>
  )
}

export const NotificationTemplatesPage = () => {
  const queryClient = useQueryClient()
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'billing', 'notification-templates'],
    queryFn: async () => {
      const response = await adminApi.billing.notificationTemplates.list()
      return response.data?.data || []
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => adminApi.billing.notificationTemplates.remove(deleting.id),
    onSuccess: () => {
      toast.success('Template deleted')
      setDeleting(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'notification-templates'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the template.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const templates = data || []

  const columns = [
    {
      key: 'name',
      header: 'Template',
      render: (row) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{row.name}</p>
          <p className="text-xs text-muted-foreground">{row.type}</p>
        </div>
      ),
    },
    {
      key: 'subject',
      header: 'Subject',
      render: (row) => <span className="block max-w-[260px] truncate text-sm">{row.subject}</span>,
    },
    {
      key: 'updated_at',
      header: 'Updated',
      render: (row) => <span className="text-xs text-muted-foreground">{formatDate(row.updated_at)}</span>,
    },
    {
      key: 'is_active',
      header: 'Status',
      render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (row) => (
        <div className="flex justify-end gap-1">
          <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Edit template"
            onClick={() => {
              setEditing(row)
              setModalOpen(true)
            }}
          >
            <PencilSquareIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Delete template"
            onClick={() => setDeleting(row)}
          >
            <TrashIcon className="h-4 w-4 text-destructive" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Notification templates"
        description="Email templates used for billing events."
        actions={
          <Button
            size="sm"
            onClick={() => {
              setEditing(null)
              setModalOpen(true)
            }}
          >
            <PlusIcon className="h-4 w-4" />
            New template
          </Button>
        }
      />

      <Card>
        <DataTable
          columns={columns}
          rows={templates}
          emptyState={
            <EmptyState
              icon={DocumentDuplicateIcon}
              title="No templates yet"
              description="Create a template to control billing emails."
              action={
                <Button
                  size="sm"
                  onClick={() => {
                    setEditing(null)
                    setModalOpen(true)
                  }}
                >
                  <PlusIcon className="h-4 w-4" />
                  New template
                </Button>
              }
            />
          }
        />
      </Card>

      {modalOpen && (
        <TemplateModal
          template={editing}
          onClose={() => setModalOpen(false)}
          onSaved={() => {
            setModalOpen(false)
            queryClient.invalidateQueries({ queryKey: ['admin', 'billing', 'notification-templates'] })
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete ${deleting?.name || 'template'}?`}
        description="Billing events that relied on this template will use the default content."
        confirmLabel="Delete template"
      />
    </div>
  )
}
