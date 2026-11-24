import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { socialService } from '../services/socialService'

export function useSocialAccounts() {
  const queryClient = useQueryClient()

  const accountsQuery = useQuery({
    queryKey: ['socialAccounts'],
    queryFn: async () => {
      const resp = await socialService.getAccounts()
      return resp.data || []
    },
  })

  const disconnectMutation = useMutation({
    mutationFn: (id) => socialService.disconnectAccount(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialAccounts'] })
      queryClient.invalidateQueries({ queryKey: ['socialAnalytics'] })
    },
  })

  const syncMutation = useMutation({
    mutationFn: (id) => socialService.syncAccount(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['socialAccounts'] })
      queryClient.invalidateQueries({ queryKey: ['socialPosts'] })
      queryClient.invalidateQueries({ queryKey: ['socialInbox'] })
    },
  })

  return {
    accounts: accountsQuery.data || [],
    isLoading: accountsQuery.isLoading,
    isError: accountsQuery.isError,
    error: accountsQuery.error,
    refetch: accountsQuery.refetch,
    disconnect: disconnectMutation.mutateAsync,
    isDisconnecting: disconnectMutation.isPending,
    sync: syncMutation.mutateAsync,
    isSyncing: syncMutation.isPending,
  }
}

