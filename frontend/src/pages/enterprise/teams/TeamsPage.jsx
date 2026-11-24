import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  UserGroupIcon,
  UserPlusIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { AddMemberModal } from '@/components/enterprise/AddMemberModal'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { TeamFormModal } from '@/components/enterprise/TeamFormModal'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const TABS = [
  { key: '', label: 'All teams' },
  { key: 'active', label: 'Active' },
  { key: 'inactive', label: 'Inactive' },
]

const Metric = ({ label, value }) => (
  <div>
    <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
    <p className="mt-0.5 text-sm font-semibold text-foreground">{value}</p>
  </div>
)

export const TeamsPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, isManager, isEmployee } = useEnterprise()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [formOpen, setFormOpen] = useState(false)
  const [addMemberOpen, setAddMemberOpen] = useState(false)
  const [addMemberRole, setAddMemberRole] = useState('employee')
  const [editing, setEditing] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'teams', { search: debouncedSearch, status, page }],
    queryFn: async () => {
      const response = await enterpriseApi.teams.list({
        search: debouncedSearch || undefined,
        status: status || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const deleteMutation = useMutation({
    mutationFn: (team) => enterpriseApi.teams.remove(team.id),
    onSuccess: () => {
      toast.success('Team deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      setDeleteTarget(null)
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this team.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const teams = data?.rows || []
  const meta = data?.meta || {}

  return (
    <div>
      <PageHeader
        title={isEmployee ? 'My team' : 'Teams'}
        description={
          isEmployee
            ? 'Your team, its manager and performance.'
            : isManager
              ? 'Teams you manage and their live performance.'
              : 'Organise managers and employees into teams for distribution and targets.'
        }
        actions={
          isAdmin ? (
            <>
              <Button
                variant="outline"
                onClick={() => {
                  setAddMemberRole('manager')
                  setAddMemberOpen(true)
                }}
              >
                <UserGroupIcon className="h-4 w-4" />
                Add manager
              </Button>
              <Button
                variant="outline"
                onClick={() => {
                  setAddMemberRole('employee')
                  setAddMemberOpen(true)
                }}
              >
                <UserPlusIcon className="h-4 w-4" />
                Add employee
              </Button>
              <Button
                onClick={() => {
                  setEditing(null)
                  setFormOpen(true)
                }}
              >
                <PlusIcon className="h-4 w-4" />
                New team
              </Button>
            </>
          ) : null
        }
      />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <Tabs tabs={TABS} value={status} onChange={setStatus} className="w-full sm:w-auto" />
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Search teams…"
          className="w-full sm:w-72"
        />
      </div>

      <p className="mt-3 text-xs text-muted-foreground">
        {formatNumber(meta.total || 0)} team{meta.total === 1 ? '' : 's'}
        {isFetching && <span className="ml-2">Refreshing…</span>}
      </p>

      {isLoading ? (
        <div className="mt-4 grid gap-5 lg:grid-cols-2">
          {Array.from({ length: 4 }, (_, index) => (
            <div key={index} className="skeleton h-56 rounded-2xl" />
          ))}
        </div>
      ) : teams.length === 0 ? (
        <Card className="mt-4">
          <CardContent>
            <EmptyState
              icon={UserGroupIcon}
              title={search || status ? 'No matching teams' : 'No teams yet'}
              description={
                search || status
                  ? 'Try a different search or status filter.'
                  : isAdmin
                    ? 'Create a team, assign a manager and add members to start distributing leads.'
                    : isManager
                      ? 'You are not managing a team yet.'
                      : 'You have not been added to a team yet.'
              }
              action={
                isAdmin && !search && !status ? (
                  <Button
                    onClick={() => {
                      setEditing(null)
                      setFormOpen(true)
                    }}
                  >
                    <PlusIcon className="h-4 w-4" />
                    New team
                  </Button>
                ) : null
              }
            />
          </CardContent>
        </Card>
      ) : (
        <div className="mt-4 grid gap-5 lg:grid-cols-2">
          {teams.map((team) => (
            <Card key={team.id}>
              <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                  <div className="flex min-w-0 items-center gap-3">
                    <Avatar name={team.manager?.name || team.name} size="md" />
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="truncate text-sm font-semibold text-foreground">{team.name}</p>
                        <StatusBadge status={team.status} />
                      </div>
                      <p className="mt-0.5 truncate text-xs text-muted-foreground">
                        {team.manager?.name ? `Manager · ${team.manager.name}` : 'No manager assigned'}
                      </p>
                    </div>
                  </div>
                  {isAdmin && (
                    <div className="flex shrink-0 items-center gap-1">
                      <button
                        type="button"
                        onClick={() => {
                          setEditing(team)
                          setFormOpen(true)
                        }}
                        className="rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        aria-label={`Edit ${team.name}`}
                      >
                        <PencilSquareIcon className="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() => setDeleteTarget(team)}
                        className="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                        aria-label={`Delete ${team.name}`}
                      >
                        <TrashIcon className="h-4 w-4" />
                      </button>
                    </div>
                  )}
                </div>

                <p className="mt-3 line-clamp-2 min-h-[40px] text-sm text-muted-foreground">
                  {team.description || 'No description provided.'}
                </p>

                <div className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-border pt-4 sm:grid-cols-4">
                  <Metric label="Members" value={formatNumber(team.members_count || 0)} />
                  <Metric label="Open leads" value={formatNumber(team.open_leads_count || 0)} />
                  <Metric label="Won deals" value={formatNumber(team.won_deals_count || 0)} />
                  <Metric label="Won value" value={formatCurrency(team.deal_value || 0)} />
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                  <div className="flex flex-wrap items-center gap-1.5">
                    <Badge variant="outline">
                      {team.lead_distribution_mode === 'round_robin' ? 'Round robin' : 'Manual assignment'}
                    </Badge>
                    {team.members?.slice(0, 3).map((member) => (
                      <Badge key={member.id} variant="neutral">
                        {member.name}
                      </Badge>
                    ))}
                    {(team.members_count || 0) > 3 && (
                      <Badge variant="neutral">+{team.members_count - 3}</Badge>
                    )}
                  </div>
                  <Button variant="outline" size="sm" onClick={() => navigate(`/enterprise/teams/${team.id}`)}>
                    View team
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <div className="mt-2">
        <Pagination
          page={meta.current_page || 1}
          lastPage={meta.last_page || 1}
          total={meta.total || 0}
          perPage={meta.per_page || PAGE_SIZE}
          onPageChange={setPage}
        />
      </div>

      <TeamFormModal open={formOpen} onClose={() => setFormOpen(false)} team={editing} />
      <AddMemberModal
        open={addMemberOpen}
        onClose={() => setAddMemberOpen(false)}
        defaultRole={addMemberRole}
      />

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        onConfirm={() => deleteMutation.mutate(deleteTarget)}
        loading={deleteMutation.isPending}
        title={`Delete ${deleteTarget?.name || 'team'}?`}
        description="This removes the team and its member assignments. Teams that still own open leads cannot be deleted — reassign those leads first."
        confirmLabel="Delete team"
      />
    </div>
  )
}
