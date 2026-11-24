import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = {
  name: '',
  email: '',
  phone: '',
  role: 'employee',
  team_id: '',
  reports_to_membership_id: '',
  password: '',
  password_confirmation: '',
}

export const AddMemberModal = ({ open, onClose, defaultRole = 'employee' }) => {
  const queryClient = useQueryClient()
  const { teams, members } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    watch,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  const password = watch('password')

  useEffect(() => {
    if (!open) return
    reset({ ...defaults, role: defaultRole })
    setGeneralError('')
  }, [open, reset, defaultRole])

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.members.create({
        name: values.name.trim(),
        email: values.email.trim().toLowerCase(),
        phone: values.phone.trim() || null,
        role: values.role,
        team_id: values.team_id ? Number(values.team_id) : null,
        reports_to_membership_id: values.reports_to_membership_id
          ? Number(values.reports_to_membership_id)
          : null,
        password: values.password,
        password_confirmation: values.password_confirmation,
      }),
    onSuccess: (response) => {
      toast.success(`${response.data?.data?.user?.name || 'Member'} added`)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      const limitError = error?.response?.data?.errors?.limit
      if (limitError) {
        setGeneralError(Array.isArray(limitError) ? limitError[0] : String(limitError))
        return
      }

      if (
        !applyServerErrors(error, setError, [
          'name',
          'email',
          'phone',
          'password',
          'password_confirmation',
          'role',
          'team_id',
          'reports_to_membership_id',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not add this member.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Add a team member"
      description="Create an enterprise login now. The member can sign in immediately with this email and password."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-add-member-form" loading={mutation.isPending}>
            Add member
          </Button>
        </>
      }
    >
      <form
        id="enterprise-add-member-form"
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

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Full name" htmlFor="member-name" error={errors.name?.message} required>
            <Input
              id="member-name"
              autoComplete="off"
              placeholder="Employee name"
              invalid={Boolean(errors.name)}
              {...register('name', { required: 'Enter the member name.' })}
            />
          </FormField>

          <FormField label="Work email" htmlFor="member-email" error={errors.email?.message} required>
            <Input
              id="member-email"
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
        </div>

        <div className="grid gap-4 sm:grid-cols-3">
          <FormField label="Phone" htmlFor="member-phone" error={errors.phone?.message}>
            <Input
              id="member-phone"
              type="tel"
              autoComplete="off"
              placeholder="Optional"
              invalid={Boolean(errors.phone)}
              {...register('phone')}
            />
          </FormField>

          <FormField
            label="Role"
            htmlFor="member-role"
            error={errors.role?.message}
            hint="Managers see their team. Employees only see their own work."
          >
            <Select id="member-role" {...register('role')}>
              <option value="employee">Employee</option>
              <option value="manager">Manager</option>
            </Select>
          </FormField>

          <FormField label="Team" htmlFor="member-team" error={errors.team_id?.message}>
            <Select id="member-team" {...register('team_id')}>
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
          htmlFor="member-reports-to"
          error={errors.reports_to_membership_id?.message}
          hint="Leave empty to report to you as the company admin. Any member can have direct reports."
        >
          <Select id="member-reports-to" {...register('reports_to_membership_id')}>
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

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField
            label="Password"
            htmlFor="member-password"
            error={errors.password?.message}
            hint="At least 8 characters. Share it with the member securely."
            required
          >
            <Input
              id="member-password"
              type="password"
              autoComplete="new-password"
              invalid={Boolean(errors.password)}
              {...register('password', {
                required: 'Set a password.',
                minLength: { value: 8, message: 'Use at least 8 characters.' },
              })}
            />
          </FormField>

          <FormField
            label="Confirm password"
            htmlFor="member-password-confirmation"
            error={errors.password_confirmation?.message}
            required
          >
            <Input
              id="member-password-confirmation"
              type="password"
              autoComplete="new-password"
              invalid={Boolean(errors.password_confirmation)}
              {...register('password_confirmation', {
                required: 'Confirm the password.',
                validate: (value) => value === password || 'Passwords do not match.',
              })}
            />
          </FormField>
        </div>

        <p className="rounded-xl border border-border bg-muted/30 px-3 py-2.5 text-xs text-muted-foreground">
          This creates an active enterprise account immediately. The member can change the password from
          Settings after signing in. Seat limits for the selected role are checked before the account is
          created.
        </p>
      </form>
    </Modal>
  )
}
