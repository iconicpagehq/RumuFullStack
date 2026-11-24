import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { socialService } from '../services/socialService'

export function useSocialInbox(initialFilters = {}) {
  const queryClient = useQueryClient()
  const [filters, setFilters] = useState(initialFilters)
  const [selectedConversationId, setSelectedConversationId] = useState(null)

  const conversationsQuery = useQuery({
    queryKey: ['socialInbox', 'conversations', filters],
    queryFn: async () => {
      const resp = await socialService.getConversations(filters)
      return resp.data || []
    },
    staleTime: 10000,
    retry: 1,
    refetchInterval: 15000, // Poll inbox every 15s
  })

  const threadQuery = useQuery({
    queryKey: ['socialInbox', 'messages', selectedConversationId],
    queryFn: async () => {
      if (!selectedConversationId) return null
      const resp = await socialService.getMessages(selectedConversationId)
      return resp.data
    },
    enabled: !!selectedConversationId,
    staleTime: 10000,
    retry: 1,
    refetchInterval: 10000,
  })

  const sendMessageMutation = useMutation({
    mutationFn: ({ conversationId, message }) =>
      socialService.sendMessage(conversationId, message),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialInbox', 'messages', selectedConversationId] })
      queryClient.invalidateQueries({ queryKey: ['socialInbox', 'conversations'] })
    },
  })

  const updateConversationMutation = useMutation({
    mutationFn: ({ conversationId, data }) =>
      socialService.updateConversation(conversationId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialInbox', 'conversations'] })
      queryClient.invalidateQueries({ queryKey: ['socialInbox', 'messages', selectedConversationId] })
    },
  })

  return {
    conversations: conversationsQuery.data?.data || conversationsQuery.data || [],
    pagination: conversationsQuery.data?.meta || null,
    isConversationsLoading: conversationsQuery.isLoading,
    selectedConversationId,
    setSelectedConversationId,
    currentThread: threadQuery.data,
    isThreadLoading: threadQuery.isLoading,
    filters,
    setFilters,
    sendMessage: sendMessageMutation.mutateAsync,
    isSending: sendMessageMutation.isPending,
    updateConversation: updateConversationMutation.mutateAsync,
  }
}

