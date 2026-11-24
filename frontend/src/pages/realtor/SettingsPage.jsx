import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline'
import { realtorApi } from '@/api/realtor'
import { useAuthStore } from '@/stores/authStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useThemeStore } from '@/stores/themeStore'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { PageLoader } from '@/components/ui/Spinner'
import { Switch } from '@/components/ui/Switch'
import { Tabs } from '@/components/ui/Tabs'
import { getErrorMessage } from '@/utils/errors'
import SocialAccounts from '@/modules/social/pages/SocialAccounts'

const TABS = [
  { key: 'notifications', label: 'Notifications' },
  { key: 'privacy', label: 'Privacy' },
  { key: 'social', label: 'Connected Accounts (Meta)' },
  { key: 'appearance', label: 'Appearance' },
  { key: 'account', label: 'Account' },
]

export const SettingsPage = ({ hideHeader = false }) => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const setUser = useAuthStore((state) => state.setUser)
  const clearAuth = useAuthStore((state) => state.clear)
  const themeStore = useThemeStore()
  const [tab, setTab] = useState('notifications')
  const [emailNotifications, setEmailNotifications] = useState(true)
  const [marketingEmails, setMarketingEmails] = useState(false)
  const [publicProfile, setPublicProfile] = useState(false)
  const [appearance, setAppearance] = useState({ theme: 'auto', language: 'en', timezone: 'Asia/Kolkata' })
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [deleteForm, setDeleteForm] = useState({ password: '', confirmation: '' })
  const [deleteError, setDeleteError] = useState('')

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'settings'],
    queryFn: async () => {
      const response = await realtorApi.settings.get()
      return response.data?.data
    },
  })

  const user = data?.user
  const options = data?.options || {}

  useEffect(() => {
    if (!user) return
    setEmailNotifications(Boolean(user.email_notifications))
    setMarketingEmails(Boolean(user.marketing_emails))
    setPublicProfile(Boolean(user.public_profile))
    setAppearance({
      theme: user.theme || 'auto',
      language: user.language || 'en',
      timezone: user.timezone || 'Asia/Kolkata',
    })
  }, [user])

  const applyUpdatedUser = (updated) => {
    if (updated) setUser(updated)
    queryClient.invalidateQueries({ queryKey: ['realtor', 'settings'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'profile'] })
    queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
  }

  const notificationsMutation = useMutation({
    mutationFn: (payload) => realtorApi.settings.updateNotifications(payload),
    onSuccess: (response) => {
      toast.success('Notification preferences saved')
      applyUpdatedUser(response.data?.data?.user)
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not save notification settings.')),
  })

  const privacyMutation = useMutation({
    mutationFn: (payload) => realtorApi.settings.updatePrivacy(payload),
    onSuccess: (response) => {
      toast.success('Privacy settings saved')
      applyUpdatedUser(response.data?.data?.user)
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not save privacy settings.')),
  })

  const appearanceMutation = useMutation({
    mutationFn: (payload) => realtorApi.settings.updateAppearance(payload),
    onSuccess: (response) => {
      toast.success('Appearance settings saved')
      const updated = response.data?.data?.user
      applyUpdatedUser(updated)
      themeStore.setTheme(appearance.theme === 'auto' ? 'system' : appearance.theme)
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not save appearance settings.')),
  })

  const deleteMutation = useMutation({
    mutationFn: (payload) => realtorApi.settings.deleteAccount(payload),
    onSuccess: () => {
      clearAuth()
      useNotificationStore.getState().reset()
      queryClient.clear()
      toast.success('Your account has been deleted.')
      navigate('/login', { replace: true })
    },
    onError: (err) => setDeleteError(getErrorMessage(err, 'Could not delete your account.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const submitDelete = (event) => {
    event.preventDefault()
    setDeleteError('')
    if (!deleteForm.password) {
      setDeleteError('Enter your password to continue.')
      return
    }
    if (deleteForm.confirmation !== 'DELETE') {
      setDeleteError('Type DELETE exactly to confirm.')
      return
    }
    deleteMutation.mutate(deleteForm)
  }

  return (
    <div className="mx-auto max-w-3xl">
      {!hideHeader && (
        <PageHeader
          title="Settings"
          description="Control your notifications, privacy, appearance and account lifecycle."
        />
      )}

      <Tabs tabs={TABS} value={tab} onChange={setTab} className="mb-5" />

      {tab === 'notifications' && (
        <Card>
          <CardHeader>
            <CardTitle>Email notifications</CardTitle>
            <CardDescription>Choose which emails you want to receive from Rumu.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-5">
            <Switch
              checked={emailNotifications}
              onChange={setEmailNotifications}
              label="Account & billing emails"
              description="Plan expiry reminders, invoices and important account updates."
            />
            <div className="border-t border-border pt-5">
              <Switch
                checked={marketingEmails}
                onChange={setMarketingEmails}
                label="Product & marketing emails"
                description="Feature announcements, tips and occasional offers."
              />
            </div>
            <div className="flex justify-end border-t border-border pt-4">
              <Button
                onClick={() =>
                  notificationsMutation.mutate({
                    email_notifications: emailNotifications,
                    marketing_emails: marketingEmails,
                  })
                }
                loading={notificationsMutation.isPending}
              >
                Save preferences
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'privacy' && (
        <Card>
          <CardHeader>
            <CardTitle>Privacy</CardTitle>
            <CardDescription>Control who can see your realtor profile.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-5">
            <Switch
              checked={publicProfile}
              onChange={setPublicProfile}
              label="Public profile"
              description="Allow others to view your name, company and bio."
            />
            <div className="flex justify-end border-t border-border pt-4">
              <Button
                onClick={() => privacyMutation.mutate({ public_profile: publicProfile })}
                loading={privacyMutation.isPending}
              >
                Save privacy settings
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'social' && (
        <div className="pt-1">
          <SocialAccounts hideHeader />
        </div>
      )}

      {tab === 'appearance' && (
        <Card>
          <CardHeader>
            <CardTitle>Appearance & locale</CardTitle>
            <CardDescription>Personalise how Rumu looks and which locale it uses.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-5">
              <FormField label="Theme" htmlFor="theme">
                <Select
                  id="theme"
                  value={appearance.theme}
                  onChange={(event) => setAppearance({ ...appearance, theme: event.target.value })}
                >
                  {(options.themes || ['light', 'dark', 'auto']).map((theme) => (
                    <option key={theme} value={theme}>
                      {theme === 'auto' ? 'Match system' : theme === 'dark' ? 'Dark' : 'Light'}
                    </option>
                  ))}
                </Select>
              </FormField>
              <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Language" htmlFor="language">
                  <Select
                    id="language"
                    value={appearance.language}
                    onChange={(event) =>
                      setAppearance({ ...appearance, language: event.target.value })
                    }
                  >
                    {Object.entries(options.languages || { en: 'English' }).map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </Select>
                </FormField>
                <FormField label="Timezone" htmlFor="timezone">
                  <Select
                    id="timezone"
                    value={appearance.timezone}
                    onChange={(event) =>
                      setAppearance({ ...appearance, timezone: event.target.value })
                    }
                  >
                    {Object.entries(options.timezones || { 'Asia/Kolkata': 'India (IST)' }).map(
                      ([value, label]) => (
                        <option key={value} value={value}>
                          {label}
                        </option>
                      ),
                    )}
                  </Select>
                </FormField>
              </div>
              <div className="flex justify-end border-t border-border pt-4">
                <Button
                  onClick={() => appearanceMutation.mutate(appearance)}
                  loading={appearanceMutation.isPending}
                >
                  Save appearance
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {tab === 'account' && (
        <Card className="border-destructive/30">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-destructive">
              <ExclamationTriangleIcon className="h-5 w-5" />
              Delete account
            </CardTitle>
            <CardDescription>
              Permanently delete your account, campaigns, leads and inventory. This cannot be undone.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Alert variant="warning" title="Before you continue">
              Export any data you need first. Active subscriptions are not refunded automatically.
            </Alert>
            <div className="mt-4 flex justify-end">
              <Button variant="danger" onClick={() => setDeleteOpen(true)}>
                Delete my account
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <Modal
        open={deleteOpen}
        onClose={() => {
          if (deleteMutation.isPending) return
          setDeleteOpen(false)
          setDeleteForm({ password: '', confirmation: '' })
          setDeleteError('')
        }}
        size="sm"
        title="Delete your account?"
        description="This permanently removes your workspace and all associated data."
      >
        <form onSubmit={submitDelete} className="space-y-4">
          {deleteError && (
            <Alert variant="danger" onDismiss={() => setDeleteError('')}>
              {deleteError}
            </Alert>
          )}
          <FormField label="Password" htmlFor="delete_password" required>
            <Input
              id="delete_password"
              type="password"
              autoComplete="current-password"
              value={deleteForm.password}
              onChange={(event) =>
                setDeleteForm({ ...deleteForm, password: event.target.value })
              }
              placeholder="Enter your current password"
            />
          </FormField>
          <FormField
            label="Type DELETE to confirm"
            htmlFor="delete_confirmation"
            required
            hint="This action is irreversible."
          >
            <Input
              id="delete_confirmation"
              value={deleteForm.confirmation}
              onChange={(event) =>
                setDeleteForm({ ...deleteForm, confirmation: event.target.value })
              }
              placeholder="DELETE"
              autoComplete="off"
            />
          </FormField>
          <div className="flex justify-end gap-2 border-t border-border pt-4">
            <Button
              variant="outline"
              onClick={() => setDeleteOpen(false)}
              disabled={deleteMutation.isPending}
            >
              Cancel
            </Button>
            <Button type="submit" variant="danger" loading={deleteMutation.isPending}>
              Delete account
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
