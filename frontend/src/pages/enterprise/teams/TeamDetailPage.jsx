import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowTrendingUpIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
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
import { FormField, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { TeamFormModal } from '@/components/enterprise/TeamFormModal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'
import { roleLabel } from '@/utils/enterprise'
import { getErrorMessage } from '@/utils/errors'

export const TeamDetailPage = () => {
  const { id } = useParams()
  const teamId = Number(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, isEmployee } = useEnterprise()
  const { assignableMembers } = useEnterpriseReference()

  const [editOpen, setEditOpen] = useState(false)
  const [addOpen, setAddOpen] = useState(false)
  const [selectedUser, setSelectedUser] = useState('')
  const [removeTarget, setRemoveTarget] = useState(null)
  const [deleteOpen, setDeleteOpen] = useState(false)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'team', teamId],
    queryFn: async () => {
      const response = await enterpriseApi.teams.get(teamId)
      return response.data?.data
    },
    enabled: Number.isFinite(teamId),
  })

  const invalidateTeam = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'team', teamId] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
  }

  const addMemberMutation = useMutation({
    mutationFn: (userId) => enterpriseApi.teams.addMember(teamId, userId),
    onSuccess: () => {
      toast.success('Member added to team')
      invalidateTeam()
      setAddOpen(false)
      setSelectedUser('')
    },
    onError: (addError) => {
      toast.error(getErrorMessage(addError, 'Could not add this member.'))
    },
  })

  const removeMemberMutation = useMutation({
    mutationFn: (userId) => enterpriseApi.teams.removeMember(teamId, userId),
    onSuccess: () => {
      toast.success('Member removed from team')
      invalidateTeam()
      setRemoveTarget(null)
    },
    onError: (removeError) => {
      toast.error(getErrorMessage(removeError, 'Could not remove this member.'))
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => enterpriseApi.teams.remove(teamId),
    onSuccess: () => {
      toast.success('Team deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      navigate('/enterprise/teams')
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this team.'))
      setDeleteOpen(false)
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />
  if (isLoading || !data?.team) {
    return (
      <div className="space-y-5">
        <div className="skeleton h-20 rounded-2xl" />
        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 4 }, (_, index) => (
            <div key={index} className="skeleton h-28 rounded-2xl" />
          ))}
        </div>
        <div className="skeleton h-72 rounded-2xl" />
      </div>
    )
  }

  const { team, members = [], performance = {} } = data
  const teamUsers = team.members || []
  const currentMemberIds = new Set(teamUsers.map((user) => user.id))
  const candidates = assignableMembers.filter(
    (member) =>
      !currentMemberIds.has(member.user.id) &&
      (!member.team || member.team.id === team.id),
  )

  const memberColumns = [
    {
      key: 'member',
      header: 'Member',
      render: (member) => (
        <div className="flex min-w-0 items-center gap-3">
          <Avatar name={member.user?.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-foreground">
              {member.user?.name || 'Unknown member'}
            </p>
            <p className="truncate text-xs text-muted-foreground">{member.user?.email || '—'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (member) => <Badge variant={member.role === 'manager' ? 'purple' : 'neutral'}>{roleLabel(member.role)}</Badge>,
    },
    {
      key: 'designation',
      header: 'Designation',
      render: (member) => <span className="text-xs text-muted-foreground">{member.designation || '—'}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (member) => <StatusBadge status={member.status} />,
    },
    {
      key: 'joined',
      header: 'Joined',
      render: (member) => <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(member.joined_at)}</span>,
    },
    ...(isAdmin
      ? [
          {
            key: 'actions',
            header: '',
            headerClassName: 'text-right',
            className: 'text-right',
            stopPropagation: true,
            render: (member) => (
              <Button
                variant="ghost"
                size="sm"
                disabled={member.user?.id === team.manager?.id || !member.user}
                title={
                  member.user?.id === team.manager?.id
                    ? 'Assign another manager before removing this member.'
                    : undefined
                }
                onClick={() => setRemoveTarget(member)}
              >
                Remove
              </Button>
            ),
          },
        ]
      : []),
  ]

  return (
    <div>
      <PageHeader
        title={team.name}
        description={team.description || 'Team members, ownership and performance.'}
        breadcrumbs={[{ label: 'Teams', to: '/enterprise/teams' }, { label: team.name }]}
        actions={
          isAdmin ? (
            <>
              <Button variant="outline" onClick={() => setEditOpen(true)}>
                <PencilSquareIcon className="h-4 w-4" />
                Edit team
              </Button>
              <Button variant="danger-outline" onClick={() => setDeleteOpen(true)}>
                <TrashIcon className="h-4 w-4" />
                Delete
              </Button>
            </>
          ) : null
        }
      />

      <div className="mb-5 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-border bg-card px-4 py-3 text-xs text-muted-foreground">
        <StatusBadge status={team.status} />
        <span className="inline-flex items-center gap-2">
          <Avatar name={team.manager?.name || team.name} size="xs" />
          {team.manager?.name ? `Led by ${team.manager.name}` : 'No manager assigned'}
        </span>
        <Badge variant="outline">
          {team.lead_distribution_mode === 'round_robin' ? 'Round robin' : 'Manual assignment'}
        </Badge>
        <span>Created {formatDate(team.created_at)}</span>
      </div>

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Members" value={formatNumber(team.members_count ?? teamUsers.length)} icon={UsersIcon} tone="purple" />
        <StatsCard label="Open leads" value={formatNumber(team.open_leads_count || 0)} icon={ClipboardDocumentCheckIcon} tone="info" />
        <StatsCard label="Won deals" value={formatNumber(team.won_deals_count || 0)} icon={CheckBadgeIcon} tone="success" />
        <StatsCard label="Won value" value={formatCurrency(team.deal_value || 0)} icon={ArrowTrendingUpIcon} tone="primary" />
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-wrap items-center justify-between gap-3">
            <CardTitle>Team members</CardTitle>
            {isAdmin && (
              <Button size="sm" onClick={() => setAddOpen(true)}>
                <PlusIcon className="h-4 w-4" />
                Add member
              </Button>
            )}
          </CardHeader>
          <CardContent className="p-0">
            <DataTable
              className="rounded-none border-0"
              columns={memberColumns}
              rows={members.filter((member) => member.user)}
              loading={isLoading}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={UserGroupIcon}
                  title="No members on this team"
                  description={
                    isAdmin
                      ? 'Add active managers or employees to this team.'
                      : 'No members have been added to this team yet.'
                  }
                  action={
                    isAdmin ? (
                      <Button onClick={() => setAddOpen(true)}>
                        <PlusIcon className="h-4 w-4" />
                        Add member
                      </Button>
                    ) : null
                  }
                />
              }
            />
          </CardContent>
        </Card>

        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Performance signals</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <Signal icon={UsersIcon} label="Assigned leads" value={formatNumber(performance.assigned_leads || 0)} />
              <Signal icon={CheckBadgeIcon} label="Won value" value={formatCurrency(performance.won_value || 0)} />
              <Signal icon={ClipboardDocumentCheckIcon} label="Overdue tasks" value={formatNumber(performance.overdue_tasks || 0)} />
              <Signal icon={CalendarDaysIcon} label="Upcoming visits" value={formatNumber(performance.upcoming_visits || 0)} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Quick links</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              <Link
                to={`/enterprise/leads?team_id=${team.id}`}
                className="block rounded-lg px-2 py-1.5 text-foreground transition hover:bg-muted"
              >
                View team leads
              </Link>
              <Link to="/enterprise/tasks" className="block rounded-lg px-2 py-1.5 text-foreground transition hover:bg-muted">
                View team tasks
              </Link>
              <Link to="/enterprise/targets" className="block rounded-lg px-2 py-1.5 text-foreground transition hover:bg-muted">
                View team targets
              </Link>
              {!isEmployee && (
                <>
                  <Link
                    to={`/enterprise/leads/distribute?team_id=${team.id}`}
                    className="block rounded-lg px-2 py-1.5 text-foreground transition hover:bg-muted"
                  >
                    Distribute team leads
                  </Link>
                  <Link to="/enterprise/users" className="block rounded-lg px-2 py-1.5 text-foreground transition hover:bg-muted">
                    Manage members
                  </Link>
                </>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      <TeamFormModal open={editOpen} onClose={() => setEditOpen(false)} team={team} />

      <Modal
        open={addOpen}
        onClose={addMemberMutation.isPending ? () => {} : () => setAddOpen(false)}
        title="Add team member"
        description="Members can belong to only one team. Use the Members page to transfer someone from another team."
        footer={
          <>
            <Button variant="outline" onClick={() => setAddOpen(false)} disabled={addMemberMutation.isPending}>
              Cancel
            </Button>
            <Button
              onClick={() => addMemberMutation.mutate(Number(selectedUser))}
              loading={addMemberMutation.isPending}
              disabled={!selectedUser || candidates.length === 0}
            >
              Add to team
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          {candidates.length === 0 ? (
            <Alert variant="info">
              Every eligible member is already on this team or belongs to another team. Use the Members
              page to transfer someone.
            </Alert>
          ) : (
            <FormField label="Team member" htmlFor="team-add-member">
              <Select
                id="team-add-member"
                value={selectedUser}
                onChange={(event) => setSelectedUser(event.target.value)}
              >
                <option value="">Select a manager or employee</option>
                {candidates.map((member) => (
                  <option key={member.user.id} value={member.user.id}>
                    {member.user.name} · {roleLabel(member.role)}
                  </option>
                ))}
              </Select>
            </FormField>
          )}
          <div className="rounded-xl border border-border bg-muted/30 px-3 py-2.5 text-xs text-muted-foreground">
            The team manager is not added automatically. You can add them as a member if they also carry
            leads.
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={Boolean(removeTarget)}
        onClose={() => setRemoveTarget(null)}
        onConfirm={() => removeMemberMutation.mutate(removeTarget?.user?.id)}
        loading={removeMemberMutation.isPending}
        title={`Remove ${removeTarget?.user?.name || 'member'} from ${team.name}?`}
        description="Their open leads in this team will be unassigned from the team. Closed deals keep their history."
        confirmLabel="Remove member"
      />

      <ConfirmDialog
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete ${team.name}?`}
        description="This removes the team and its member assignments. Teams that still own open leads cannot be deleted."
        confirmLabel="Delete team"
      />
    </div>
  )
}

const Signal = ({ icon: Icon, label, value }) => (
  <div className="flex items-center justify-between gap-3 rounded-xl border border-border bg-muted/25 px-3 py-2.5">
    <span className="inline-flex items-center gap-2 text-xs text-muted-foreground">
      <Icon className="h-4 w-4" />
      {label}
    </span>
    <span className="text-sm font-semibold text-foreground">{value}</span>
  </div>
)
