import { useEffect, useMemo, useRef, useState } from 'react'
import { Navigate, useNavigate, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowsRightLeftIcon,
  CheckCircleIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { PriorityBadge, StageBadge } from '@/components/enterprise/StageBadge'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Select } from '@/components/ui/Input'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const SelectAllCheckbox = ({ checked, indeterminate, onChange }) => {
  const ref = useRef(null)

  useEffect(() => {
    if (ref.current) ref.current.indeterminate = indeterminate
  }, [indeterminate])

  return (
    <input
      ref={ref}
      type="checkbox"
      checked={checked}
      onChange={onChange}
      aria-label="Select all unassigned leads"
      className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
    />
  )
}

export const LeadDistributionPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [searchParams] = useSearchParams()
  const { isEmployee } = useEnterprise()
  const { teams, assignableMembers } = useEnterpriseReference()

  const [search, setSearch] = useState('')
  const [teamId, setTeamId] = useState(() => searchParams.get('team_id') || '')
  const [mode, setMode] = useState('round_robin')
  const [assigneeId, setAssigneeId] = useState('')
  const [selected, setSelected] = useState([])
  const debouncedSearch = useDebounce(search)

  const { data, isLoading, isFetching, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'leads', 'unassigned', { search: debouncedSearch }],
    queryFn: async () => {
      const response = await enterpriseApi.leads.list({
        search: debouncedSearch || undefined,
        unassigned: 1,
        sort: 'oldest',
        per_page: 100,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
  })

  useEffect(() => {
    setSelected([])
  }, [debouncedSearch])

  useEffect(() => {
    if (!teams.length || !teamId) return
    if (!teams.some((team) => String(team.id) === String(teamId))) {
      setTeamId('')
      setAssigneeId('')
    }
  }, [teams, teamId])

  const teamMembers = useMemo(
    () =>
      assignableMembers.filter(
        (member) => teamId && String(member.team?.id) === String(teamId),
      ),
    [assignableMembers, teamId],
  )

  useEffect(() => {
    if (mode !== 'manual') {
      setAssigneeId('')
      return
    }
    if (assigneeId && !teamMembers.some((member) => String(member.user.id) === String(assigneeId))) {
      setAssigneeId('')
    }
  }, [mode, teamMembers, assigneeId])

  const distributeMutation = useMutation({
    mutationFn: () =>
      enterpriseApi.leads.distribute({
        lead_ids: selected,
        team_id: Number(teamId),
        mode,
        ...(mode === 'manual' ? { assigned_to: Number(assigneeId) } : {}),
      }),
    onSuccess: (response) => {
      const count = response.data?.data?.assigned_count || 0
      toast.success(`${count} lead${count === 1 ? '' : 's'} distributed`)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'team'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      setSelected([])
      navigate(`/enterprise/leads?team_id=${teamId}`)
    },
    onError: (mutationError) => {
      toast.error(getErrorMessage(mutationError, 'Could not distribute leads.'))
    },
  })

  if (isEmployee) return <Navigate to="/enterprise/leads" replace />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const leads = data?.rows || []
  const meta = data?.meta || {}
  const selectedSet = new Set(selected)
  const allSelected = leads.length > 0 && leads.every((lead) => selectedSet.has(lead.id))
  const targetTeam = teams.find((team) => String(team.id) === String(teamId))
  const fieldErrors = serverFieldErrors(distributeMutation.error)
  const canDistribute =
    selected.length > 0 &&
    Boolean(teamId) &&
    teamMembers.length > 0 &&
    (mode === 'round_robin' || Boolean(assigneeId))

  const toggleLead = (id) => {
    setSelected((current) =>
      current.includes(id) ? current.filter((leadId) => leadId !== id) : [...current, id],
    )
  }

  const toggleAll = () => {
    setSelected(allSelected ? [] : leads.map((lead) => lead.id))
  }

  const columns = [
    {
      key: 'select',
      header: (
        <SelectAllCheckbox
          checked={allSelected}
          indeterminate={selected.length > 0 && !allSelected}
          onChange={toggleAll}
        />
      ),
      className: 'w-10',
      headerClassName: 'w-10',
      render: (lead) => (
        <input
          type="checkbox"
          checked={selectedSet.has(lead.id)}
          onChange={() => toggleLead(lead.id)}
          aria-label={`Select ${lead.name}`}
          className="h-4 w-4 rounded border-border text-highlight focus:ring-highlight"
        />
      ),
    },
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="flex min-w-0 items-center gap-3">
          <Avatar name={lead.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{lead.name}</p>
            <p className="truncate text-xs text-muted-foreground">
              {lead.company || lead.source?.replace('_', ' ') || 'No company'}
            </p>
          </div>
        </div>
      ),
    },
    {
      key: 'contact',
      header: 'Contact',
      render: (lead) => (
        <div className="min-w-0 text-xs">
          <p className="max-w-[190px] truncate text-foreground">{lead.phone || '—'}</p>
          <p className="max-w-[190px] truncate text-muted-foreground">{lead.email || '—'}</p>
        </div>
      ),
    },
    {
      key: 'stage',
      header: 'Stage',
      render: (lead) => <StageBadge stage={lead.stage} />,
    },
    {
      key: 'priority',
      header: 'Priority',
      render: (lead) => <PriorityBadge priority={lead.priority} />,
    },
    {
      key: 'created_at',
      header: 'Created',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(lead.created_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Lead distribution"
        description="Pick unassigned leads, choose a team, and hand them out evenly or to one owner."
        actions={
          <Button variant="outline" size="sm" onClick={() => navigate('/enterprise/leads')}>
            All leads
          </Button>
        }
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatsCard
          label="Unassigned leads"
          value={formatNumber(meta.total || 0)}
          icon={UsersIcon}
          tone="warning"
          hint={meta.total > leads.length ? `Showing first ${leads.length}` : 'Ready to distribute'}
          loading={isLoading}
        />
        <StatsCard
          label="Selected"
          value={formatNumber(selected.length)}
          icon={CheckCircleIcon}
          tone="primary"
          hint={selected.length ? 'Included in this batch' : 'Select leads below'}
        />
        <StatsCard
          label="Eligible members"
          value={formatNumber(teamMembers.length)}
          icon={UserGroupIcon}
          tone="info"
          hint={targetTeam?.name || 'Choose a target team'}
        />
      </div>

      <Card className="mt-5">
        <CardHeader>
          <div>
            <CardTitle>Distribution setup</CardTitle>
            <CardDescription>
              Round robin hands out leads to whoever received one least recently. Manual mode sends
              the whole batch to one member.
            </CardDescription>
          </div>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 lg:grid-cols-3">
            <FormField
              label="Target team"
              htmlFor="distribution-team"
              error={fieldErrors.team_id}
              required
            >
              <Select
                id="distribution-team"
                value={teamId}
                onChange={(event) => setTeamId(event.target.value)}
                invalid={Boolean(fieldErrors.team_id)}
              >
                <option value="">Select a team</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                    {team.manager ? ` · ${team.manager.name}` : ''}
                  </option>
                ))}
              </Select>
            </FormField>

            <FormField label="Distribution mode" htmlFor="distribution-mode" required>
              <Select
                id="distribution-mode"
                value={mode}
                onChange={(event) => setMode(event.target.value)}
              >
                <option value="round_robin">Round robin · balance across the team</option>
                <option value="manual">Manual · assign to one member</option>
              </Select>
            </FormField>

            <FormField
              label="Assign to"
              htmlFor="distribution-assignee"
              error={fieldErrors.assigned_to}
              hint={
                mode === 'manual'
                  ? 'The selected member receives every lead in this batch.'
                  : 'Round robin picks the least recently assigned member automatically.'
              }
              required={mode === 'manual'}
            >
              <Select
                id="distribution-assignee"
                value={assigneeId}
                onChange={(event) => setAssigneeId(event.target.value)}
                disabled={mode !== 'manual' || !teamId}
                invalid={Boolean(fieldErrors.assigned_to)}
              >
                <option value="">
                  {mode === 'manual' ? 'Select a member' : 'Automatic round robin'}
                </option>
                {teamMembers.map((member) => (
                  <option key={member.user.id} value={member.user.id}>
                    {member.user.name} · {member.role}
                  </option>
                ))}
              </Select>
            </FormField>
          </div>

          {teamId && teamMembers.length === 0 && (
            <Alert variant="warning" className="mt-4">
              This team has no eligible employees or managers yet. Add members to the team before
              distributing leads.
            </Alert>
          )}

          <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
            <p className="text-xs text-muted-foreground">
              {selected.length > 0
                ? `${formatNumber(selected.length)} lead${selected.length === 1 ? '' : 's'} selected`
                : 'Select one or more unassigned leads from the table below.'}
            </p>
            <div className="flex items-center gap-2">
              {selected.length > 0 && (
                <Button variant="ghost" size="sm" onClick={() => setSelected([])}>
                  Clear selection
                </Button>
              )}
              <Button
                onClick={() => distributeMutation.mutate()}
                loading={distributeMutation.isPending}
                disabled={!canDistribute}
              >
                <ArrowsRightLeftIcon className="h-4 w-4" />
                Distribute {selected.length > 0 ? formatNumber(selected.length) : ''} lead
                {selected.length === 1 ? '' : 's'}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="mt-5">
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <CardTitle>Unassigned leads</CardTitle>
              <p className="mt-1 text-xs text-muted-foreground">
                {formatNumber(meta.total || 0)} lead{meta.total === 1 ? '' : 's'} without an owner
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
            </div>
            <SearchInput
              value={search}
              onChange={setSearch}
              placeholder="Search name, email, phone or company…"
              className="w-full sm:w-80"
            />
          </div>

          <div className="border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={leads}
              loading={isLoading}
              rowKey="id"
              emptyState={
                <EmptyState
                  icon={UsersIcon}
                  title={search ? 'No matching unassigned leads' : 'Nothing left to distribute'}
                  description={
                    search
                      ? 'Try a different search term.'
                      : 'Every lead in your workspace already has an owner.'
                  }
                  action={
                    search ? (
                      <Button variant="outline" size="sm" onClick={() => setSearch('')}>
                        Clear search
                      </Button>
                    ) : (
                      <Button variant="outline" size="sm" onClick={() => navigate('/enterprise/leads')}>
                        View all leads
                      </Button>
                    )
                  }
                />
              }
            />
          </div>

          {meta.total > leads.length && (
            <div className="border-t border-border px-5 py-3 text-xs text-muted-foreground">
              Showing the {leads.length} oldest unassigned leads. Distribute this batch, then the
              next set will appear.
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
