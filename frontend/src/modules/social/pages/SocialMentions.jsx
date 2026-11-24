import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { socialService } from '../services/socialService'
import { MentionCard } from '../components/MentionCard'
import { PageHeader } from '@/components/shared/PageHeader'

export default function SocialMentions() {
  const [platform, setPlatform] = useState('all')

  const { data, isLoading } = useQuery({
    queryKey: ['socialMentions', { platform: platform === 'all' ? undefined : platform }],
    queryFn: async () => {
      const resp = await socialService.getMentions({
        platform: platform === 'all' ? undefined : platform,
        per_page: 30,
      })
      return resp.data?.data || resp.data || []
    },
  })

  const mentions = Array.isArray(data) ? data : []

  return (
    <div className="space-y-6">
      <PageHeader
        title="Mentions & Tags"
        description="Track audience mentions, brand tags, and photo tags on Instagram and Facebook."
      />

      {/* Platform Filter */}
      <div className="flex space-x-2">
        {['all', 'facebook', 'instagram'].map((p) => (
          <button
            key={p}
            onClick={() => setPlatform(p)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition capitalize ${
              platform === p
                ? 'bg-primary-600 text-white shadow-sm'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
            }`}
          >
            {p === 'all' ? 'All Channels' : p}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="p-12 text-center text-sm text-slate-400 bg-white rounded-xl border border-slate-200">
          Loading mentions...
        </div>
      ) : mentions.length === 0 ? (
        <div className="p-16 text-center text-sm text-slate-500 bg-white rounded-xl border border-dashed border-slate-300">
          <p className="font-medium text-slate-700">No mentions detected</p>
          <p className="text-xs text-slate-400 mt-1">
            When users tag your Facebook Page or Instagram Business profile in posts and stories, they will appear here in real-time.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {mentions.map((mention) => (
            <MentionCard key={mention.id} mention={mention} />
          ))}
        </div>
      )}
    </div>
  )
}

