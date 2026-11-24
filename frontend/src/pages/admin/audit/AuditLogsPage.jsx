import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { ShieldCheckIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'

const actionTone = (action = '') => {
  if (/(suspend|reject|delete|remove)/.test(action)) return 'danger'
  if (/(resume|approve|create|sent|connect)/.test(action)) return 'success'
  if (/(update|reorder|toggle|mapping|capi)/.test(action)) return 'info'
  return 'neutral'
}

export const AuditLogsPage = () => {
  const [search, setSearch] = useState('')
  const [action, setAction] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [page, setPage] = useState(1)
  const [selected, setSelected] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'audit-logs', { search, action, from, to, page }],
    queryFn: async () => {
      const response = await adminApi.auditLogs({
        search: search || undefined,
        action: action || undefined,
        from: from || undefined,
        to: to || undefined,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'created_at',
      header: 'When',
      render: (log) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(log.created_at)}
        </span>
      ),
    },
    {
      key: 'admin',
      header: 'Admin',
      render: (log) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-foreground">{log.admin?.name || 'System'}</p>
          <p className="truncate text-xs text-muted-foreground">{log.admin?.email || '—'}</p>
        </div>
      ),
    },
    {
      key: 'action',
      header: 'Action',
      render: (log) => <Badge variant={actionTone(log.action)}>{log.action}</Badge>,
    },
    {
      key: 'description',
      header: 'Description',
      render: (log) => (
        <span className="block max-w-[320px] truncate text-sm text-foreground" title={log.description || ''}>
          {log.description || '—'}
        </span>
      ),
    },
    {
      key: 'subject',
      header: 'Subject',
      render: (log) =>
        log.subject_type ? (
          <span className="text-xs text-muted-foreground">
            {log.subject_type.split('\\').pop()} #{log.subject_id}
          </span>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'ip_address',
      header: 'IP',
      render: (log) => (
        <span className="font-mono text-[11px] text-muted-foreground">{log.ip_address || '—'}</span>
      ),
    },
    {
      key: 'details',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (log) => (
        <Button size="sm" variant="ghost" onClick={() => setSelected(log)}>
          Details
        </Button>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Platform audit log"
        description="Administrative actions taken across the platform, newest first."
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 pb-4">
            <div className="flex flex-wrap items-center gap-3">
              <SearchInput
                value={search}
                onChange={(value) => {
                  setSearch(value)
                  setPage(1)
                }}
                placeholder="Search action, description or subject…"
                className="w-full sm:w-72"
              />
              <Input
                value={action}
                onChange={(event) => {
                  setAction(event.target.value)
                  setPage(1)
                }}
                placeholder="Filter by action e.g. enterprise.suspended"
                className="w-full sm:w-72"
              />
              <span className="ml-auto text-xs text-muted-foreground">
                {formatNumber(data?.meta?.total || 0)} events
              </span>
            </div>
            <div className="flex flex-wrap items-center gap-3">
              <label className="flex items-center gap-2 text-xs text-muted-foreground">
                From
                <Input
                  type="date"
                  value={from}
                  onChange={(event) => {
                    setFrom(event.target.value)
                    setPage(1)
                  }}
                  className="w-40"
                />
              </label>
              <label className="flex items-center gap-2 text-xs text-muted-foreground">
                To
                <Input
                  type="date"
                  value={to}
                  onChange={(event) => {
                    setTo(event.target.value)
                    setPage(1)
                  }}
                  className="w-40"
                />
              </label>
              {(search || action || from || to) && (
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => {
                    setSearch('')
                    setAction('')
                    setFrom('')
                    setTo('')
                    setPage(1)
                  }}
                >
                  Clear filters
                </Button>
              )}
            </div>
          </div>

          <div className="border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={data?.rows || []}
              loading={isLoading}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={ShieldCheckIcon}
                  title="No audit events"
                  description={
                    search || action || from || to
                      ? 'Try loosening the filters.'
                      : 'Platform administrative actions will be recorded here.'
                  }
                />
              }
            />
          </div>

          <div className="border-t border-border px-4">
            <Pagination
              page={data?.meta?.current_page || 1}
              lastPage={data?.meta?.last_page || 1}
              total={data?.meta?.total || 0}
              perPage={data?.meta?.per_page || PAGE_SIZE}
              onPageChange={setPage}
            />
          </div>
        </CardContent>
      </Card>

      <Modal
        open={Boolean(selected)}
        onClose={() => setSelected(null)}
        title={selected?.action || 'Audit event'}
        description={selected?.description || undefined}
        size="lg"
      >
        {selected && (
          <div className="space-y-3 text-sm">
            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <p className="text-xs text-muted-foreground">Admin</p>
                <p className="mt-0.5 text-foreground">
                  {selected.admin?.name || 'System'} {selected.admin?.email ? `· ${selected.admin.email}` : ''}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">When</p>
                <p className="mt-0.5 text-foreground">{formatDateTime(selected.created_at)}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Subject</p>
                <p className="mt-0.5 text-foreground">
                  {selected.subject_type
                    ? `${selected.subject_type.split('\\').pop()} #${selected.subject_id}`
                    : '—'}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">IP address</p>
                <p className="mt-0.5 font-mono text-xs text-foreground">{selected.ip_address || '—'}</p>
              </div>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Metadata</p>
              <pre className="mt-1 max-h-80 overflow-auto rounded-lg border border-border bg-muted/30 p-3 text-xs text-foreground">
                {selected.meta ? JSON.stringify(selected.meta, null, 2) : 'No metadata recorded.'}
              </pre>
            </div>
          </div>
        )}
      </Modal>
    </div>
  )
}
