import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import {
  BuildingOffice2Icon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  LockClosedIcon,
  UserIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { useAuthStore } from '@/stores/authStore'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatDate, formatDateTime } from '@/utils/formatters'
import { roleLabel } from '@/utils/enterprise'

export const EnterpriseInvitationAcceptPage = () => {
  const { token } = useParams()
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)
  const [showPassword, setShowPassword] = useState(false)
  const [generalError, setGeneralError] = useState('')

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['enterprise', 'invitation', token],
    queryFn: async () => {
      const response = await enterpriseApi.invitation.show(token)
      return response.data?.data
    },
    retry: false,
    staleTime: 30000,
  })

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: { name: '', phone: '', password: '', password_confirmation: '' } })

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.invitation.accept(token, {
        name: values.name.trim(),
        phone: values.phone.trim() || null,
        password: values.password,
        password_confirmation: values.password_confirmation,
      }),
    onSuccess: (response) => {
      const payload = response.data?.data
      if (!payload?.token || !payload?.user) {
        setGeneralError('The server returned an unexpected response. Please try again.')
        return
      }
      setAuth({ token: payload.token, user: payload.user })
      toast.success('Welcome to your enterprise workspace.')
      navigate('/enterprise/dashboard', { replace: true })
    },
    onError: (err) => {
      if (!applyServerErrors(err, setError, ['name', 'phone', 'password', 'password_confirmation'])) {
        setGeneralError(getErrorMessage(err, 'Could not accept this invitation.'))
      }
    },
  })

  if (isLoading) return <PageLoader />

  if (isError || !data) {
    return (
      <div className="text-center">
        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10 text-destructive">
          <EnvelopeIcon className="h-6 w-6" />
        </div>
        <h1 className="mt-4 text-xl font-semibold text-foreground">Invitation unavailable</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          {getErrorMessage(error, 'This invitation is invalid, expired or has already been used.')}
        </p>
        <div className="mt-6 flex flex-col gap-2">
          <Link to="/login">
            <Button className="w-full">Sign in</Button>
          </Link>
          <Link to="/" className="text-sm font-medium text-highlight hover:underline">
            Back to home
          </Link>
        </div>
      </div>
    )
  }

  const invitation = data.invitation || {}
  const enterprise = data.enterprise || {}
  const accountExists = Boolean(data.account_exists)

  return (
    <div>
      <div className="mb-7">
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-highlight">
          Enterprise invitation
        </p>
        <h1 className="mt-2 text-[26px] font-semibold tracking-[-0.02em] text-foreground">
          Join {enterprise.name || 'your team'}
        </h1>
        <p className="mt-1.5 text-sm text-muted-foreground">
          You have been invited as {roleLabel(invitation.role).toLowerCase()}
          {invitation.team?.name ? ` for the ${invitation.team.name} team` : ''}.
        </p>
      </div>

      <div className="mb-6 rounded-xl border border-border bg-card p-4">
        <div className="flex items-start gap-3">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-highlight">
            <BuildingOffice2Icon className="h-4 w-4" />
          </span>
          <div className="min-w-0 text-sm">
            <p className="font-semibold text-foreground">{enterprise.name || 'Enterprise workspace'}</p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              Invited email: <span className="font-medium text-foreground">{invitation.email}</span>
            </p>
            {invitation.invited_by?.name && (
              <p className="text-xs text-muted-foreground">Invited by {invitation.invited_by.name}</p>
            )}
            {invitation.expires_at && (
              <p className="mt-1 text-xs text-muted-foreground">
                Expires {formatDateTime(invitation.expires_at)}
              </p>
            )}
          </div>
        </div>
      </div>

      {accountExists ? (
        <Alert variant="info" title="You already have a Rumu account">
          <p>
            This invitation requires a new enterprise account for {invitation.email}. Sign in with
            that account, or ask your administrator for help.
          </p>
          <div className="mt-3">
            <Link to="/login">
              <Button size="sm">Go to sign in</Button>
            </Link>
          </div>
        </Alert>
      ) : (
        <>
          {generalError && (
            <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
              {generalError}
            </Alert>
          )}

          <form
            onSubmit={handleSubmit((values) => {
              setGeneralError('')
              mutation.mutate(values)
            })}
            className="space-y-4"
            noValidate
          >
            <FormField label="Full name" htmlFor="invite-name" error={errors.name?.message} required>
              <Input
                id="invite-name"
                autoComplete="name"
                leftIcon={UserIcon}
                invalid={Boolean(errors.name)}
                {...register('name', { required: 'Enter your full name.' })}
              />
            </FormField>

            <FormField label="Email" htmlFor="invite-email">
              <Input id="invite-email" type="email" value={invitation.email || ''} leftIcon={EnvelopeIcon} disabled />
            </FormField>

            <FormField label="Phone" htmlFor="invite-phone" error={errors.phone?.message}>
              <Input id="invite-phone" autoComplete="tel" {...register('phone')} />
            </FormField>

            <FormField
              label="Create a password"
              htmlFor="invite-password"
              error={errors.password?.message}
              hint="At least 8 characters."
              required
            >
              <Input
                id="invite-password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                leftIcon={LockClosedIcon}
                invalid={Boolean(errors.password)}
                rightSlot={
                  <button
                    type="button"
                    onClick={() => setShowPassword((value) => !value)}
                    className="rounded p-1 text-muted-foreground transition hover:text-foreground"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <EyeSlashIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
                  </button>
                }
                {...register('password', {
                  required: 'Create a password.',
                  minLength: { value: 8, message: 'Use at least 8 characters.' },
                })}
              />
            </FormField>

            <FormField
              label="Confirm password"
              htmlFor="invite-password-confirmation"
              error={errors.password_confirmation?.message}
              required
            >
              <Input
                id="invite-password-confirmation"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                leftIcon={LockClosedIcon}
                invalid={Boolean(errors.password_confirmation)}
                {...register('password_confirmation', {
                  required: 'Confirm your password.',
                  validate: (value, values) => value === values.password || 'Passwords do not match.',
                })}
              />
            </FormField>

            <Button type="submit" className="w-full" size="lg" loading={mutation.isPending}>
              Accept invitation
            </Button>
          </form>

          <p className="mt-6 text-center text-xs text-muted-foreground">
            This invitation is valid until {formatDate(invitation.expires_at)}. Need help? Contact
            your workspace administrator.
          </p>
        </>
      )}
    </div>
  )
}
