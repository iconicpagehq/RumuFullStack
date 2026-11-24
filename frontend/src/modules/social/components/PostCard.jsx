import React from 'react'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
  HeartIcon,
  ChatBubbleLeftIcon,
  ShareIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/react/24/outline'

export function PostCard({ post, onSelectComments }) {
  const isInstagram = post.platform === 'instagram'
  const publishedDate = post.published_at ? new Date(post.published_at).toLocaleString() : ''

  return (
    <Card className="overflow-hidden border border-slate-200 flex flex-col hover:shadow-md transition">
      {/* Post Media */}
      {post.media_url && (
        <div className="relative aspect-video bg-slate-100 overflow-hidden">
          <img
            src={post.media_url}
            alt="Social media post"
            className="w-full h-full object-cover"
            loading="lazy"
          />
          <span
            className={`absolute top-3 left-3 px-2 py-0.5 rounded-full text-xs font-semibold shadow-sm ${
              isInstagram ? 'bg-pink-600 text-white' : 'bg-blue-600 text-white'
            }`}
          >
            {isInstagram ? 'Instagram' : 'Facebook'}
          </span>
        </div>
      )}

      {/* Content */}
      <div className="p-4 flex-1 flex flex-col justify-between">
        <div>
          {!post.media_url && (
            <div className="mb-2">
              <Badge variant={isInstagram ? 'default' : 'primary'} className="text-xs">
                {isInstagram ? 'Instagram' : 'Facebook'}
              </Badge>
            </div>
          )}

          <div className="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>{post.page?.name || 'Page'}</span>
            <span>{publishedDate}</span>
          </div>

          <p className="text-sm text-slate-800 line-clamp-3 whitespace-pre-wrap">
            {post.message || 'No caption'}
          </p>
        </div>

        {/* Engagement and actions */}
        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
          <div className="flex items-center space-x-4 text-xs font-medium text-slate-500">
            <span className="flex items-center space-x-1">
              <HeartIcon className="w-4 h-4 text-rose-500" />
              <span>{post.likes_count || 0}</span>
            </span>

            <span className="flex items-center space-x-1">
              <ChatBubbleLeftIcon className="w-4 h-4 text-blue-500" />
              <span>{post.comments_count || 0}</span>
            </span>

            {post.shares_count > 0 && (
              <span className="flex items-center space-x-1">
                <ShareIcon className="w-4 h-4 text-emerald-500" />
                <span>{post.shares_count}</span>
              </span>
            )}
          </div>

          <div className="flex items-center space-x-1">
            {onSelectComments && (
              <Button
                variant="ghost"
                size="sm"
                onClick={() => onSelectComments(post)}
                className="text-xs"
              >
                Comments
              </Button>
            )}

            {post.permalink && (
              <a
                href={post.permalink}
                target="_blank"
                rel="noreferrer"
                className="p-1.5 text-slate-400 hover:text-slate-600 transition"
                title="View original post"
              >
                <ArrowTopRightOnSquareIcon className="w-4 h-4" />
              </a>
            )}
          </div>
        </div>
      </div>
    </Card>
  )
}

