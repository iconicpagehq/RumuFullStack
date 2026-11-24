import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowTrendingUpIcon,
  ArrowsRightLeftIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  NoSymbolIcon,
  PencilSquareIcon,
  PlayIcon,
  ShieldCheckIcon,
  TrashIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { MemberManagerModal } from '@/components/enterprise/MemberManagerModal'
import { MemberRemoveModal } from '@/components/enterprise/MemberRemoveModal'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { MemberEditModal } from '@/components/enterprise/MemberEditModal'
import { MemberTransferModal } from '@/components/enterprise/MemberTransferModal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useAuthStore } from '@/stores/authStore'
import { formatCurrency, formatDate, formatDateTime, formatNumber, titleCase } from '@/utils/formatters'
import { roleLabel } from '@/utils/enterprise'
import { getErrorMessage } from '@/utils/errors'
import { PAGE_SIZE } from '@/utils/constants'

export const MemberDetailPage = () => {
  const { id } = useParams()
  const memberId = Number(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, canTransferMembers, canRemoveMembers, canManageHierarchy } = useEnterprise()
  const currentUser = useAuthStore((state) => state.user)

  const [editOpen, setEditOpen] = useState(false)
  const [transferOpen, setTransferOpen] = useState(false)
  const [removeOpen, setRemoveOpen] = useState(false)
  const [suspendOpen, setSuspendOpen] = useState(false)
  const [managerOpen, setManagerOpen] = useState(false)
  const [historyPage, setHistoryPage] = useState(1)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'member', memberId],
    queryFn: async () => {
      const response = await enterpriseApi.members.get(memberId)
      return response.data?.data
    },
    enabled: Number.isFinite(memberId),
  })

  const membership = data?.membership
  const stats = data?.stats || {}

  const historyQuery = useQuery({
    queryKey: ['enterprise', 'member-history', memberId, historyPage],
    queryFn: async () => {
      const response = await enterpriseApi.members.history(memberId, { page: historyPage })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    enabled: isAdmin && Number.isFinite(memberId),
    placeholderData: (previous) => previous,
  })

  const reportsQuery = useQuery({
    queryKey: ['enterprise', 'member-reports', memberId],
    queryFn: async () => {
      const response = await enterpriseApi.members.reports(memberId)
      return response.data?.data
    },
    enabled: Boolean(membership) && Number.isFinite(memberId),
  })

  const invalidateMember = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'member', memberId] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'member-reports', memberId] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'member-history', memberId] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'organization'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
  }

  const suspendMutation = useMutation({
    mutationFn: () => enterpriseApi.members.suspend(memberId),
    onSuccess: () => {
      toast.success('Member suspended')
      invalidateMember()
      setSuspendOpen(false)
    },
    onError: (suspendError) => {
      toast.error(getErrorMessage(suspendError, 'Could not suspend this member.'))
      setSuspendOpen(false)
    },
  })

  const resumeMutation = useMutation({
    mutationFn: () => enterpriseApi.members.resume(memberId),
    onSuccess: () => {
      toast.success('Member reactivated')
      invalidateMember()
    },
    onError: (resumeError) => {
      toast.error(getErrorMessage(resumeError, 'Could not reactivate this member.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  if (isLoading || !membership) {
    return (
      <div className="space-y-5">
        <div className="skeleton h-20 rounded-2xl" />
        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 4 }, (_, index) => (
            <div key={index} className="skeleton h-28 rounded-2xl" />
          ))}
        </div>
        <div className="skeleton h-64 rounded-2xl" />
      </div>
    )
  }

  const isAdminMembership = membership.role === 'admin'
  const isSelf = membership.user?.id === currentUser?.id
  const canTransfer = canTransferMembers && !isAdminMembership && !isSelf
  const canRemove = canRemoveMembers && !isAdminMembership && !isSelf
  const memberName = membership.user?.name || 'Team member'
  const directReports = reportsQuery.data?.reports || []
  const reportsTo = membership.reports_to

  const historyColumns = [
    {
      key: 'action',
      header: 'Action',
      render: (log) => (
        <div>
          <p className="text-sm font-medium text-foreground">{titleCase(log.action)}</p>
          {log.description && (
            <p className="mt-0.5 text-xs text-muted-foreground">{log.description}</p>
          )}
        </div>
      ),
    },
    {
      key: 'actor',
      header: 'Actor',
      render: (log) => (
        <div className="flex items-center gap-2">
          <Avatar name={log.user?.name || 'System'} size="xs" />
          <span className="text-xs text-foreground">{log.user?.name || 'System'}</span>
        </div>
      ),
    },
    {
      key: 'ip',
      header: 'IP address',
      render: (log) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{log.ip_address || '—'}</span>
      ),
    },
    {
      key: 'created_at',
      header: 'When',
      render: (log) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(log.created_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title={memberName}
        description={membership.user?.email || 'Enterprise team member'}
        breadcrumbs={[{ label: 'Members', to: '/enterprise/users' }, { label: memberName }]}
        actions={
          isAdmin || canTransfer ? (
            <>
              {isAdmin && (
                <Button variant="outline" onClick={() => setEditOpen(true)}>
                  <PencilSquareIcon className="h-4 w-4" />
                  Edit
                </Button>
              )}
              {canManageHierarchy && !isAdminMembership && (
                <Button variant="outline" onClick={() => setManagerOpen(true)}>
                  <UserGroupIcon className="h-4 w-4" />
                  Change manager
                </Button>
              )}
              {canTransfer && (
                <Button variant="outline" onClick={() => setTransferOpen(true)}>
                  <ArrowsRightLeftIcon className="h-4 w-4" />
                  Transfer
                </Button>
              )}
              {isAdmin && canTransfer && (
                <>
                  {membership.status === 'active' ? (
                    <Button variant="danger-outline" onClick={() => setSuspendOpen(true)}>
                      <NoSymbolIcon className="h-4 w-4" />
                      Suspend
                    </Button>
                  ) : (
                    <Button
                      variant="outline"
                      onClick={() => resumeMutation.mutate()}
                      loading={resumeMutation.isPending}
                    >
                      <PlayIcon className="h-4 w-4" />
                      Reactivate
                    </Button>
                  )}
                </>
              )}
              {canRemove && (
                <Button variant="danger-outline" onClick={() => setRemoveOpen(true)}>
                  <TrashIcon className="h-4 w-4" />
                  Remove
                </Button>
              )}
            </>
          ) : null
        }
      />

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Profile</CardTitle>
          </CardHeader>
          <CardContent className="space-y-5">
            <div className="flex items-start gap-4">
              <Avatar name={memberName} size="lg" />
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <h2 className="text-lg font-semibold text-foreground">{memberName}</h2>
                  <StatusBadge status={membership.status} />
                </div>
                <p className="mt-1 text-sm text-muted-foreground">{membership.user?.email || '—'}</p>
                <div className="mt-2 flex flex-wrap items-center gap-2">
                  <Badge
                    variant={
                      membership.role === 'admin'
                        ? 'primary'
                        : membership.role === 'manager'
                          ? 'purple'
                          : 'neutral'
                    }
                  >
                    {roleLabel(membership.role)}
                  </Badge>
                  {membership.role === 'employee' && membership.is_lead && (
                    <Badge variant="info">Lead</Badge>
                  )}
                  <Badge variant="outline">{membership.team?.name || 'No team'}</Badge>
                  {membership.employee_code && (
                    <Badge variant="outline">Code {membership.employee_code}</Badge>
                  )}
                </div>
              </div>
            </div>

            <dl className="grid gap-4 border-t border-border pt-4 sm:grid-cols-2">
              <Detail label="Phone" value={membership.user?.phone || '—'} />
              <Detail label="Designation" value={membership.designation || '—'} />
              <Detail label="Team" value={membership.team?.name || 'Not assigned'} />
              <Detail
                label="Reports to"
                value={
                  membership.role === 'admin'
                    ? 'Top of organization'
                    : membership.reports_to?.name || 'No manager set'
                }
              />
              <Detail label="Employee code" value={membership.employee_code || '—'} />
              <Detail label="Joined" value={formatDate(membership.joined_at)} />
              <Detail
                label="Suspended"
                value={membership.suspended_at ? formatDate(membership.suspended_at) : '—'}
              />
              <Detail
                label="Invited by"
                value={
                  membership.inviter?.name
                    ? `${membership.inviter.name}${membership.inviter.email ? ` · ${membership.inviter.email}` : ''}`
                    : '—'
                }
              />
              <Detail label="Member since" value={formatDate(membership.created_at)} />
            </dl>

            {membership.status === 'suspended' && (
              <Alert variant="warning" title="Workspace access is suspended">
                This member cannot sign in to the enterprise workspace until an administrator
                reactivates their membership.
              </Alert>
            )}
          </CardContent>
        </Card>

        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Access</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <p className="text-muted-foreground">
                {isAdminMembership
                  ? 'Company admins can manage members, teams, billing and every lead in the workspace.'
                  : membership.role === 'manager'
                    ? 'Managers see their assigned teams, leads, tasks, visits and targets.'
                    : 'Employees see only the leads, tasks, visits and targets assigned to them.'}
              </p>
              {isSelf && (
                <p className="rounded-lg border border-border bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                  This is your own membership. Profile details can be edited by a company admin.
                </p>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex items-center justify-between gap-3">
              <CardTitle>Reporting line</CardTitle>
              <UserGroupIcon className="h-5 w-5 text-muted-foreground" />
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
              <div className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/25 px-3 py-2">
                <div className="min-w-0">
                  <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Reports to</p>
                  {membership.role === 'admin' ? (
                    <p className="text-sm font-medium text-foreground">Top of organization</p>
                  ) : reportsTo ? (
                    <button
                      type="button"
                      onClick={() => navigate(`/enterprise/users/${reportsTo.membership_id}`)}
                      className="truncate text-sm font-medium text-highlight hover:underline"
                    >
                      {reportsTo.name || 'Manager'}
                    </button>
                  ) : (
                    <p className="text-sm font-medium text-foreground">No manager set</p>
                  )}
                </div>
                {canManageHierarchy && !isAdminMembership && (
                  <Button variant="ghost" size="sm" onClick={() => setManagerOpen(true)}>
                    Change
                  </Button>
                )}
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div className="rounded-lg border border-border bg-muted/25 px-3 py-2">
                  <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Direct reports</p>
                  <p className="text-sm font-semibold text-foreground">
                    {formatNumber(membership.direct_reports_count || directReports.length || 0)}
                  </p>
                </div>
                <div className="rounded-lg border border-border bg-muted/25 px-3 py-2">
                  <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Team size</p>
                  <p className="text-sm font-semibold text-foreground">
                    {formatNumber(membership.subtree_size || 1)} in chain
                  </p>
                </div>
              </div>

              <div>
                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  Direct reports
                </p>
                {reportsQuery.isLoading ? (
                  <div className="mt-2 space-y-2">
                    {Array.from({ length: 2 }, (_, index) => (
                      <div key={index} className="skeleton h-9 rounded-lg" />
                    ))}
                  </div>
                ) : directReports.length === 0 ? (
                  <p className="mt-2 rounded-lg border border-dashed border-border px-3 py-3 text-xs text-muted-foreground">
                    No one reports to this member yet.
                  </p>
                ) : (
                  <ul className="mt-2 max-h-56 space-y-1 overflow-y-auto">
                    {directReports.map((report) => (
                      <li key={report.membership_id}>
                        <button
                          type="button"
                          onClick={() => navigate(`/enterprise/users/${report.membership_id}`)}
                          className="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left transition hover:bg-muted"
                        >
                          <Avatar name={report.name} size="xs" />
                          <span className="min-w-0 flex-1">
                            <span className="block truncate text-xs font-medium text-foreground">
                              {report.name}
                            </span>
                            <span className="block truncate text-[11px] text-muted-foreground">
                              {report.designation || roleLabel(report.role)}
                              {report.team?.name ? ` · ${report.team.name}` : ''}
                            </span>
                          </span>
                          {report.role === 'employee' && report.is_lead && (
                            <Badge variant="info">Lead</Badge>
                          )}
                        </button>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </CardContent>
          </Card>

          {canTransfer && (
            <Card>
              <CardHeader className="flex items-center justify-between gap-3">
                <CardTitle>Lead transfer wizard</CardTitle>                <ArrowsRightLeftIcon className="h-5 w-5 text-muted-foreground" />
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="grid grid-cols-2 gap-2">
                  <div className="rounded-lg border border-border bg-muted/25 px-3 py-2">
                    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Assigned</p>
                    <p className="text-sm font-semibold text-foreground">
                      {formatNumber(stats.assigned_leads || 0)} leads
                    </p>
                  </div>
                  <div className="rounded-lg border border-border bg-muted/25 px-3 py-2">
                    <p className="text-[11px] uppercase tracking-wide text-muted-foreground">Open</p>
                    <p className="text-sm font-semibold text-foreground">
                      {formatNumber(stats.open_leads || 0)} leads
                    </p>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground">
                  Move open leads to the destination team and optionally hand them to a specific
                  agent. Won and lost deals keep their history.
                </p>
                <Button className="w-full justify-start" onClick={() => setTransferOpen(true)}>
                  <ArrowsRightLeftIcon className="h-4 w-4" />
                  Open transfer wizard
                </Button>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle>Member actions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {isAdminMembership ? (
                <p className="text-xs text-muted-foreground">
                  The company owner cannot be transferred, suspended or removed.
                </p>
              ) : isSelf ? (
                <p className="text-xs text-muted-foreground">
                  You cannot change your own membership. Ask another administrator if access needs
                  to change.
                </p>
              ) : !isAdmin && !canTransfer ? (
                <p className="text-xs text-muted-foreground">
                  Only company admins and managers can manage this member.
                </p>
              ) : (
                <>
                  {canTransfer && (
                    <Button
                      variant="outline"
                      className="w-full justify-start"
                      onClick={() => setTransferOpen(true)}
                    >
                      <ArrowsRightLeftIcon className="h-4 w-4" />
                      Transfer to another team
                    </Button>
                  )}
                  {isAdmin && canTransfer && membership.status === 'active' && (
                    <Button
                      variant="danger-outline"
                      className="w-full justify-start"
                      onClick={() => setSuspendOpen(true)}
                    >
                      Suspend workspace access
                    </Button>
                  )}
                  {isAdmin && canTransfer && membership.status !== 'active' && (
                    <Button
                      variant="outline"
                      className="w-full justify-start"
                      onClick={() => resumeMutation.mutate()}
                      loading={resumeMutation.isPending}
                    >
                      Reactivate workspace access
                    </Button>
                  )}
                  {canRemove && (
                    <Button
                      variant="danger-outline"
                      className="w-full justify-start"
                      onClick={() => setRemoveOpen(true)}
                    >
                      <TrashIcon className="h-4 w-4" />
                      Remove from enterprise
                    </Button>
                  )}
                </>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      <div className="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Assigned leads"
          value={formatNumber(stats.assigned_leads || 0)}
          icon={UsersIcon}
          tone="purple"
        />
        <StatsCard
          label="Open leads"
          value={formatNumber(stats.open_leads || 0)}
          icon={ClipboardDocumentCheckIcon}
          tone="info"
        />
        <StatsCard
          label="Won deals"
          value={formatNumber(stats.won_deals || 0)}
          icon={CheckBadgeIcon}
          tone="success"
        />
        <StatsCard
          label="Won value"
          value={formatCurrency(stats.deal_value || 0)}
          icon={ArrowTrendingUpIcon}
          tone="primary"
        />
      </div>

      {isAdmin && (
        <Card className="mt-5">
          <CardHeader className="flex items-center justify-between gap-3">
            <div>
              <CardTitle>Activity history</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">
                Audit events for this membership and the member&apos;s own actions.
              </p>
            </div>
            <ShieldCheckIcon className="h-5 w-5 text-muted-foreground" />
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={historyColumns}
              rows={historyQuery.data?.rows || []}
              loading={historyQuery.isLoading}
              emptyState={
                <EmptyState
                  icon={ShieldCheckIcon}
                  title="No activity recorded yet"
                  description="Membership changes and member actions will appear here."
                />
              }
            />
            <div className="border-t border-border px-4">
              <Pagination
                page={historyQuery.data?.meta?.current_page || 1}
                lastPage={historyQuery.data?.meta?.last_page || 1}
                total={historyQuery.data?.meta?.total || 0}
                perPage={historyQuery.data?.meta?.per_page || PAGE_SIZE}
                onPageChange={setHistoryPage}
              />
            </div>
          </CardContent>
        </Card>
      )}

      <MemberEditModal open={editOpen} onClose={() => setEditOpen(false)} membership={membership} />
      <MemberTransferModal
        open={transferOpen}
        onClose={() => setTransferOpen(false)}
        membership={membership}
      />
      <MemberRemoveModal
        open={removeOpen}
        onClose={() => setRemoveOpen(false)}
        membership={membership}
        onRemoved={() => navigate('/enterprise/users')}
      />
      <MemberManagerModal
        open={managerOpen}
        onClose={() => setManagerOpen(false)}
        membership={membership}
      />

      <ConfirmDialog
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        onConfirm={() => suspendMutation.mutate()}
        loading={suspendMutation.isPending}
        title={`Suspend ${memberName}?`}
        description="They will immediately lose access to the enterprise workspace. Their leads, tasks and history stay intact and access can be restored later."
        confirmLabel="Suspend member"
      />
    </div>
  )
}

const Detail = ({ label, value }) => (
  <div>
    <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</dt>
    <dd className="mt-1 break-words text-sm text-foreground">{value}</dd>
  </div>
)
