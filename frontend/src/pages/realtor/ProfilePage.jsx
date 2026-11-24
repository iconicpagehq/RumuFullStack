import { useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import toast from 'react-hot-toast'
import {
  BellIcon,
  CameraIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  EnvelopeIcon,
  IdentificationIcon,
  SparklesIcon,
  UserCircleIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { useAuthStore } from '@/stores/authStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'
import { FormField, Input, Textarea } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatDate } from '@/utils/formatters'
import { passwordSchema, profileSchema } from '@/utils/validators'
import { PlansPage } from './PlansPage'
import { BillingPage } from './billing/BillingPage'
import { BillingNotificationsPage } from './billing/BillingNotificationsPage'
import { SettingsPage } from './SettingsPage'
import SocialAccounts from '@/modules/social/pages/SocialAccounts'

export const ProfilePage = () => {
  const [searchParams, setSearchParams] = useSearchParams()
  const activeTab = searchParams.get('tab') || 'profile'
  const billingUnread = useNotificationStore((state) => state.billingUnread)
  const queryClient = useQueryClient()
  const setUser = useAuthStore((state) => state.setUser)
  const fileInputRef = useRef(null)
  const [generalError, setGeneralError] = useState('')
  const [passwordError, setPasswordError] = useState('')

  const ACCOUNT_TABS = [
    { key: 'profile', label: 'Personal details' },
    { key: 'plans', label: 'Plans & upgrade' },
    { key: 'billing', label: 'Billing & invoices' },
    {
      key: 'notifications',
      label: 'Notifications',
      count: billingUnread > 0 ? billingUnread : undefined,
    },
    { key: 'social', label: 'Connected Accounts' },
    { key: 'settings', label: 'Settings' },
  ]

  const handleTabChange = (key) => {
    setSearchParams(key === 'profile' ? {} : { tab: key }, { replace: true })
  }

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'profile'],
    queryFn: async () => {
      const response = await realtorApi.profile.get()
      return response.data?.data
    },
  })

  const profile = data?.user

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(profileSchema),
    defaultValues: { name: '', email: '', phone: '', company: '', bio: '' },
  })

  const {
    register: registerPassword,
    handleSubmit: handlePasswordSubmit,
    reset: resetPassword,
    setError: setPasswordFieldError,
    formState: { errors: passwordErrors },
  } = useForm({
    resolver: zodResolver(passwordSchema),
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  })

  useEffect(() => {
    if (!profile) return
    reset({
      name: profile.name || '',
      email: profile.email || '',
      phone: profile.phone || '',
      company: profile.company || '',
      bio: profile.bio || '',
    })
  }, [profile, reset])

  const profileMutation = useMutation({
    mutationFn: (values) => realtorApi.profile.update(values),
    onSuccess: (response) => {
      toast.success('Profile updated')
      const updated = response.data?.data
      if (updated) setUser(updated)
      queryClient.invalidateQueries({ queryKey: ['realtor', 'profile'] })
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
    onError: (err) => {
      if (!applyServerErrors(err, setError, ['name', 'email', 'phone', 'company', 'bio'])) {
        setGeneralError(getErrorMessage(err, 'Could not update your profile.'))
      }
    },
  })

  const passwordMutation = useMutation({
    mutationFn: (values) => realtorApi.profile.updatePassword(values),
    onSuccess: () => {
      toast.success('Password updated')
      resetPassword()
    },
    onError: (err) => {
      if (
        !applyServerErrors(err, setPasswordFieldError, [
          'current_password',
          'password',
          'password_confirmation',
        ])
      ) {
        setPasswordError(getErrorMessage(err, 'Could not update your password.'))
      }
    },
  })

  const avatarMutation = useMutation({
    mutationFn: (file) => realtorApi.profile.uploadAvatar(file),
    onSuccess: (response) => {
      toast.success('Avatar updated')
      const updated = response.data?.data
      if (updated) setUser(updated)
      queryClient.invalidateQueries({ queryKey: ['realtor', 'profile'] })
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not upload the avatar.')),
  })

  const handleAvatarChange = (event) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return
    if (!file.type.startsWith('image/')) {
      toast.error('Choose an image file.')
      return
    }
    if (file.size > 2 * 1024 * 1024) {
      toast.error('Avatar must be 2 MB or smaller.')
      return
    }
    avatarMutation.mutate(file)
  }

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div>
      <PageHeader
        title="Account & profile"
        description="Manage your personal details, subscription plans, billing, notifications and settings."
      />

      <Tabs
        tabs={ACCOUNT_TABS}
        value={activeTab}
        onChange={handleTabChange}
        className="mb-6"
      />

      {activeTab === 'profile' && (
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="order-2 space-y-6 lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Personal details</CardTitle>
              <CardDescription>This information appears across your workspace.</CardDescription>
            </CardHeader>
            <CardContent>
              {generalError && (
                <Alert variant="danger" className="mb-5" onDismiss={() => setGeneralError('')}>
                  {generalError}
                </Alert>
              )}
              <form
                onSubmit={handleSubmit((values) => {
                  setGeneralError('')
                  profileMutation.mutate(values)
                })}
                className="space-y-5"
                noValidate
              >
                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField label="Full name" htmlFor="profile_name" required error={errors.name?.message}>
                    <Input
                      id="profile_name"
                      invalid={Boolean(errors.name)}
                      {...register('name')}
                    />
                  </FormField>
                  <FormField label="Email address" htmlFor="profile_email" required error={errors.email?.message}>
                    <Input
                      id="profile_email"
                      type="email"
                      invalid={Boolean(errors.email)}
                      {...register('email')}
                    />
                  </FormField>
                  <FormField label="Phone" htmlFor="profile_phone" error={errors.phone?.message}>
                    <Input
                      id="profile_phone"
                      placeholder="+91 98765 43210"
                      invalid={Boolean(errors.phone)}
                      {...register('phone')}
                    />
                  </FormField>
                  <FormField label="Company" htmlFor="profile_company" error={errors.company?.message}>
                    <Input
                      id="profile_company"
                      placeholder="Agency or company name"
                      invalid={Boolean(errors.company)}
                      {...register('company')}
                    />
                  </FormField>
                </div>
                <FormField
                  label="Bio"
                  htmlFor="profile_bio"
                  error={errors.bio?.message}
                  hint="A short introduction visible to your team."
                >
                  <Textarea
                    id="profile_bio"
                    rows={4}
                    placeholder="Tell us a little about yourself…"
                    invalid={Boolean(errors.bio)}
                    {...register('bio')}
                  />
                </FormField>
                <div className="flex justify-end border-t border-border pt-4">
                  <Button type="submit" loading={profileMutation.isPending}>
                    Save changes
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Change password</CardTitle>
              <CardDescription>Use at least 8 characters for your new password.</CardDescription>
            </CardHeader>
            <CardContent>
              {passwordError && (
                <Alert variant="danger" className="mb-5" onDismiss={() => setPasswordError('')}>
                  {passwordError}
                </Alert>
              )}
              <form
                onSubmit={handlePasswordSubmit((values) => {
                  setPasswordError('')
                  passwordMutation.mutate(values)
                })}
                className="space-y-5"
                noValidate
              >
                <FormField
                  label="Current password"
                  htmlFor="current_password"
                  required
                  error={passwordErrors.current_password?.message}
                >
                  <Input
                    id="current_password"
                    type="password"
                    autoComplete="current-password"
                    invalid={Boolean(passwordErrors.current_password)}
                    {...registerPassword('current_password')}
                  />
                </FormField>
                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField
                    label="New password"
                    htmlFor="new_password"
                    required
                    error={passwordErrors.password?.message}
                  >
                    <Input
                      id="new_password"
                      type="password"
                      autoComplete="new-password"
                      invalid={Boolean(passwordErrors.password)}
                      {...registerPassword('password')}
                    />
                  </FormField>
                  <FormField
                    label="Confirm new password"
                    htmlFor="confirm_password"
                    required
                    error={passwordErrors.password_confirmation?.message}
                  >
                    <Input
                      id="confirm_password"
                      type="password"
                      autoComplete="new-password"
                      invalid={Boolean(passwordErrors.password_confirmation)}
                      {...registerPassword('password_confirmation')}
                    />
                  </FormField>
                </div>
                <div className="flex justify-end border-t border-border pt-4">
                  <Button type="submit" variant="outline" loading={passwordMutation.isPending}>
                    Update password
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>

        <div className="order-1 lg:order-3">
          <Card>
            <CardContent className="flex flex-col items-center pt-6 text-center">
              <div className="relative">
                <Avatar src={profile?.avatar_url} name={profile?.name || ''} size="xl" />
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  disabled={avatarMutation.isPending}
                  className="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full border-2 border-card bg-primary text-primary-foreground shadow-sm transition hover:bg-primary-600 disabled:opacity-60"
                  aria-label="Change avatar"
                >
                  <CameraIcon className="h-4 w-4" />
                </button>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  className="hidden"
                  onChange={handleAvatarChange}
                />
              </div>
              <h2 className="mt-3 text-base font-semibold text-foreground">{profile?.name}</h2>
              <p className="mt-0.5 text-sm text-muted-foreground">{profile?.email}</p>
              <div className="mt-2 flex flex-wrap justify-center gap-1.5">
                {profile?.plan?.name && <Badge variant="primary">{profile.plan.name} plan</Badge>}
                <Badge variant="outline">Realtor</Badge>
              </div>
              <p className="mt-3 text-[11px] text-muted-foreground">
                {avatarMutation.isPending ? 'Uploading avatar…' : 'JPG, PNG, GIF or WebP up to 2 MB'}
              </p>
            </CardContent>
          </Card>
        </div>
        {/* 
        <div className="order-3 lg:order-4">
          <Card>
            <CardHeader>
              <CardTitle>Account</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3.5 text-sm">
              <div className="flex items-center justify-between gap-3">
                <span className="inline-flex items-center gap-2 text-muted-foreground">
                  <EnvelopeIcon className="h-4 w-4" />
                  Email
                </span>
                <span className="truncate font-medium text-foreground">{profile?.email}</span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="inline-flex items-center gap-2 text-muted-foreground">
                  <IdentificationIcon className="h-4 w-4" />
                  Referral code
                </span>
                <span className="font-mono text-xs font-semibold text-foreground">
                  {profile?.referral_code || '—'}
                </span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Member since</span>
                <span className="font-medium text-foreground">{formatDate(profile?.created_at)}</span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Google</span>
                {profile?.google_linked ? (
                  <Badge variant="success">Linked</Badge>
                ) : (
                  <Badge variant="neutral">Not linked</Badge>
                )}
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Facebook</span>
                {profile?.facebook_linked ? (
                  <Badge variant="success">Linked</Badge>
                ) : (
                  <Badge variant="neutral">Not linked</Badge>
                )}
              </div>
            </CardContent>
          </Card>
        </div> */}
      </div>
      )}

      {activeTab === 'plans' && <PlansPage hideHeader />}
      {activeTab === 'billing' && <BillingPage hideHeader />}
      {activeTab === 'notifications' && <BillingNotificationsPage hideHeader />}
      {activeTab === 'social' && <SocialAccounts hideHeader />}
      {activeTab === 'settings' && <SettingsPage hideHeader />}
    </div>
  )
}
