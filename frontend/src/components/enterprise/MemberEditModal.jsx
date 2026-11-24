import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = { name: '', phone: '', designation: '', employee_code: '', role: 'employee' }

export const MemberEditModal = ({ open, onClose, membership }) => {
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')
  const isAdminMembership = membership?.role === 'admin'

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open || !membership) return
    reset({
      name: membership.user?.name || '',
      phone: membership.user?.phone || '',
      designation: membership.designation || '',
      employee_code: membership.employee_code || '',
      role: membership.role === 'admin' ? 'manager' : membership.role,
    })
    setGeneralError('')
  }, [open, membership, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        name: values.name.trim(),
        phone: values.phone?.trim() || null,
        designation: values.designation?.trim() || null,
        employee_code: values.employee_code?.trim() || null,
      }
      if (!isAdminMembership) payload.role = values.role
      return enterpriseApi.members.update(membership.id, payload)
    },
    onSuccess: () => {
      toast.success('Member updated')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'member', membership.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'name',
          'phone',
          'designation',
          'employee_code',
          'role',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not update this member.'))
      }
    },
  })

  if (!membership) return null

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Edit member"
      description={`${membership.user?.name || 'Member'} · ${membership.user?.email || ''}`}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-member-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="enterprise-member-form"
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

        <FormField label="Full name" htmlFor="member-name" error={errors.name?.message} required>
          <Input
            id="member-name"
            invalid={Boolean(errors.name)}
            {...register('name', { required: 'Enter a name.' })}
          />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Phone" htmlFor="member-phone" error={errors.phone?.message}>
            <Input id="member-phone" {...register('phone')} />
          </FormField>
          <FormField label="Designation" htmlFor="member-designation" error={errors.designation?.message}>
            <Input id="member-designation" placeholder="e.g. Sales executive" {...register('designation')} />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Employee code" htmlFor="member-code" error={errors.employee_code?.message}>
            <Input id="member-code" {...register('employee_code')} />
          </FormField>
          {!isAdminMembership && (
            <FormField
              label="Role"
              htmlFor="member-role"
              error={errors.role?.message}
              hint="Roles control what this member can see and manage."
            >
              <Select id="member-role" {...register('role')}>
                <option value="manager">Manager</option>
                <option value="employee">Employee</option>
              </Select>
            </FormField>
          )}
        </div>

        {isAdminMembership && (
          <Alert variant="info">
            The company administrator role cannot be changed. Update the owner profile from enterprise
            settings.
          </Alert>
        )}

        <p className="text-xs text-muted-foreground">
          To move this member to another team, use Transfer. That flow also lets you decide what happens
          to their open leads.
        </p>
      </form>
    </Modal>
  )
}
