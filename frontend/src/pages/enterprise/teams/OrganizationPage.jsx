import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  ChevronDownIcon,
  ChevronRightIcon,
  LockClosedIcon,
  Squares2X2Icon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { MemberManagerModal } from '@/components/enterprise/MemberManagerModal'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { useEnterprise } from '@/hooks/useEnterprise'
import { formatNumber } from '@/utils/formatters'
import { roleLabel } from '@/utils/enterprise'
import { cn } from '@/utils/cn'

const nodeMatches = (node, term) =>
  [node.name, node.email, node.designation, node.employee_code, node.team?.name]
    .filter(Boolean)
    .some((value) => String(value).toLowerCase().includes(term))

const filterNodes = (nodes, term) => {
  if (!term) return nodes
  return nodes.reduce((acc, node) => {
    const children = filterNodes(node.children || [], term)
    if (nodeMatches(node, term) || children.length > 0) {
      acc.push({ ...node, children })
    }
    return acc
  }, [])
}

const flattenNodes = (nodes, acc = []) => {
  nodes.forEach((node) => {
    acc.push(node)
    flattenNodes(node.children || [], acc)
  })
  return acc
}

const toMembership = (node) => ({
  id: node.membership_id,
  role: node.role,
  status: node.status,
  designation: node.designation,
  employee_code: node.employee_code,
  user: { id: node.user_id, name: node.name, email: node.email, avatar: node.avatar },
  team: node.team,
  reports_to_membership_id: node.reports_to_membership_id,
  direct_reports_count: node.direct_reports_count,
  subtree_size: node.subtree_size,
  is_lead: node.is_lead,
  reports_to: null,
})

const TreeNode = ({ node, depth, collapsed, onToggle, onOpen, onManage, canManage }) => {
  const hasChildren = (node.children || []).length > 0
  const isCollapsed = collapsed.has(node.membership_id)

  return (
    <li>
      <div
        className={cn(
          'group flex items-center gap-3 rounded-xl border border-border bg-card px-3 py-2.5 shadow-sm transition',
          'hover:border-highlight/30 hover:shadow-md',
        )}
      >
        <button
          type="button"
          onClick={() => onOpen(node)}
          className="flex min-w-0 flex-1 items-center gap-3 text-left"
        >
          <Avatar src={node.avatar} name={node.name} size={depth === 0 ? 'md' : 'sm'} />
          <span className="min-w-0 flex-1">
            <span className="flex flex-wrap items-center gap-1.5">
              <span className="truncate text-sm font-semibold text-foreground">
                {node.name || 'Unnamed member'}
              </span>
              <Badge
                variant={node.role === 'admin' ? 'primary' : node.role === 'manager' ? 'purple' : 'neutral'}
              >
                {roleLabel(node.role)}
              </Badge>
              {node.role === 'employee' && node.is_lead && <Badge variant="info">Lead</Badge>}
              {node.status !== 'active' && <Badge variant="warning">{node.status}</Badge>}
            </span>
            <span className="mt-0.5 block truncate text-xs text-muted-foreground">
              {[node.designation, node.team?.name, node.email].filter(Boolean).join(' · ') ||
                'No designation'}
            </span>
          </span>
          <span className="hidden shrink-0 flex-col items-end text-right sm:flex">
            <span className="text-xs font-medium text-foreground">
              {formatNumber(node.direct_reports_count || 0)} direct
            </span>
            <span className="text-[11px] text-muted-foreground">
              {formatNumber(node.subtree_size || 1)} in chain
            </span>
          </span>
        </button>

        {canManage && node.role !== 'admin' && (
          <Button
            variant="ghost"
            size="sm"
            className="hidden shrink-0 sm:inline-flex"
            onClick={(event) => {
              event.stopPropagation()
              onManage(node)
            }}
          >
            Change manager
          </Button>
        )}

        {hasChildren ? (
          <button
            type="button"
            onClick={() => onToggle(node.membership_id)}
            className="shrink-0 rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            aria-label={isCollapsed ? 'Expand reporting line' : 'Collapse reporting line'}
            aria-expanded={!isCollapsed}
          >
            {isCollapsed ? (
              <ChevronRightIcon className="h-4 w-4" />
            ) : (
              <ChevronDownIcon className="h-4 w-4" />
            )}
          </button>
        ) : (
          <span className="w-7" aria-hidden="true" />
        )}
      </div>

      {hasChildren && !isCollapsed && (
        <ul className="ml-4 mt-2 space-y-2 border-l border-border pl-4 sm:ml-6 sm:pl-6">
          {node.children.map((child) => (
            <TreeNode
              key={child.membership_id}
              node={child}
              depth={depth + 1}
              collapsed={collapsed}
              onToggle={onToggle}
              onOpen={onOpen}
              onManage={onManage}
              canManage={canManage}
            />
          ))}
        </ul>
      )}
    </li>
  )
}

