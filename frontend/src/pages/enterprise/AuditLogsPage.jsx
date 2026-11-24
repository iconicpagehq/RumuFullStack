import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import {
  ShieldCheckIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Input, Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { formatDateTime, titleCase } from '@/utils/formatters'

const ACTION_GROUPS = [
  { value: '', label: 'All actions' },
  { value: 'lead.', label: 'Leads' },
  { value: 'task.', label: 'Tasks' },
  { value: 'site_visit.', label: 'Site visits' },
  { value: 'inventory.', label: 'Inventory' },
  { value: 'target.', label: 'Targets' },
  { value: 'campaign.', label: 'Campaigns' },
  { value: 'team.', label: 'Teams' },
  { value: 'member.', label: 'Members' },
  { value: 'billing.', label: 'Billing' },
  { value: 'enterprise.', label: 'Enterprise' },
  { value: 'profile.', label: 'Profiles' },
]

const PER_PAGE = 30

const subjectLabel = (type) => {
  if (!type) return '—'
  const parts = String(type).split('\\')
  return titleCase(parts[parts.length - 1])
}

export const AuditLogsPage = () => {
  const { isAdmin } = useEnterprise()
  const [search, setSearch] = useState('')
  const [action, setAction] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, action, from, to])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'audit-logs', { search: debouncedSearch, action, from, to, page }],
    queryFn: async () => {
      const response = await enterpriseApi.auditLogs({
        search: debouncedSearch || undefined,
        action: action || undefined,
        from: from || undefined,
        to: to ? `${to} 23:59:59` : undefined,
        page,
        per_page: PER_PAGE,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
    enabled: isAdmin,
  })

  if (!isAdmin) {
    return (
      <div>
        <PageHeader title="Audit log" description="Workspace security activity." />
        <EmptyState
          icon={ShieldCheckIcon}
          title="Admin access required"
          description="Only company administrators can review the enterprise audit log."
        />
      </div>
    )
  }

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const rows = data?.rows || []
  const meta = data?.meta || {}
  const hasFilters = Boolean(search || action || from || to)

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
      key: 'actor',
      header: 'Actor',
      render: (log) => (
        <div className="flex min-w-0 items-center gap-2.5">
          <Avatar name={log.user?.name || 'System'} size="xs" />
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-foreground">{log.user?.name || 'System'}</p>
            <p className="truncate text-[11px] text-muted-foreground">{log.user?.email || 'Automated'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'action',
      header: 'Action',
      render: (log) => (
        <div>
          <Badge variant="outline">{titleCase(log.action)}</Badge>
          {log.description && (
            <p className="mt-1 max-w-xs truncate text-xs text-muted-foreground">{log.description}</p>
          )}
        </div>
      ),
    },
    {
      key: 'subject',
      header: 'Subject',
      render: (log) => (
        <div className="text-xs">
          <p className="text-foreground">{subjectLabel(log.subject_type)}</p>
          <p className="text-muted-foreground">#{log.subject_id ?? '—'}</p>
        </div>
      ),
    },
    {
      key: 'ip',
      header: 'IP address',
      render: (log) => (
        <span className="whitespace-nowrap font-mono text-[11px] text-muted-foreground">
          {log.ip_address || '—'}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Audit log"
        description="Immutable record of workspace changes, member actions and inventory movements."
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 xl:flex-row xl:items-center xl:justify-between">
            <div className="flex flex-wrap items-center gap-2">
              <Select
                value={action}
                onChange={(event) => setAction(event.target.value)}
                className="w-44"
                aria-label="Filter by action group"
              >
                {ACTION_GROUPS.map((group) => (
                  <option key={group.value} value={group.value}>
                    {group.label}
                  </option>
                ))}
              </Select>
              <div className="flex items-center gap-2">
                <Input
                  type="date"
                  value={from}
                  onChange={(event) => setFrom(event.target.value)}
                  className="w-40"
                  aria-label="From date"
                />
                <span className="text-xs text-muted-foreground">to</span>
                <Input
                  type="date"
                  value={to}
                  onChange={(event) => setTo(event.target.value)}
                  className="w-40"
                  aria-label="To date"
                />
              </div>
              {hasFilters && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setSearch('')
                    setAction('')
                    setFrom('')
                    setTo('')
                  }}
                >
                  Clear filters
                </Button>
              )}
            </div>
            <div className="flex items-center gap-3">
              <p className="hidden text-xs text-muted-foreground sm:block">
                {meta.total ?? 0} event{meta.total === 1 ? '' : 's'}
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
              <SearchInput
                value={search}
                onChange={setSearch}
                placeholder="Search action or description…"
                className="w-full sm:w-72"
              />
            </div>
          </div>

          <div className="mt-4 border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={rows}
              loading={isLoading}
              emptyState={
                <EmptyState
                  icon={ShieldCheckIcon}
                  title={hasFilters ? 'No matching events' : 'No audit events yet'}
                  description={
                    hasFilters
                      ? 'Try a different action group, date range or search term.'
                      : 'Workspace activity will be recorded here as your team works.'
                  }
                />
              }
            />
          </div>

          <div className="border-t border-border px-4">
            <Pagination
              page={meta.current_page || 1}
              lastPage={meta.last_page || 1}
              total={meta.total || 0}
              perPage={meta.per_page || PER_PAGE}
              onPageChange={setPage}
            />
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
