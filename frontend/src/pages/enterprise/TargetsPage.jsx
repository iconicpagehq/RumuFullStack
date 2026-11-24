import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowTrendingUpIcon,
  ChartBarIcon,
  CheckBadgeIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Input } from '@/components/ui/Input'
import { TargetFormModal } from '@/components/enterprise/TargetFormModal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { formatCurrency, formatNumber, formatPercent } from '@/utils/formatters'
import { metricFormat, metricLabel } from '@/utils/enterprise'
import { getErrorMessage } from '@/utils/errors'

const currentMonth = () => {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

const shiftMonth = (value, delta) => {
  const [year, month] = value.split('-').map(Number)
  const date = new Date(year, month - 1 + delta, 1)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
}

const formatValue = (value, metric) =>
  metricFormat(metric) === 'currency' ? formatCurrency(value) : formatNumber(value)

const progressTone = (percentage) => {
  if (percentage === null || percentage === undefined) return 'bg-muted-foreground/30'
  if (percentage >= 100) return 'bg-emerald-500'
  if (percentage >= 75) return 'bg-primary'
  if (percentage >= 40) return 'bg-violet-500'
  return 'bg-rose-500'
}

export const TargetsPage = () => {
  const queryClient = useQueryClient()
  const { canManageTargets, isEmployee } = useEnterprise()
  const [month, setMonth] = useState(currentMonth)
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'targets', month],
    queryFn: async () => {
      const response = await enterpriseApi.targets.list({ month })
      return response.data?.data || { targets: [], month }
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (target) => enterpriseApi.targets.remove(target.id),
    onSuccess: () => {
      toast.success('Target deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'targets'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      setDeleteTarget(null)
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this target.'))
    },
  })

  const targets = data?.targets || []
  const stats = useMemo(() => {
    const percentages = targets
      .map((target) => target.achievement_percentage)
      .filter((value) => value !== null && value !== undefined)
    return {
      total: targets.length,
      achieved: percentages.filter((value) => value >= 100).length,
      average: percentages.length
        ? percentages.reduce((sum, value) => sum + Number(value), 0) / percentages.length
        : null,
      team: targets.filter((target) => target.team).length,
    }
  }, [targets])

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const hasRevenueTarget = targets.some((target) => target.metric === 'revenue_received')

  const columns = [
    {
      key: 'metric',
      header: 'Metric',
      render: (target) => (
        <div className="min-w-0">
          <p className="text-sm font-semibold text-foreground">{metricLabel(target.metric)}</p>
          <p className="text-[11px] text-muted-foreground">{target.period_month}</p>
        </div>
      ),
    },
    {
      key: 'scope',
      header: 'Scope',
      render: (target) => (
        <div className="min-w-0 text-xs">
          <p className="truncate font-medium text-foreground">
            {target.user?.name || target.team?.name || 'Company-wide'}
          </p>
          <p className="text-muted-foreground">
            {target.user ? 'Team member' : target.team ? 'Team' : 'Enterprise'}
          </p>
        </div>
      ),
    },
    {
      key: 'target_value',
      header: 'Target',
      render: (target) => (
        <span className="whitespace-nowrap text-sm font-medium text-foreground">
          {formatValue(target.target_value, target.metric)}
        </span>
      ),
    },
    {
      key: 'actual_value',
      header: 'Actual',
      render: (target) => (
        <span className="whitespace-nowrap text-sm text-foreground">
          {formatValue(target.actual_value || 0, target.metric)}
        </span>
      ),
    },
    {
      key: 'progress',
      header: 'Progress',
      render: (target) => {
        const percentage = target.achievement_percentage
        const width = percentage === null || percentage === undefined ? 0 : Math.min(100, percentage)
        return (
          <div className="min-w-[140px]">
            <div className="flex items-center justify-between gap-2 text-[11px]">
              <span className="font-medium text-foreground">
                {percentage === null || percentage === undefined ? '—' : formatPercent(percentage)}
              </span>
              {percentage !== null && percentage !== undefined && percentage >= 100 && (
                <span className="text-emerald-600 dark:text-emerald-400">Achieved</span>
              )}
            </div>
            <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
              <div
                className={`h-full rounded-full ${progressTone(percentage)}`}
                style={{ width: `${width}%` }}
              />
            </div>
          </div>
        )
      },
    },
    ...(canManageTargets
      ? [
          {
            key: 'actions',
            header: '',
            headerClassName: 'text-right',
            className: 'text-right',
            stopPropagation: true,
            render: (target) => (
              <div className="flex items-center justify-end gap-1">
                <button
                  type="button"
                  onClick={() => {
                    setEditing(target)
                    setFormOpen(true)
                  }}
                  className="rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                  aria-label={`Edit ${metricLabel(target.metric)} target`}
                >
                  <PencilSquareIcon className="h-4 w-4" />
                </button>
                <button
                  type="button"
                  onClick={() => setDeleteTarget(target)}
                  className="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                  aria-label={`Delete ${metricLabel(target.metric)} target`}
                >
                  <TrashIcon className="h-4 w-4" />
                </button>
              </div>
            ),
          },
        ]
      : []),
  ]

  return (
    <div>
      <PageHeader
        title={isEmployee ? 'My targets' : 'Targets'}
        description={
          isEmployee
            ? 'Your personal sales targets and progress for the selected month.'
            : 'Company, team and individual sales targets with live attainment.'
        }
        actions={
          canManageTargets ? (
            <Button
              onClick={() => {
                setEditing(null)
                setFormOpen(true)
              }}
            >
              <PlusIcon className="h-4 w-4" />
              Set target
            </Button>
          ) : null
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Targets set" value={formatNumber(stats.total)} icon={ArrowTrendingUpIcon} tone="purple" loading={isLoading} />
        <StatsCard label="Achieved" value={formatNumber(stats.achieved)} icon={CheckBadgeIcon} tone="success" loading={isLoading} />
        <StatsCard
          label="Average attainment"
          value={stats.average === null ? '—' : formatPercent(stats.average)}
          icon={ChartBarIcon}
          tone="primary"
          loading={isLoading}
        />
        <StatsCard label="Team targets" value={formatNumber(stats.team)} icon={UserGroupIcon} tone="info" loading={isLoading} />
      </div>

      <Card className="mt-5">
        <CardContent className="p-0">
          <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3">
            <div className="flex items-center gap-2">
              <Button variant="outline" size="icon-sm" onClick={() => setMonth((value) => shiftMonth(value, -1))} aria-label="Previous month">
                <ChevronLeftIcon className="h-4 w-4" />
              </Button>
              <Input
                type="month"
                value={month}
                onChange={(event) => setMonth(event.target.value || currentMonth())}
                className="w-40"
                aria-label="Target month"
              />
              <Button variant="outline" size="icon-sm" onClick={() => setMonth((value) => shiftMonth(value, 1))} aria-label="Next month">
                <ChevronRightIcon className="h-4 w-4" />
              </Button>
              {month !== currentMonth() && (
                <Button variant="ghost" size="sm" onClick={() => setMonth(currentMonth())}>
                  This month
                </Button>
              )}
            </div>
            <p className="text-xs text-muted-foreground">
              {formatNumber(targets.length)} target{targets.length === 1 ? '' : 's'} for {month}
            </p>
          </div>

          {hasRevenueTarget && (
            <div className="border-b border-border p-4">
              <Alert variant="info">
                Revenue received targets show zero actuals because payment reconciliation is not yet
                connected to enterprise targets.
              </Alert>
            </div>
          )}

          <DataTable
            className="rounded-none border-0"
            columns={columns}
            rows={targets}
            loading={isLoading}
            rowKey="id"
            emptyState={
              <EmptyState
                icon={ArrowTrendingUpIcon}
                title="No targets for this month"
                description={
                  canManageTargets
                    ? 'Set a target for a metric, then track live attainment from leads, visits and deals.'
                    : 'No targets have been set for you this month.'
                }
                action={
                  canManageTargets ? (
                    <Button
                      onClick={() => {
                        setEditing(null)
                        setFormOpen(true)
                      }}
                    >
                      <PlusIcon className="h-4 w-4" />
                      Set target
                    </Button>
                  ) : null
                }
              />
            }
          />
        </CardContent>
      </Card>

      <TargetFormModal
        open={formOpen}
        onClose={() => setFormOpen(false)}
        target={editing}
        month={month}
      />

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        onConfirm={() => deleteMutation.mutate(deleteTarget)}
        loading={deleteMutation.isPending}
        title="Delete this target?"
        description={`${metricLabel(deleteTarget?.metric || '')} target for ${deleteTarget?.period_month || 'this month'} will be removed. Actual performance data is not affected.`}
        confirmLabel="Delete target"
      />
    </div>
  )
}
