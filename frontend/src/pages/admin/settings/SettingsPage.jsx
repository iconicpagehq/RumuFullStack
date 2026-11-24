import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ComputerDesktopIcon, MoonIcon, SunIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { FormField, Input } from '@/components/ui/Input'
import { Switch } from '@/components/ui/Switch'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { PageLoader } from '@/components/ui/Spinner'
import { useAuth } from '@/hooks/useAuth'
import { useThemeStore } from '@/stores/themeStore'
import { cn } from '@/utils/cn'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'

const themeOptions = [
  { value: 'light', label: 'Light', icon: SunIcon },
  { value: 'dark', label: 'Dark', icon: MoonIcon },
  { value: 'auto', label: 'System', icon: ComputerDesktopIcon },
]

export const SettingsPage = () => {
  const queryClient = useQueryClient()
  const { user, setUser } = useAuth()
  const setTheme = useThemeStore((state) => state.setTheme)
  const [form, setForm] = useState({
    site_name: '',
    support_email: '',
    support_phone: '',
    default_timezone: '',
    maintenance_mode: false,
  })
  const [errors, setErrors] = useState({})
  const [theme, setThemeValue] = useState('auto')

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'settings'],
    queryFn: async () => {
      const response = await adminApi.settings.get()
      return response.data?.data
    },
  })

  useEffect(() => {
    if (!data?.general) return
    setForm({
      site_name: data.general.site_name || '',
      support_email: data.general.support_email || '',
      support_phone: data.general.support_phone || '',
      default_timezone: data.general.default_timezone || '',
      maintenance_mode: Boolean(data.general.maintenance_mode),
    })
    setThemeValue(data.appearance?.theme || 'auto')
  }, [data])

  const set = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const generalMutation = useMutation({
    mutationFn: () => adminApi.settings.update(form),
    onSuccess: () => {
      toast.success('Settings saved')
      setErrors({})
      queryClient.invalidateQueries({ queryKey: ['admin', 'settings'] })
    },
    onError: (err) => {
      setErrors(serverFieldErrors(err))
      toast.error(getErrorMessage(err, 'Could not save settings.'))
    },
  })

  const appearanceMutation = useMutation({
    mutationFn: (value) => adminApi.settings.updateAppearance({ theme: value }),
    onSuccess: (response, value) => {
      toast.success('Appearance updated')
      setThemeValue(value)
      setTheme(value === 'auto' ? 'system' : value)
      if (user) setUser({ ...user, theme: value })
      queryClient.invalidateQueries({ queryKey: ['admin', 'settings'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update appearance.')),
  })

  const capiMutation = useMutation({
    mutationFn: (enabled) => adminApi.settings.updateMetaCapi({ meta_capi_enabled: enabled }),
    onSuccess: (_, enabled) => {
      toast.success(enabled ? 'Meta CAPI enabled' : 'Meta CAPI disabled')
      queryClient.invalidateQueries({ queryKey: ['admin', 'settings'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update Meta CAPI settings.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const metaCapi = data?.meta_capi || {}
  const system = data?.system || {}

  return (
    <div>
      <PageHeader title="Settings" description="Platform configuration and system information." />

      <div className="grid gap-5 lg:grid-cols-3">
        <div className="space-y-5 lg:col-span-2">
          <Card>
            <CardHeader>
              <div>
                <CardTitle>General</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">Public site details and support contact.</p>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Site name" htmlFor="site-name" error={errors.site_name}>
                  <Input
                    id="site-name"
                    value={form.site_name}
                    onChange={(event) => set('site_name', event.target.value)}
                    invalid={Boolean(errors.site_name)}
                  />
                </FormField>
                <FormField label="Support email" htmlFor="support-email" error={errors.support_email}>
                  <Input
                    id="support-email"
                    type="email"
                    value={form.support_email}
                    onChange={(event) => set('support_email', event.target.value)}
                    invalid={Boolean(errors.support_email)}
                  />
                </FormField>
                <FormField label="Support phone" htmlFor="support-phone" error={errors.support_phone}>
                  <Input
                    id="support-phone"
                    value={form.support_phone}
                    onChange={(event) => set('support_phone', event.target.value)}
                    invalid={Boolean(errors.support_phone)}
                  />
                </FormField>
                <FormField
                  label="Default timezone"
                  htmlFor="default-timezone"
                  hint="e.g. Asia/Kolkata"
                  error={errors.default_timezone}
                >
                  <Input
                    id="default-timezone"
                    value={form.default_timezone}
                    onChange={(event) => set('default_timezone', event.target.value)}
                    invalid={Boolean(errors.default_timezone)}
                  />
                </FormField>
              </div>

              <div className="rounded-lg border border-border bg-muted/30 p-3">
                <Switch
                  checked={form.maintenance_mode}
                  onChange={(value) => set('maintenance_mode', value)}
                  label="Maintenance mode"
                  description="Temporarily restrict access while performing maintenance."
                />
              </div>

              <div className="flex justify-end">
                <Button onClick={() => generalMutation.mutate()} loading={generalMutation.isPending}>
                  Save settings
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div>
                <CardTitle>Appearance</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">Your preferred admin theme.</p>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-3 sm:grid-cols-3">
                {themeOptions.map((option) => {
                  const Icon = option.icon
                  const active = theme === option.value
                  return (
                    <button
                      key={option.value}
                      type="button"
                      onClick={() => appearanceMutation.mutate(option.value)}
                      disabled={appearanceMutation.isPending}
                      className={cn(
                        'flex flex-col items-center gap-2 rounded-xl border p-4 text-sm font-medium transition',
                        active
                          ? 'border-highlight bg-primary/5 text-highlight'
                          : 'border-border bg-card text-muted-foreground hover:bg-muted',
                      )}
                    >
                      <Icon className="h-5 w-5" />
                      {option.label}
                    </button>
                  )
                })}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div>
                <CardTitle>Meta Conversions API</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  Server-side event tracking for realtor campaigns.
                </p>
              </div>
              <Badge variant={metaCapi.configured ? 'success' : 'warning'}>
                {metaCapi.configured ? 'Configured' : 'Not configured'}
              </Badge>
            </CardHeader>
            <CardContent className="space-y-4">
              {!metaCapi.configured && (
                <Alert variant="warning" title="Gateway credentials missing">
                  Add the Meta CAPI access token to the backend environment before enabling server-side events.
                </Alert>
              )}
              <Switch
                checked={Boolean(metaCapi.enabled)}
                onChange={(value) => capiMutation.mutate(value)}
                disabled={capiMutation.isPending}
                label="Enable Meta Conversions API"
                description="When enabled, supported events are sent to Meta for connected pixels."
              />
            </CardContent>
          </Card>
        </div>

        <Card className="h-fit">
          <CardHeader>
            <CardTitle>System</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Application</span>
              <span className="font-semibold text-foreground">{system.app_name || '—'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Laravel</span>
              <span className="font-semibold text-foreground">{system.laravel_version || '—'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">PHP</span>
              <span className="font-semibold text-foreground">{system.php_version || '—'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Timezone</span>
              <span className="font-semibold text-foreground">{system.timezone || '—'}</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
