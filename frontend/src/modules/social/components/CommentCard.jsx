import React, { useState } from 'react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import {
  ArrowUturnLeftIcon,
  EyeSlashIcon,
  EyeIcon,
  TrashIcon,
} from '@heroicons/react/24/outline'

export function CommentCard({ comment, onReply, onModerate, onDelete }) {
  const [showReplyBox, setShowReplyBox] = useState(false)
  const [replyText, setReplyText] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSendReply = async () => {
    if (!replyText.trim() || isSubmitting) return
    setIsSubmitting(true)
    try {
      await onReply(comment.id, replyText)
      setReplyText('')
      setShowReplyBox(false)
    } finally {
      setIsSubmitting(false)
    }
  }

  const isInstagram = comment.platform === 'instagram'
  const time = comment.created_time ? new Date(comment.created_time).toLocaleString() : ''

  return (
    <Card className="p-4 border border-slate-200">
      <div className="flex items-start justify-between">
        <div className="flex items-start space-x-3">
          <div
            className={`w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 ${
              isInstagram ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700'
            }`}
          >
            {comment.from_name ? comment.from_name[0] : 'U'}
          </div>

          <div>
            <div className="flex items-center space-x-2">
              <span className="font-semibold text-sm text-slate-900">{comment.from_name || 'User'}</span>
              <Badge variant={isInstagram ? 'default' : 'primary'} className="text-[10px]">
                {isInstagram ? 'Instagram' : 'Facebook'}
              </Badge>
              {comment.is_hidden && (
                <Badge variant="warning" className="text-[10px]">
                  Hidden
                </Badge>
              )}
            </div>

            <p className="text-xs text-slate-400 mt-0.5">{time}</p>

            <p className="text-sm text-slate-800 mt-2 whitespace-pre-wrap">{comment.message}</p>
          </div>
        </div>

        {/* Action buttons */}
        <div className="flex items-center space-x-1">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setShowReplyBox(!showReplyBox)}
            title="Reply to comment"
          >
            <ArrowUturnLeftIcon className="w-4 h-4 mr-1 text-slate-600" />
            Reply
          </Button>

          <Button
            variant="ghost"
            size="sm"
            onClick={() => onModerate(comment.id, !comment.is_hidden)}
            title={comment.is_hidden ? 'Unhide comment' : 'Hide comment'}
          >
            {comment.is_hidden ? (
              <EyeIcon className="w-4 h-4 text-emerald-600" />
            ) : (
              <EyeSlashIcon className="w-4 h-4 text-amber-600" />
            )}
          </Button>

          <Button
            variant="ghost"
            size="sm"
            onClick={() => onDelete(comment.id)}
            className="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
            title="Delete comment"
          >
            <TrashIcon className="w-4 h-4" />
          </Button>
        </div>
      </div>

      {/* Reply Input Box */}
      {showReplyBox && (
        <div className="mt-3 pl-12 flex items-center space-x-2">
          <input
            type="text"
            placeholder={`Reply to ${comment.from_name || 'comment'}...`}
            value={replyText}
            onChange={(e) => setReplyText(e.target.value)}
            className="flex-1 text-xs px-3 py-1.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500"
          />
          <Button
            size="sm"
            onClick={handleSendReply}
            disabled={!replyText.trim() || isSubmitting}
            className="text-xs"
          >
            {isSubmitting ? 'Posting...' : 'Post Reply'}
          </Button>
        </div>
      )}

      {/* Nested Replies */}
      {comment.replies && comment.replies.length > 0 && (
        <div className="mt-4 pl-10 border-l-2 border-slate-100 space-y-2">
          {comment.replies.map((reply) => (
            <div key={reply.id} className="p-2.5 rounded-lg bg-slate-50 text-xs">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-slate-800">{reply.from_name || 'Page Admin'}</span>
                <span className="text-[10px] text-slate-400">
                  {reply.created_time ? new Date(reply.created_time).toLocaleString() : ''}
                </span>
              </div>
              <p className="text-slate-700 mt-1 whitespace-pre-wrap">{reply.message}</p>
            </div>
          ))}
        </div>
      )}
    </Card>
  )
}

