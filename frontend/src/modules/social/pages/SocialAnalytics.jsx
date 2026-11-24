import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { socialService } from '../services/socialService'
import { AnalyticsCharts } from '../components/AnalyticsCharts'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import {
  ChartBarIcon,
  EyeIcon,
  HeartIcon,
  ChatBubbleBottomCenterIcon,
} from '@heroicons/react/24/outline'

export default function SocialAnalytics() {
  const { data, isLoading } = useQuery({
    queryKey: ['socialAnalytics', 'overview'],
    queryFn: async () => {
      const resp = await socialService.getAnalyticsOverview()
      return resp.data || {}
    },
  })

  const engagement = data?.engagement || {}
  const insights = data?.insights || []

  return (
    <div className="space-y-6">
      <PageHeader
        title="Social Performance Analytics"
        description="Official Graph API metrics for impressions, reach, post engagement, comments, and audience interactions."
      />

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatsCard
          title="Total Published Posts"
          value={isLoading ? '...' : (engagement.total_posts || 0)}
          subtitle="Facebook & Instagram"
          icon={ChartBarIcon}
        />
        <StatsCard
          title="Total Post Likes"
          value={isLoading ? '...' : (engagement.total_likes || 0)}
          subtitle="Audience reactions"
          icon={HeartIcon}
        />
        <StatsCard
          title="Total Post Comments"
          value={isLoading ? '...' : (engagement.total_comments || 0)}
          subtitle="Customer conversations"
          icon={ChatBubbleBottomCenterIcon}
        />
        <StatsCard
          title="Brand Mentions"
          value={isLoading ? '...' : (engagement.total_mentions || 0)}
          subtitle="Tagged content"
          icon={EyeIcon}
        />
      </div>

      {/* Recharts Analytics Charts */}
      <AnalyticsCharts insights={insights} engagement={engagement} />
    </div>
  )
}

