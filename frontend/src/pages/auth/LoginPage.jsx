import { useState } from 'react'
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import toast from 'react-hot-toast'
import { EnvelopeIcon, EyeIcon, EyeSlashIcon, LockClosedIcon } from '@heroicons/react/20/solid'
import { authApi } from '@/api/auth'
import { ForgotPasswordModal } from '@/components/auth/ForgotPasswordModal'
import { SocialButtons } from '@/components/auth/SocialButtons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input } from '@/components/ui/Input'
import { useAuthStore } from '@/stores/authStore'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { canAccessPathForRole, homePathForRole } from '@/utils/enterprise'
import { loginSchema } from '@/utils/validators'

export const LoginPage = () => {
  const [showPassword, setShowPassword] = useState(false)
  const [forgotOpen, setForgotOpen] = useState(false)
  const [generalError, setGeneralError] = useState('')
  const [searchParams] = useSearchParams()
  const location = useLocation()
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    getValues,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
  })

  const mutation = useMutation({
    mutationFn: (values) => authApi.login(values),
    onSuccess: (response) => {
      const payload = response.data?.data
      if (!payload?.token || !payload?.user) {
        setGeneralError('The server returned an unexpected response. Please try again.')
        return
      }
      setAuth({ token: payload.token, user: payload.user })
      toast.success(`Welcome back, ${payload.user.name?.split(' ')[0] || 'there'}`)
      const fallback = homePathForRole(payload.user.role)
      const from = location.state?.from?.pathname
      const allowed = from && canAccessPathForRole(payload.user.role, from)
      navigate(allowed ? from : fallback, { replace: true })
    },
    onError: (error) => {
      if (!applyServerErrors(error, setError, ['email', 'password'])) {
        setGeneralError(getErrorMessage(error, 'Unable to sign in.'))
      }
    },
  })

  const onSubmit = (values) => {
    setGeneralError('')
    mutation.mutate(values)
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-[26px] font-semibold tracking-[-0.02em] text-foreground">Welcome back</h1>
        <p className="mt-1.5 text-sm text-muted-foreground">
          Sign in to manage your campaigns, leads and billing.
        </p>
      </div>

      {searchParams.get('expired') === '1' && (
        <Alert variant="warning" className="mb-5" title="Your session expired">
          Please sign in again to continue.
        </Alert>
      )}
      {generalError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
          {generalError}
        </Alert>
      )}

      <SocialButtons disabled={mutation.isPending} label="Sign in" />

      <div className="my-6 flex items-center gap-3">
        <span className="h-px flex-1 bg-border" />
        <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
          or continue with email
        </span>
        <span className="h-px flex-1 bg-border" />
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
        <FormField label="Email address" htmlFor="email" error={errors.email?.message}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            placeholder="you@company.com"
            leftIcon={EnvelopeIcon}
            invalid={Boolean(errors.email)}
            {...register('email')}
          />
        </FormField>

        <div>
          <div className="mb-1.5 flex items-center justify-between">
            <label htmlFor="password" className="form-label mb-0">
              Password
            </label>
            <button
              type="button"
              onClick={() => setForgotOpen(true)}
              className="text-xs font-semibold text-highlight hover:underline"
            >
              Forgot password?
            </button>
          </div>
          <Input
            id="password"
            type={showPassword ? 'text' : 'password'}
            autoComplete="current-password"
            placeholder="Enter your password"
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
            {...register('password')}
          />
          {errors.password && <p className="form-error mt-1">{errors.password.message}</p>}
        </div>

        <Button type="submit" className="w-full" size="lg" loading={mutation.isPending}>
          Sign in
        </Button>
      </form>

      <p className="mt-7 text-center text-sm text-muted-foreground">
        New to Rumu?{' '}
        <Link to="/register" className="font-semibold text-highlight hover:underline">
          Create an account
        </Link>
      </p>

      <ForgotPasswordModal
        open={forgotOpen}
        onClose={() => setForgotOpen(false)}
        defaultEmail={getValues('email') || ''}
        onSuccessEmail={(confirmedEmail) => {
          setValue('email', confirmedEmail)
        }}
      />
    </div>
  )
}
