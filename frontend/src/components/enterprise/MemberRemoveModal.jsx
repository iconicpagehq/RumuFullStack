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

const defaults = {
  lead_action: 'unassign',
  transfer_to: '',
  reports_action: 'reparent_to_manager',
  reassign_to_membership_id: '',
}

export const MemberRemoveModal = ({ open, onClose, membership, onRemoved }) => {
  const queryClient = useQueryClient()
  const { members } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')

  const destinations = members.filter(
    (member) =>
      member.user &&
      member.status === 'active' &&
      member.user.id !== membership?.user?.id,
  )

  const {
    register,
    handleSubmit,
    reset,
    setError,
    watch,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  const leadAction = watch('lead_action')
  const reportsAction = watch('reports_action')
  const hasDirectReports = Number(membership?.direct_reports_count || 0) > 0

  useEffect(() => {
    if (!open) return
    reset(defaults)
    setGeneralError('')
  }, [open, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        lead_action: values.lead_action,
        transfer_to: values.lead_action === 'transfer' ? Number(values.transfer_to) : null,
      }

      if (hasDirectReports) {
        payload.reports_action = values.reports_action
        payload.reassign_to_membership_id =
          values.reports_action === 'reassign'
            ? Number(values.reassign_to_membership_id)
            : null
      }

      return enterpriseApi.members.remove(membership.id, payload)
    },
    onSuccess: (response) => {
      const movedLeads = Number(response.data?.data?.leads_moved || 0)
      const movedReports = Number(response.data?.data?.reports_moved || 0)
      const parts = []
      if (movedLeads) parts.push(`${movedLeads} open lead${movedLeads === 1 ? '' : 's'} reassigned`)
      if (movedReports) {
        parts.push(`${movedReports} direct report${movedReports === 1 ? '' : 's'} moved`)
      }
      toast.success(parts.length ? `Member removed · ${parts.join(' · ')}` : 'Member removed')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'member', membership.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'organization'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'billing'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onRemoved?.()
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'lead_action',
          'transfer_to',
          'reports_action',
          'reassign_to_membership_id',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not remove this member.'))
      }
    },
  })

  if (!membership) return null

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Remove member"
      description={`Remove ${membership.user?.name || 'this member'} from the enterprise workspace.`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button
            type="submit"
            form="enterprise-remove-form"
            variant="danger"
            loading={mutation.isPending}
          >
            Remove member
          </Button>
        </>
      }
    >
      <form
        id="enterprise-remove-form"
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

        <Alert variant="warning">
          The member loses workspace access immediately and their sessions are revoked. Their user
          record, lead history and notes are preserved; only open leads are reassigned by this
          action.
        </Alert>

        <FormField
          label="Open leads"
          htmlFor="remove-lead-action"
          error={errors.lead_action?.message}
          required
        >
          <Select id="remove-lead-action" {...register('lead_action')}>
            <option value="unassign">Return open leads to the pool</option>
            <option value="transfer">Transfer open leads to another agent</option>
          </Select>
        </FormField>

        {leadAction === 'transfer' && (
          <FormField
            label="Destination agent"
            htmlFor="remove-transfer-to"
            error={errors.transfer_to?.message}
            hint="Only active members of this enterprise are available."
            required
          >
            <Select
              id="remove-transfer-to"
              invalid={Boolean(errors.transfer_to)}
              {...register('transfer_to', {
                required: 'Select the agent who will receive the leads.',
              })}
            >
              <option value="">Select an agent</option>
              {destinations.map((member) => (
                <option key={member.id} value={member.user.id}>
                  {member.user.name}
                  {member.team?.name ? ` · ${member.team.name}` : ''}
                </option>
              ))}
            </Select>
          </FormField>
        )}

        {hasDirectReports && (
          <div className="space-y-4 rounded-xl border border-border bg-muted/30 p-4">
            <div>
              <p className="text-sm font-semibold text-foreground">
                Direct reports ({membership.direct_reports_count})
              </p>
              <p className="mt-1 text-xs text-muted-foreground">
                People who report to this member must be moved before removal. Choose where they go.
              </p>
            </div>

            <FormField
              label="Move direct reports"
              htmlFor="remove-reports-action"
              error={errors.reports_action?.message}
              required
            >
              <Select id="remove-reports-action" {...register('reports_action')}>
                <option value="reparent_to_manager">To this member&apos;s manager</option>
                <option value="reparent_to_admin">To the company admin</option>
                <option value="reassign">To another member</option>
              </Select>
            </FormField>

            {reportsAction === 'reassign' && (
              <FormField
                label="New manager"
                htmlFor="remove-reassign-to"
                error={errors.reassign_to_membership_id?.message}
                hint="Must be active and outside this member's reporting line."
                required
              >
                <Select
                  id="remove-reassign-to"
                  invalid={Boolean(errors.reassign_to_membership_id)}
                  {...register('reassign_to_membership_id', {
                    required: 'Select who will receive these direct reports.',
                  })}
                >
                  <option value="">Select a member</option>
                  {members
                    .filter(
                      (member) =>
                        member.status === 'active' &&
                        member.id !== membership.id &&
                        member.user,
                    )
                    .map((member) => (
                      <option key={member.id} value={member.id}>
                        {member.user.name}
                        {member.team?.name ? ` · ${member.team.name}` : ''}
                      </option>
                    ))}
                </Select>
              </FormField>
            )}
          </div>
        )}
      </form>
    </Modal>
  )
}
