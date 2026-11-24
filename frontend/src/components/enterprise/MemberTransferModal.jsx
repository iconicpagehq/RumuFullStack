import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = { team_id: '', lead_action: 'retain', destination_user_id: '' }

export const MemberTransferModal = ({ open, onClose, membership }) => {
  const queryClient = useQueryClient()
  const { teams, members } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')

  const currentTeamId = membership?.team?.id
  const destinations = teams.filter((team) => team.id !== currentTeamId)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    watch,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  const destinationTeamId = watch('team_id')
  const leadAction = watch('lead_action')

  const agents = members.filter(
    (member) =>
      member.user &&
      member.status === 'active' &&
      member.user.id !== membership?.user?.id &&
      String(member.team?.id) === String(destinationTeamId),
  )

  useEffect(() => {
    if (!open) return
    reset(defaults)
    setGeneralError('')
  }, [open, reset])

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.members.transfer(membership.id, {
        team_id: Number(values.team_id),
        lead_action: values.lead_action,
        destination_user_id:
          values.lead_action === 'transfer' ? Number(values.destination_user_id) : null,
      }),
    onSuccess: () => {
      toast.success('Member transferred')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'member', membership.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'team'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, ['team_id', 'lead_action', 'destination_user_id'])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not transfer this member.'))
      }
    },
  })

  if (!membership) return null

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Transfer member"
      description={`Move ${membership.user?.name || 'this member'} to another team.`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button
            type="submit"
            form="enterprise-transfer-form"
            loading={mutation.isPending}
            disabled={destinations.length === 0}
          >
            Transfer member
          </Button>
        </>
      }
    >
      <form
        id="enterprise-transfer-form"
        className="space-y-4"
        onSubmit={handleSubmit((values) => {
          setGeneralError('')
          mutation.mutate(values)
        })}
        noValidate
      >
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        {destinations.length === 0 ? (
          <Alert variant="info">
            There is no other team available. Create another team before transferring this member.
          </Alert>
        ) : (
          <>
            <FormField label="Destination team" htmlFor="transfer-team" error={errors.team_id?.message} required>
              <Select
                id="transfer-team"
                invalid={Boolean(errors.team_id)}
                {...register('team_id', { required: 'Select a destination team.' })}
              >
                <option value="">Select a team</option>
                {destinations.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </Select>
            </FormField>

            <FormField
              label="Open leads"
              htmlFor="transfer-leads"
              error={errors.lead_action?.message}
              hint="Closed deals are never changed by a transfer."
            >
              <Select id="transfer-leads" {...register('lead_action')}>
                <option value="retain">Keep open leads assigned to this member</option>
                <option value="transfer">Transfer open leads to another agent</option>
                <option value="unassign">Return open leads to the pool</option>
              </Select>
            </FormField>

            {leadAction === 'transfer' && (
              <FormField
                label="Destination agent"
                htmlFor="transfer-destination"
                error={errors.destination_user_id?.message}
                hint="Only active members of the destination team are available."
                required
              >
                <Select
                  id="transfer-destination"
                  invalid={Boolean(errors.destination_user_id)}
                  {...register('destination_user_id', {
                    required: 'Select the agent who will receive the leads.',
                  })}
                >
                  <option value="">Select an agent</option>
                  {agents.map((member) => (
                    <option key={member.id} value={member.user.id}>
                      {member.user.name}
                      {member.role === 'manager' ? ' · Manager' : ''}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}

            {leadAction === 'transfer' && destinationTeamId && agents.length === 0 && (
              <Alert variant="warning">
                The destination team has no active agents yet. Add a member to that team first, or
                return the leads to the pool instead.
              </Alert>
            )}
          </>
        )}
      </form>
    </Modal>
  )
}
