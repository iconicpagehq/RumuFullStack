import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  CheckIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  LockClosedIcon,
  UserIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { authApi } from '@/api/auth'
import { checkoutApi } from '@/api/checkout'
import { enterpriseApi } from '@/api/enterprise'
import { SocialButtons } from '@/components/auth/SocialButtons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { useAuthStore } from '@/stores/authStore'
import { cn } from '@/utils/cn'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const STEPS = ['Account type', 'Your details', 'Choose a plan']

const ACCOUNT_TYPES = [
  {
    value: 'realtor',
    title: 'Realtor',
    description: 'For individual agents, brokers and small teams managing their own leads.',
    points: ['Campaigns, leads & inventory', 'Meta and CSV lead imports', 'Start free, upgrade anytime'],
    icon: UserIcon,
  },
  {
    value: 'enterprise',
    title: 'Enterprise',
    description: 'For companies that need managers, teams and role-based access.',
    points: ['Managers, teams & reporting lines', 'Lead distribution & targets', '14-day trial included'],
    icon: BuildingOffice2Icon,
  },
]

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

const REALTOR_STEP_FIELDS = ['name', 'email', 'password', 'password_confirmation']

const ENTERPRISE_STEP_FIELDS = [
  'company.name',
  'company.email',
  'company.website',
  'admin.name',
  'admin.email',
  'admin.password',
  'admin.password_confirmation',
]

const planLimit = (value) => (value === null || value === undefined ? 'Unlimited' : formatNumber(value))

