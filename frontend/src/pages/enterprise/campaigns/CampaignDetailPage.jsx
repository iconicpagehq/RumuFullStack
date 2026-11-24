import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
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
  LockClosedIcon,
  SignalIcon,
  XCircleIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { StageBadge, PriorityBadge } from '@/components/enterprise/StageBadge'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { Switch } from '@/components/ui/Switch'
import { useEnterprise } from '@/hooks/useEnterprise'
import { LEAD_STATUSES, META_EVENTS, PAGE_SIZE } from '@/utils/constants'
import { cn } from '@/utils/cn'
import { startEnterpriseMetaConnect } from '@/utils/enterpriseMetaConnect'
import {
  formatCurrency,
  formatDate,
  formatDateTime,
  formatNumber,
  timeAgo,
  titleCase,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const emptyMapping = Object.fromEntries(LEAD_STATUSES.map((status) => [status.value, '']))

const logStyles = {
  success: { wrap: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', icon: CheckCircleIcon },
  error: { wrap: 'bg-red-500/10 text-red-600 dark:text-red-400', icon: XCircleIcon },
  warning: { wrap: 'bg-amber-500/10 text-amber-600 dark:text-amber-400', icon: ExclamationTriangleIcon },
  info: { wrap: 'bg-sky-500/10 text-sky-600 dark:text-sky-400', icon: InformationCircleIcon },
}

export const CampaignDetailPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, canManageMeta, canConnectMeta, features, plan } = useEnterprise()
  const [page, setPage] = useState(1)
  const [capiForm, setCapiForm] = useState({
    pixel_id: '',
    access_token: '',
    test_event_code: '',
    is_active: true,
    status_mapping: emptyMapping,
  })
  const [capiError, setCapiError] = useState('')
  const [mappingPageId, setMappingPageId] = useState('')
  const [mappingFormId, setMappingFormId] = useState('')
  const [connecting, setConnecting] = useState(false)
  const [disconnecting, setDisconnecting] = useState(null)

  const campaignQuery = useQuery({
    queryKey: ['enterprise', 'campaign', id],
    queryFn: async () => {
      const response = await enterpriseApi.campaigns.get(id)
      return response.data?.data
    },
    enabled: Boolean(id),
  })

  const leadsQuery = useQuery({
    queryKey: ['enterprise', 'campaign-leads', id, page],
    queryFn: async () => {
      const response = await enterpriseApi.leads.list({ campaign_id: id, page, per_page: 10 })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    enabled: Boolean(id),
    placeholderData: (previous) => previous,
  })

  const metaQuery = useQuery({
    queryKey: ['enterprise', 'meta-integration', id],
    queryFn: async () => {
      const response = await enterpriseApi.meta.integration(id)
      return response.data?.data
    },
    enabled: Boolean(id) && canManageMeta && features?.meta_integrations !== false,
    retry: false,
  })

  const meta = metaQuery.data
  const featureLocked =
    metaQuery.error?.response?.data?.code === 'feature_locked' ||
    features?.meta_integrations === false
  const pages = meta?.pages || []
  const connectedPages = pages.filter((item) => item.status === 'connected')
  const selectedForm = meta?.form
  const capiSettings = meta?.capi_settings
  const lastEvent = meta?.last_capi_event
  const logs = meta?.logs || []

  useEffect(() => {
    if (!meta) return
    setCapiForm({
      pixel_id: capiSettings?.pixel_id || '',
      access_token: '',
      test_event_code: capiSettings?.test_event_code || '',
      is_active: capiSettings ? Boolean(capiSettings.is_active) : true,
      status_mapping: { ...emptyMapping, ...(capiSettings?.status_mapping || {}) },
    })
    setCapiError('')
  }, [meta, capiSettings])

  useEffect(() => {
    if (!meta) return
    const initialPage =
      meta.selected_page_id ||
      meta.pages?.find((item) => item.status === 'connected')?.page_id ||
      ''
    setMappingPageId(initialPage ? String(initialPage) : '')
    setMappingFormId(meta.form?.form_id || '')
  }, [meta])

  const formsQuery = useQuery({
    queryKey: ['enterprise', 'campaign-meta-forms', id, mappingPageId],
    queryFn: async () => {
      const response = await enterpriseApi.meta.forms(id, { page_id: mappingPageId })
      return response.data?.data?.forms || []
    },
    enabled:
      Boolean(id) &&
      Boolean(mappingPageId) &&
      !selectedForm &&
      canManageMeta &&
      !featureLocked,
    retry: false,
  })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'campaign', id] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'campaign-leads', id] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'campaigns'] })
    queryClient.invalidateQueries({ queryKey: ['enterprise', 'meta-integration', id] })
  }

  const connectFacebook = async () => {
    setConnecting(true)
    try {
      await startEnterpriseMetaConnect(`/enterprise/campaigns/${id}`)
    } catch (err) {
      toast.error(getErrorMessage(err, 'Could not start the Facebook connection.'))
      setConnecting(false)
    }
  }

  const disconnectFacebook = useMutation({
    mutationFn: () => enterpriseApi.meta.disconnect(),
    onSuccess: () => {
      toast.success('Facebook Pages disconnected')
      setDisconnecting(null)
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not disconnect Facebook.')),
  })

  const disconnectForm = useMutation({
    mutationFn: () => enterpriseApi.meta.disconnectForm(id),
    onSuccess: () => {
      toast.success('Lead form disconnected')
      setDisconnecting(null)
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not disconnect the lead form.')),
  })

  const saveMapping = useMutation({
    mutationFn: (payload) => enterpriseApi.meta.saveMapping(id, payload),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Lead form mapped')
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not map the lead form.')),
  })

  const saveCapi = useMutation({
    mutationFn: (payload) => enterpriseApi.meta.saveCapi(id, payload),
    onSuccess: () => {
      toast.success('Conversions API settings saved')
      setCapiError('')
      refresh()
    },
    onError: (err) => setCapiError(getErrorMessage(err, 'Could not save the CAPI settings.')),
  })

  const syncLeads = useMutation({
    mutationFn: () => enterpriseApi.meta.sync(id),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Meta leads synced')
      refresh()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not sync Meta leads.')),
  })

  const submitMapping = (event) => {
    event.preventDefault()
    if (!mappingPageId) {
      toast.error('Select a connected Facebook Page.')
      return
    }
    if (!mappingFormId) {
      toast.error('Select a lead form.')
      return
    }
    const page = pages.find((item) => String(item.page_id) === String(mappingPageId))
    const form = (formsQuery.data || []).find((item) => String(item.id) === String(mappingFormId))
    saveMapping.mutate({
      page_id: mappingPageId,
      form_id: mappingFormId,
      form_name: form?.name || page?.page_name || 'Mapped Form',
    })
  }

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

  if (campaignQuery.isLoading) return <PageLoader />
  if (campaignQuery.isError) {
    return <ErrorState error={campaignQuery.error} onRetry={campaignQuery.refetch} />
  }

  const campaign = campaignQuery.data
  const leads = leadsQuery.data?.rows || []
  const leadsMeta = leadsQuery.data?.meta || {}
  const connectedCount = connectedPages.length
  const selectedPage = pages.find(
    (item) => String(item.page_id) === String(meta?.selected_page_id),
  )

  const leadColumns = [
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="min-w-0">
          <Link
            to={`/enterprise/leads/${lead.id}`}
            className="truncate text-sm font-semibold text-foreground hover:text-highlight"
          >
            {lead.name}
          </Link>
          <p className="truncate text-xs text-muted-foreground">
            {lead.email || lead.phone || 'No contact details'}
          </p>
        </div>
      ),
    },
    {
      key: 'stage',
      header: 'Stage',
      render: (lead) => <StageBadge stage={lead.enterprise_stage} />,
    },
    {
      key: 'priority',
      header: 'Priority',
      render: (lead) => <PriorityBadge priority={lead.priority} />,
    },
    {
      key: 'assignee',
      header: 'Assigned to',
      render: (lead) => (
        <span className="text-xs text-foreground">{lead.assignee?.name || 'Unassigned'}</span>
      ),
    },
    {
      key: 'source',
      header: 'Source',
      render: (lead) => (
        <span className="text-xs font-medium text-muted-foreground">
          {titleCase(lead.source || 'manual')}
        </span>
      ),
    },
    {
      key: 'created_at',
      header: 'Added',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(lead.created_at)}
        </span>
      ),
    },
  ]

  const metaLockedCard = (
    <Card className="p-8 text-center lg:col-span-2">
      <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-300">
        <LockClosedIcon className="h-6 w-6" />
      </span>
      <h2 className="mt-4 text-lg font-semibold text-foreground">Meta Lead Ads is locked</h2>
      <p className="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
        Connecting Facebook Pages, syncing lead forms and the Conversions API are available on
        plans with Meta integrations. Upgrade {plan?.name ? `from ${plan.name} ` : ''}to unlock
        them.
      </p>
      <Link to="/enterprise/billing" className="mt-5 inline-block">
        <Button>View plans</Button>
      </Link>
    </Card>
  )

  return (
    <div>
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/enterprise/campaigns' },
          { label: campaign.name },
        ]}
        title={campaign.name}
        description={campaign.description || 'Campaign performance and Meta lead integration.'}
        actions={
          <Link to="/enterprise/campaigns">
            <Button variant="outline" size="sm">
              Back to campaigns
            </Button>
          </Link>
        }
      />

      <div className="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Leads"
          value={formatNumber(campaign.leads_count || 0)}
          icon={ArrowUpTrayIcon}
          tone="info"
          hint={`${formatNumber(leadsMeta.total || 0)} visible to you`}
        />
        <StatsCard
          label="Won deals"
          value={formatNumber(campaign.won_deals || 0)}
          icon={CheckCircleIcon}
          tone="success"
          hint="Closed-won leads"
        />
        <StatsCard
          label="Won value"
          value={formatCurrency(campaign.deal_value || 0)}
          icon={BoltIcon}
          tone="primary"
          hint="Revenue attributed"
        />
        <StatsCard
          label="Budget"
          value={campaign.budget ? formatCurrency(campaign.budget) : '—'}
          icon={SignalIcon}
          tone="warning"
          hint={campaign.team?.name || 'Company-wide campaign'}
        />
      </div>

      <Card>
        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
          <div>
            <CardTitle>Campaign leads</CardTitle>
            <CardDescription>Leads attributed to this campaign that you can access.</CardDescription>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Badge variant={campaign.meta_connected ? 'success' : 'neutral'} dot>
              {campaign.meta_connected ? 'Meta connected' : 'Meta not connected'}
            </Badge>
            {selectedForm && canManageMeta && (
              <Button
                size="sm"
                variant="outline"
                onClick={() => syncLeads.mutate()}
                loading={syncLeads.isPending}
              >
                <ArrowPathIcon className="h-4 w-4" />
                Sync leads
              </Button>
            )}
          </div>
        </CardHeader>
        <CardContent className="px-0 pb-0">
          <DataTable
            className="rounded-none border-x-0 border-b-0"
            columns={leadColumns}
            rows={leads}
            loading={leadsQuery.isLoading}
            rowKey="id"
            onRowClick={(lead) => navigate(`/enterprise/leads/${lead.id}`)}
            emptyState={
              <EmptyState
                icon={ArrowUpTrayIcon}
                title="No leads yet"
                description={
                  selectedForm
                    ? 'Sync the connected Meta form or add leads manually to see them here.'
                    : 'Connect a Meta lead form or add leads manually to this campaign.'
                }
              />
            }
          />
          <div className="border-t border-border px-4">
            <Pagination
              page={leadsMeta.current_page || 1}
              lastPage={leadsMeta.last_page || 1}
              total={leadsMeta.total || 0}
              perPage={leadsMeta.per_page || PAGE_SIZE}
              onPageChange={setPage}
            />
          </div>
        </CardContent>
      </Card>

      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          {canManageMeta && featureLocked && metaLockedCard}

          {canManageMeta && !featureLocked && metaQuery.isError && (
            <Alert variant="danger" title="Could not load the Meta integration">
              {getErrorMessage(metaQuery.error, 'Try again in a moment.')}
            </Alert>
          )}

          {canManageMeta && !featureLocked && meta && (
            <>
              <Card>
                <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                  <div>
                    <CardTitle>Facebook Pages</CardTitle>
                    <CardDescription>
                      Pages connected to this company workspace feed your lead forms.
                    </CardDescription>
                  </div>
                  {canConnectMeta && (
                    <div className="flex flex-wrap gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={connectFacebook}
                        loading={connecting}
                        disabled={!meta.meta_configured}
                      >
                        <ArrowPathIcon className="h-4 w-4" />
                        {pages.length ? 'Reconnect' : 'Connect Facebook'}
                      </Button>
                      {pages.length > 0 && (
                        <Button
                          size="sm"
                          variant="danger-outline"
                          onClick={() => setDisconnecting('facebook')}
                        >
                          Disconnect
                        </Button>
                      )}
                    </div>
                  )}
                </CardHeader>
                <CardContent className="space-y-3">
                  {!meta.meta_configured && (
                    <Alert variant="warning" title="Meta integration is not configured">
                      This server is missing Meta app credentials. Ask a platform administrator to
                      configure them before connecting Facebook.
                    </Alert>
                  )}

                  {!canConnectMeta && (
                    <Alert variant="info">
                      Only a company admin can connect or disconnect Facebook Pages. You can map the
                      connected Pages below to your team campaigns.
                    </Alert>
                  )}

                  {pages.length === 0 ? (
                    <EmptyState
                      icon={GlobeAltIcon}
                      title="No Facebook Pages connected"
                      description={
                        canConnectMeta
                          ? 'Connect Facebook to grant Rumu access to company Pages and lead forms.'
                          : 'A company admin needs to connect Facebook Pages first.'
                      }
                      className="py-8"
                      action={
                        canConnectMeta ? (
                          <Button
                            onClick={connectFacebook}
                            loading={connecting}
                            disabled={!meta.meta_configured}
                          >
                            <LinkIcon className="h-4 w-4" />
                            Connect Facebook
                          </Button>
                        ) : null
                      }
                    />
                  ) : (
                    <div className="space-y-3">
                      {pages.map((item) => {
                        const selected = String(item.page_id) === String(meta.selected_page_id)
                        return (
                          <div
                            key={item.id}
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
                                  {item.page_name}
                                  {selected && (
                                    <Badge variant="primary" dot>
                                      Campaign page
                                    </Badge>
                                  )}
                                </p>
                                <p className="truncate font-mono text-[11px] text-muted-foreground">
                                  Page ID: {item.page_id}
                                </p>
                              </div>
                            </div>
                            <div className="text-right">
                              <StatusBadge status={item.status} />
                              <p className="mt-1 text-[11px] text-muted-foreground">
                                {item.token_last_synced_at
                                  ? `Synced ${timeAgo(item.token_last_synced_at)}`
                                  : 'Never synced'}
                              </p>
                            </div>
                          </div>
                        )
                      })}
                      {!selectedPage && (
                        <p className="text-xs text-muted-foreground">
                          {formatNumber(connectedCount)} connected Page
                          {connectedCount === 1 ? '' : 's'} available for mapping.
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
                      The Meta lead form feeding this campaign, or map a new one.
                    </CardDescription>
                  </div>
                  {selectedForm && canManageMeta && (
                    <div className="flex flex-wrap gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => syncLeads.mutate()}
                        loading={syncLeads.isPending}
                      >
                        <ArrowPathIcon className="h-4 w-4" />
                        Sync now
                      </Button>
                      <Button
                        size="sm"
                        variant="danger-outline"
                        onClick={() => setDisconnecting('form')}
                      >
                        Disconnect form
                      </Button>
                    </div>
                  )}
                </CardHeader>
                <CardContent>
                  {selectedForm ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <p className="text-xs text-muted-foreground">Form name</p>
                        <p className="mt-0.5 text-sm font-semibold text-foreground">
                          {selectedForm.form_name}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Form ID</p>
                        <p className="mt-0.5 break-all font-mono text-xs text-foreground">
                          {selectedForm.form_id}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Page ID</p>
                        <p className="mt-0.5 break-all font-mono text-xs text-foreground">
                          {selectedForm.page_id}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Last webhook received</p>
                        <p className="mt-0.5 text-sm text-foreground">
                          {selectedForm.last_webhook
                            ? formatDateTime(selectedForm.last_webhook)
                            : 'Never'}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Last manual sync</p>
                        <p className="mt-0.5 text-sm text-foreground">
                          {selectedForm.last_sync
                            ? formatDateTime(selectedForm.last_sync)
                            : 'Never'}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Leads imported</p>
                        <p className="mt-0.5 text-sm text-foreground">
                          {formatNumber(meta.stats?.total_imported || 0)}
                          {meta.stats?.last_import_time
                            ? ` · last ${timeAgo(meta.stats.last_import_time)}`
                            : ''}
                        </p>
                      </div>
                    </div>
                  ) : connectedPages.length === 0 ? (
                    <Alert variant="info">
                      Connect a Facebook Page above before mapping a lead form to this campaign.
                    </Alert>
                  ) : (
                    <form onSubmit={submitMapping} className="space-y-4">
                      {formsQuery.isError && (
                        <Alert variant="danger">
                          {getErrorMessage(formsQuery.error, 'Could not load lead forms.')}
                        </Alert>
                      )}
                      <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Facebook Page" htmlFor="mapping_page" required>
                          <Select
                            id="mapping_page"
                            value={mappingPageId}
                            onChange={(event) => {
                              setMappingPageId(event.target.value)
                              setMappingFormId('')
                            }}
                          >
                            <option value="">Select a Page</option>
                            {connectedPages.map((item) => (
                              <option key={item.id} value={item.page_id}>
                                {item.page_name}
                              </option>
                            ))}
                          </Select>
                        </FormField>
                        <FormField
                          label="Lead form"
                          htmlFor="mapping_form"
                          required
                          hint={
                            formsQuery.isLoading
                              ? 'Loading forms from Meta…'
                              : 'Only forms from the selected Page are listed.'
                          }
                        >
                          <Select
                            id="mapping_form"
                            value={mappingFormId}
                            onChange={(event) => setMappingFormId(event.target.value)}
                            disabled={!mappingPageId || formsQuery.isLoading}
                          >
                            <option value="">
                              {formsQuery.isLoading ? 'Loading forms…' : 'Select a form'}
                            </option>
                            {(formsQuery.data || []).map((item) => (
                              <option key={item.id} value={item.id}>
                                {item.name}
                              </option>
                            ))}
                          </Select>
                        </FormField>
                      </div>
                      <div className="flex items-center justify-between gap-3 border-t border-border pt-4">
                        <p className="text-xs text-muted-foreground">
                          Webhook leads from this form will be imported into this campaign.
                        </p>
                        <Button
                          type="submit"
                          loading={saveMapping.isPending}
                          disabled={!mappingPageId || !mappingFormId}
                        >
                          Map form
                        </Button>
                      </div>
                    </form>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Conversions API</CardTitle>
                  <CardDescription>
                    Send lead stage conversions to Meta so your ad campaigns can optimise.
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
                            capiSettings?.has_access_token
                              ? '••••••••••••••••'
                              : 'Enter access token'
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
                          description="Send events when lead stages change."
                        />
                      </div>
                    </div>

                    <div className="border-t border-border pt-5">
                      <h3 className="text-sm font-semibold text-foreground">Lead stage mapping</h3>
                      <p className="mt-1 text-xs text-muted-foreground">
                        Map each lead stage to a Meta event. Leave a stage unmapped to stop sending
                        events for it.
                      </p>
                      <div className="mt-3 grid gap-2.5 sm:grid-cols-2">
                        {LEAD_STATUSES.map((status) => (
                          <div
                            key={status.value}
                            className="flex items-center justify-between gap-3 rounded-lg border border-border bg-muted/30 p-2.5"
                          >
                            <span className="text-sm font-medium text-foreground">
                              {status.label}
                            </span>
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
                          ? `Last event: ${lastEvent.event_name} (${lastEvent.status}) ${timeAgo(
                              lastEvent.sent_at,
                            )}`
                          : 'No Conversions API events sent yet.'}
                      </p>
                      <Button type="submit" loading={saveCapi.isPending}>
                        Save CAPI settings
                      </Button>
                    </div>
                  </form>
                </CardContent>
              </Card>
            </>
          )}

          {!canManageMeta && (
            <Alert variant="info">
              Meta Lead Ads is managed by company admins and managers. Ask your manager to connect
              a form to this campaign.
            </Alert>
          )}
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Campaign details</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Status</span>
                <StatusBadge status={campaign.status} />
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Team</span>
                <span className="font-medium text-foreground">
                  {campaign.team?.name || 'Company-wide'}
                </span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Start date</span>
                <span className="font-medium text-foreground">{formatDate(campaign.start_date)}</span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">End date</span>
                <span className="font-medium text-foreground">{formatDate(campaign.end_date)}</span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Created by</span>
                <span className="font-medium text-foreground">
                  {campaign.creator?.name || '—'}
                </span>
              </div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-muted-foreground">Created</span>
                <span className="font-medium text-foreground">
                  {formatDateTime(campaign.created_at)}
                </span>
              </div>
            </CardContent>
          </Card>

          {canManageMeta && !featureLocked && meta && (
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
          )}
        </div>
      </div>

      <ConfirmDialog
        open={Boolean(disconnecting)}
        onClose={() => setDisconnecting(null)}
        onConfirm={() =>
          disconnecting === 'facebook' ? disconnectFacebook.mutate() : disconnectForm.mutate()
        }
        loading={disconnectFacebook.isPending || disconnectForm.isPending}
        title={
          disconnecting === 'facebook'
            ? 'Disconnect Facebook Pages?'
            : 'Disconnect this lead form?'
        }
        description={
          disconnecting === 'facebook'
            ? 'All company Pages will be disconnected and lead syncing will stop until an admin reconnects.'
            : 'The campaign will stop receiving webhook leads from this form.'
        }
        confirmLabel="Disconnect"
      />

      {isAdmin && (
        <p className="mt-6 text-xs text-muted-foreground">
          Campaign admins can edit budgets and team assignment from the campaigns list.
        </p>
      )}
    </div>
  )
}
