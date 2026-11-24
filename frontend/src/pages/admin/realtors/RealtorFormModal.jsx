import { useState } from 'react'
import toast from 'react-hot-toast'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const toFormState = (realtor) => ({
  name: realtor?.name || '',
  email: realtor?.email || '',
  phone: realtor?.phone || '',
  company: realtor?.company || '',
  plan_id: realtor?.plan_id || realtor?.plan?.id || '',
  password: '',
  password_confirmation: '',
})

export const RealtorFormModal = ({ open, realtor, plans, onClose, onSaved }) => {
  const isEdit = Boolean(realtor)
  const [form, setForm] = useState(() => toFormState(realtor))
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const handleSubmit = async (event) => {
    event.preventDefault()
    const nextErrors = {}
    if (!form.name.trim()) nextErrors.name = 'Name is required.'
    if (!form.email.trim()) nextErrors.email = 'Email is required.'
    if (!form.plan_id) nextErrors.plan_id = 'Choose a plan.'
    if (!isEdit && !form.password) nextErrors.password = 'Password is required.'
    if (form.password && form.password.length < 8) nextErrors.password = 'Use at least 8 characters.'
    if (form.password !== form.password_confirmation) {
      nextErrors.password_confirmation = 'Passwords do not match.'
    }
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    setSubmitting(true)
    try {
      const payload = {
        name: form.name.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() || null,
        company: form.company.trim() || null,
        plan_id: Number(form.plan_id),
      }
      if (form.password) {
        payload.password = form.password
        payload.password_confirmation = form.password_confirmation
      }

      if (isEdit) await adminApi.realtors.update(realtor.id, payload)
      else await adminApi.realtors.create(payload)

      toast.success(isEdit ? 'Realtor updated' : 'Realtor created')
      onSaved()
    } catch (error) {
      setErrors(serverFieldErrors(error))
      toast.error(getErrorMessage(error, 'Could not save the realtor.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={isEdit ? 'Edit realtor' : 'Add realtor'}
      description={
        isEdit
          ? 'Update account details. Leave the password blank to keep the current one.'
          : 'Create a realtor account and assign their starting plan.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={submitting}>
            Cancel
          </Button>
          <Button type="submit" form="realtor-form" loading={submitting}>
            {isEdit ? 'Save changes' : 'Create realtor'}
          </Button>
        </>
      }
    >
      <form id="realtor-form" onSubmit={handleSubmit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Full name" htmlFor="realtor-name" required error={errors.name}>
            <Input
              id="realtor-name"
              value={form.name}
              onChange={(event) => set('name', event.target.value)}
              invalid={Boolean(errors.name)}
            />
          </FormField>
          <FormField label="Email" htmlFor="realtor-email" required error={errors.email}>
            <Input
              id="realtor-email"
              type="email"
              value={form.email}
              onChange={(event) => set('email', event.target.value)}
              invalid={Boolean(errors.email)}
            />
          </FormField>
          <FormField label="Phone" htmlFor="realtor-phone" error={errors.phone}>
            <Input
              id="realtor-phone"
              value={form.phone}
              onChange={(event) => set('phone', event.target.value)}
              invalid={Boolean(errors.phone)}
            />
          </FormField>
          <FormField label="Company" htmlFor="realtor-company" error={errors.company}>
            <Input
              id="realtor-company"
              value={form.company}
              onChange={(event) => set('company', event.target.value)}
              invalid={Boolean(errors.company)}
            />
          </FormField>
        </div>

        <FormField label="Plan" htmlFor="realtor-plan" required error={errors.plan_id}>
          <Select
            id="realtor-plan"
            value={form.plan_id}
            onChange={(event) => set('plan_id', event.target.value)}
            invalid={Boolean(errors.plan_id)}
          >
            <option value="">Select a plan</option>
            {plans.map((plan) => (
              <option key={plan.id} value={plan.id}>
                {plan.name} · ₹{Number(plan.price).toLocaleString('en-IN')}
              </option>
            ))}
          </Select>
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField
            label={isEdit ? 'New password (optional)' : 'Password'}
            htmlFor="realtor-password"
            error={errors.password}
            required={!isEdit}
          >
            <Input
              id="realtor-password"
              type="password"
              value={form.password}
              onChange={(event) => set('password', event.target.value)}
              invalid={Boolean(errors.password)}
              autoComplete="new-password"
            />
          </FormField>
          <FormField
            label="Confirm password"
            htmlFor="realtor-password-confirmation"
            error={errors.password_confirmation}
            required={!isEdit}
          >
            <Input
              id="realtor-password-confirmation"
              type="password"
              value={form.password_confirmation}
              onChange={(event) => set('password_confirmation', event.target.value)}
              invalid={Boolean(errors.password_confirmation)}
              autoComplete="new-password"
            />
          </FormField>
        </div>
      </form>
    </Modal>
  )
}
