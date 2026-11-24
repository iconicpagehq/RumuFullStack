import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  ArrowRightIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { SearchInput } from '@/components/shared/SearchInput'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { Avatar } from '@/components/ui/Avatar'
import { useDebounce } from '@/hooks/useDebounce'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, formatNumber } from '@/utils/formatters'

const TABS = [
  { key: 'all', label: 'All customers' },
  { key: 'won', label: 'Won' },
  { key: 'booked', label: 'Booked' },
  { key: 'sold', label: 'Sold' },
]

export const CustomerListPage = ({ view = 'list' }) => {
  const isAnalytics = view === 'analytics'
  const basePath = '/realtor/customers'

  const [search, setSearch] = useState('')
  const [tab, setTab] = useState('all')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, tab])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['realtor', 'customers', { search: debouncedSearch, status: tab, page }],
    queryFn: async () => {
      const response = await realtorApi.customers({
        search: debouncedSearch || undefined,
        status: tab === 'all' ? undefined : tab,
        page,
      })
      return { data: response.data?.data, meta: response.data?.meta }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const stats = data?.data?.stats || { total: 0, won: 0, booked: 0, sold: 0 }
  const customers = data?.data?.customers || []
  const pagination = data?.meta || { current_page: 1, last_page: 1, per_page: PAGE_SIZE, total: 0 }

  const totalCustomers = Number(stats.total || 0)
  const wonCount = Number(stats.won || 0)
  const bookedCount = Number(stats.booked || 0)
  const soldCount = Number(stats.sold || 0)
  const winRate = totalCustomers > 0 ? Math.round((wonCount / totalCustomers) * 100) : 0

  const tabs = TABS.map((item) => ({
    ...item,
    count: item.key === 'all' ? stats.total : stats[item.key],
  }))

  const columns = [
    {
      key: 'name',
      header: 'Customer',
      render: (customer) => (
        <div className="flex items-center gap-3">
          <Avatar name={customer.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{customer.name}</p>
            <p className="truncate text-xs text-muted-foreground">{customer.email || 'No email'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (customer) => <span className="text-sm">{customer.phone || '—'}</span>,
    },
    {
      key: 'campaign',
      header: 'Campaign',
      render: (customer) => (
        <span className="text-xs text-muted-foreground">{customer.campaign?.name || '—'}</span>
      ),
    },
    {
      key: 'inventory_unit',
      header: 'Unit',
      render: (customer) =>
        customer.inventory_unit ? (
          <div className="min-w-0">
            <p className="max-w-[220px] truncate text-xs font-medium text-foreground">
              {customer.inventory_unit.full_path || customer.inventory_unit.label}
            </p>
            <p className="text-xs text-muted-foreground">
              {customer.inventory_unit.unit_number ? `Unit ${customer.inventory_unit.unit_number}` : ''}
            </p>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">No unit assigned</span>
        ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (customer) => <StatusBadge status={customer.status} />,
    },
    {
      key: 'updated_at',
      header: 'Last updated',
      render: (customer) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(customer.updated_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Customer Analytics' : 'Customers'}
        description={
          isAnalytics
            ? 'Customer growth, deals won, booked units, and sales performance.'
            : 'Leads that have progressed into booked or won business.'
        }
      />

      {/* Sub-navigation tabs */}
      <div className="mb-5 flex gap-2 border-b border-border pb-3">
        <Link
          to={`${basePath}/list`}
          className={cn(
            'rounded-lg px-3.5 py-1.5 text-xs font-medium transition',
            !isAnalytics
              ? 'bg-primary/10 font-semibold text-highlight'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
          )}
        >
          Customer List
        </Link>
        <Link
          to={`${basePath}/analytics`}
          className={cn(
            'rounded-lg px-3.5 py-1.5 text-xs font-medium transition',
            isAnalytics
              ? 'bg-primary/10 font-semibold text-highlight'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
          )}
        >
          Analytics
        </Link>
      </div>

      {isAnalytics ? (
        /* Analytics View (Pic 2 style) */
        <div className="space-y-6">
          <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
              label="Total customers"
              value={formatNumber(totalCustomers)}
              icon={UsersIcon}
              tone="primary"
              hint="Active client accounts"
              loading={isLoading}
            />
            <StatsCard
              label="Won"
              value={formatNumber(wonCount)}
              icon={CheckBadgeIcon}
              tone="success"
              hint="Successfully converted"
              loading={isLoading}
            />
            <StatsCard
              label="Booked"
              value={formatNumber(bookedCount)}
              icon={BuildingOffice2Icon}
              tone="info"
              hint="Reserved units"
              loading={isLoading}
            />
            <StatsCard
              label="Sold"
              value={formatNumber(soldCount)}
              icon={BanknotesIcon}
              tone="purple"
              hint="Fully executed sales"
              loading={isLoading}
            />
          </div>

          <div className="grid gap-5 lg:grid-cols-2">
            {/* Conversion & Win Rate */}
            <Card>
              <CardHeader className="flex flex-row items-center justify-between gap-4 pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Win Rate & Conversion</h3>
                  <p className="text-xs text-muted-foreground">Percentage of customers successfully won</p>
                </div>
                <span className="text-2xl font-bold text-highlight shrink-0">{winRate}%</span>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                  <div
                    className="h-full rounded-full bg-emerald-500 transition-all duration-500"
                    style={{ width: `${winRate}%` }}
                  />
                </div>

                <div className="grid grid-cols-3 gap-2 rounded-lg border border-border p-3 text-center">
                  <div>
                    <p className="text-xs text-muted-foreground">Total</p>
                    <p className="text-base font-bold text-foreground">{formatNumber(totalCustomers)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Won Deals</p>
                    <p className="text-base font-bold text-emerald-600 dark:text-emerald-400">{formatNumber(wonCount)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Booked Units</p>
                    <p className="text-base font-bold text-blue-600 dark:text-blue-400">{formatNumber(bookedCount)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Portfolio Status Distribution */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Customer Pipeline Distribution</h3>
                  <p className="text-xs text-muted-foreground">Deals across customer stages</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                    Won Business
                  </span>
                  <span className="font-semibold">{formatNumber(wonCount)}</span>
                </div>
                <div className="flex items-center justify-between border-b border-border pb-2 text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-blue-500" />
                    Booked Units
                  </span>
                  <span className="font-semibold">{formatNumber(bookedCount)}</span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-purple-500" />
                    Sold / Handed Over
                  </span>
                  <span className="font-semibold">{formatNumber(soldCount)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Customer Health Overview */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Customer Engagement</h3>
                  <p className="text-xs text-muted-foreground">Client base metrics</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="rounded-lg border border-border p-3">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-foreground">Won & Handed Over</span>
                    <span className="font-semibold text-emerald-600 dark:text-emerald-400">{wonCount + soldCount}</span>
                  </div>
                  <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full bg-emerald-500 rounded-full"
                      style={{ width: `${totalCustomers > 0 ? ((wonCount + soldCount) / totalCustomers) * 100 : 0}%` }}
                    />
                  </div>
                </div>
                <div className="rounded-lg border border-border p-3">
                  <div className="flex justify-between text-xs">
                    <span className="font-medium text-foreground">In Progress (Booked)</span>
                    <span className="font-semibold text-blue-600 dark:text-blue-400">{bookedCount}</span>
                  </div>
                  <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                      className="h-full bg-blue-500 rounded-full"
                      style={{ width: `${totalCustomers > 0 ? (bookedCount / totalCustomers) * 100 : 0}%` }}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Quick Actions & Navigation */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Quick Jump</h3>
                  <p className="text-xs text-muted-foreground">Directly access customer segments</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-2.5">
                <Link
                  to={`${basePath}/list`}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span>All Customers</span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list`}
                  onClick={() => setTab('won')}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-emerald-500" />
                    Won Customers ({wonCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
                <Link
                  to={`${basePath}/list`}
                  onClick={() => setTab('booked')}
                  className="flex items-center justify-between rounded-lg border border-border p-2.5 text-xs font-medium text-foreground transition hover:bg-muted/50"
                >
                  <span className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-blue-500" />
                    Booked Customers ({bookedCount})
                  </span>
                  <ArrowRightIcon className="h-3.5 w-3.5 text-muted-foreground" />
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      ) : (
        /* Customer List View (Pic 3 style) */
        <Card>
          <CardContent className="px-0 pb-0 pt-4">
            <div className="px-5">
              <Tabs tabs={tabs} value={tab} onChange={setTab} />
              <div className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                  {formatNumber(pagination.total)} customer{pagination.total === 1 ? '' : 's'}
                  {isFetching && <span className="ml-2 text-xs text-muted-foreground">Refreshing…</span>}
                </p>
                <SearchInput
                  value={search}
                  onChange={setSearch}
                  placeholder="Search name, email or phone…"
                  className="w-full sm:w-80"
                />
              </div>
            </div>
            <div className="border-t border-border">
              <DataTable
                className="rounded-none border-0"
                columns={columns}
                rows={customers}
                loading={isLoading}
                rowKey="id"
                emptyState={
                  <EmptyState
                    icon={UsersIcon}
                    title={search || tab !== 'all' ? 'No matching customers' : 'No customers yet'}
                    description={
                      search || tab !== 'all'
                        ? 'Try a different search or customer segment.'
                        : 'As leads are marked won or units are booked, they will appear here.'
                    }
                  />
                }
              />
            </div>
            <div className="border-t border-border px-4">
              <Pagination
                page={pagination.current_page || 1}
                lastPage={pagination.last_page || 1}
                total={pagination.total || 0}
                perPage={pagination.per_page || PAGE_SIZE}
                onPageChange={setPage}
              />
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
