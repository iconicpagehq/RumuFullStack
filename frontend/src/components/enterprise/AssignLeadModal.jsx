import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ArrowsRightLeftIcon } from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

export const AssignLeadModal = ({ open, onClose, lead }) => {
  const queryClient = useQueryClient()
  const { teams, assignableMembers } = useEnterpriseReference()
  const [assignedTo, setAssignedTo] = useState('')
  const [teamId, setTeamId] = useState('')
  const [generalError, setGeneralError] = useState('')
  const [fieldErrors, setFieldErrors] = useState({})

  useEffect(() => {
    if (!open) return
    setAssignedTo(lead?.assigned_to ? String(lead.assigned_to) : '')
    setTeamId(lead?.team_id ? String(lead.team_id) : '')
    setGeneralError('')
    setFieldErrors({})
  }, [open, lead])

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', lead.id] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
  }

  const assignMutation = useMutation({
    mutationFn: () =>
      enterpriseApi.leads.assign(lead.id, {
        assigned_to: Number(assignedTo),
        team_id: teamId ? Number(teamId) : null,
      }),
    onSuccess: () => {
      toast.success('Lead assignment updated')
      refresh()
      onClose()
    },
    onError: (error) => {
      const applied = applyServerErrors(error, (field, value) =>
        setFieldErrors((current) => ({ ...current, [field]: value.message })),
      ['assigned_to', 'team_id'])
      if (!applied) setGeneralError(getErrorMessage(error, 'Could not assign this lead.'))
    },
  })

  const roundRobinMutation = useMutation({
    mutationFn: () => enterpriseApi.leads.roundRobin(lead.id, teamId ? { team_id: Number(teamId) } : {}),
    onSuccess: () => {
      toast.success('Lead assigned by round robin')
      refresh()
      onClose()
    },
    onError: (error) => setGeneralError(getErrorMessage(error, 'No eligible team member was found.')),
  })

  const selectedMembers = membersForTeam(assignableMembers, teamId)

  return (
    <Modal
      open={open}
      onClose={assignMutation.isPending || roundRobinMutation.isPending ? () => {} : onClose}
      title="Assign this lead"
      description={`Route ${lead?.name || 'this lead'} to a team and owner.`}
      footer={
        <>
          <Button
            variant="outline"
            onClick={() => roundRobinMutation.mutate()}
            loading={roundRobinMutation.isPending}
            disabled={!teamId || assignMutation.isPending}
          >
            <ArrowsRightLeftIcon className="h-4 w-4" />
            Round robin
          </Button>
          <div className="flex-1" />
          <Button variant="outline" onClick={onClose} disabled={assignMutation.isPending}>
            Cancel
          </Button>
          <Button
            onClick={() => assignMutation.mutate()}
            loading={assignMutation.isPending}
            disabled={!assignedTo || roundRobinMutation.isPending}
          >
            Assign lead
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        <FormField label="Team" htmlFor="assign-team" error={fieldErrors.team_id}>
          <Select id="assign-team" value={teamId} onChange={(event) => setTeamId(event.target.value)}>
            <option value="">No team</option>
            {teams.map((team) => (
              <option key={team.id} value={team.id}>
                {team.name}
                {team.manager ? ` · ${team.manager.name}` : ''}
              </option>
            ))}
          </Select>
        </FormField>

        <FormField
          label="Assign to"
          htmlFor="assign-user"
          error={fieldErrors.assigned_to}
          hint="Choose an active manager or employee."
        >
          <Select id="assign-user" value={assignedTo} onChange={(event) => setAssignedTo(event.target.value)}>
            <option value="">Select a member</option>
            {selectedMembers.map((member) => (
              <option key={member.user.id} value={member.user.id}>
                {member.user.name} · {member.role}
              </option>
            ))}
          </Select>
        </FormField>

        <p className="text-xs text-muted-foreground">
          Round robin assigns to the team member who received a lead least recently.
        </p>
      </div>
    </Modal>
  )
}

const membersForTeam = (members, teamId) => {
  if (!teamId) return members
  return members.filter((member) => String(member.team?.id) === String(teamId))
}
