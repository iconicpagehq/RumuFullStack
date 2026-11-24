import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { ClipboardDocumentIcon } from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = { email: '', role: 'employee', team_id: '', reports_to_membership_id: '' }

const copyToClipboard = async (value) => {
  try {
    await navigator.clipboard.writeText(value)
    toast.success('Invitation link copied')
  } catch {
    toast.error('Could not copy automatically. Select the link and copy it manually.')
  }
}

export const InviteMemberModal = ({ open, onClose }) => {
  const queryClient = useQueryClient()
  const { teams, members } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')
  const [created, setCreated] = useState(null)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open) return
    reset(defaults)
    setGeneralError('')
    setCreated(null)
  }, [open, reset])

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.invitations.create({
        email: values.email.trim().toLowerCase(),
        role: values.role,
        team_id: values.team_id ? Number(values.team_id) : null,
        reports_to_membership_id: values.reports_to_membership_id
          ? Number(values.reports_to_membership_id)
          : null,
      }),
    onSuccess: (response) => {
      const invitation = response.data?.data
      setCreated(invitation)
      toast.success('Invitation sent')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'invitations'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'email',
          'role',
          'team_id',
          'reports_to_membership_id',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not send this invitation.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={created ? 'Invitation sent' : 'Invite a team member'}
      description={
        created
          ? 'Share the secure link below if the email does not arrive.'
          : 'Send a secure, single-use invitation to join your enterprise workspace.'
      }
      footer={
        created ? (
          <Button onClick={onClose}>Done</Button>
        ) : (
          <>
            <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
              Cancel
            </Button>
            <Button type="submit" form="enterprise-invite-form" loading={mutation.isPending}>
              Send invitation
            </Button>
          </>
        )
      }
    >
      {created ? (
        <div className="space-y-4">
          <Alert variant="success" title="Invitation created">
            We sent an invitation email to <span className="font-semibold">{created.email}</span> to
            join as {created.role}. The link expires in 7 days and can be used once.
          </Alert>

          <FormField label="Invitation link" htmlFor="invite-link" hint="Anyone with this link can join this workspace.">
            <div className="flex gap-2">
              <Input id="invite-link" readOnly value={created.invite_url || ''} className="text-xs" />
              <Button
                type="button"
                variant="outline"
                onClick={() => copyToClipboard(created.invite_url || '')}
                disabled={!created.invite_url}
              >
                <ClipboardDocumentIcon className="h-4 w-4" />
                Copy
              </Button>
            </div>
          </FormField>

          {created.team?.name && (
            <p className="text-xs text-muted-foreground">
              This member will be added to <span className="font-medium text-foreground">{created.team.name}</span>{' '}
              when they accept.
            </p>
          )}
        </div>
      ) : (
        <form
          id="enterprise-invite-form"
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

          <FormField label="Work email" htmlFor="invite-email" error={errors.email?.message} required>
            <Input
              id="invite-email"
              type="email"
              autoComplete="off"
              placeholder="name@company.com"
              invalid={Boolean(errors.email)}
              {...register('email', {
                required: 'Enter an email address.',
                pattern: {
                  value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                  message: 'Enter a valid email address.',
                },
              })}
            />
          </FormField>

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField
              label="Role"
              htmlFor="invite-role"
              error={errors.role?.message}
              hint="Managers see their whole team. Employees only see their own work."
            >
              <Select id="invite-role" {...register('role')}>
                <option value="employee">Employee</option>
                <option value="manager">Manager</option>
              </Select>
            </FormField>
            <FormField label="Team" htmlFor="invite-team" error={errors.team_id?.message}>
              <Select id="invite-team" {...register('team_id')}>
                <option value="">No team yet</option>
                {teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </Select>
            </FormField>
          </div>

          <FormField
            label="Reports to"
            htmlFor="invite-reports-to"
            error={errors.reports_to_membership_id?.message}
            hint="Leave empty to report to you as the company admin. If this manager leaves before the invitation is accepted, the member falls back to the admin."
          >
            <Select id="invite-reports-to" {...register('reports_to_membership_id')}>
              <option value="">Me (company admin)</option>
              {members
                .filter((member) => member.user && member.status === 'active' && member.role !== 'admin')
                .map((member) => (
                  <option key={member.id} value={member.id}>
                    {member.user.name} · {member.role}
                    {member.team?.name ? ` · ${member.team.name}` : ''}
                  </option>
                ))}
            </Select>
          </FormField>

          <p className="rounded-xl border border-border bg-muted/30 px-3 py-2.5 text-xs text-muted-foreground">
            Invitations expire after 7 days. Your plan limits for managers and employees are checked
            before the invitation is created.
          </p>
        </form>
      )}
    </Modal>
  )
}
