import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  EnvelopeIcon,
  TrashIcon,
  UserPlusIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { AddMemberModal } from '@/components/enterprise/AddMemberModal'
import { MemberRemoveModal } from '@/components/enterprise/MemberRemoveModal'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Select } from '@/components/ui/Input'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { useAuthStore } from '@/stores/authStore'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDate, formatNumber } from '@/utils/formatters'
import { roleLabel } from '@/utils/enterprise'

export const MembersPage = () => {
  const navigate = useNavigate()
  const { isAdmin } = useEnterprise()
  const { teams } = useEnterpriseReference()
  const currentUser = useAuthStore((state) => state.user)
  const [removeTarget, setRemoveTarget] = useState(null)
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [status, setStatus] = useState('')
  const [teamId, setTeamId] = useState('')
  const [page, setPage] = useState(1)
  const [addOpen, setAddOpen] = useState(false)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, role, status, teamId])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'members', { search: debouncedSearch, role, status, teamId, page }],
    queryFn: async () => {
      const response = await enterpriseApi.members.list({
        search: debouncedSearch || undefined,
        role: role || undefined,
        status: status || undefined,
        team_id: teamId || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const members = data?.rows || []
  const meta = data?.meta || {}
  const stats = meta.stats || {}

  const columns = [
    {
      key: 'member',
      header: 'Member',
      render: (member) => (
        <div className="flex min-w-0 items-center gap-3">
          <Avatar name={member.user?.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">
              {member.user?.name || 'Unknown member'}
            </p>
            <p className="truncate text-xs text-muted-foreground">{member.user?.email || '—'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (member) => (
        <span className="whitespace-nowrap text-xs text-foreground">{member.user?.phone || '—'}</span>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (member) => (
        <div className="flex flex-wrap items-center gap-1.5">
          <Badge
            variant={
              member.role === 'admin' ? 'primary' : member.role === 'manager' ? 'purple' : 'neutral'
            }
          >
            {roleLabel(member.role)}
          </Badge>
          {member.role === 'employee' && member.is_lead && <Badge variant="info">Lead</Badge>}
        </div>
      ),
    },
    {
      key: 'reports_to',
      header: 'Reports to',
      render: (member) =>
        member.reports_to ? (
          <div className="min-w-0">
            <p className="truncate text-xs font-medium text-foreground">{member.reports_to.name || '—'}</p>
            <p className="truncate text-[11px] text-muted-foreground">
              {roleLabel(member.reports_to.role)}
              {member.direct_reports_count
                ? ` · ${member.direct_reports_count} direct`
                : ''}
            </p>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">Top of organization</span>
        ),
    },
    {
      key: 'team',
      header: 'Team',
      render: (member) => (
        <span className="text-xs text-foreground">{member.team?.name || 'No team'}</span>
      ),
    },
    {
      key: 'designation',
      header: 'Designation',
      render: (member) => (
        <span className="text-xs text-muted-foreground">{member.designation || '—'}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (member) => <StatusBadge status={member.status} />,
    },
    {
      key: 'joined_at',
      header: 'Joined',
      render: (member) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(member.joined_at)}
        </span>
      ),
    },
    ...(isAdmin
      ? [
          {
            key: 'actions',
            header: '',
            headerClassName: 'text-right',
            className: 'text-right',
            stopPropagation: true,
            render: (member) => {
              const isSelf = member.user?.id === currentUser?.id
              const protectedAdmin = member.role === 'admin'
              if (isSelf || protectedAdmin) return null
              return (
                <button
                  type="button"
                  onClick={() => setRemoveTarget(member)}
                  className="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                  aria-label={`Remove ${member.user?.name || 'member'}`}
                >
                  <TrashIcon className="h-4 w-4" />
                </button>
              )
            },
          },
        ]
      : []),
  ]

  return (
    <div>
      <PageHeader
        title="Members"
        description="Managers and employees in your enterprise workspace."
        actions={
          isAdmin ? (
            <div className="flex flex-wrap gap-2">
              <Button variant="outline" onClick={() => navigate('/enterprise/invitations')}>
                <EnvelopeIcon className="h-4 w-4" />
                Invite via email
              </Button>
              <Button onClick={() => setAddOpen(true)}>
                <UserPlusIcon className="h-4 w-4" />
                Add member
              </Button>
            </div>
          ) : null
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total members"
          value={formatNumber(stats.total || 0)}
          icon={UsersIcon}
          tone="purple"
          hint={`${formatNumber(stats.admins || 0)} company admin${stats.admins === 1 ? '' : 's'}`}
          loading={isLoading}
        />
        <StatsCard
          label="Active"
          value={formatNumber(stats.active || 0)}
          icon={UserGroupIcon}
          tone="success"
          hint={`${formatNumber(stats.suspended || 0)} suspended`}
          loading={isLoading}
        />
        <StatsCard label="Managers" value={formatNumber(stats.managers || 0)} icon={UserGroupIcon} tone="info" loading={isLoading} />
        <StatsCard label="Employees" value={formatNumber(stats.employees || 0)} icon={UsersIcon} tone="primary" loading={isLoading} />
      </div>

      <Card className="mt-5">
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-2">
              <Select value={role} onChange={(event) => setRole(event.target.value)} className="w-40" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="manager">Managers</option>
                <option value="employee">Employees</option>
                <option value="admin">Admins</option>
              </Select>
              <Select value={status} onChange={(event) => setStatus(event.target.value)} className="w-40" aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </Select>
              <Select value={teamId} onChange={(event) => setTeamId(event.target.value)} className="w-44" aria-label="Filter by team">
                <option value="">All teams</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </Select>
              {(role || status || teamId) && (
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setRole('')
                    setStatus('')
                    setTeamId('')
                  }}
                >
                  Clear filters
                </Button>
              )}
            </div>
            <div className="flex items-center gap-3">
              <p className="hidden text-xs text-muted-foreground sm:block">
                {formatNumber(meta.total || 0)} member{meta.total === 1 ? '' : 's'}
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
              <SearchInput
                value={search}
                onChange={setSearch}
                placeholder="Search name or email…"
                className="w-full sm:w-72"
              />
            </div>
          </div>

          <div className="mt-4 border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={members}
              loading={isLoading}
              rowKey="id"
              onRowClick={(member) => navigate(`/enterprise/users/${member.id}`)}
              emptyState={
                <EmptyState
                  icon={UsersIcon}
                  title={search || role || status || teamId ? 'No matching members' : 'No members yet'}
                  description={
                    search || role || status || teamId
                      ? 'Try a different search or filter.'
                      : 'Add a member directly or send an email invitation to start building your sales team.'
                  }
                  action={
                    isAdmin && !search && !role && !status && !teamId ? (
                      <Button onClick={() => setAddOpen(true)}>
                        <UserPlusIcon className="h-4 w-4" />
                        Add member
                      </Button>
                    ) : null
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
              perPage={meta.per_page || PAGE_SIZE}
              onPageChange={setPage}
            />
          </div>
        </CardContent>
      </Card>

      <AddMemberModal open={addOpen} onClose={() => setAddOpen(false)} />
      <MemberRemoveModal
        open={Boolean(removeTarget)}
        onClose={() => setRemoveTarget(null)}
        membership={removeTarget}
      />
    </div>
  )
}
