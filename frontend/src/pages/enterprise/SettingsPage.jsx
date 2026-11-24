import { useEffect, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import {
  BuildingOffice2Icon,
  KeyIcon,
  PhotoIcon,
  UserCircleIcon,
} from '@heroicons/react/24/outline'
import { enterpriseApi } from '@/api/enterprise'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { Switch } from '@/components/ui/Switch'
import { Tabs } from '@/components/ui/Tabs'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { useAuthStore } from '@/stores/authStore'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatDate, formatNumber } from '@/utils/formatters'

const BUSINESS_TYPES = [
  'Developer',
  'Real estate agency',
  'Brokerage',
  'Property management',
  'Channel partner',
  'Other',
]

const COMPANY_SIZES = ['1–10', '11–50', '51–200', '201–500', '500+']

const DAYS = [
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday',
  'Sunday',
]

const defaultWorkingHours = () =>
  DAYS.map((day) => ({
    day,
    closed: day === 'Sunday',
    open: '09:00',
    close: '18:00',
  }))

const profileDefaults = { name: '', phone: '', designation: '' }
const passwordDefaults = { current_password: '', password: '', password_confirmation: '' }

const planLimit = (value) => (value === null || value === undefined ? 'Unlimited' : formatNumber(value))

export const SettingsPage = () => {
  const queryClient = useQueryClient()
  const { isAdmin, role } = useEnterprise()
  const { teams } = useEnterpriseReference()
  const setUser = useAuthStore((state) => state.setUser)
  const currentUser = useAuthStore((state) => state.user)
  const logoInput = useRef(null)
  const [tab, setTab] = useState('profile')
  const [autoAssign, setAutoAssign] = useState(false)
  const [duplicateDetection, setDuplicateDetection] = useState(true)
  const [distributionTeam, setDistributionTeam] = useState('')
  const [workingHours, setWorkingHours] = useState(defaultWorkingHours)
  const [profileError, setProfileError] = useState('')
  const [enterpriseError, setEnterpriseError] = useState('')
  const [passwordError, setPasswordError] = useState('')
  const [passwordSuccess, setPasswordSuccess] = useState('')

  const tabs = [
    { key: 'profile', label: 'My profile' },
    { key: 'security', label: 'Password' },
    { key: 'company', label: 'Company' },
  ]

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'settings', isAdmin],
    queryFn: async () => {
      const response = isAdmin ? await enterpriseApi.settings.get() : await enterpriseApi.me()
      return response.data?.data
    },
  })

  const enterprise = data?.enterprise
  const membership = data?.membership
  const plan = data?.plan

  const profileForm = useForm({ defaultValues: profileDefaults })
  const passwordForm = useForm({ defaultValues: passwordDefaults })
  const enterpriseForm = useForm({
    defaultValues: {
      name: '',
      legal_name: '',
      email: '',
      phone: '',
      address: '',
      website: '',
      business_type: '',
      company_size: '',
      rera_number: '',
      billing_interval: 'monthly',
    },
  })

  useEffect(() => {
    if (!membership) return
    profileForm.reset({
      name: membership.user?.name || '',
      phone: membership.user?.phone || '',
      designation: membership.designation || '',
    })
  }, [membership, profileForm])

  useEffect(() => {
    if (!enterprise) return
    enterpriseForm.reset({
      name: enterprise.name || '',
      legal_name: enterprise.legal_name || '',
      email: enterprise.email || '',
      phone: enterprise.phone || '',
      address: enterprise.address || '',
      website: enterprise.website || '',
      business_type: enterprise.business_type || '',
      company_size: enterprise.company_size || '',
      rera_number: enterprise.rera_number || '',
      billing_interval: enterprise.settings?.billing_interval || 'monthly',
    })
    setAutoAssign(Boolean(enterprise.settings?.auto_assign_round_robin))
    setDuplicateDetection(enterprise.settings?.lead_duplicate_detection ?? true)
    setDistributionTeam(
      enterprise.settings?.auto_distribution_team_id
        ? String(enterprise.settings.auto_distribution_team_id)
        : '',
    )
    setWorkingHours(
      Array.isArray(enterprise.working_hours) && enterprise.working_hours.length > 0
        ? enterprise.working_hours
        : defaultWorkingHours(),
    )
  }, [enterprise, enterpriseForm])

  const invalidateWorkspace = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'settings'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'me'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'billing'] })
  }

  const profileMutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        name: values.name.trim(),
        phone: values.phone?.trim() || null,
      }
      if (!isAdmin) payload.designation = values.designation?.trim() || null
      return enterpriseApi.settings.updateProfile(payload)
    },
    onSuccess: (response, values) => {
      toast.success('Profile updated')
      const updatedUser = response.data?.data?.membership?.user
      if (updatedUser) {
        setUser({
          ...currentUser,
          name: updatedUser.name,
          phone: updatedUser.phone,
        })
      }
      profileForm.reset({
        name: values.name.trim(),
        phone: values.phone?.trim() || '',
        designation: values.designation?.trim() || '',
      })
      invalidateWorkspace()
    },
    onError: (profileMutationError) => {
      if (!applyServerErrors(profileMutationError, profileForm.setError, ['name', 'phone', 'designation'])) {
        setProfileError(getErrorMessage(profileMutationError, 'Could not update your profile.'))
      }
    },
  })

  const passwordMutation = useMutation({
    mutationFn: (values) => enterpriseApi.settings.updatePassword(values),
    onSuccess: () => {
      setPasswordSuccess('Your password has been updated.')
      passwordForm.reset(passwordDefaults)
      invalidateWorkspace()
    },
    onError: (passwordMutationError) => {
      setPasswordSuccess('')
      if (
        !applyServerErrors(passwordMutationError, passwordForm.setError, [
          'current_password',
          'password',
        ])
      ) {
        setPasswordError(getErrorMessage(passwordMutationError, 'Could not update your password.'))
      }
    },
  })

  const enterpriseMutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.settings.update({
        name: values.name.trim(),
        legal_name: values.legal_name?.trim() || null,
        email: values.email.trim(),
        phone: values.phone?.trim() || null,
        address: values.address?.trim() || null,
        website: values.website?.trim() || null,
        business_type: values.business_type || null,
        company_size: values.company_size || null,
        rera_number: values.rera_number?.trim() || null,
        working_hours: workingHours,
        billing_interval: values.billing_interval,
        auto_assign_round_robin: autoAssign,
        lead_duplicate_detection: duplicateDetection,
        auto_distribution_mode: autoAssign ? 'round_robin' : 'manual',
        auto_distribution_team_id: distributionTeam ? Number(distributionTeam) : null,
      }),
    onSuccess: () => {
      toast.success('Enterprise settings updated')
      invalidateWorkspace()
    },
    onError: (enterpriseMutationError) => {
      if (
        !applyServerErrors(enterpriseMutationError, enterpriseForm.setError, [
          'name',
          'legal_name',
          'email',
          'phone',
          'address',
          'website',
          'business_type',
          'company_size',
          'rera_number',
          'working_hours',
        ])
      ) {
        setEnterpriseError(
          getErrorMessage(enterpriseMutationError, 'Could not update enterprise settings.'),
        )
      }
    },
  })

  const logoMutation = useMutation({
    mutationFn: (file) => enterpriseApi.settings.uploadLogo(file),
    onSuccess: () => {
      toast.success('Company logo updated')
      invalidateWorkspace()
    },
    onError: (logoError) => {
      toast.error(getErrorMessage(logoError, 'Could not upload this logo.'))
    },
  })

  const removeLogoMutation = useMutation({
    mutationFn: () => enterpriseApi.settings.removeLogo(),
    onSuccess: () => {
      toast.success('Company logo removed')
      invalidateWorkspace()
    },
    onError: (logoError) => {
      toast.error(getErrorMessage(logoError, 'Could not remove the logo.'))
    },
  })

  const updateWorkingHour = (day, patch) => {
    setWorkingHours((current) =>
      current.map((entry) => (entry.day === day ? { ...entry, ...patch } : entry)),
    )
  }

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div className="mx-auto max-w-4xl">
      <PageHeader
        title="Settings"
        description="Your profile, password and enterprise workspace configuration."
      />

      <Tabs tabs={tabs} value={tab} onChange={setTab} className="mb-5" />

      {tab === 'profile' && (
        <Card>
          <CardHeader className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle>My profile</CardTitle>
              <CardDescription>This is how you appear to your team.</CardDescription>
            </div>
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-highlight">
              <UserCircleIcon className="h-5 w-5" />
            </span>
          </CardHeader>
          <CardContent>
            <div className="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-border bg-muted/25 px-3 py-2.5 text-xs text-muted-foreground">
              <Badge variant={role === 'admin' ? 'primary' : role === 'manager' ? 'purple' : 'neutral'}>
                {membership?.role || role}
              </Badge>
              <span>{membership?.user?.email}</span>
              {membership?.team?.name && <span>· {membership.team.name}</span>}
              <span>· Joined {formatDate(membership?.joined_at)}</span>
            </div>

            <form
              className="space-y-4"
              onSubmit={profileForm.handleSubmit((values) => {
                setProfileError('')
                profileMutation.mutate(values)
              })}
              noValidate
            >
              {profileError && (
                <Alert variant="danger" onDismiss={() => setProfileError('')}>
                  {profileError}
                </Alert>
              )}

              <div className="grid gap-4 sm:grid-cols-2">
                <FormField
                  label="Full name"
                  htmlFor="profile-name"
                  error={profileForm.formState.errors.name?.message}
                  required
                >
                  <Input
                    id="profile-name"
                    invalid={Boolean(profileForm.formState.errors.name)}
                    {...profileForm.register('name', { required: 'Enter your name.' })}
                  />
                </FormField>
                <FormField label="Phone" htmlFor="profile-phone" error={profileForm.formState.errors.phone?.message}>
                  <Input id="profile-phone" {...profileForm.register('phone')} />
                </FormField>
              </div>

              {!isAdmin && (
                <FormField
                  label="Designation"
                  htmlFor="profile-designation"
                  error={profileForm.formState.errors.designation?.message}
                >
                  <Input
                    id="profile-designation"
                    placeholder="e.g. Sales executive"
                    {...profileForm.register('designation')}
                  />
                </FormField>
              )}

              <div className="flex justify-end">
                <Button type="submit" loading={profileMutation.isPending}>
                  Save profile
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {tab === 'security' && (
        <Card>
          <CardHeader className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle>Change password</CardTitle>
              <CardDescription>Use at least 8 characters and keep it private.</CardDescription>
            </div>
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-highlight">
              <KeyIcon className="h-5 w-5" />
            </span>
          </CardHeader>
          <CardContent>
            <form
              className="max-w-lg space-y-4"
              onSubmit={passwordForm.handleSubmit((values) => {
                setPasswordError('')
                setPasswordSuccess('')
                passwordMutation.mutate(values)
              })}
              noValidate
            >
              {passwordError && (
                <Alert variant="danger" onDismiss={() => setPasswordError('')}>
                  {passwordError}
                </Alert>
              )}
              {passwordSuccess && (
                <Alert variant="success" onDismiss={() => setPasswordSuccess('')}>
                  {passwordSuccess}
                </Alert>
              )}

              <FormField
                label="Current password"
                htmlFor="current-password"
                error={passwordForm.formState.errors.current_password?.message}
                required
              >
                <Input
                  id="current-password"
                  type="password"
                  autoComplete="current-password"
                  invalid={Boolean(passwordForm.formState.errors.current_password)}
                  {...passwordForm.register('current_password', {
                    required: 'Enter your current password.',
                  })}
                />
              </FormField>

              <FormField
                label="New password"
                htmlFor="new-password"
                error={passwordForm.formState.errors.password?.message}
                hint="At least 8 characters."
                required
              >
                <Input
                  id="new-password"
                  type="password"
                  autoComplete="new-password"
                  invalid={Boolean(passwordForm.formState.errors.password)}
                  {...passwordForm.register('password', {
                    required: 'Enter a new password.',
                    minLength: { value: 8, message: 'Use at least 8 characters.' },
                  })}
                />
              </FormField>

              <FormField
                label="Confirm new password"
                htmlFor="confirm-password"
                error={passwordForm.formState.errors.password_confirmation?.message}
                required
              >
                <Input
                  id="confirm-password"
                  type="password"
                  autoComplete="new-password"
                  invalid={Boolean(passwordForm.formState.errors.password_confirmation)}
                  {...passwordForm.register('password_confirmation', {
                    required: 'Confirm the new password.',
                    validate: (value) =>
                      value === passwordForm.getValues('password') || 'Passwords do not match.',
                  })}
                />
              </FormField>

              <div className="flex justify-end">
                <Button type="submit" loading={passwordMutation.isPending}>
                  Update password
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {tab === 'company' && (
        <div className="space-y-5">
          <Card>
            <CardHeader className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <CardTitle>Company profile</CardTitle>
                <CardDescription>
                  {isAdmin
                    ? 'Company details shown to your team and on outbound communication.'
                    : 'Company details are managed by a company administrator.'}
                </CardDescription>
              </div>
              <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-highlight">
                <BuildingOffice2Icon className="h-5 w-5" />
              </span>
            </CardHeader>
            <CardContent>
              <div className="mb-5 flex flex-wrap items-center gap-4 rounded-xl border border-border bg-muted/20 p-4">
                <div className="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-border bg-card">
                  {enterprise?.logo_url ? (
                    <img
                      src={enterprise.logo_url}
                      alt={`${enterprise.name} logo`}
                      className="h-full w-full object-contain"
                    />
                  ) : (
                    <PhotoIcon className="h-6 w-6 text-muted-foreground" />
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold text-foreground">{enterprise?.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {enterprise?.rera_number ? `RERA ${enterprise.rera_number}` : 'No RERA number set'}
                  </p>
                </div>
                {isAdmin && (
                  <div className="flex flex-wrap gap-2">
                    <input
                      ref={logoInput}
                      type="file"
                      accept="image/png,image/jpeg,image/webp"
                      className="hidden"
                      onChange={(event) => {
                        const file = event.target.files?.[0]
                        if (file) logoMutation.mutate(file)
                        event.target.value = ''
                      }}
                    />
                    <Button
                      variant="outline"
                      size="sm"
                      loading={logoMutation.isPending}
                      onClick={() => logoInput.current?.click()}
                    >
                      Upload logo
                    </Button>
                    {enterprise?.logo_url && (
                      <Button
                        variant="ghost"
                        size="sm"
                        loading={removeLogoMutation.isPending}
                        onClick={() => removeLogoMutation.mutate()}
                      >
                        Remove
                      </Button>
                    )}
                  </div>
                )}
              </div>

              {plan && (
                <div className="mb-5 flex flex-wrap items-center gap-2 rounded-xl border border-border bg-muted/25 px-3 py-2.5 text-xs text-muted-foreground">
                  <Badge variant="info">{plan.name}</Badge>
                  <span>
                    {planLimit(plan.max_managers)} managers · {planLimit(plan.max_employees)} employees ·{' '}
                    {planLimit(plan.max_teams)} teams
                  </span>
                  {enterprise?.trial_ends_at && <span>· Trial ends {formatDate(enterprise.trial_ends_at)}</span>}
                </div>
              )}

              {!isAdmin ? (
                <dl className="grid gap-4 sm:grid-cols-2">
                  <ReadOnlyDetail label="Company name" value={enterprise?.name || '—'} />
                  <ReadOnlyDetail label="Company email" value={enterprise?.email || '—'} />
                  <ReadOnlyDetail label="Phone" value={enterprise?.phone || '—'} />
                  <ReadOnlyDetail label="Website" value={enterprise?.website || '—'} />
                  <ReadOnlyDetail label="RERA number" value={enterprise?.rera_number || '—'} />
                  <ReadOnlyDetail label="Registered address" value={enterprise?.address || '—'} />
                </dl>
              ) : (
                <form
                  className="space-y-5"
                  onSubmit={enterpriseForm.handleSubmit((values) => {
                    setEnterpriseError('')
                    enterpriseMutation.mutate(values)
                  })}
                  noValidate
                >
                  {enterpriseError && (
                    <Alert variant="danger" onDismiss={() => setEnterpriseError('')}>
                      {enterpriseError}
                    </Alert>
                  )}

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                      label="Company name"
                      htmlFor="enterprise-name"
                      error={enterpriseForm.formState.errors.name?.message}
                      required
                    >
                      <Input
                        id="enterprise-name"
                        invalid={Boolean(enterpriseForm.formState.errors.name)}
                        {...enterpriseForm.register('name', { required: 'Enter the company name.' })}
                      />
                    </FormField>
                    <FormField
                      label="Legal name"
                      htmlFor="enterprise-legal-name"
                      error={enterpriseForm.formState.errors.legal_name?.message}
                    >
                      <Input id="enterprise-legal-name" {...enterpriseForm.register('legal_name')} />
                    </FormField>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                      label="Company email"
                      htmlFor="enterprise-email"
                      error={enterpriseForm.formState.errors.email?.message}
                      required
                    >
                      <Input
                        id="enterprise-email"
                        type="email"
                        invalid={Boolean(enterpriseForm.formState.errors.email)}
                        {...enterpriseForm.register('email', {
                          required: 'Enter a company email.',
                          pattern: {
                            value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                            message: 'Enter a valid email address.',
                          },
                        })}
                      />
                    </FormField>
                    <FormField label="Phone" htmlFor="enterprise-phone" error={enterpriseForm.formState.errors.phone?.message}>
                      <Input id="enterprise-phone" {...enterpriseForm.register('phone')} />
                    </FormField>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Website" htmlFor="enterprise-website" error={enterpriseForm.formState.errors.website?.message}>
                      <Input
                        id="enterprise-website"
                        placeholder="https://"
                        {...enterpriseForm.register('website')}
                      />
                    </FormField>
                    <FormField
                      label="RERA number"
                      htmlFor="enterprise-rera"
                      error={enterpriseForm.formState.errors.rera_number?.message}
                    >
                      <Input
                        id="enterprise-rera"
                        placeholder="e.g. RERA/2026/0001"
                        {...enterpriseForm.register('rera_number')}
                      />
                    </FormField>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField
                      label="Business type"
                      htmlFor="enterprise-business-type"
                      error={enterpriseForm.formState.errors.business_type?.message}
                    >
                      <Select id="enterprise-business-type" {...enterpriseForm.register('business_type')}>
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
                      htmlFor="enterprise-size"
                      error={enterpriseForm.formState.errors.company_size?.message}
                    >
                      <Select id="enterprise-size" {...enterpriseForm.register('company_size')}>
                        <option value="">Select company size</option>
                        {COMPANY_SIZES.map((size) => (
                          <option key={size} value={size}>
                            {size}
                          </option>
                        ))}
                      </Select>
                    </FormField>
                  </div>

                  <FormField
                    label="Registered address"
                    htmlFor="enterprise-address"
                    error={enterpriseForm.formState.errors.address?.message}
                  >
                    <Textarea id="enterprise-address" rows={3} {...enterpriseForm.register('address')} />
                  </FormField>

                  <div>
                    <p className="form-label">Working hours</p>
                    <div className="space-y-2">
                      {workingHours.map((entry) => (
                        <div
                          key={entry.day}
                          className="grid grid-cols-[100px_1fr_1fr_auto] items-center gap-2 rounded-lg border border-border px-3 py-2"
                        >
                          <span className="text-xs font-medium text-foreground">{entry.day}</span>
                          <Input
                            type="time"
                            aria-label={`${entry.day} opening time`}
                            value={entry.open || ''}
                            disabled={entry.closed}
                            onChange={(event) => updateWorkingHour(entry.day, { open: event.target.value })}
                            className="py-1.5 text-xs"
                          />
                          <Input
                            type="time"
                            aria-label={`${entry.day} closing time`}
                            value={entry.close || ''}
                            disabled={entry.closed}
                            onChange={(event) => updateWorkingHour(entry.day, { close: event.target.value })}
                            className="py-1.5 text-xs"
                          />
                          <label className="flex items-center gap-2 text-[11px] text-muted-foreground">
                            <input
                              type="checkbox"
                              checked={Boolean(entry.closed)}
                              onChange={(event) => updateWorkingHour(entry.day, { closed: event.target.checked })}
                              className="h-3.5 w-3.5 rounded border-border"
                            />
                            Closed
                          </label>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="space-y-3 rounded-xl border border-border bg-muted/20 p-4">
                    <Switch
                      checked={autoAssign}
                      onChange={setAutoAssign}
                      label="Automatic lead assignment"
                      description="New unassigned leads are distributed round robin within the selected team."
                    />
                    {autoAssign && (
                      <FormField label="Distribution team" htmlFor="distribution-team">
                        <Select
                          id="distribution-team"
                          value={distributionTeam}
                          onChange={(event) => setDistributionTeam(event.target.value)}
                        >
                          <option value="">Select a team</option>
                          {teams.map((team) => (
                            <option key={team.id} value={team.id}>
                              {team.name}
                            </option>
                          ))}
                        </Select>
                      </FormField>
                    )}
                    <div className="h-px bg-border" />
                    <Switch
                      checked={duplicateDetection}
                      onChange={setDuplicateDetection}
                      label="Duplicate lead detection"
                      description="Warn or block duplicate phone numbers or emails when new leads are added."
                    />
                  </div>

                  <FormField
                    label="Billing interval"
                    htmlFor="enterprise-billing-interval"
                    hint="Used when the Rumu team confirms your enterprise plan."
                  >
                    <Select id="enterprise-billing-interval" {...enterpriseForm.register('billing_interval')}>
                      <option value="monthly">Monthly</option>
                      <option value="annual">Annual</option>
                    </Select>
                  </FormField>

                  <Alert variant="info">
                    Plan limits and billing are managed from the Billing page and confirmed by the Rumu
                    platform team.
                  </Alert>

                  <div className="flex justify-end">
                    <Button type="submit" loading={enterpriseMutation.isPending}>
                      Save enterprise settings
                    </Button>
                  </div>
                </form>
              )}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}

const ReadOnlyDetail = ({ label, value }) => (
  <div>
    <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</dt>
    <dd className="mt-1 break-words text-sm text-foreground">{value}</dd>
  </div>
)
