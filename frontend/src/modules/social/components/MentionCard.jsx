import React from 'react'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/24/outline'

export function MentionCard({ mention }) {
  const isInstagram = mention.platform === 'instagram'
  const time = mention.mentioned_at ? new Date(mention.mentioned_at).toLocaleString() : ''

  return (
    <Card className="p-4 border border-slate-200 hover:shadow-md transition flex items-start space-x-4">
      {mention.media_url ? (
        <img
          src={mention.media_url}
          alt="Mention preview"
          className="w-20 h-20 rounded-lg object-cover flex-shrink-0 bg-slate-100"
        />
      ) : (
        <div className="w-20 h-20 rounded-lg bg-slate-100 flex items-center justify-center text-xs text-slate-400 flex-shrink-0">
          No media
        </div>
      )}

      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <span className="font-semibold text-sm text-slate-900">
              {mention.author_username ? `@${mention.author_username}` : 'User'}
            </span>
            <Badge variant={isInstagram ? 'default' : 'primary'} className="text-[10px]">
              {isInstagram ? 'Instagram Tag' : 'Facebook Tag'}
            </Badge>
          </div>
          <span className="text-xs text-slate-400">{time}</span>
        </div>

        <p className="text-sm text-slate-700 mt-2 line-clamp-3 whitespace-pre-wrap">
          {mention.caption || 'No caption available'}
        </p>

        <div className="mt-2 flex items-center justify-between text-xs text-slate-400">
          <span>Target: {mention.page?.name || 'Social Account'}</span>
          {mention.raw_data?.permalink && (
            <a
              href={mention.raw_data.permalink}
              target="_blank"
              rel="noreferrer"
              className="text-primary-600 hover:text-primary-700 flex items-center space-x-1"
            >
              <span>View Original</span>
              <ArrowTopRightOnSquareIcon className="w-3 h-3" />
            </a>
          )}
        </div>
      </div>
    </Card>
  )
}

