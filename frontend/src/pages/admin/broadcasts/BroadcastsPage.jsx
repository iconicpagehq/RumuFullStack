import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { MegaphoneIcon, PaperAirplaneIcon, UsersIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const AUDIENCES = [
  { value: 'all_realtors', label: 'All realtors', hint: 'Every active realtor account.' },
  {
    value: 'all_enterprise_admins',
    label: 'Enterprise admins',
    hint: 'Admins of every active enterprise workspace.',
  },
  {
    value: 'all_enterprise_members',
    label: 'All enterprise members',
    hint: 'Admins, managers and employees of active enterprises.',
  },
  {
    value: 'enterprise',
    label: 'A specific enterprise',
    hint: 'All active members of one enterprise workspace.',
  },
]

const emptyForm = { title: '', message: '', audience: 'all_realtors', enterprise_id: '' }

export const BroadcastsPage = () => {
  const queryClient = useQueryClient()
  const [form, setForm] = useState(emptyForm)
  const [recipients, setRecipients] = useState(null)
  const [error, setError] = useState('')
  const [page, setPage] = useState(1)

  const historyQuery = useQuery({
    queryKey: ['admin', 'broadcasts', page],
    queryFn: async () => {
      const response = await adminApi.broadcasts.list({ page })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const enterprisesQuery = useQuery({
    queryKey: ['admin', 'enterprises', 'broadcast-options'],
    queryFn: async () => {
      const response = await adminApi.enterprises.list({ per_page: 100 })
      return response.data?.data || []
    },
    enabled: form.audience === 'enterprise',
  })

  const set = (key, value) => {
    setForm((current) => ({ ...current, [key]: value }))
    setRecipients(null)
    setError('')
  }

  const buildPayload = () => {
    const payload = {
      title: form.title.trim(),
      message: form.message.trim(),
      audience: form.audience,
    }
    if (form.audience === 'enterprise') payload.enterprise_id = Number(form.enterprise_id)
    return payload
  }

  const validate = () => {
    if (!form.title.trim()) {
      setError('A title is required.')
      return false
    }
    if (!form.message.trim()) {
      setError('A message is required.')
      return false
    }
    if (form.audience === 'enterprise' && !form.enterprise_id) {
      setError('Select the enterprise that should receive this broadcast.')
      return false
    }
    return true
  }

  const previewMutation = useMutation({
    mutationFn: () => adminApi.broadcasts.preview(buildPayload()),
    onSuccess: (response) => {
      setRecipients(response.data?.data?.recipients_count ?? 0)
      setError('')
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not preview the audience.')),
  })

  const sendMutation = useMutation({
    mutationFn: () => adminApi.broadcasts.create(buildPayload()),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Broadcast queued')
      setForm(emptyForm)
      setRecipients(null)
      setError('')
      queryClient.invalidateQueries({ queryKey: ['admin', 'broadcasts'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'audit-logs'] })
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not send the broadcast.')),
  })

  const submit = (event) => {
    event.preventDefault()
    if (!validate()) return
    sendMutation.mutate()
  }

  const columns = [
    {
      key: 'title',
      header: 'Broadcast',
      render: (broadcast) => (
        <div className="min-w-0 max-w-[320px]">
          <p className="truncate text-sm font-semibold text-foreground">{broadcast.title}</p>
          <p className="truncate text-xs text-muted-foreground" title={broadcast.message}>
            {broadcast.message}
          </p>
        </div>
      ),
    },
    {
      key: 'audience',
      header: 'Audience',
      render: (broadcast) => (
        <div className="min-w-0">
          <Badge variant="primary">
            {AUDIENCES.find((item) => item.value === broadcast.audience)?.label || broadcast.audience}
          </Badge>
          {broadcast.enterprise && (
            <p className="mt-1 truncate text-xs text-muted-foreground">{broadcast.enterprise.name}</p>
          )}
        </div>
      ),
    },
    {
      key: 'recipients_count',
      header: 'Recipients',
      render: (broadcast) => (
        <span className="text-sm tabular-nums text-foreground">
          {formatNumber(broadcast.recipients_count || 0)}
        </span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (broadcast) => <StatusBadge status={broadcast.status} />,
    },
    {
      key: 'admin',
      header: 'Sent by',
      render: (broadcast) => (
        <span className="text-xs text-foreground">{broadcast.admin || '—'}</span>
      ),
    },
    {
      key: 'created_at',
      header: 'Queued',
      render: (broadcast) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(broadcast.created_at)}
        </span>
      ),
    },
    {
      key: 'sent_at',
      header: 'Sent',
      render: (broadcast) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {broadcast.sent_at ? formatDateTime(broadcast.sent_at) : '—'}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Broadcast notifications"
        description="Send in-app announcements to realtors or enterprise workspaces."
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle>New broadcast</CardTitle>
            <CardDescription>
              Recipients receive an in-app notification. Delivery is queued.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={submit} className="space-y-4">
              {error && <p className="rounded-lg bg-destructive/10 px-3 py-2 text-xs text-destructive">{error}</p>}
              <FormField label="Title" htmlFor="broadcast-title" required>
                <Input
                  id="broadcast-title"
                  value={form.title}
                  maxLength={150}
                  onChange={(event) => set('title', event.target.value)}
                  placeholder="e.g. New reporting features are live"
                />
              </FormField>
              <FormField label="Message" htmlFor="broadcast-message" required>
                <Textarea
                  id="broadcast-message"
                  value={form.message}
                  rows={5}
                  maxLength={5000}
                  onChange={(event) => set('message', event.target.value)}
                  placeholder="Write the announcement shown in the notification."
                />
              </FormField>
              <FormField label="Audience" htmlFor="broadcast-audience" required>
                <Select
                  id="broadcast-audience"
                  value={form.audience}
                  onChange={(event) => set('audience', event.target.value)}
                >
                  {AUDIENCES.map((audience) => (
                    <option key={audience.value} value={audience.value}>
                      {audience.label}
                    </option>
                  ))}
                </Select>
              </FormField>
              <p className="-mt-2 text-xs text-muted-foreground">
                {AUDIENCES.find((item) => item.value === form.audience)?.hint}
              </p>
              {form.audience === 'enterprise' && (
                <FormField label="Enterprise" htmlFor="broadcast-enterprise" required>
                  <Select
                    id="broadcast-enterprise"
                    value={form.enterprise_id}
                    onChange={(event) => set('enterprise_id', event.target.value)}
                    disabled={enterprisesQuery.isLoading}
                  >
                    <option value="">
                      {enterprisesQuery.isLoading ? 'Loading enterprises…' : 'Select an enterprise'}
                    </option>
                    {(enterprisesQuery.data || []).map((enterprise) => (
                      <option key={enterprise.id} value={enterprise.id}>
                        {enterprise.name} ({enterprise.status})
                      </option>
                    ))}
                  </Select>
                </FormField>
              )}

              <div className="flex items-center justify-between gap-3 border-t border-border pt-4">
                <div className="text-xs text-muted-foreground">
                  {recipients === null ? (
                    <span className="inline-flex items-center gap-1.5">
                      <UsersIcon className="h-4 w-4" />
                      Preview the audience first
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1.5 text-foreground">
                      <UsersIcon className="h-4 w-4" />
                      {formatNumber(recipients)} recipient{recipients === 1 ? '' : 's'}
                    </span>
                  )}
                </div>
                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      if (!validate()) return
                      previewMutation.mutate()
                    }}
                    loading={previewMutation.isPending}
                  >
                    Preview
                  </Button>
                  <Button
                    type="submit"
                    size="sm"
                    loading={sendMutation.isPending}
                    disabled={recipients === 0}
                  >
                    <PaperAirplaneIcon className="h-4 w-4" />
                    Send
                  </Button>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>History</CardTitle>
            <CardDescription>Queued and delivered platform broadcasts.</CardDescription>
          </CardHeader>
          <CardContent className="px-0 pb-0">
            <DataTable
              className="rounded-none border-x-0 border-b-0"
              columns={columns}
              rows={historyQuery.data?.rows || []}
              loading={historyQuery.isLoading}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={MegaphoneIcon}
                  title="No broadcasts yet"
                  description="Send your first announcement to see it here."
                />
              }
            />
            <div className="border-t border-border px-4">
              <Pagination
                page={historyQuery.data?.meta?.current_page || 1}
                lastPage={historyQuery.data?.meta?.last_page || 1}
                total={historyQuery.data?.meta?.total || 0}
                perPage={historyQuery.data?.meta?.per_page || 20}
                onPageChange={setPage}
              />
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
