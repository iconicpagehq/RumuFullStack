import React from 'react'
import { Card } from '@/components/ui/Card'
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  BarChart,
  Bar,
  Legend,
} from 'recharts'

export function AnalyticsCharts({ insights = [], engagement = {} }) {
  // Aggregate daily metrics
  const chartDataMap = {}

  insights.forEach((item) => {
    const date = item.recorded_date
    if (!chartDataMap[date]) {
      chartDataMap[date] = { date, impressions: 0, engagement: 0 }
    }

    const val = item.metrics?.value || 0
    const numVal = typeof val === 'number' ? val : 0

    if (item.metric_type.includes('impression')) {
      chartDataMap[date].impressions += numVal
    } else {
      chartDataMap[date].engagement += numVal
    }
  })

  const timeSeriesData = Object.values(chartDataMap).sort((a, b) =>
    a.date.localeCompare(b.date)
  )

  const distributionData = [
    { name: 'Likes', count: engagement.total_likes || 0, fill: '#f43f5e' },
    { name: 'Comments', count: engagement.total_comments || 0, fill: '#3b82f6' },
    { name: 'Mentions', count: engagement.total_mentions || 0, fill: '#a855f7' },
    { name: 'Shares', count: engagement.total_shares || 0, fill: '#10b981' },
  ]

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {/* Impressions & Reach Trend */}
      <Card className="p-6 border border-slate-200 lg:col-span-2">
        <h4 className="font-semibold text-slate-900 text-sm mb-4">
          Audience Impressions & Reach Trend (Last 14 Days)
        </h4>

        {timeSeriesData.length === 0 ? (
          <div className="h-64 flex items-center justify-center text-xs text-slate-400">
            No daily insight records available yet. Sync accounts to load live metrics.
          </div>
        ) : (
          <div className="h-64 w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={timeSeriesData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorImp" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.4} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="date" tick={{ fontSize: 11, fill: '#94a3b8' }} />
                <YAxis tick={{ fontSize: 11, fill: '#94a3b8' }} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: '#1e293b',
                    borderRadius: '8px',
                    color: '#fff',
                    fontSize: '12px',
                    border: 'none',
                  }}
                />
                <Area
                  type="monotone"
                  dataKey="impressions"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  fillOpacity={1}
                  fill="url(#colorImp)"
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        )}
      </Card>

      {/* Engagement Breakdown */}
      <Card className="p-6 border border-slate-200">
        <h4 className="font-semibold text-slate-900 text-sm mb-4">Engagement Distribution</h4>

        <div className="h-64 w-full">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={distributionData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
              <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
              <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#94a3b8' }} />
              <YAxis tick={{ fontSize: 11, fill: '#94a3b8' }} />
              <Tooltip
                contentStyle={{
                  backgroundColor: '#1e293b',
                  borderRadius: '8px',
                  color: '#fff',
                  fontSize: '12px',
                  border: 'none',
                }}
              />
              <Bar dataKey="count" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </Card>
    </div>
  )
}