export const OrganizationPage = () => {
  const navigate = useNavigate()
  const { canViewHierarchy, canManageHierarchy, hierarchyScope, isLeadEmployee } = useEnterprise()
  const [search, setSearch] = useState('')
  const [collapsed, setCollapsed] = useState(() => new Set())
  const [manageTarget, setManageTarget] = useState(null)

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'organization'],
    queryFn: async () => {
      const response = await enterpriseApi.organization.tree()
      return response.data?.data
    },
    enabled: canViewHierarchy,
    placeholderData: (previous) => previous,
  })

  const roots = data?.roots || []
  const term = search.trim().toLowerCase()
  const visibleRoots = useMemo(() => filterNodes(roots, term), [roots, term])
  const allNodes = useMemo(() => flattenNodes(roots), [roots])

  const stats = useMemo(
    () => ({
      total: allNodes.length,
      managers: allNodes.filter((node) => node.role === 'manager').length,
      leads: allNodes.filter((node) => node.role === 'employee' && node.is_lead).length,
      employees: allNodes.filter((node) => node.role === 'employee').length,
    }),
    [allNodes],
  )

  const toggle = (id) => {
    setCollapsed((previous) => {
      const next = new Set(previous)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }

  const openMember = (node) => navigate(`/enterprise/users/${node.membership_id}`)

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  if (!canViewHierarchy) {
    return (
      <div>
        <PageHeader
          title="Organization"
          description="Your company's reporting structure."
          breadcrumbs={[{ label: 'Enterprise' }, { label: 'Organization' }]}
        />
        <Card>
          <CardContent>
            <EmptyState
              icon={LockClosedIcon}
              title="Organization chart is restricted"
              description="Managers, team leads and company admins can view the reporting structure. Ask your manager if you need access to your team's chart."
            />
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div>
      <PageHeader
        title="Organization"
        description="Who reports to whom across the enterprise. Reporting chains can have any number of levels."
        breadcrumbs={[{ label: 'Enterprise' }, { label: 'Organization' }]}
        actions={
          <Badge variant={hierarchyScope === 'all' ? 'primary' : 'outline'} dot>
            {hierarchyScope === 'all'
              ? 'Full organization'
              : isLeadEmployee
                ? 'Your team chain'
                : 'Your reporting line'}
          </Badge>
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="People in view"
          value={formatNumber(stats.total)}
          icon={UsersIcon}
          tone="primary"
          loading={isLoading}
        />
        <StatsCard
          label="Managers"
          value={formatNumber(stats.managers)}
          icon={UserGroupIcon}
          tone="purple"
          loading={isLoading}
        />
        <StatsCard
          label="Lead employees"
          value={formatNumber(stats.leads)}
          icon={UserGroupIcon}
          tone="info"
          hint="Employees with direct reports"
          loading={isLoading}
        />
        <StatsCard
          label="Employees"
          value={formatNumber(stats.employees)}
          icon={UsersIcon}
          tone="success"
          loading={isLoading}
        />
      </div>

      <Card className="mt-5">
        <CardContent className="pt-4">
          <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="text-sm font-semibold text-foreground">Reporting tree</p>
              <p className="mt-0.5 text-xs text-muted-foreground">
                {isFetching && !isLoading ? 'Refreshing…' : 'Select a member to open their profile.'}
              </p>
            </div>
            <SearchInput
              value={search}
              onChange={setSearch}
              placeholder="Search people, teams or codes…"
              className="w-full sm:w-80"
            />
          </div>

          {isLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 4 }, (_, index) => (
                <div key={index} className="skeleton h-16 rounded-xl" />
              ))}
            </div>
          ) : visibleRoots.length === 0 ? (
            <EmptyState
              icon={Squares2X2Icon}
              title={term ? 'No matching people' : 'No reporting line yet'}
              description={
                term
                  ? 'Try a different name, team or employee code.'
                  : 'Add team members or run the reporting-line backfill to build the organization chart.'
              }
            />
          ) : (
            <ul className="space-y-3">
              {visibleRoots.map((node) => (
                <TreeNode
                  key={node.membership_id}
                  node={node}
                  depth={0}
                  collapsed={term ? new Set() : collapsed}
                  onToggle={toggle}
                  onOpen={openMember}
                  onManage={(target) => setManageTarget(toMembership(target))}
                  canManage={canManageHierarchy}
                />
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <MemberManagerModal
        open={Boolean(manageTarget)}
        onClose={() => setManageTarget(null)}
        membership={manageTarget}
      />
    </div>
  )
}
