import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { UsersIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDate, formatNumber, titleCase } from '@/utils/formatters'

export const UsersPage = () => {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('all')
  const [page, setPage] = useState(1)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'users', { search, role, page }],
    queryFn: async () => {
      const response = await adminApi.users.list({
        search: search || undefined,
        role: role === 'all' ? undefined : role,
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
      key: 'name',
      header: 'User',
      render: (user) => (
        <div className="flex min-w-[200px] items-center gap-3">
          <Avatar src={user.avatar_url} name={user.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{user.name}</p>
            <p className="truncate text-xs text-muted-foreground">{user.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (user) => (
        <Badge variant={user.role === 'admin' ? 'purple' : 'info'}>{titleCase(user.role)}</Badge>
      ),
    },
    {
      key: 'plan',
      header: 'Plan',
      render: (user) =>
        user.plan ? (
          <span className="text-sm font-medium text-foreground">{user.plan.name}</span>
        ) : (
          <span className="text-sm text-muted-foreground">—</span>
        ),
    },
    {
      key: 'company',
      header: 'Company',
      render: (user) => <span className="text-sm text-muted-foreground">{user.company || '—'}</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (user) =>
        user.subscription_suspended ? <StatusBadge status="suspended" /> : <StatusBadge status="active" />,
    },
    {
      key: 'created_at',
      header: 'Joined',
      render: (user) => <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(user.created_at)}</span>,
    },
  ]

  return (
    <div>
      <PageHeader title="Users" description="All accounts registered on the platform." />

      <Tabs
        className="mb-4"
        value={role}
        onChange={(value) => {
          setRole(value)
          setPage(1)
        }}
        tabs={[
          { key: 'all', label: 'All users' },
          { key: 'realtor', label: 'Realtors' },
          { key: 'admin', label: 'Admins' },
        ]}
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <SearchInput
          value={search}
          onChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          placeholder="Search name, email or company…"
          className="w-full max-w-sm"
        />
        <span className="ml-auto text-xs text-muted-foreground">
          {formatNumber(data?.meta?.total || 0)} users
        </span>
      </div>

      <DataTable
        columns={columns}
        rows={data?.rows || []}
        loading={isLoading}
        onRowClick={(user) => navigate(`/admin/users/${user.id}`)}
        emptyState={
          <EmptyState
            icon={UsersIcon}
            title="No users found"
            description="Try a different search or role filter."
          />
        }
      />

      <Pagination
        page={data?.meta?.current_page || 1}
        lastPage={data?.meta?.last_page || 1}
        total={data?.meta?.total || 0}
        perPage={data?.meta?.per_page || PAGE_SIZE}
        onPageChange={setPage}
      />
    </div>
  )
}
