import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowPathIcon,
  ArrowUpTrayIcon,
  BoltIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  GlobeAltIcon,
  InformationCircleIcon,
  LinkIcon,
  SignalIcon,
  XCircleIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { Switch } from '@/components/ui/Switch'
import { LEAD_STATUSES, META_EVENTS } from '@/utils/constants'
import { cn } from '@/utils/cn'
import { formatDateTime, formatNumber, timeAgo, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'
import { startMetaConnect } from '@/utils/metaConnect'

const emptyMapping = Object.fromEntries(LEAD_STATUSES.map((status) => [status.value, '']))

const logStyles = {
  success: { wrap: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', icon: CheckCircleIcon },
  error: { wrap: 'bg-red-500/10 text-red-600 dark:text-red-400', icon: XCircleIcon },
  warning: { wrap: 'bg-amber-500/10 text-amber-600 dark:text-amber-400', icon: ExclamationTriangleIcon },
  info: { wrap: 'bg-sky-500/10 text-sky-600 dark:text-sky-400', icon: InformationCircleIcon },
}

export const MetaIntegrationPage = () => {
  const { id } = useParams()
  const queryClient = useQueryClient()
  const [capiForm, setCapiForm] = useState({
    pixel_id: '',
    access_token: '',
    test_event_code: '',
    is_active: true,
    status_mapping: emptyMapping,
  })
  const [capiError, setCapiError] = useState('')
  const [connecting, setConnecting] = useState(false)
  const [disconnecting, setDisconnecting] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'meta-integration', id],
    queryFn: async () => {
      const response = await realtorApi.meta.integration(id)
      return response.data?.data
    },
    enabled: Boolean(id),
  })

  const capiSettings = data?.capi_settings

  useEffect(() => {
    if (!data) return
    setCapiForm({
      pixel_id: capiSettings?.pixel_id || '',
      access_token: '',
      test_event_code: capiSettings?.test_event_code || '',
      is_active: capiSettings ? Boolean(capiSettings.is_active) : true,
      status_mapping: { ...emptyMapping, ...(capiSettings?.status_mapping || {}) },
    })
    setCapiError('')
  }, [data, capiSettings])

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['realtor', 'meta-integration', id] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'meta', 'pages'] })
  }

  const connectFacebook = async () => {
    setConnecting(true)
    try {
      await startMetaConnect(`/realtor/campaigns/${id}/meta`)
    } catch (err) {
      toast.error(getErrorMessage(err, 'Could not start the Facebook connection.'))
      setConnecting(false)
    }
  }

  const disconnectFacebook = useMutation({
    mutationFn: () => realtorApi.meta.disconnect(),
    onSuccess: () => {
      toast.success('Facebook account disconnected')
      setDisconnecting(null)
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not disconnect Facebook.')),
  })

  const disconnectForm = useMutation({
    mutationFn: () => realtorApi.meta.disconnectForm(id),
    onSuccess: () => {
      toast.success('Lead form disconnected')
      setDisconnecting(null)
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not disconnect the lead form.')),
  })

  const saveCapi = useMutation({
    mutationFn: (payload) => realtorApi.meta.saveCapi(id, payload),
    onSuccess: () => {
      toast.success('Conversions API settings saved')
      setCapiError('')
      refresh()
    },
    onError: (err) => setCapiError(getErrorMessage(err, 'Could not save the CAPI settings.')),
  })

  const submitCapi = (event) => {
    event.preventDefault()
    setCapiError('')
    if (!capiForm.pixel_id.trim()) {
      setCapiError('Pixel ID is required.')
      return
    }
    if (!capiSettings?.has_access_token && !capiForm.access_token.trim()) {
      setCapiError('Access token is required for the initial setup.')
      return
    }
    saveCapi.mutate({
      pixel_id: capiForm.pixel_id.trim(),
      access_token: capiForm.access_token.trim() || undefined,
      test_event_code: capiForm.test_event_code.trim() || undefined,
      is_active: capiForm.is_active,
      status_mapping: capiForm.status_mapping,
    })
  }

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const campaign = data.campaign
  const pages = data.pages || []
  const form = data.form
  const stats = data.stats || {}
  const logs = data.logs || []
  const apiHealth = data.api_health
  const lastEvent = data.last_capi_event
  const connectedPages = pages.filter((page) => page.status === 'connected').length

  return (
    <div>
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/realtor/campaigns' },
          { label: campaign.name, to: `/realtor/campaigns/${id}` },
          { label: 'Meta integration' },
        ]}
        title="Meta integration"
        description={`Connect Facebook Pages, sync lead forms and configure the Conversions API for ${campaign.name}.`}
        actions={
          <>
            <Link to={`/realtor/campaigns/${id}`}>
              <Button variant="outline" size="sm">
                Back to campaign
              </Button>
            </Link>
            <Link to={`/realtor/campaigns/${id}/import`}>
              <Button size="sm">
                <ArrowUpTrayIcon className="h-4 w-4" />
                Import leads
              </Button>
            </Link>
          </>
        }
      />

      {!data.meta_configured && (
        <Alert variant="warning" className="mb-5" title="Meta integration is not configured">
          This server is missing Meta app credentials. Ask an administrator to configure the Meta
          app ID and secret before connecting Facebook.
        </Alert>
      )}

      <div className="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Connected Pages"
          value={formatNumber(connectedPages)}
          icon={LinkIcon}
          tone="primary"
          hint={`${formatNumber(pages.length)} total linked`}
        />
        <StatsCard
          label="Leads imported"
          value={formatNumber(stats.total_imported || 0)}
          icon={ArrowPathIcon}
          tone="info"
          hint={stats.last_import_time ? `Last ${timeAgo(stats.last_import_time)}` : 'No imports yet'}
        />
        <StatsCard
          label="Webhook health"
          value={data.webhook_health || 'Inactive'}
          icon={SignalIcon}
          tone={data.webhook_health === 'Active' ? 'success' : 'warning'}
          hint="Lead form delivery"
        />
        <StatsCard
          label="Conversions API"
          value={capiSettings?.is_active ? 'Active' : 'Inactive'}
          icon={BoltIcon}
          tone={capiSettings?.is_active ? 'success' : 'warning'}
          hint={lastEvent ? `Last event ${timeAgo(lastEvent.sent_at)}` : 'No events sent'}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Facebook connection</CardTitle>
                <CardDescription>
                  Pages linked to your account are used to fetch lead forms and sync leads.
                </CardDescription>
              </div>
              {data.facebook_connected && (
                <div className="flex flex-wrap gap-2">
                  <Button size="sm" variant="outline" onClick={connectFacebook} loading={connecting}>
                    <ArrowPathIcon className="h-4 w-4" />
                    Reconnect
                  </Button>
                  <Button
                    size="sm"
                    variant="danger-outline"
                    onClick={() => setDisconnecting('facebook')}
                  >
                    Disconnect
                  </Button>
                </div>
              )}
            </CardHeader>
            <CardContent>
              {!data.facebook_connected ? (
                <EmptyState
                  icon={GlobeAltIcon}
                  title="Facebook is not connected"
                  description="Connect your Facebook account to grant Rumu access to your Pages and lead forms."
                  className="py-8"
                  action={
                    <Button onClick={connectFacebook} loading={connecting} disabled={!data.meta_configured}>
                      <LinkIcon className="h-4 w-4" />
                      Connect Facebook
                    </Button>
                  }
                />
              ) : pages.length === 0 ? (
                <Alert variant="info">
                  Facebook is connected but no Pages were found. Reconnect and grant access to at
                  least one Page.
                </Alert>
              ) : (
                <div className="space-y-3">
                  {apiHealth?.status === 'expired' && (
                    <Alert variant="danger" title="Page access token expired">
                      Reconnect Facebook to refresh the Page token and resume lead syncing.
                    </Alert>
                  )}
                  {pages.map((page) => {
                    const selected = String(page.page_id) === String(data.selected_page_id)
                    return (
                      <div
                        key={page.id}
                        className={cn(
                          'flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5',
                          selected ? 'border-highlight/40 bg-primary/[0.03]' : 'border-border',
                        )}
                      >
                        <div className="flex min-w-0 items-center gap-3">
                          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#1877F2]/10 text-[#1877F2]">
                            <GlobeAltIcon className="h-4 w-4" />
                          </span>
                          <div className="min-w-0">
                            <p className="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-foreground">
                              {page.page_name}
                              {selected && <StatusBadge label="Campaign page" status="new" />}
                            </p>
                            <p className="truncate font-mono text-[11px] text-muted-foreground">
                              Page ID: {page.page_id}
                            </p>
                          </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                          <div className="text-right">
                            <StatusBadge status={page.status} />
                            <p className="mt-1 text-[11px] text-muted-foreground">
                              {page.token_last_synced_at
                                ? `Synced ${timeAgo(page.token_last_synced_at)}`
                                : 'Never synced'}
                            </p>
                          </div>
                        </div>
                      </div>
                    )
                  })}
                  {apiHealth && (
                    <p className="text-xs text-muted-foreground">
                      API health: {titleCase(apiHealth.status)} ·{' '}
                      {formatNumber(apiHealth.consecutive_failures || 0)} consecutive failures
                      {apiHealth.last_success ? ` · last success ${timeAgo(apiHealth.last_success)}` : ''}
                    </p>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Lead form</CardTitle>
                <CardDescription>
                  The Meta Lead Form currently feeding this campaign.
                </CardDescription>
              </div>
              {form && (
                <Button
                  size="sm"
                  variant="danger-outline"
                  onClick={() => setDisconnecting('form')}
                >
                  Disconnect form
                </Button>
              )}
            </CardHeader>
            <CardContent>
              {form ? (
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <p className="text-xs text-muted-foreground">Form name</p>
                    <p className="mt-0.5 text-sm font-semibold text-foreground">{form.form_name}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Form ID</p>
                    <p className="mt-0.5 break-all font-mono text-xs text-foreground">{form.form_id}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Page ID</p>
                    <p className="mt-0.5 break-all font-mono text-xs text-foreground">{form.page_id}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Webhook health</p>
                    <div className="mt-1">
                      <StatusBadge
                        label={data.webhook_health || 'Inactive'}
                        status={data.webhook_health === 'Active' ? 'connected' : 'inactive'}
                      />
                    </div>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Last webhook received</p>
                    <p className="mt-0.5 text-sm text-foreground">
                      {form.last_webhook ? formatDateTime(form.last_webhook) : 'Never'}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Last manual sync</p>
                    <p className="mt-0.5 text-sm text-foreground">
                      {form.last_sync ? formatDateTime(form.last_sync) : 'Never'}
                    </p>
                  </div>
                </div>
              ) : (
                <Alert variant="info">
                  No lead form is connected yet. Sync a form from the{' '}
                  <Link to={`/realtor/campaigns/${id}/import`} className="font-semibold underline">
                    Meta import
                  </Link>{' '}
                  page to link it to this campaign.
                </Alert>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Conversions API</CardTitle>
              <CardDescription>
                Send lead status conversions to Meta so your ad campaigns can optimise.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={submitCapi} className="space-y-5">
                {capiError && (
                  <Alert variant="danger" onDismiss={() => setCapiError('')}>
                    {capiError}
                  </Alert>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                  <FormField label="Pixel ID" htmlFor="pixel_id" required>
                    <Input
                      id="pixel_id"
                      value={capiForm.pixel_id}
                      onChange={(event) =>
                        setCapiForm({ ...capiForm, pixel_id: event.target.value })
                      }
                      placeholder="e.g. 123456789012345"
                    />
                  </FormField>
                  <FormField
                    label="System user access token"
                    htmlFor="capi_token"
                    required={!capiSettings?.has_access_token}
                    hint={
                      capiSettings?.has_access_token
                        ? 'A token is saved. Leave blank to keep it.'
                        : 'Required for the initial setup.'
                    }
                  >
                    <Input
                      id="capi_token"
                      type="password"
                      value={capiForm.access_token}
                      onChange={(event) =>
                        setCapiForm({ ...capiForm, access_token: event.target.value })
                      }
                      placeholder={
                        capiSettings?.has_access_token ? '••••••••••••••••' : 'Enter access token'
                      }
                      autoComplete="off"
                    />
                  </FormField>
                  <FormField label="Test event code" htmlFor="test_event_code" hint="Optional">
                    <Input
                      id="test_event_code"
                      value={capiForm.test_event_code}
                      onChange={(event) =>
                        setCapiForm({ ...capiForm, test_event_code: event.target.value })
                      }
                      placeholder="e.g. TEST12345"
                    />
                  </FormField>
                  <div className="flex items-end pb-1">
                    <Switch
                      checked={capiForm.is_active}
                      onChange={(value) => setCapiForm({ ...capiForm, is_active: value })}
                      label="Enable Conversions API"
                      description="Send events when lead statuses change."
                    />
                  </div>
                </div>

                <div className="border-t border-border pt-5">
                  <h3 className="text-sm font-semibold text-foreground">Lead status mapping</h3>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Map each lead status to a Meta event. Leave a status unmapped to stop sending
                    events for it.
                  </p>
                  <div className="mt-3 grid gap-2.5 sm:grid-cols-2">
                    {LEAD_STATUSES.map((status) => (
                      <div
                        key={status.value}
                        className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 p-2.5"
                      >
                        <span className="text-sm font-medium text-foreground">{status.label}</span>
                        <Select
                          value={capiForm.status_mapping[status.value] || ''}
                          onChange={(event) =>
                            setCapiForm({
                              ...capiForm,
                              status_mapping: {
                                ...capiForm.status_mapping,
                                [status.value]: event.target.value,
                              },
                            })
                          }
                          className="h-8 w-40 text-xs"
                        >
                          <option value="">Ignore</option>
                          {META_EVENTS.map((eventName) => (
                            <option key={eventName} value={eventName}>
                              {eventName}
                            </option>
                          ))}
                        </Select>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex items-center justify-between gap-3 border-t border-border pt-4">
                  <p className="text-xs text-muted-foreground">
                    {lastEvent
                      ? `Last event: ${lastEvent.event_name} (${lastEvent.status}) ${timeAgo(lastEvent.sent_at)}`
                      : 'No Conversions API events sent yet.'}
                  </p>
                  <Button type="submit" loading={saveCapi.isPending}>
                    Save CAPI settings
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Sync activity</CardTitle>
              <CardDescription>Latest Meta sync events for this campaign.</CardDescription>
            </CardHeader>
            <CardContent>
              {logs.length === 0 ? (
                <EmptyState
                  icon={ClockIcon}
                  title="No activity yet"
                  description="Connection and sync events will appear here."
                  className="py-6"
                />
              ) : (
                <ol className="space-y-4">
                  {logs.map((log) => {
                    const style = logStyles[log.type] || logStyles.info
                    const Icon = style.icon
                    return (
                      <li key={log.id} className="flex gap-3">
                        <span
                          className={cn(
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full',
                            style.wrap,
                          )}
                        >
                          <Icon className="h-3.5 w-3.5" />
                        </span>
                        <div className="min-w-0">
                          <p className="text-sm text-foreground">{log.message}</p>
                          <p className="mt-0.5 text-[11px] text-muted-foreground">
                            {titleCase(log.event_type || log.type)} · {timeAgo(log.created_at)}
                          </p>
                        </div>
                      </li>
                    )
                  })}
                </ol>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Quick links</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Link
                to={`/realtor/campaigns/${id}/import`}
                className="flex items-center justify-between rounded-lg border border-border px-3 py-2.5 text-sm font-medium text-foreground transition hover:border-highlight/40 hover:text-highlight"
              >
                Import & sync leads
                <ArrowPathIcon className="h-4 w-4" />
              </Link>
              <Link
                to={`/realtor/campaigns/${id}`}
                className="flex items-center justify-between rounded-lg border border-border px-3 py-2.5 text-sm font-medium text-foreground transition hover:border-highlight/40 hover:text-highlight"
              >
                Open campaign
                <ArrowUpTrayIcon className="h-4 w-4" />
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>

      <ConfirmDialog
        open={Boolean(disconnecting)}
        onClose={() => setDisconnecting(null)}
        onConfirm={() =>
          disconnecting === 'facebook'
            ? disconnectFacebook.mutate()
            : disconnectForm.mutate()
        }
        loading={disconnectFacebook.isPending || disconnectForm.isPending}
        title={
          disconnecting === 'facebook'
            ? 'Disconnect Facebook?'
            : 'Disconnect this lead form?'
        }
        description={
          disconnecting === 'facebook'
            ? 'All Pages will be disconnected and lead syncing will stop until you reconnect.'
            : 'The campaign will stop receiving webhook leads from this form.'
        }
        confirmLabel="Disconnect"
      />
    </div>
  )
}
