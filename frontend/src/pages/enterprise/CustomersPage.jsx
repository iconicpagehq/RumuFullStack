import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  ArrowRightIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { StageBadge } from '@/components/enterprise/StageBadge'
import { Avatar } from '@/components/ui/Avatar'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Tabs } from '@/components/ui/Tabs'
import { useDebounce } from '@/hooks/useDebounce'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'

const TABS = [
  { key: 'all', label: 'All customers' },
  { key: 'won', label: 'Won' },
  { key: 'booked', label: 'Booked' },
]

export const CustomersPage = ({ view = 'list' }) => {
  const navigate = useNavigate()
  const isAnalytics = view === 'analytics'
  const basePath = '/enterprise/customers'

  const [search, setSearch] = useState('')
  const [tab, setTab] = useState('all')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, tab])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'customers', { search: debouncedSearch, tab, page }],
    queryFn: async () => {
      const response = await enterpriseApi.customers({
        search: debouncedSearch || undefined,
        stage: tab === 'won' ? 'won' : undefined,
        booked: tab === 'booked' ? 1 : undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const customers = data?.rows || []
  const meta = data?.meta || {}
  const stats = meta.stats || {}

  const totalCustomers = Number(stats.total || 0)
  const wonCount = Number(stats.won || 0)
  const bookedCount = Number(stats.booked || 0)
  const soldCount = Number(stats.sold || 0)
  const dealValue = Number(stats.deal_value || 0)
  const winRate = totalCustomers > 0 ? Math.round((wonCount / totalCustomers) * 100) : 0

  const tabs = TABS.map((item) => ({
    ...item,
    count: item.key === 'all' ? stats.total : item.key === 'won' ? stats.won : stats.booked,
  }))

  const columns = [
    {
      key: 'name',
      header: 'Customer',
      render: (customer) => (
        <div className="flex min-w-0 items-center gap-3">
          <Avatar name={customer.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{customer.name}</p>
            <p className="truncate text-xs text-muted-foreground">{customer.email || customer.company || 'No email'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'Phone',
      render: (customer) => <span className="whitespace-nowrap text-xs text-foreground">{customer.phone || '—'}</span>,
    },
    {
      key: 'unit',
      header: 'Unit',
      render: (customer) =>
        customer.unit ? (
          <div className="min-w-0">
            <Link
              to={`/enterprise/inventory/${customer.project?.id || ''}`}
              onClick={(event) => event.stopPropagation()}
              className="block max-w-[220px] truncate text-xs font-medium text-foreground hover:text-highlight"
            >
              {customer.unit.full_path || customer.unit.unit_number}
            </Link>
            <p className="truncate text-xs text-muted-foreground">{customer.project?.name || 'Project'}</p>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
    {
      key: 'assignee',
      header: 'Owner',
      render: (customer) => (
        <div className="min-w-0 text-xs">
          <p className="truncate font-medium text-foreground">{customer.assignee?.name || 'Unassigned'}</p>
          <p className="truncate text-muted-foreground">{customer.team?.name || 'No team'}</p>
        </div>
      ),
    },
    {
      key: 'stage',
      header: 'Stage',
      render: (customer) => <StageBadge stage={customer.stage} />,
    },
    {
      key: 'deal_value',
      header: 'Value',
      render: (customer) => (
        <span className="whitespace-nowrap text-sm font-semibold text-foreground">
          {customer.deal_value ? formatCurrency(customer.deal_value) : '—'}
        </span>
      ),
    },
    {
      key: 'won_at',
      header: 'Won',
      render: (customer) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDate(customer.won_at)}</span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title={isAnalytics ? 'Customer Analytics' : 'Customers'}
        description={
          isAnalytics
            ? 'Customer conversion rates, total deal volume, and team sales pipeline.'
            : 'Leads that converted into won deals or booked inventory.'
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
              icon={UserGroupIcon}
              tone="purple"
              hint="Active converted accounts"
              loading={isLoading}
            />
            <StatsCard
              label="Won"
              value={formatNumber(wonCount)}
              icon={CheckBadgeIcon}
              tone="success"
              hint="Confirmed wins"
              loading={isLoading}
            />
            <StatsCard
              label="Booked"
              value={formatNumber(bookedCount)}
              icon={BuildingOffice2Icon}
              tone="info"
              hint="Reserved inventory"
              loading={isLoading}
            />
            <StatsCard
              label="Total deal value"
              value={formatCurrency(dealValue)}
              icon={BanknotesIcon}
              tone="warning"
              hint="Gross converted volume"
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
                    <p className="text-xs text-muted-foreground">Won</p>
                    <p className="text-base font-bold text-emerald-600 dark:text-emerald-400">{formatNumber(wonCount)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Booked</p>
                    <p className="text-base font-bold text-blue-600 dark:text-blue-400">{formatNumber(bookedCount)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Pipeline Distribution */}
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
                {soldCount > 0 && (
                  <div className="flex items-center justify-between text-xs">
                    <span className="flex items-center gap-2">
                      <span className="h-2 w-2 rounded-full bg-purple-500" />
                      Sold Units
                    </span>
                    <span className="font-semibold">{formatNumber(soldCount)}</span>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Total Deal Value Overview */}
            <Card>
              <CardHeader className="pb-3">
                <div>
                  <h3 className="text-sm font-semibold text-foreground">Portfolio Value</h3>
                  <p className="text-xs text-muted-foreground">Financial impact of customer deals</p>
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="rounded-lg border border-border p-3">
                  <p className="text-xs text-muted-foreground">Gross Closed Value</p>
                  <p className="text-xl font-bold text-foreground">{formatCurrency(dealValue)}</p>
                </div>
                <div className="rounded-lg border border-border p-3">
                  <p className="text-xs text-muted-foreground">Avg. Value Per Customer</p>
                  <p className="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                    {totalCustomers > 0 ? formatCurrency(dealValue / totalCustomers) : '—'}
                  </p>
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
                <p className="text-xs text-muted-foreground">
                  {formatNumber(meta.total || 0)} customer{meta.total === 1 ? '' : 's'}
                  {stats.sold ? ` · ${formatNumber(stats.sold)} with sold units` : ''}
                  {isFetching && <span className="ml-2">Refreshing…</span>}
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
                onRowClick={(customer) => navigate(`/enterprise/leads/${customer.id}`)}
                emptyState={
                  <EmptyState
                    icon={UserGroupIcon}
                    title={search || tab !== 'all' ? 'No matching customers' : 'No customers yet'}
                    description={
                      search || tab !== 'all'
                        ? 'Try a different search or customer segment.'
                        : 'Leads marked won or linked to a booked unit will appear here.'
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
      )}
    </div>
  )
}
