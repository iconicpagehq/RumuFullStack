import { useQuery } from '@tanstack/react-query'
import { enterpriseApi } from '@/api/enterprise'
import { useAuthStore } from '@/stores/authStore'
import { enterprisePermissions } from '@/utils/enterprise'

export const useEnterprise = () => {
  const user = useAuthStore((state) => state.user)

  const query = useQuery({
    queryKey: ['enterprise', 'me'],
    queryFn: async () => {
      const response = await enterpriseApi.me()
      return response.data?.data
    },
    staleTime: 60000,
  })

  const fallbackMembership = user?.enterprise_membership
  const membership = query.data?.membership || fallbackMembership
  const enterprise = query.data?.enterprise || fallbackMembership?.enterprise || null
  const role = membership?.role || null
  const hierarchy = query.data?.hierarchy || {}

  return {
    ...query,
    context: query.data,
    enterprise,
    membership,
    hierarchy,
    plan: query.data?.plan || enterprise?.plan || null,
    features: query.data?.features || {},
    role,
    ...enterprisePermissions(role, hierarchy),
  }
}
