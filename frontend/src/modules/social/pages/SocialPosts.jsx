import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { socialService } from '../services/socialService'
import { useSocialAccounts } from '../hooks/useSocialAccounts'
import { PostCard } from '../components/PostCard'
import { PageHeader } from '@/components/shared/PageHeader'
import { useNavigate } from 'react-router-dom'

export default function SocialPosts({ basePath = '/realtor/social' }) {
  const navigate = useNavigate()
  const [platform, setPlatform] = useState('all')
  const [selectedPageId, setSelectedPageId] = useState('all')
  const [search, setSearch] = useState('')

  const { accounts } = useSocialAccounts()
  const allPages = (accounts || []).flatMap((acc) => acc.pages || [])

  const { data, isLoading } = useQuery({
    queryKey: [
      'socialPosts',
      {
        platform: platform === 'all' ? undefined : platform,
        social_page_id: selectedPageId === 'all' ? undefined : selectedPageId,
        search: search || undefined,
      },
    ],
    queryFn: async () => {
      const resp = await socialService.getPosts({
        platform: platform === 'all' ? undefined : platform,
        social_page_id: selectedPageId === 'all' ? undefined : selectedPageId,
        search: search || undefined,
        per_page: 50,
      })
      return resp.data?.data || resp.data || []
    },
  })

  const posts = Array.isArray(data) ? data : []

  return (
    <div className="space-y-6">
      <PageHeader
        title="Posts & Media"
        description="Browse, inspect, and analyze content published across Facebook Pages and Instagram Professional accounts."
      />

      {/* Filter and Search Bar */}
      <div className="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200">
        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
          {/* Platform toggle */}
          <div className="flex space-x-1 bg-slate-100 p-1 rounded-lg">
            {['all', 'facebook', 'instagram'].map((p) => (
              <button
                key={p}
                onClick={() => setPlatform(p)}
                className={`px-3 py-1.5 rounded-md text-xs font-medium transition capitalize ${
                  platform === p
                    ? 'bg-primary-600 text-white shadow-sm'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                {p === 'all' ? 'All Platforms' : p}
              </button>
            ))}
          </div>

          {/* Page Selector dropdown */}
          {allPages.length > 0 && (
            <select
              value={selectedPageId}
              onChange={(e) => setSelectedPageId(e.target.value)}
              className="text-xs px-3 py-1.5 border border-slate-200 rounded-lg bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-primary-500"
            >
              <option value="all">All Pages ({allPages.length})</option>
              {allPages.map((pg) => (
                <option key={pg.id} value={pg.id}>
                  {pg.name} ({pg.platform})
                </option>
              ))}
            </select>
          )}
        </div>

        <input
          type="text"
          placeholder="Search post captions..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full md:w-72 text-sm px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
        />
      </div>

      {/* Posts Grid */}
      {isLoading ? (
        <div className="p-12 text-center text-sm text-slate-400 bg-white rounded-xl border border-slate-200">
          Loading published posts...
        </div>
      ) : posts.length === 0 ? (
        <div className="p-16 text-center text-sm text-slate-500 bg-white rounded-xl border border-dashed border-slate-300">
          <p className="font-medium text-slate-700">No posts found</p>
          <p className="text-xs text-slate-400 mt-1">
            Connect an active Facebook Page or Instagram Business profile, or click &quot;Sync&quot; in Connected Accounts to pull latest media.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {posts.map((post) => (
            <PostCard
              key={post.id}
              post={post}
              onSelectComments={() => navigate(`${basePath}/comments`)}
            />
          ))}
        </div>
      )}
    </div>
  )
}

