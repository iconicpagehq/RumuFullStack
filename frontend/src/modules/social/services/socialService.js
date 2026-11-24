import { api } from '@/api/axios'

export const socialService = {
  // Accounts
  getAccounts: async () => {
    const res = await api.get('/social/accounts')
    return res.data
  },

  getConnectUrl: async (redirectUri) => {
    const res = await api.get('/social/connect/meta', {
      params: redirectUri ? { redirect_uri: redirectUri } : {},
    })
    return res.data
  },

  handleCallback: async (code, state) => {
    const res = await api.get('/social/connect/meta/callback', {
      params: { code, state },
    })
    return res.data
  },

  disconnectAccount: async (id) => {
    const res = await api.delete(`/social/accounts/${id}`)
    return res.data
  },

  syncAccount: async (id) => {
    const res = await api.post(`/social/accounts/${id}/sync`)
    return res.data
  },

  // Posts
  getPosts: async (params = {}) => {
    const res = await api.get('/social/posts', { params })
    return res.data
  },

  getPost: async (id) => {
    const res = await api.get(`/social/posts/${id}`)
    return res.data
  },

  // Comments
  getPostComments: async (postId, params = {}) => {
    const res = await api.get(`/social/posts/${postId}/comments`, { params })
    return res.data
  },

  syncPostComments: async (postId) => {
    const res = await api.post(`/social/posts/${postId}/sync-comments`)
    return res.data
  },

  replyComment: async (commentId, message) => {
    const res = await api.post(`/social/comments/${commentId}/reply`, { message })
    return res.data
  },

  moderateComment: async (commentId, isHidden) => {
    const res = await api.patch(`/social/comments/${commentId}`, { is_hidden: isHidden })
    return res.data
  },

  deleteComment: async (commentId) => {
    const res = await api.delete(`/social/comments/${commentId}`)
    return res.data
  },

  // Inbox & Conversations
  getConversations: async (params = {}) => {
    const res = await api.get('/social/inbox/conversations', { params })
    return res.data
  },

  getMessages: async (conversationId) => {
    const res = await api.get(`/social/inbox/conversations/${conversationId}/messages`)
    return res.data
  },

  sendMessage: async (conversationId, message) => {
    const res = await api.post(`/social/inbox/conversations/${conversationId}/messages`, { message })
    return res.data
  },

  updateConversation: async (conversationId, data) => {
    const res = await api.patch(`/social/inbox/conversations/${conversationId}`, data)
    return res.data
  },

  // Mentions
  getMentions: async (params = {}) => {
    const res = await api.get('/social/mentions', { params })
    return res.data
  },

  // Analytics
  getAnalyticsOverview: async () => {
    const res = await api.get('/social/analytics/overview')
    return res.data
  },

  getPostAnalytics: async (postId) => {
    const res = await api.get(`/social/analytics/posts/${postId}`)
    return res.data
  },
}

