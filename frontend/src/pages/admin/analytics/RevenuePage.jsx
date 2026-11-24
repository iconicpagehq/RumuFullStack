import { useQuery } from '@tanstack/react-query'
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { BanknotesIcon, ChartPieIcon, CurrencyRupeeIcon, UsersIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { formatCurrency } from '@/utils/formatters'

const tooltipStyle = {
  background: 'rgb(var(--card))',
  border: '1px solid rgb(var(--border))',
  borderRadius: 12,
  fontSize: 12,
  boxShadow: '0 12px 32px -16px rgba(16,24,40,.3)',
}

const toChartData = (chart) =>
  (chart?.labels || []).map((label, index) => ({
    label,
    revenue: Number(chart?.data?.[index] || 0),
  }))

export const RevenuePage = () => {
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'analytics', 'revenue'],
    queryFn: async () => {
      const response = await adminApi.analytics.revenue()
      return response.data?.data
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const kpis = data?.kpis || {}
  const daily = toChartData(data?.daily_chart)
  const monthly = toChartData(data?.monthly_chart)

  return (
    <div>
      <PageHeader title="Revenue" description="Revenue performance across subscriptions and payments." />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Total revenue"
          value={formatCurrency(kpis.total || 0)}
          icon={CurrencyRupeeIcon}
          tone="success"
        />
        <StatsCard label="MRR" value={formatCurrency(kpis.mrr || 0)} icon={BanknotesIcon} />
        <StatsCard label="ARR" value={formatCurrency(kpis.arr || 0)} icon={ChartPieIcon} tone="info" />
        <StatsCard
          label="ARPC"
          value={formatCurrency(kpis.arpc || 0)}
          icon={UsersIcon}
          hint="Average revenue per customer"
          tone="purple"
        />
      </div>

      <div className="mt-5 grid gap-5 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <div>
              <CardTitle>Last 30 days</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Daily completed payment revenue.</p>
            </div>
          </CardHeader>
          <CardContent>
            {daily.length === 0 ? (
              <p className="py-20 text-center text-sm text-muted-foreground">No revenue in this period.</p>
            ) : (
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={daily} margin={{ top: 8, right: 8, left: -8, bottom: 0 }}>
                    <defs>
                      <linearGradient id="dailyRevenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#d333aa" stopOpacity={0.28} />
                        <stop offset="100%" stopColor="#d333aa" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="rgb(var(--border))" />
                    <XAxis
                      dataKey="label"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 10 }}
                      interval="preserveStartEnd"
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fill: 'rgb(var(--muted-foreground))', fontSize: 11 }}
                      tickFormatter={(value) => `₹${Math.round(value / 1000)}k`}
                    />
                    <Tooltip contentStyle={tooltipStyle} formatter={(value) => formatCurrency(value)} />
                    <Area
                      type="monotone"
                      dataKey="revenue"
                      stroke="#d333aa"
                      strokeWidth={2.5}
                      fill="url(#dailyRevenue)"
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
              <CardTitle>Monthly revenue</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Completed payments by month.</p>
            </div>
          </CardHeader>
          <CardContent>
            {monthly.length === 0 ? (
              <p className="py-20 text-center text-sm text-muted-foreground">No revenue recorded yet.</p>
            ) : (
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={monthly} margin={{ top: 8, right: 8, left: -8, bottom: 0 }}>
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
                    <Tooltip contentStyle={tooltipStyle} formatter={(value) => formatCurrency(value)} />
                    <Bar dataKey="revenue" fill="#22c55e" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card className="mt-5">
        <CardHeader>
          <CardTitle>Monthly breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="divide-y divide-border">
            {monthly.length ? (
              monthly.map((row) => (
                <div key={row.label} className="flex items-center justify-between py-2.5 text-sm">
                  <span className="text-muted-foreground">{row.label}</span>
                  <span className="font-semibold text-foreground">{formatCurrency(row.revenue)}</span>
                </div>
              ))
            ) : (
              <p className="py-6 text-center text-sm text-muted-foreground">No data yet.</p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
