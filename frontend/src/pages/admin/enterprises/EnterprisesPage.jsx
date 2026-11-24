import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { BuildingOffice2Icon, NoSymbolIcon, PlayIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const EnterprisesPage = () => {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [actionTarget, setActionTarget] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'enterprises', { search, status, page }],
    queryFn: async () => {
      const response = await adminApi.enterprises.list({
        search: search || undefined,
        status: status || undefined,
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

  const actionMutation = useMutation({
    mutationFn: ({ enterprise, action }) =>
      action === 'suspend'
        ? adminApi.enterprises.suspend(enterprise.id)
        : adminApi.enterprises.resume(enterprise.id),
    onSuccess: (response, variables) => {
      toast.success(response.data?.message || `Enterprise ${variables.action === 'suspend' ? 'suspended' : 'resumed'}`)
      setActionTarget(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'enterprises'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the enterprise.')),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const columns = [
    {
      key: 'name',
      header: 'Enterprise',
      render: (enterprise) => (
        <div className="flex min-w-[200px] items-center gap-3">
          {enterprise.logo_url ? (
            <img
              src={enterprise.logo_url}
              alt=""
              className="h-9 w-9 shrink-0 rounded-lg border border-border object-cover"
            />
          ) : (
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-highlight">
              <BuildingOffice2Icon className="h-4 w-4" />
            </span>
          )}
          <div className="min-w-0">
            <Link
              to={`/admin/enterprises/${enterprise.id}`}
              className="truncate text-sm font-semibold text-foreground hover:text-highlight"
            >
              {enterprise.name}
            </Link>
            <p className="truncate text-xs text-muted-foreground">{enterprise.slug}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'owner',
      header: 'Owner',
      render: (enterprise) => (
        <div className="min-w-[160px]">
          <p className="truncate text-sm text-foreground">{enterprise.owner?.name || '—'}</p>
          <p className="truncate text-xs text-muted-foreground">{enterprise.owner?.email || ''}</p>
        </div>
      ),
    },
    {
      key: 'plan',
      header: 'Plan',
      render: (enterprise) =>
        enterprise.plan ? (
          <Badge variant="primary">{enterprise.plan.name}</Badge>
        ) : (
          <span className="text-xs text-muted-foreground">No plan</span>
        ),
    },
    {
      key: 'members',
      header: 'Members',
      render: (enterprise) => (
        <div className="whitespace-nowrap text-xs">
          <span className="font-semibold text-foreground">{formatNumber(enterprise.active_members || 0)}</span>
          <span className="text-muted-foreground">
            {' '}
            · {formatNumber(enterprise.managers || 0)} mgr · {formatNumber(enterprise.employees || 0)} emp
          </span>
        </div>
      ),
    },
    {
      key: 'teams',
      header: 'Teams',
      render: (enterprise) => (
        <span className="text-sm tabular-nums text-foreground">{formatNumber(enterprise.teams || 0)}</span>
      ),
    },
    {
      key: 'leads',
      header: 'Leads',
      render: (enterprise) => (
        <div className="whitespace-nowrap text-xs">
          <span className="font-semibold text-foreground">{formatNumber(enterprise.leads || 0)}</span>
          <span className="text-muted-foreground"> · {formatNumber(enterprise.leads_this_month || 0)} this month</span>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (enterprise) => <StatusBadge status={enterprise.status} />,
    },
    {
      key: 'created_at',
      header: 'Created',
      render: (enterprise) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(enterprise.created_at)}
        </span>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (enterprise) => (
        <div className="flex items-center justify-end gap-1">
          <Button
            size="sm"
            variant="outline"
            onClick={() => navigate(`/admin/enterprises/${enterprise.id}`)}
          >
            View
          </Button>
          {enterprise.status === 'suspended' ? (
            <Button size="sm" variant="ghost" onClick={() => setActionTarget({ enterprise, action: 'resume' })}>
              <PlayIcon className="h-4 w-4" />
              Resume
            </Button>
          ) : (
            <Button
              size="sm"
              variant="ghost"
              className="text-destructive hover:bg-destructive/10 hover:text-destructive"
              onClick={() => setActionTarget({ enterprise, action: 'suspend' })}
            >
              <NoSymbolIcon className="h-4 w-4" />
              Suspend
            </Button>
          )}
        </div>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Enterprise accounts"
        description="Every enterprise workspace on the platform, their plans and usage."
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-wrap items-center gap-3">
              <SearchInput
                value={search}
                onChange={(value) => {
                  setSearch(value)
                  setPage(1)
                }}
                placeholder="Search name, email or slug…"
                className="w-full sm:w-72"
              />
              <Select
                value={status}
                onChange={(event) => {
                  setStatus(event.target.value)
                  setPage(1)
                }}
                className="w-40"
              >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </Select>
            </div>
            <span className="text-xs text-muted-foreground">
              {formatNumber(data?.meta?.total || 0)} enterprises
            </span>
          </div>

          <div className="border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={data?.rows || []}
              loading={isLoading}
              rowKey="id"
              onRowClick={(enterprise) => navigate(`/admin/enterprises/${enterprise.id}`)}
              emptyState={
                <EmptyState
                  icon={BuildingOffice2Icon}
                  title="No enterprise accounts"
                  description={
                    search || status
                      ? 'Try a different search or status filter.'
                      : 'Enterprise workspaces will appear here after registration.'
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

      <ConfirmDialog
        open={Boolean(actionTarget)}
        onClose={() => setActionTarget(null)}
        onConfirm={() => actionMutation.mutate(actionTarget)}
        loading={actionMutation.isPending}
        title={
          actionTarget?.action === 'suspend'
            ? `Suspend ${actionTarget?.enterprise?.name}?`
            : `Resume ${actionTarget?.enterprise?.name}?`
        }
        description={
          actionTarget?.action === 'suspend'
            ? 'All enterprise members will be blocked from signing in and using the workspace. Data is preserved and the account can be resumed later.'
            : 'Enterprise members will regain access to their workspace immediately.'
        }
        confirmLabel={actionTarget?.action === 'suspend' ? 'Suspend enterprise' : 'Resume enterprise'}
        variant={actionTarget?.action === 'suspend' ? 'danger' : 'primary'}
      />
    </div>
  )
}
