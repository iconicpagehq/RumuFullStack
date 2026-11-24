import { useQuery } from '@tanstack/react-query'
import { enterpriseApi } from '@/api/enterprise'

export const useEnterpriseReference = () => {
  const query = useQuery({
    queryKey: ['enterprise', 'reference'],
    queryFn: async () => {
      const response = await enterpriseApi.referenceData()
      return response.data?.data
    },
    staleTime: 120000,
  })

  const teams = query.data?.teams || []
  const members = query.data?.members || []

  return {
    ...query,
    teams,
    members,
    assignableMembers: members.filter(
      (member) => member.user && ['manager', 'employee'].includes(member.role),
    ),
    projects: query.data?.projects || [],
    campaigns: query.data?.campaigns || [],
    team: query.data?.team || null,
    permissions: query.data?.permissions || {},
    options: (team = null) => {
      const memberList = team
        ? members.filter((member) => member.team?.id === team)
        : members
      return memberList
        .filter((member) => member.user && member.role !== 'admin')
        .map((member) => ({
          id: member.user.id,
          name: member.user.name,
          role: member.role,
          team: member.team?.name || null,
        }))
    },
  }
}