const EnterprisePlanOption = ({ plan, selected, onSelect }) => {
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

export const RegisterPage = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)

  const requestedType = searchParams.get('type')
  const [accountType, setAccountType] = useState(() => {
    if (requestedType === 'enterprise') return 'enterprise'
    if (requestedType === 'realtor' || searchParams.get('plan')) return 'realtor'
    return null
  })
  const [step, setStep] = useState(0)
  const [showPassword, setShowPassword] = useState(false)
  const [generalError, setGeneralError] = useState('')
  const [accepted, setAccepted] = useState(false)
  const [realtorPlanId, setRealtorPlanId] = useState(null)
  const [enterprisePlanId, setEnterprisePlanId] = useState(null)
  const [billingInterval, setBillingInterval] = useState('monthly')

  const {
    register,
    handleSubmit,
    trigger,
    setError,
    clearErrors,
    getValues,
    formState: { errors },
  } = useForm({
    mode: 'onTouched',
    defaultValues: {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
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

  const { data: realtorPlans = [], isLoading: realtorPlansLoading } = useQuery({
    queryKey: ['checkout-plans'],
    queryFn: async () => {
      const response = await checkoutApi.plans()
      return (response.data?.data?.plans || []).filter((plan) => plan.audience !== 'enterprise')
    },
    staleTime: 5 * 60 * 1000,
    enabled: accountType === 'realtor',
  })

  const { data: enterprisePlans = [], isLoading: enterprisePlansLoading } = useQuery({
    queryKey: ['enterprise', 'public-plans'],
    queryFn: async () => {
      const response = await enterpriseApi.plans()
      return response.data?.data || []
    },
    staleTime: 5 * 60 * 1000,
    enabled: accountType === 'enterprise',
  })

  useEffect(() => {
    if (realtorPlans.length === 0) return
    setRealtorPlanId((current) => {
      if (current && realtorPlans.some((plan) => plan.id === current)) return current
      const requested = Number(searchParams.get('plan'))
      const match = realtorPlans.find((plan) => plan.id === requested)
      const fallback = realtorPlans.find((plan) => Number(plan.price) === 0) || realtorPlans[0]
      return match?.id || fallback?.id || null
    })
  }, [realtorPlans, searchParams])

  useEffect(() => {
    if (enterprisePlans.length === 0) return
    setEnterprisePlanId((current) =>
      current && enterprisePlans.some((plan) => plan.id === current) ? current : enterprisePlans[0].id,
    )
  }, [enterprisePlans])

  const applyAuth = (response) => {
    const payload = response.data?.data
    if (!payload?.token || !payload?.user) {
      setGeneralError('The server returned an unexpected response. Please try again.')
      return false
    }
    setAuth({ token: payload.token, user: payload.user })
    return true
  }

  const realtorMutation = useMutation({
    mutationFn: (values) =>
      authApi.register({
        name: values.name.trim(),
        email: values.email.trim().toLowerCase(),
        password: values.password,
        password_confirmation: values.password_confirmation,
      }),
    onSuccess: (response) => {
      if (!applyAuth(response)) return
      toast.success('Your account is ready.')
      const plan = realtorPlans.find((item) => item.id === realtorPlanId)
      if (plan && Number(plan.price) > 0) {
        navigate(`/checkout?plan=${plan.id}`, { replace: true })
      } else {
        navigate('/realtor/dashboard', { replace: true })
      }
    },
    onError: (error) => {
      if (applyServerErrors(error, setError, REALTOR_STEP_FIELDS)) {
        setStep(1)
        return
      }
      setGeneralError(getErrorMessage(error, 'Unable to create your account.'))
    },
  })

  const enterpriseMutation = useMutation({
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
        plan_id: enterprisePlanId,
        billing_interval: billingInterval,
        terms_accepted: true,
      }),
    onSuccess: (response) => {
      if (!applyAuth(response)) return
      toast.success('Your enterprise workspace is ready.')
      navigate('/enterprise/dashboard', { replace: true })
    },
    onError: (error) => {
      const apiErrors = error?.response?.data?.errors
      if (apiErrors && typeof apiErrors === 'object') {
        let applied = false
        let detailsApplied = false
        Object.entries(apiErrors).forEach(([field, messages]) => {
          const message = Array.isArray(messages) ? messages[0] : String(messages)
          if (field.startsWith('company.') || field.startsWith('admin.')) {
            setError(field, { type: 'server', message })
            applied = true
            detailsApplied = true
          } else if (field === 'plan_id' || field === 'terms_accepted') {
            setGeneralError(message)
            applied = true
          }
        })
        if (applied) {
          if (detailsApplied) setStep(1)
          return
        }
      }
      setGeneralError(getErrorMessage(error, 'Could not create your enterprise workspace.'))
    },
  })

  const isEnterprise = accountType === 'enterprise'
  const isSubmitting = realtorMutation.isPending || enterpriseMutation.isPending

  const chooseType = (value) => {
    setAccountType(value)
    setGeneralError('')
    clearErrors()
  }

  const goNext = async () => {
    setGeneralError('')
    if (step === 0) {
      if (!accountType) {
        toast.error('Choose Realtor or Enterprise to continue.')
        return
      }
      setStep(1)
      return
    }
    if (step === 1) {
      const valid = await trigger(isEnterprise ? ENTERPRISE_STEP_FIELDS : REALTOR_STEP_FIELDS)
      if (valid) setStep(2)
    }
  }

  const goBack = () => {
    setGeneralError('')
    setStep((current) => Math.max(0, current - 1))
  }

  const onSubmit = (values) => {
    setGeneralError('')
    if (!accepted) {
      toast.error('Please accept the terms to continue.')
      return
    }
    if (isEnterprise) {
      if (!enterprisePlanId) {
        setGeneralError('Select a plan to continue.')
        return
      }
      enterpriseMutation.mutate(values)
      return
    }
    realtorMutation.mutate(values)
  }

  const selectedRealtorPlan = realtorPlans.find((plan) => plan.id === realtorPlanId)
  const selectedEnterprisePlan = enterprisePlans.find((plan) => plan.id === enterprisePlanId)

  const headings = (() => {
    if (step === 0) {
      return {
        title: 'Create your account',
        subtitle: 'Tell us how you will use Rumu so we can set up the right workspace.',
      }
    }
    if (step === 1) {
      return isEnterprise
        ? {
            title: 'Your company details',
            subtitle: 'Set up your organisation and become its first administrator.',
          }
        : { title: 'Your details', subtitle: 'Create the login you will use every day.' }
    }
    return {
      title: 'Choose a plan',
      subtitle: isEnterprise
        ? 'Your workspace starts on a 14-day trial. Our team confirms billing during onboarding.'
        : 'Pick a plan now — you can change or upgrade later.',
    }
  })()

  const submitLabel = isEnterprise
    ? 'Create workspace'
    : selectedRealtorPlan && Number(selectedRealtorPlan.price) > 0
      ? 'Create account & continue'
      : 'Create free account'

  return (
    <div>
      <ol className="mb-7 flex items-center gap-2" aria-label="Registration progress">
        {STEPS.map((label, index) => {
          const complete = index < step
          const current = index === step
          return (
            <li key={label} className="flex flex-1 items-center gap-2">
              <span
                className={cn(
                  'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold transition',
                  complete
                    ? 'bg-highlight text-white'
                    : current
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-muted text-muted-foreground',
                )}
                aria-current={current ? 'step' : undefined}
              >
                {complete ? <CheckIcon className="h-3.5 w-3.5" /> : index + 1}
              </span>
              <span
                className={cn(
                  'hidden text-[11px] font-medium sm:block',
                  current ? 'text-foreground' : 'text-muted-foreground',
                )}
              >
                {label}
              </span>
              {index < STEPS.length - 1 && (
                <span className={cn('h-px flex-1', complete ? 'bg-highlight/40' : 'bg-border')} />
              )}
            </li>
          )
        })}
      </ol>

      <div className="mb-7">
        {step === 0 ? (
          <p className="text-xs font-semibold uppercase tracking-[0.18em] text-highlight">
            Step 1 of 3
          </p>
        ) : null}
        <h1
          className={cn(
            'text-[26px] font-semibold tracking-[-0.02em] text-foreground',
            step === 0 && 'mt-2',
          )}
        >
          {headings.title}
        </h1>
        <p className="mt-1.5 text-sm text-muted-foreground">{headings.subtitle}</p>
      </div>

      {generalError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
          {generalError}
        </Alert>
      )}

      {step === 0 && (
        <>
          <div className="space-y-3">
            {ACCOUNT_TYPES.map((option) => {
              const selected = accountType === option.value
              const Icon = option.icon
              return (
                <button
                  key={option.value}
                  type="button"
                  onClick={() => chooseType(option.value)}
                  aria-pressed={selected}
                  className={cn(
                    'w-full rounded-2xl border p-4 text-left transition',
                    selected
                      ? 'border-highlight bg-primary/5 ring-1 ring-highlight/30'
                      : 'border-border bg-card hover:border-highlight/30',
                  )}
                >
                  <div className="flex items-start gap-3">
                    <span
                      className={cn(
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                        selected ? 'bg-primary/10 text-highlight' : 'bg-muted text-muted-foreground',
                      )}
                    >
                      <Icon className="h-5 w-5" />
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="flex items-center justify-between gap-2">
                        <span className="text-sm font-semibold text-foreground">{option.title}</span>
                        <span
                          className={cn(
                            'flex h-5 w-5 items-center justify-center rounded-full border',
                            selected ? 'border-highlight bg-highlight text-white' : 'border-border',
                          )}
                        >
                          {selected ? <CheckIcon className="h-3 w-3" /> : null}
                        </span>
                      </span>
                      <span className="mt-1 block text-xs text-muted-foreground">{option.description}</span>
                      <span className="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                        {option.points.map((point) => (
                          <span
                            key={point}
                            className="flex items-center gap-1 text-[11px] text-muted-foreground"
                          >
                            <CheckCircleIcon className="h-3 w-3 text-highlight" />
                            {point}
                          </span>
                        ))}
                      </span>
                    </span>
                  </div>
                </button>
              )
            })}
          </div>

          <Button type="button" className="mt-6 w-full" size="lg" onClick={goNext} disabled={!accountType}>
            Continue
          </Button>
        </>
      )}

      {step === 1 && (
        <>
          {!isEnterprise && (
            <>
              <SocialButtons disabled={isSubmitting} label="Sign up" />
              <div className="my-6 flex items-center gap-3">
                <span className="h-px flex-1 bg-border" />
                <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  or sign up with email
                </span>
                <span className="h-px flex-1 bg-border" />
              </div>
            </>
          )}

          <form
            onSubmit={(event) => {
              event.preventDefault()
              goNext()
            }}
            className="space-y-4"
            noValidate
          >
            {!isEnterprise ? (
              <>
                <FormField label="Full name" htmlFor="name" error={errors.name?.message} required>
                  <Input
                    id="name"
                    autoComplete="name"
                    placeholder="Your full name"
                    leftIcon={UserIcon}
                    invalid={Boolean(errors.name)}
                    {...register('name', {
                      required: 'Enter your full name.',
                      minLength: { value: 2, message: 'Use at least 2 characters.' },
                    })}
                  />
                </FormField>

                <FormField label="Work email" htmlFor="email" error={errors.email?.message} required>
                  <Input
                    id="email"
                    type="email"
                    autoComplete="email"
                    placeholder="you@company.com"
                    leftIcon={EnvelopeIcon}
                    invalid={Boolean(errors.email)}
                    {...register('email', { required: 'Enter your email address.', pattern: EMAIL_PATTERN })}
                  />
                </FormField>

                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField
                    label="Password"
                    htmlFor="password"
                    error={errors.password?.message}
                    hint="At least 8 characters"
                    required
                  >
                    <Input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      autoComplete="new-password"
                      placeholder="Create a password"
                      leftIcon={LockClosedIcon}
                      invalid={Boolean(errors.password)}
                      rightSlot={
                        <button
                          type="button"
                          onClick={() => setShowPassword((value) => !value)}
                          className="rounded p-1 text-muted-foreground transition hover:text-foreground"
                          aria-label={showPassword ? 'Hide password' : 'Show password'}
                        >
                          {showPassword ? (
                            <EyeSlashIcon className="h-4 w-4" />
                          ) : (
                            <EyeIcon className="h-4 w-4" />
                          )}
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
                    htmlFor="password_confirmation"
                    error={errors.password_confirmation?.message}
                    required
                  >
                    <Input
                      id="password_confirmation"
                      type={showPassword ? 'text' : 'password'}
                      autoComplete="new-password"
                      placeholder="Repeat password"
                      leftIcon={LockClosedIcon}
                      invalid={Boolean(errors.password_confirmation)}
                      {...register('password_confirmation', {
                        required: 'Confirm the password.',
                        validate: (value) =>
                          value === getValues('password') || 'Passwords do not match.',
                      })}
                    />
                  </FormField>
                </div>
              </>
            ) : (
              <>
                <section className="space-y-4">
                  <div className="flex items-center gap-2">
                    <BuildingOffice2Icon className="h-4 w-4 text-highlight" />
                    <h2 className="text-sm font-semibold text-foreground">Company details</h2>
                  </div>

                  <FormField
                    label="Company name"
                    htmlFor="company-name"
                    error={errors.company?.name?.message}
                    required
                  >
                    <Input
                      id="company-name"
                      placeholder="e.g. Sunrise Realty"
                      invalid={Boolean(errors.company?.name)}
                      {...register('company.name', { required: 'Company name is required.' })}
                    />
                  </FormField>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                      label="Legal name"
                      htmlFor="company-legal-name"
                      error={errors.company?.legal_name?.message}
                    >
                      <Input id="company-legal-name" {...register('company.legal_name')} />
                    </FormField>
                    <FormField
                      label="Business email"
                      htmlFor="company-email"
                      error={errors.company?.email?.message}
                      required
                    >
                      <Input
                        id="company-email"
                        type="email"
                        autoComplete="email"
                        invalid={Boolean(errors.company?.email)}
                        {...register('company.email', {
                          required: 'Business email is required.',
                          pattern: EMAIL_PATTERN,
                        })}
                      />
                    </FormField>
                    <FormField label="Phone" htmlFor="company-phone" error={errors.company?.phone?.message}>
                      <Input id="company-phone" {...register('company.phone')} />
                    </FormField>
                    <FormField
                      label="Website"
                      htmlFor="company-website"
                      error={errors.company?.website?.message}
                    >
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
                    <FormField
                      label="Business type"
                      htmlFor="company-type"
                      error={errors.company?.business_type?.message}
                    >
                      <Select id="company-type" {...register('company.business_type')}>
                        <option value="">Select a type</option>
                        {BUSINESS_TYPES.map((type) => (
                          <option key={type} value={type}>
                            {type}
                          </option>
                        ))}
                      </Select>
                    </FormField>
                    <FormField
                      label="Company size"
                      htmlFor="company-size"
                      error={errors.company?.company_size?.message}
                    >
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

                  <FormField
                    label="Registered address"
                    htmlFor="company-address"
                    error={errors.company?.address?.message}
                  >
                    <Input id="company-address" {...register('company.address')} />
                  </FormField>
                </section>

                <section className="space-y-4 border-t border-border pt-6">
                  <div className="flex items-center gap-2">
                    <UserGroupIcon className="h-4 w-4 text-highlight" />
                    <h2 className="text-sm font-semibold text-foreground">Administrator account</h2>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                      label="Full name"
                      htmlFor="admin-name"
                      error={errors.admin?.name?.message}
                      required
                    >
                      <Input
                        id="admin-name"
                        autoComplete="name"
                        invalid={Boolean(errors.admin?.name)}
                        {...register('admin.name', { required: 'Your name is required.' })}
                      />
                    </FormField>
                    <FormField
                      label="Work email"
                      htmlFor="admin-email"
                      error={errors.admin?.email?.message}
                      required
                    >
                      <Input
                        id="admin-email"
                        type="email"
                        autoComplete="email"
                        leftIcon={EnvelopeIcon}
                        invalid={Boolean(errors.admin?.email)}
                        {...register('admin.email', {
                          required: 'Your email is required.',
                          pattern: EMAIL_PATTERN,
                        })}
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
                            {showPassword ? (
                              <EyeSlashIcon className="h-4 w-4" />
                            ) : (
                              <EyeIcon className="h-4 w-4" />
                            )}
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
                          validate: (value) =>
                            value === getValues('admin.password') || 'Passwords do not match.',
                        })}
                      />
                    </FormField>
                  </div>
                </section>
              </>
            )}

            <div className="flex items-center gap-3 pt-1">
              <Button type="button" variant="outline" onClick={goBack} disabled={isSubmitting}>
                <ArrowLeftIcon className="h-4 w-4" />
                Back
              </Button>
              <Button type="button" className="flex-1" onClick={goNext}>
                Continue
              </Button>
            </div>
          </form>
        </>
      )}

      {step === 2 && (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5" noValidate>
          {isEnterprise ? (
            <section className="space-y-3">
              <div className="flex items-center justify-between gap-3">
                <h2 className="text-sm font-semibold text-foreground">Enterprise plans</h2>
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

              {enterprisePlansLoading ? (
                <p className="text-sm text-muted-foreground">Loading plans…</p>
              ) : enterprisePlans.length === 0 ? (
                <Alert variant="warning">
                  No enterprise plans are published yet. Contact sales to get started.
                </Alert>
              ) : (
                <div className="space-y-2.5">
                  {enterprisePlans.map((plan) => (
                    <EnterprisePlanOption
                      key={plan.id}
                      plan={plan}
                      selected={plan.id === enterprisePlanId}
                      onSelect={() => {
                        setEnterprisePlanId(plan.id)
                        clearErrors('plan_id')
                      }}
                    />
                  ))}
                </div>
              )}

              {billingInterval === 'annual' && (
                <p className="text-[11px] text-muted-foreground">
                  Annual billing is confirmed by our team during onboarding. Your workspace starts on
                  a 14-day trial today.
                </p>
              )}
            </section>
          ) : (
            <section className="space-y-3">
              <h2 className="text-sm font-semibold text-foreground">Realtor plans</h2>
              {realtorPlansLoading ? (
                <p className="text-sm text-muted-foreground">Loading plans…</p>
              ) : realtorPlans.length === 0 ? (
                <p className="text-[11px] text-muted-foreground">
                  No plans are published right now. Your account will start on the free tier.
                </p>
              ) : (
                <div className="space-y-2">
                  {realtorPlans.map((plan) => {
                    const selected = plan.id === realtorPlanId
                    return (
                      <button
                        key={plan.id}
                        type="button"
                        onClick={() => setRealtorPlanId(plan.id)}
                        className={cn(
                          'flex w-full items-center gap-3 rounded-xl border p-3 text-left transition',
                          selected
                            ? 'border-highlight bg-primary/5 ring-1 ring-highlight/20'
                            : 'border-border bg-card hover:border-highlight/30',
                        )}
                      >
                        <span
                          className={cn(
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                            selected ? 'bg-primary/10 text-highlight' : 'bg-muted text-muted-foreground',
                          )}
                        >
                          {selected ? (
                            <CheckCircleIcon className="h-4 w-4" />
                          ) : (
                            <BuildingOffice2Icon className="h-4 w-4" />
                          )}
                        </span>
                        <span className="min-w-0 flex-1">
                          <span className="flex items-center gap-2">
                            <span className="text-sm font-semibold text-foreground">{plan.name}</span>
                            {plan.is_default && (
                              <span className="rounded bg-muted px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-muted-foreground">
                                Default
                              </span>
                            )}
                          </span>
                          <span className="mt-0.5 block text-[11px] text-muted-foreground">
                            {plan.max_campaigns ? `${plan.max_campaigns} campaigns` : 'Unlimited campaigns'}{' '}
                            ·{' '}
                            {plan.max_leads_per_campaign
                              ? `${plan.max_leads_per_campaign} leads each`
                              : 'Unlimited leads'}
                          </span>
                        </span>
                        <span className="text-sm font-semibold text-foreground">
                          {Number(plan.price) > 0 ? formatCurrency(plan.price) : 'Free'}
                        </span>
                      </button>
                    )
                  })}
                </div>
              )}
            </section>
          )}

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

          <Button
            type="submit"
            className="w-full"
            size="lg"
            loading={isSubmitting}
            disabled={!accepted}
          >
            {submitLabel}
          </Button>

          {isEnterprise && selectedEnterprisePlan && (
            <p className="text-center text-[11px] leading-relaxed text-muted-foreground">
              <CheckCircleIcon className="mr-1 inline h-3.5 w-3.5 text-highlight" />
              {selectedEnterprisePlan.name} ·{' '}
              {billingInterval === 'annual' ? 'Annual billing' : 'Monthly billing'} · 14-day trial
              included
            </p>
          )}

          <div className="flex items-center gap-3">
            <Button type="button" variant="outline" onClick={goBack} disabled={isSubmitting}>
              <ArrowLeftIcon className="h-4 w-4" />
              Back
            </Button>
            <button
              type="button"
              onClick={() => {
                setAccountType(null)
                setStep(0)
                setGeneralError('')
              }}
              className="text-xs font-medium text-muted-foreground transition hover:text-foreground"
            >
              Change account type
            </button>
          </div>
        </form>
      )}

      <p className="mt-7 text-center text-sm text-muted-foreground">
        Already have an account?{' '}
        <Link to="/login" className="font-semibold text-highlight hover:underline">
          Sign in
        </Link>
      </p>
    </div>
  )
}
