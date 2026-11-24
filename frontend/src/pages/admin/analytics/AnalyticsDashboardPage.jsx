import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import {
  Area,
  AreaChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  ArrowRightIcon,
  BanknotesIcon,
  ChartPieIcon,
  CreditCardIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { PageLoader } from '@/components/ui/Spinner'
import { formatCurrency, formatDate, formatNumber } from '@/utils/formatters'

const tooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const COLORS = ['#8a006b', '#e9b8df', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4']

const toChartData = (chart) =>
  (chart?.labels || []).map((label, index) => ({
    label,
    value: Number(chart?.data?.[index] || 0),
  }))

const DonutCard = ({ title, description, chart, valueFormatter = formatNumber, icon: Icon }) => {
  const chartData = toChartData(chart).filter((item) => item.value > 0)
  const total = chartData.reduce((sum, item) => sum + item.value, 0)

  return (
    <Card>
      <CardHeader>
        <div>
          <CardTitle>{title}</CardTitle>
          {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
        </div>
        {Icon && <Icon className="h-5 w-5 text-muted-foreground" />}
      </CardHeader>
      <CardContent>
        {chartData.length === 0 ? (
          <p className="py-10 text-center text-sm text-muted-foreground">No data yet.</p>
        ) : (
          <div className="flex flex-col items-center gap-4 sm:flex-row">
            <div className="h-44 w-44 shrink-0">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={chartData}
                    dataKey="value"
                    nameKey="label"
                    innerRadius={50}
                    outerRadius={75}
                    paddingAngle={2}
                  >
                    {chartData.map((item, index) => (
                      <Cell key={item.label} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip contentStyle={tooltipStyle} formatter={(value) => valueFormatter(value)} />
                </PieChart>
              </ResponsiveContainer>
            </div>
            <div className="w-full space-y-2">
              {chartData.map((item, index) => (
                <div key={item.label} className="flex items-center justify-between gap-2 text-xs">
                  <span className="flex min-w-0 items-center gap-2">
                    <span
                      className="h-2.5 w-2.5 shrink-0 rounded-full"
                      style={{ backgroundColor: COLORS[index % COLORS.length] }}
                    />
                    <span className="truncate text-muted-foreground">{item.label}</span>
                  </span>
                  <span className="shrink-0 font-semibold text-foreground">
                    {valueFormatter(item.value)}
                    <span className="ml-1 font-normal text-muted-foreground">
                      {total ? `${Math.round((item.value / total) * 100)}%` : ''}
                    </span>
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}

export const AnalyticsDashboardPage = () => {
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'analytics', 'dashboard'],
    queryFn: async () => {
      const response = await adminApi.analytics.dashboard()
      return response.data?.data
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const kpis = data?.kpis || {}
  const revenueChart = toChartData(data?.revenue_monthly).map((item) => ({
    ...item,
    revenue: item.value,
  }))

  return (
    <div>
      <PageHeader
        title="Analytics"
        description="Revenue, customers and billing performance."
        actions={
          <Link
            to="/admin/analytics/revenue"
            className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
          >
            Revenue details
            <ArrowRightIcon className="h-3.5 w-3.5" />
          </Link>
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total revenue"
          value={formatCurrency(kpis.total_revenue || 0)}
          icon={BanknotesIcon}
          hint={`${formatNumber(kpis.successful_payments || 0)} successful payments`}
          tone="success"
        />
        <StatsCard
          label="MRR"
          value={formatCurrency(kpis.mrr || 0)}
          icon={ChartPieIcon}
          hint={`ARR ${formatCurrency(kpis.arr || 0)}`}
          tone="info"
        />
        <StatsCard
          label="Active subscriptions"
          value={formatNumber(kpis.active_subscriptions || 0)}
          icon={CreditCardIcon}
          hint={`${formatNumber(kpis.expired_subscriptions || 0)} expired`}
          tone="purple"
        />
        <StatsCard
          label="Customers"
          value={formatNumber(kpis.total_customers || 0)}
          icon={UsersIcon}
          hint={`${formatNumber(kpis.new_customers_month || 0)} new this month`}
        />
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardHeader>
            <div>
              <CardTitle>Monthly revenue</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Completed payments over time.</p>
            </div>
            <StatusBadge status="active" label="Revenue" variant="primary" />
          </CardHeader>
          <CardContent>
            {revenueChart.length === 0 ? (
              <p className="py-20 text-center text-sm text-muted-foreground">No revenue recorded yet.</p>
            ) : (
              <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={revenueChart} margin={{ top: 8, right: 8, left: -8, bottom: 0 }}>
                    <defs>
                      <linearGradient id="revenueFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#d333aa" stopOpacity={0.28} />
                        <stop offset="100%" stopColor="#d333aa" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgb(var(--border))" />
                    <XAxis
                      dataKey="label"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                      tickFormatter={(value) => `₹${Math.round(value / 1000)}k`}
                    />
                    <Tooltip
                      contentStyle={tooltipStyle}
                      formatter={(value) => formatCurrency(value)}
                    />
                    <Area
                      type="monotone"
                      dataKey="revenue"
                      name="Revenue"
                      stroke="#d333aa"
                      strokeWidth={2.5}
                      fill="url(#revenueFill)"
                    />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>This month</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Payment activity at a glance.</p>
            </div>
          </CardHeader>
          <CardContent className="space-y-4 text-sm">
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Successful payments</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.successful_payments || 0)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Pending payments</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.pending_payments || 0)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Failed payments</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.failed_payments || 0)}</span>
            </div>
            <div className="flex items-center justify-between border-t border-border pt-3">
              <span className="text-muted-foreground">New subscriptions</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.new_subscriptions_month || 0)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">New customers</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.new_customers_month || 0)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Invoices generated</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.total_invoices || 0)}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-3">
        <DonutCard
          title="Plan distribution"
          description="Customers per plan."
          chart={data?.plan_distribution}
          icon={UserGroupIcon}
        />
        <DonutCard
          title="Payment gateways"
          description="Volume by payment method."
          chart={data?.payment_methods}
          icon={CreditCardIcon}
          valueFormatter={(value) => formatCurrency(value)}
        />
        <DonutCard
          title="Invoice status"
          description="Issued invoices by state."
          chart={data?.invoice_status}
          icon={ChartPieIcon}
        />
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div>
              <CardTitle>Recent payments</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">The latest settled transactions.</p>
            </div>
            <Link to="/admin/billing/payments" className="text-sm font-semibold text-highlight hover:underline">
              View all
            </Link>
          </CardHeader>
          <CardContent>
            {data?.recent_payments?.length ? (
              <div className="divide-y divide-border">
                {data.recent_payments.map((payment) => (
                  <div key={payment.id} className="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                      <BanknotesIcon className="h-4 w-4" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold text-foreground">
                        {payment.subscription?.user?.name || 'Realtor'}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {payment.subscription?.plan_name || 'Plan'} · {formatDate(payment.payment_date || payment.created_at)}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold text-foreground">{formatCurrency(payment.amount)}</p>
                      <StatusBadge status={payment.payment_status} />
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="py-8 text-center text-sm text-muted-foreground">No payments yet.</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div>
              <CardTitle>Recent realtors</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Newest accounts on the platform.</p>
            </div>
            <Link to="/admin/realtors" className="text-sm font-semibold text-highlight hover:underline">
              View all
            </Link>
          </CardHeader>
          <CardContent>
            {data?.recent_realtors?.length ? (
              <div className="divide-y divide-border">
                {data.recent_realtors.map((realtor) => (
                  <Link
                    key={realtor.id}
                    to={`/admin/realtors/${realtor.id}`}
                    className="flex items-center gap-3 py-3 transition first:pt-0 last:pb-0 hover:opacity-80"
                  >
                    <Avatar src={realtor.avatar_url} name={realtor.name} size="sm" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold text-foreground">{realtor.name}</p>
                      <p className="truncate text-xs text-muted-foreground">{realtor.email}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-xs font-medium text-foreground">{realtor.plan?.name || 'No plan'}</p>
                      <p className="text-[11px] text-muted-foreground">{formatDate(realtor.created_at)}</p>
                    </div>
                  </Link>
                ))}
              </div>
            ) : (
              <p className="py-8 text-center text-sm text-muted-foreground">No realtors yet.</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
