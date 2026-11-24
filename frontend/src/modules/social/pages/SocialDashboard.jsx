import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { socialService } from '../services/socialService'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { AccountConnection } from '../components/AccountConnection'
import { AnalyticsCharts } from '../components/AnalyticsCharts'
import { Link } from 'react-router-dom'
import {
  ChatBubbleBottomCenterTextIcon,
  UserGroupIcon,
  HeartIcon,
  EnvelopeIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline'

export default function SocialDashboard({ basePath = '/realtor/social' }) {
  const { data, isLoading } = useQuery({
    queryKey: ['socialAnalytics', 'overview'],
    queryFn: async () => {
      const resp = await socialService.getAnalyticsOverview()
      return resp.data || {}
    },
  })

  const accounts = data?.accounts || { facebook_pages: 0, instagram_accounts: 0 }
  const messages = data?.messages || { total_conversations: 0, unread: 0 }
  const engagement = data?.engagement || { total_posts: 0, total_comments: 0, total_mentions: 0, total_likes: 0, total_shares: 0 }
  const insights = data?.insights || []

  return (
    <div className="space-y-6">
      <PageHeader
        title="Social Media & Community"
        description="Unified management for Facebook Pages, Instagram Professional, Messenger, and Direct Messages."
      />

      {/* Connect Banner if no accounts connected */}
      {accounts.facebook_pages === 0 && accounts.instagram_accounts === 0 && (
        <AccountConnection />
      )}

      {/* KPI Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatsCard
          title="Facebook Pages"
          value={isLoading ? '...' : accounts.facebook_pages}
          subtitle="Connected pages"
          icon={UserGroupIcon}
        />
        <StatsCard
          title="Instagram Accounts"
          value={isLoading ? '...' : accounts.instagram_accounts}
          subtitle="Business & creator profiles"
          icon={UserGroupIcon}
        />
        <StatsCard
          title="Unread Messages"
          value={isLoading ? '...' : messages.unread}
          subtitle={`Across ${messages.total_conversations} conversations`}
          icon={EnvelopeIcon}
        />
        <StatsCard
          title="Total Engagement"
          value={isLoading ? '...' : (engagement.total_likes + engagement.total_comments)}
          subtitle={`${engagement.total_comments} comments • ${engagement.total_likes} likes`}
          icon={HeartIcon}
        />
      </div>

      {/* Analytics Visualization */}
      <AnalyticsCharts insights={insights} engagement={engagement} />

      {/* Navigation Quick Links */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Link
          to={`${basePath}/inbox`}
          className="p-5 bg-white border border-slate-200 rounded-xl hover:border-primary-500 hover:shadow-sm transition flex items-center justify-between group"
        >
          <div className="flex items-center space-x-3">
            <span className="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
              <ChatBubbleBottomCenterTextIcon className="w-5 h-5" />
            </span>
            <div>
              <h4 className="font-semibold text-slate-900 text-sm">Unified Inbox</h4>
              <p className="text-xs text-slate-500">Reply to Messenger & Instagram DMs</p>
            </div>
          </div>
          <ArrowRightIcon className="w-4 h-4 text-slate-400 group-hover:text-primary-600 transition" />
        </Link>

        <Link
          to={`${basePath}/comments`}
          className="p-5 bg-white border border-slate-200 rounded-xl hover:border-primary-500 hover:shadow-sm transition flex items-center justify-between group"
        >
          <div className="flex items-center space-x-3">
            <span className="p-2.5 bg-purple-50 text-purple-600 rounded-lg">
              <HeartIcon className="w-5 h-5" />
            </span>
            <div>
              <h4 className="font-semibold text-slate-900 text-sm">Comments & Moderation</h4>
              <p className="text-xs text-slate-500">Engage and moderate public comments</p>
            </div>
          </div>
          <ArrowRightIcon className="w-4 h-4 text-slate-400 group-hover:text-primary-600 transition" />
        </Link>

        <Link
          to={`${basePath}/accounts`}
          className="p-5 bg-white border border-slate-200 rounded-xl hover:border-primary-500 hover:shadow-sm transition flex items-center justify-between group"
        >
          <div className="flex items-center space-x-3">
            <span className="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
              <UserGroupIcon className="w-5 h-5" />
            </span>
            <div>
              <h4 className="font-semibold text-slate-900 text-sm">Connected Accounts</h4>
              <p className="text-xs text-slate-500">Manage OAuth permissions & syncing</p>
            </div>
          </div>
          <ArrowRightIcon className="w-4 h-4 text-slate-400 group-hover:text-primary-600 transition" />
        </Link>
      </div>
    </div>
  )
}

