import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { socialService } from '../services/socialService'
import { CommentCard } from '../components/CommentCard'
import { PageHeader } from '@/components/shared/PageHeader'
import { toast } from 'react-hot-toast'
import { ArrowPathIcon } from '@heroicons/react/24/outline'

export default function SocialComments() {
  const queryClient = useQueryClient()
  const [platform, setPlatform] = useState('instagram')

  // We can fetch posts first or load posts with recent comments
  const { data: postsData, isLoading: isPostsLoading } = useQuery({
    queryKey: ['socialPosts', { platform: platform === 'all' ? undefined : platform }],
    queryFn: async () => {
      const resp = await socialService.getPosts({
        platform: platform === 'all' ? undefined : platform,
        per_page: 50,
      })
      return resp.data?.data || resp.data || []
    },
  })

  const [selectedPostId, setSelectedPostId] = useState(null)
  const selectedPost = postsData?.find((p) => p.id === selectedPostId)

  const { data: commentsData, isLoading: isCommentsLoading } = useQuery({
    queryKey: ['socialComments', selectedPostId],
    queryFn: async () => {
      if (!selectedPostId) return []
      const resp = await socialService.getPostComments(selectedPostId)
      return resp.data?.data || resp.data || []
    },
    enabled: !!selectedPostId,
  })

  // Auto-select first post with comments if available, or first post
  React.useEffect(() => {
    if (postsData && postsData.length > 0 && !selectedPostId) {
      const withComments = postsData.find((p) => Number(p.comments_count) > 0)
      setSelectedPostId(withComments ? withComments.id : postsData[0].id)
    }
  }, [postsData, selectedPostId])

  const replyMutation = useMutation({
    mutationFn: ({ commentId, message }) => socialService.replyComment(commentId, message),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialComments', selectedPostId] })
      toast.success('Reply published.')
    },
    onError: (err) => {
      toast.error(err?.response?.data?.message || 'Failed to post reply.')
    },
  })

  const moderateMutation = useMutation({
    mutationFn: ({ commentId, isHidden }) => socialService.moderateComment(commentId, isHidden),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialComments', selectedPostId] })
      toast.success('Comment visibility updated.')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (commentId) => socialService.deleteComment(commentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialComments', selectedPostId] })
      toast.success('Comment deleted.')
    },
  })

  const syncCommentsMutation = useMutation({
    mutationFn: (postId) => socialService.syncPostComments(postId),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['socialComments', selectedPostId] })
      toast.success(res?.message || 'Comments synced successfully.')
    },
    onError: (err) => {
      const msg = err?.response?.data?.message || 'Failed to sync comments from Meta.'
      toast.error(msg, { duration: 6000 })
    },
  })

  return (
    <div className="space-y-6">
      <PageHeader
        title="Comments & Moderation"
        description="Monitor, reply to, and moderate customer comments on Facebook Page posts and Instagram Media."
      />

      {/* Platform Filter Tabs */}
      <div className="flex space-x-2">
        {['all', 'facebook', 'instagram'].map((p) => (
          <button
            key={p}
            onClick={() => {
              setPlatform(p)
              setSelectedPostId(null)
            }}
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

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Posts selector sidebar */}
        <div className="space-y-3">
          <h4 className="text-sm font-semibold text-slate-900">Select Post</h4>
          {isPostsLoading ? (
            <div className="p-4 text-center text-xs text-slate-400 bg-white rounded-lg border border-slate-200">
              Loading posts...
            </div>
          ) : !postsData || postsData.length === 0 ? (
            <div className="p-6 text-center text-xs text-slate-400 bg-white rounded-lg border border-slate-200">
              No posts found.
            </div>
          ) : (
            <div className="space-y-2 max-h-[600px] overflow-y-auto">
              {postsData.map((post) => (
                <div
                  key={post.id}
                  onClick={() => setSelectedPostId(post.id)}
                  className={`p-3 rounded-xl border cursor-pointer transition text-xs ${
                    selectedPostId === post.id
                      ? 'bg-primary-50 border-primary-500 shadow-sm'
                      : 'bg-white border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <div className="flex items-center justify-between mb-1">
                    <div className="flex items-center gap-1.5">
                      <span
                        className={`px-1.5 py-0.5 rounded text-[10px] font-semibold capitalize ${
                          post.platform === 'instagram' ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700'
                        }`}
                      >
                        {post.platform}
                      </span>
                      {Number(post.comments_count) > 0 && (
                        <span className="px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800">
                          {post.comments_count} {Number(post.comments_count) === 1 ? 'comment' : 'comments'}
                        </span>
                      )}
                    </div>
                    <span className="text-slate-400">
                      {post.published_at ? new Date(post.published_at).toLocaleDateString() : ''}
                    </span>
                  </div>
                  <p className="line-clamp-2 text-slate-800 font-medium">{post.message || 'Media post'}</p>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Comments Feed for Selected Post */}
        <div className="lg:col-span-2 space-y-4">
          <div className="flex items-center justify-between">
            <h4 className="text-sm font-semibold text-slate-900">
              Comments Thread {commentsData ? `(${commentsData.length})` : ''}
            </h4>
            {selectedPostId && selectedPost?.platform === 'instagram' && (
              <button
                onClick={() => syncCommentsMutation.mutate(selectedPostId)}
                disabled={syncCommentsMutation.isPending}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 transition shadow-sm"
              >
                <ArrowPathIcon className={`w-3.5 h-3.5 ${syncCommentsMutation.isPending ? 'animate-spin' : ''}`} />
                {syncCommentsMutation.isPending ? 'Syncing...' : 'Sync from Meta'}
              </button>
            )}
          </div>

          {selectedPost?.platform === 'facebook' && (
            <div className="p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-2">
              <p className="text-xs font-semibold text-amber-900">Facebook Page Comments Notice</p>
              <p className="text-xs text-amber-800">
                Meta requires App Review approval for <code className="bg-amber-100 px-1 py-0.5 rounded font-mono text-[11px]">pages_read_user_content</code> before external apps can read comments on Facebook Page posts.
              </p>
              <button
                type="button"
                onClick={() => {
                  setPlatform('instagram')
                  setSelectedPostId(null)
                }}
                className="inline-flex items-center gap-1 text-xs font-medium text-pink-700 hover:text-pink-900 underline"
              >
                Switch to Instagram tab to view active comments &rarr;
              </button>
            </div>
          )}

          {isCommentsLoading ? (
            <div className="p-8 text-center text-sm text-slate-400 bg-white rounded-xl border border-slate-200">
              Loading comments...
            </div>
          ) : !commentsData || commentsData.length === 0 ? (
            <div className="p-10 text-center bg-white rounded-xl border border-dashed border-slate-300 space-y-3">
              <p className="text-sm text-slate-500">No comments recorded on this post yet.</p>
              {selectedPost?.platform === 'instagram' ? (
                <div className="flex flex-col items-center justify-center gap-2">
                  <button
                    onClick={() => selectedPostId && syncCommentsMutation.mutate(selectedPostId)}
                    disabled={syncCommentsMutation.isPending}
                    className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 rounded-lg border border-primary-200 transition"
                  >
                    <ArrowPathIcon className={`w-3.5 h-3.5 ${syncCommentsMutation.isPending ? 'animate-spin' : ''}`} />
                    {syncCommentsMutation.isPending ? 'Fetching comments from Meta...' : 'Fetch Comments from Meta'}
                  </button>
                  <p className="text-[11px] text-slate-400 max-w-md">
                    Syncs latest comments directly from Instagram via <code className="bg-slate-100 px-1 py-0.5 rounded text-slate-600">instagram_manage_comments</code>.
                  </p>
                </div>
              ) : (
                <p className="text-xs text-slate-400">
                  Switch to the Instagram tab above to moderate active Instagram comments.
                </p>
              )}
            </div>
          ) : (
            <div className="space-y-3">
              {commentsData.map((comment) => (
                <CommentCard
                  key={comment.id}
                  comment={comment}
                  onReply={(commentId, message) => replyMutation.mutateAsync({ commentId, message })}
                  onModerate={(commentId, isHidden) => moderateMutation.mutateAsync({ commentId, isHidden })}
                  onDelete={(commentId) => deleteMutation.mutateAsync(commentId)}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

