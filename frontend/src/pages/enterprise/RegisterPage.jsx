import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import {
  BuildingOffice2Icon,
  CheckCircleIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  LockClosedIcon,
  UserIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { useAuthStore } from '@/stores/authStore'
import { cn } from '@/utils/cn'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const BUSINESS_TYPES = [
  'Developer',
  'Real estate agency',
  'Brokerage',
  'Property management',
  'Channel partner',
  'Other',
]

const COMPANY_SIZES = ['1–10', '11–50', '51–200', '201–500', '500+']

const EMAIL_PATTERN = { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Enter a valid email address.' }

const planLimit = (value) => (value === null || value === undefined ? 'Unlimited' : formatNumber(value))

const PlanOption = ({ plan, selected, onSelect }) => {
  const limits = [
    ['Managers', plan.max_managers],
    ['Employees', plan.max_employees],
    ['Teams', plan.max_teams],
    ['Leads / month', plan.max_monthly_leads],
    ['Inventory units', plan.max_inventory_units],
  ]

  return (
    <button
      type="button"
      onClick={onSelect}
      className={cn(
        'w-full rounded-2xl border p-4 text-left transition',
        selected
          ? 'border-highlight bg-primary/5 ring-1 ring-highlight/30'
          : 'border-border bg-card hover:border-highlight/30',
      )}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-sm font-semibold text-foreground">{plan.name}</span>
            {plan.is_default && (
              <span className="rounded bg-muted px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-muted-foreground">
                Recommended
              </span>
            )}
          </div>
          <p className="mt-1 text-[11px] text-muted-foreground">
            {plan.advanced_reports ? 'Advanced reports' : 'Core reports'}
            {plan.meta_integrations ? ' · Meta integrations' : ''}
          </p>
        </div>
        <span className="shrink-0 text-right">
          <span className="block text-lg font-semibold text-foreground">
            {Number(plan.price) > 0 ? formatCurrency(plan.price) : 'Free'}
          </span>
          {Number(plan.price) > 0 && (
            <span className="block text-[10px] text-muted-foreground">per month</span>
          )}
        </span>
      </div>

      <div className="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 border-t border-border pt-3 text-[11px]">
        {limits.map(([label, value]) => (
          <span key={label} className="flex items-center justify-between gap-2 text-muted-foreground">
            <span>{label}</span>
            <span className="font-medium text-foreground">{planLimit(value)}</span>
          </span>
        ))}
      </div>
    </button>
  )
}

export const EnterpriseRegisterPage = () => {
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)
  const [showPassword, setShowPassword] = useState(false)
  const [generalError, setGeneralError] = useState('')
  const [accepted, setAccepted] = useState(false)
  const [selectedPlanId, setSelectedPlanId] = useState(null)
  const [billingInterval, setBillingInterval] = useState('monthly')

  const { data: plans = [], isLoading: plansLoading } = useQuery({
    queryKey: ['enterprise', 'public-plans'],
    queryFn: async () => {
      const response = await enterpriseApi.plans()
      return response.data?.data || []
    },
    staleTime: 5 * 60 * 1000,
  })

  const {
    register,
    handleSubmit,
    watch,
    setError,
    clearErrors,
    formState: { errors },
  } = useForm({
    defaultValues: {
      company: {
        name: '',
        legal_name: '',
        email: '',
        phone: '',
        website: '',
        business_type: '',
        company_size: '',
        address: '',
      },
      admin: {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
      },
    },
  })

  const password = watch('admin.password')
  const planId = selectedPlanId || plans[0]?.id || null
  const selectedPlan = plans.find((plan) => plan.id === planId)

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.register({
        company: {
          name: values.company.name.trim(),
          legal_name: values.company.legal_name.trim() || null,
          email: values.company.email.trim().toLowerCase(),
          phone: values.company.phone.trim() || null,
          website: values.company.website.trim() || null,
          business_type: values.company.business_type || null,
          company_size: values.company.company_size || null,
          address: values.company.address.trim() || null,
        },
        admin: {
          name: values.admin.name.trim(),
          email: values.admin.email.trim().toLowerCase(),
          phone: values.admin.phone.trim() || null,
          password: values.admin.password,
          password_confirmation: values.admin.password_confirmation,
        },
        plan_id: planId,
        billing_interval: billingInterval,
        terms_accepted: true,
      }),
    onSuccess: (response) => {
      const payload = response.data?.data
      if (!payload?.token || !payload?.user) {
        setGeneralError('The server returned an unexpected response. Please try again.')
        return
      }
      setAuth({ token: payload.token, user: payload.user })
      toast.success('Your enterprise workspace is ready.')
      navigate('/enterprise/dashboard', { replace: true })
    },
    onError: (error) => {
      const apiErrors = error?.response?.data?.errors
      if (apiErrors && typeof apiErrors === 'object') {
        let applied = false
        Object.entries(apiErrors).forEach(([field, messages]) => {
          const message = Array.isArray(messages) ? messages[0] : String(messages)
          if (field.startsWith('company.') || field.startsWith('admin.')) {
            setError(field, { type: 'server', message })
            applied = true
          } else if (field === 'plan_id') {
            setGeneralError(message)
            applied = true
          } else if (field === 'terms_accepted') {
            setGeneralError(message)
            applied = true
          }
        })
        if (applied) return
      }
      setGeneralError(getErrorMessage(error, 'Could not create your enterprise workspace.'))
    },
  })

  const onSubmit = (values) => {
    setGeneralError('')
    if (!planId) {
      setGeneralError('Select a plan to continue.')
      return
    }
    if (!accepted) {
      setGeneralError('Please accept the terms to continue.')
      return
    }
    mutation.mutate(values)
  }

  return (
    <div>
      <div className="mb-7">
        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-highlight">
          Enterprise edition
        </p>
        <h1 className="mt-2 text-[26px] font-semibold tracking-[-0.02em] text-foreground">
          Create your company workspace
        </h1>
        <p className="mt-1.5 text-sm text-muted-foreground">
          Set up your organisation and become its first administrator. Your 14-day trial starts
          immediately.
        </p>
      </div>

      {generalError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
          {generalError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
        <section className="space-y-4">
          <div className="flex items-center gap-2">
            <BuildingOffice2Icon className="h-4 w-4 text-highlight" />
            <h2 className="text-sm font-semibold text-foreground">Company details</h2>
          </div>

          <FormField label="Company name" htmlFor="company-name" error={errors.company?.name?.message} required>
            <Input
              id="company-name"
              placeholder="e.g. Sunrise Realty"
              invalid={Boolean(errors.company?.name)}
              {...register('company.name', { required: 'Company name is required.' })}
            />
          </FormField>

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField label="Legal name" htmlFor="company-legal-name" error={errors.company?.legal_name?.message}>
              <Input id="company-legal-name" {...register('company.legal_name')} />
            </FormField>
            <FormField label="Business email" htmlFor="company-email" error={errors.company?.email?.message} required>
              <Input
                id="company-email"
                type="email"
                autoComplete="email"
                invalid={Boolean(errors.company?.email)}
                {...register('company.email', { required: 'Business email is required.', pattern: EMAIL_PATTERN })}
              />
            </FormField>
            <FormField label="Phone" htmlFor="company-phone" error={errors.company?.phone?.message}>
              <Input id="company-phone" {...register('company.phone')} />
            </FormField>
            <FormField label="Website" htmlFor="company-website" error={errors.company?.website?.message}>
              <Input
                id="company-website"
                placeholder="https://"
                invalid={Boolean(errors.company?.website)}
                {...register('company.website', {
                  pattern: {
                    value: /^https?:\/\/.+/i,
                    message: 'Enter a full URL starting with http:// or https://',
                  },
                })}
              />
            </FormField>
            <FormField label="Business type" htmlFor="company-type" error={errors.company?.business_type?.message}>
              <Select id="company-type" {...register('company.business_type')}>
                <option value="">Select a type</option>
                {BUSINESS_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {type}
                  </option>
                ))}
              </Select>
            </FormField>
            <FormField label="Company size" htmlFor="company-size" error={errors.company?.company_size?.message}>
              <Select id="company-size" {...register('company.company_size')}>
                <option value="">Select team size</option>
                {COMPANY_SIZES.map((size) => (
                  <option key={size} value={size}>
                    {size} employees
                  </option>
                ))}
              </Select>
            </FormField>
          </div>

          <FormField label="Registered address" htmlFor="company-address" error={errors.company?.address?.message}>
            <Input id="company-address" {...register('company.address')} />
          </FormField>
        </section>

        <section className="space-y-4 border-t border-border pt-6">
          <div className="flex items-center gap-2">
            <UserIcon className="h-4 w-4 text-highlight" />
            <h2 className="text-sm font-semibold text-foreground">Administrator account</h2>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField label="Full name" htmlFor="admin-name" error={errors.admin?.name?.message} required>
              <Input
                id="admin-name"
                autoComplete="name"
                invalid={Boolean(errors.admin?.name)}
                {...register('admin.name', { required: 'Your name is required.' })}
              />
            </FormField>
            <FormField label="Work email" htmlFor="admin-email" error={errors.admin?.email?.message} required>
              <Input
                id="admin-email"
                type="email"
                autoComplete="email"
                leftIcon={EnvelopeIcon}
                invalid={Boolean(errors.admin?.email)}
                {...register('admin.email', { required: 'Your email is required.', pattern: EMAIL_PATTERN })}
              />
            </FormField>
            <FormField label="Phone" htmlFor="admin-phone" error={errors.admin?.phone?.message}>
              <Input id="admin-phone" autoComplete="tel" {...register('admin.phone')} />
            </FormField>
            <div className="hidden sm:block" />
            <FormField
              label="Password"
              htmlFor="admin-password"
              error={errors.admin?.password?.message}
              hint="At least 8 characters."
              required
            >
              <Input
                id="admin-password"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                leftIcon={LockClosedIcon}
                invalid={Boolean(errors.admin?.password)}
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
                {...register('admin.password', {
                  required: 'Create a password.',
                  minLength: { value: 8, message: 'Use at least 8 characters.' },
                })}
              />
            </FormField>
            <FormField
              label="Confirm password"
              htmlFor="admin-password-confirmation"
              error={errors.admin?.password_confirmation?.message}
              required
            >
              <Input
                id="admin-password-confirmation"
                type={showPassword ? 'text' : 'password'}
                autoComplete="new-password"
                leftIcon={LockClosedIcon}
                invalid={Boolean(errors.admin?.password_confirmation)}
                {...register('admin.password_confirmation', {
                  required: 'Confirm the password.',
                  validate: (value) => value === password || 'Passwords do not match.',
                })}
              />
            </FormField>
          </div>
        </section>

        <section className="space-y-3 border-t border-border pt-6">
          <div className="flex items-center justify-between gap-3">
            <h2 className="text-sm font-semibold text-foreground">Choose a plan</h2>
            <div className="flex rounded-full border border-border bg-card p-0.5">
              {[
                ['monthly', 'Monthly'],
                ['annual', 'Annual'],
              ].map(([value, label]) => (
                <button
                  key={value}
                  type="button"
                  onClick={() => setBillingInterval(value)}
                  className={cn(
                    'rounded-full px-3 py-1 text-[11px] font-medium transition',
                    billingInterval === value
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                  )}
                >
                  {label}
                </button>
              ))}
            </div>
          </div>

          {plansLoading ? (
            <p className="text-sm text-muted-foreground">Loading plans…</p>
          ) : plans.length === 0 ? (
            <Alert variant="warning">No enterprise plans are published yet. Contact sales to get started.</Alert>
          ) : (
            <div className="space-y-2.5">
              {plans.map((plan) => (
                <PlanOption
                  key={plan.id}
                  plan={plan}
                  selected={plan.id === planId}
                  onSelect={() => {
                    setSelectedPlanId(plan.id)
                    clearErrors('plan_id')
                  }}
                />
              ))}
            </div>
          )}

          {billingInterval === 'annual' && (
            <p className="text-[11px] text-muted-foreground">
              Annual billing is confirmed by our team during onboarding. Your workspace starts on a
              14-day trial today.
            </p>
          )}
        </section>

        <label className="flex items-start gap-2.5 text-xs leading-relaxed text-muted-foreground">
          <input
            type="checkbox"
            checked={accepted}
            onChange={(event) => setAccepted(event.target.checked)}
            className="mt-0.5 h-4 w-4 rounded border-border text-highlight focus:ring-ring"
          />
          <span>
            I agree to the{' '}
            <Link to="/terms-of-service" className="font-medium text-highlight hover:underline">
              Terms of Service
            </Link>{' '}
            and{' '}
            <Link to="/privacy-policy" className="font-medium text-highlight hover:underline">
              Privacy Policy
            </Link>
            .
          </span>
        </label>

        <Button type="submit" className="w-full" size="lg" loading={mutation.isPending} disabled={!accepted}>
          Create workspace
        </Button>

        {selectedPlan && (
          <p className="text-center text-[11px] leading-relaxed text-muted-foreground">
            <CheckCircleIcon className="mr-1 inline h-3.5 w-3.5 text-highlight" />
            {selectedPlan.name} · {billingInterval === 'annual' ? 'Annual billing' : 'Monthly billing'} ·
            14-day trial included
          </p>
        )}
      </form>

      <div className="mt-7 space-y-2 text-center text-sm text-muted-foreground">
        <p>
          Already have an account?{' '}
          <Link to="/login" className="font-semibold text-highlight hover:underline">
            Sign in
          </Link>
        </p>
        <p className="text-xs">
          Working as an individual Realtor?{' '}
          <Link to="/register" className="font-medium text-highlight hover:underline">
            Create a Realtor account
          </Link>
        </p>
      </div>
    </div>
  )
}
