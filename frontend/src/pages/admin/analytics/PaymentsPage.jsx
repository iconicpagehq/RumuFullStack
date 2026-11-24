import { useQuery } from '@tanstack/react-query'
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { ArrowPathIcon, CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { formatNumber } from '@/utils/formatters'

const COLORS = ['#8a006b', '#e9b8df', '#22c55e', '#f59e0b', '#ef4444']

export const AnalyticsPaymentsPage = () => {
  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'analytics', 'payments'],
    queryFn: async () => {
      const response = await adminApi.analytics.payments()
      return response.data?.data
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const kpis = data?.kpis || {}
  const total = Number(kpis.successful || 0) + Number(kpis.failed || 0) + Number(kpis.pending || 0)
  const successRate = total ? Math.round((Number(kpis.successful || 0) / total) * 100) : 0
  const gatewayChart = (data?.gateway_chart?.labels || [])
    .map((label, index) => ({
      label,
      value: Number(data?.gateway_chart?.data?.[index] || 0),
    }))
    .filter((item) => item.value > 0)
  const gatewayTotal = gatewayChart.reduce((sum, item) => sum + item.value, 0)

  return (
    <div>
      <PageHeader title="Payment analytics" description="Payment success rates and gateway mix." />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Successful"
          value={formatNumber(kpis.successful || 0)}
          icon={CheckCircleIcon}
          tone="success"
        />
        <StatsCard label="Pending" value={formatNumber(kpis.pending || 0)} icon={ClockIcon} tone="warning" />
        <StatsCard label="Failed" value={formatNumber(kpis.failed || 0)} icon={XCircleIcon} tone="danger" />
        <StatsCard
          label="Success rate"
          value={`${successRate}%`}
          icon={ArrowPathIcon}
          tone="info"
          hint={`${formatNumber(total)} total attempts`}
        />
      </div>

      <div className="mt-5 grid gap-5 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div>
              <CardTitle>Payments by gateway</CardTitle>
              <p className="mt-1 text-sm text-muted-foreground">Completed volume split by provider.</p>
            </div>
          </CardHeader>
          <CardContent>
            {gatewayChart.length === 0 ? (
              <p className="py-16 text-center text-sm text-muted-foreground">No payment data yet.</p>
            ) : (
              <div className="flex flex-col items-center gap-6 sm:flex-row sm:justify-center">
                <div className="h-52 w-52">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie
                        data={gatewayChart}
                        dataKey="value"
                        nameKey="label"
                        innerRadius={60}
                        outerRadius={90}
                        paddingAngle={2}
                      >
                        {gatewayChart.map((item, index) => (
                          <Cell key={item.label} fill={COLORS[index % COLORS.length]} />
                        ))}
                      </Pie>
                      <Tooltip formatter={(value) => formatNumber(value)} />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
                <div className="w-full max-w-xs space-y-3">
                  {gatewayChart.map((item, index) => (
                    <div key={item.label} className="flex items-center justify-between gap-3 text-sm">
                      <span className="flex items-center gap-2">
                        <span
                          className="h-2.5 w-2.5 rounded-full"
                          style={{ backgroundColor: COLORS[index % COLORS.length] }}
                        />
                        <span className="capitalize text-muted-foreground">{item.label}</span>
                      </span>
                      <span className="font-semibold text-foreground">
                        {formatNumber(item.value)}
                        <span className="ml-1 font-normal text-muted-foreground">
                          {gatewayTotal ? `${Math.round((item.value / gatewayTotal) * 100)}%` : ''}
                        </span>
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Health check</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 text-sm">
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Total attempts</span>
              <span className="font-semibold text-foreground">{formatNumber(total)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Successful</span>
              <span className="font-semibold text-emerald-600 dark:text-emerald-400">
                {formatNumber(kpis.successful || 0)}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Failed</span>
              <span className="font-semibold text-destructive">{formatNumber(kpis.failed || 0)}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Pending verification</span>
              <span className="font-semibold text-foreground">{formatNumber(kpis.pending || 0)}</span>
            </div>
            <div className="border-t border-border pt-4">
              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>Success rate</span>
                <span>{successRate}%</span>
              </div>
              <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-muted">
                <div className="h-full rounded-full bg-emerald-500" style={{ width: `${successRate}%` }} />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
