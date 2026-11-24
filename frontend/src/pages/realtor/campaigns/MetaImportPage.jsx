import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  GlobeAltIcon,
  LinkIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FileUploader } from '@/components/ui/FileUploader'
import { FormField, Select } from '@/components/ui/Input'
import { PageLoader, Spinner } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import {
  filenameFromResponse,
  formatNumber,
  saveBlob,
} from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'
import { startMetaConnect } from '@/utils/metaConnect'

const TABS = [
  { key: 'csv', label: 'CSV / Excel upload' },
  { key: 'meta-csv', label: 'Meta Ads CSV' },
  { key: 'sync', label: 'Facebook sync' },
]

const extractServerErrors = (err) => {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object') {
    return Object.values(errors).flat().filter(Boolean)
  }
  return [err.response?.data?.message || getErrorMessage(err, 'Import failed.')]
}

const ImportResult = ({ result, onDismiss }) => {
  const { counts, errors, mode } = result
  const totalErrors = errors?.length || 0
  const failed = Number(counts?.failed || 0)
  const hasCounts = Boolean(counts)

  return (
    <Card className="mb-5">
      <CardContent className="pt-5">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <span
              className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
                totalErrors || failed
                  ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                  : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
              }`}
            >
              {totalErrors || failed ? (
                <ExclamationTriangleIcon className="h-4 w-4" />
              ) : (
                <CheckCircleIcon className="h-4 w-4" />
              )}
            </span>
            <div>
              <p className="text-sm font-semibold text-foreground">
                {mode === 'sync' ? 'Sync finished' : 'Import finished'}
              </p>
              <p className="mt-0.5 text-xs text-muted-foreground">
                {hasCounts
                  ? `${formatNumber(counts.imported || 0)} imported · ${formatNumber(
                      counts.skipped || 0,
                    )} skipped · ${formatNumber(failed)} failed`
                  : 'The import could not be completed.'}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onDismiss}
            className="rounded p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            aria-label="Dismiss result"
          >
            <XMarkIcon className="h-4 w-4" />
          </button>
        </div>

        {errors?.length > 0 && (
          <ul className="mt-3 max-h-40 space-y-1.5 overflow-y-auto rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground scrollbar-thin">
            {errors.slice(0, 50).map((message, index) => (
              <li key={index} className="flex gap-2">
                <span className="text-destructive">•</span>
                <span>{typeof message === 'string' ? message : JSON.stringify(message)}</span>
              </li>
            ))}
            {errors.length > 50 && <li>…and {errors.length - 50} more</li>}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

export const MetaImportPage = () => {
  const { id } = useParams()
  const queryClient = useQueryClient()
  const [tab, setTab] = useState('csv')
  const [csvFile, setCsvFile] = useState(null)
  const [metaCsvFile, setMetaCsvFile] = useState(null)
  const [result, setResult] = useState(null)
  const [pageId, setPageId] = useState('')
  const [formId, setFormId] = useState('')
  const [refreshToken, setRefreshToken] = useState(0)
  const [connecting, setConnecting] = useState(false)
  const [formError, setFormError] = useState('')

  const campaignQuery = useQuery({
    queryKey: ['realtor', 'campaign', id],
    queryFn: async () => {
      const response = await realtorApi.campaigns.get(id)
      return response.data?.data
    },
    enabled: Boolean(id),
  })

  const metaPagesQuery = useQuery({
    queryKey: ['realtor', 'meta', 'pages'],
    queryFn: async () => {
      const response = await realtorApi.meta.pages()
      return response.data?.data
    },
  })

  const formsQuery = useQuery({
    queryKey: ['realtor', 'meta', 'forms', id, pageId, refreshToken],
    queryFn: async () => {
      const response = await realtorApi.imports.metaForms(id, {
        page_id: pageId,
        refresh: refreshToken ? 1 : undefined,
      })
      return response.data?.data?.forms || []
    },
    enabled: Boolean(pageId) && tab === 'sync',
    staleTime: 5 * 60 * 1000,
  })

  const metaPages = metaPagesQuery.data?.pages || []
  const facebookConnected = metaPagesQuery.data?.facebook_connected

  useEffect(() => {
    if (!pageId && metaPages.length === 1) {
      setPageId(metaPages[0].page_id)
    }
  }, [metaPages, pageId])

  const refreshLists = () => {
    queryClient.invalidateQueries({ queryKey: ['realtor', 'campaign', id] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'meta-integration', id] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'meta', 'pages'] })
  }

  const csvMutation = useMutation({
    mutationFn: (file) => realtorApi.imports.csv(id, file),
    onSuccess: (response) => {
      setResult({ mode: 'csv', counts: response.data?.data, errors: response.data?.data?.errors || [] })
      setCsvFile(null)
      refreshLists()
      toast.success('CSV import finished')
    },
    onError: (err) => setResult({ mode: 'csv', errors: extractServerErrors(err) }),
  })

  const metaCsvMutation = useMutation({
    mutationFn: (file) => realtorApi.imports.metaCsv(id, file),
    onSuccess: (response) => {
      setResult({
        mode: 'meta-csv',
        counts: response.data?.data,
        errors: response.data?.data?.errors || [],
      })
      setMetaCsvFile(null)
      refreshLists()
      toast.success('Meta Ads import finished')
    },
    onError: (err) => setResult({ mode: 'meta-csv', errors: extractServerErrors(err) }),
  })

  const syncMutation = useMutation({
    mutationFn: () => realtorApi.imports.syncMeta(id, { form_id: formId, page_id: pageId }),
    onSuccess: (response) => {
      setResult({
        mode: 'sync',
        counts: response.data?.data,
        errors: response.data?.data?.errors || [],
      })
      refreshLists()
      queryClient.invalidateQueries({ queryKey: ['realtor', 'meta', 'forms'] })
      toast.success('Facebook leads synced')
    },
    onError: (err) => {
      setResult({ mode: 'sync', errors: extractServerErrors(err) })
    },
  })

  const sampleMutation = useMutation({
    mutationFn: (kind) =>
      kind === 'meta' ? realtorApi.imports.metaSample(id) : realtorApi.imports.sample(id),
    onSuccess: (response, kind) => {
      saveBlob(
        response.data,
        filenameFromResponse(
          response,
          kind === 'meta' ? `meta-ads-sample-campaign-${id}.csv` : `leads-sample-campaign-${id}.csv`,
        ),
      )
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not download the sample CSV.')),
  })

  const handleConnect = async () => {
    setConnecting(true)
    try {
      await startMetaConnect(`/realtor/campaigns/${id}/import`)
    } catch (err) {
      toast.error(getErrorMessage(err, 'Could not start the Facebook connection.'))
      setConnecting(false)
    }
  }

  const submitSync = (event) => {
    event.preventDefault()
    setFormError('')
    if (!pageId) {
      setFormError('Choose a Facebook Page.')
      return
    }
    if (!formId) {
      setFormError('Choose a lead form to sync.')
      return
    }
    syncMutation.mutate()
  }

  if (campaignQuery.isLoading) return <PageLoader />
  if (campaignQuery.isError) {
    return <ErrorState error={campaignQuery.error} onRetry={campaignQuery.refetch} />
  }

  const campaign = campaignQuery.data

  return (
    <div className="mx-auto max-w-4xl">
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/realtor/campaigns' },
          { label: campaign.name, to: `/realtor/campaigns/${id}` },
          { label: 'Import leads' },
        ]}
        title="Import leads"
        description={`Add leads to ${campaign.name} from a CSV file or directly from Facebook.`}
        actions={
          <Link to={`/realtor/campaigns/${id}/meta`}>
            <Button size="sm" variant="outline">
              Meta integration
            </Button>
          </Link>
        }
      />

      <Tabs tabs={TABS} value={tab} onChange={setTab} className="mb-5" />

      {result && <ImportResult result={result} onDismiss={() => setResult(null)} />}

      {tab === 'csv' && (
        <div className="grid gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Upload a CSV or Excel file</CardTitle>
                <CardDescription>
                  Import leads in bulk from a CSV, XLSX or XLS file. Every row is validated before
                  it is added to the campaign.
                </CardDescription>
              </div>
              <Button
                size="sm"
                variant="outline"
                onClick={() => sampleMutation.mutate('leads')}
                loading={sampleMutation.isPending && sampleMutation.variables === 'leads'}
              >
                <ArrowDownTrayIcon className="h-4 w-4" />
                Sample CSV
              </Button>
            </CardHeader>
            <CardContent>
              <Alert variant="info" className="mb-4">
                Required header: <strong>Name, Email, Phone, Company</strong>. CSV, XLSX and XLS
                files are supported; the first row must contain these column names.
              </Alert>
              <FileUploader
                file={csvFile}
                onFileChange={setCsvFile}
                accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                hint="CSV or Excel (.xlsx/.xls) up to 2 MB"
                disabled={csvMutation.isPending}
              />
              <div className="mt-4 flex justify-end">
                <Button
                  onClick={() => csvMutation.mutate(csvFile)}
                  loading={csvMutation.isPending}
                  disabled={!csvFile}
                >
                  Import leads
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Before you import</CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="space-y-3 text-sm text-muted-foreground">
                <li className="flex gap-2.5">
                  <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-highlight">
                    1
                  </span>
                  Download the sample file and keep the header row exactly as provided.
                </li>
                <li className="flex gap-2.5">
                  <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-highlight">
                    2
                  </span>
                  Add one lead per row. Leave optional fields blank when unknown.
                </li>
                <li className="flex gap-2.5">
                  <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-highlight">
                    3
                  </span>
                  Imports respect your plan's per-campaign lead limit.
                </li>
              </ol>
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'meta-csv' && (
        <div className="grid gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Upload a Meta Ads export</CardTitle>
                <CardDescription>
                  Import leads from a CSV exported from Meta Ads Manager or Lead Center.
                </CardDescription>
              </div>
              <Button
                size="sm"
                variant="outline"
                onClick={() => sampleMutation.mutate('meta')}
                loading={sampleMutation.isPending && sampleMutation.variables === 'meta'}
              >
                <ArrowDownTrayIcon className="h-4 w-4" />
                Sample CSV
              </Button>
            </CardHeader>
            <CardContent>
              <Alert variant="info" className="mb-4">
                Expected columns include <strong>id, created_time, full_name, email, phone_number</strong>.
                Additional Meta columns are preserved where possible.
              </Alert>
              <FileUploader
                file={metaCsvFile}
                onFileChange={setMetaCsvFile}
                accept=".csv,text/csv"
                hint="CSV up to 5 MB"
                disabled={metaCsvMutation.isPending}
              />
              <div className="mt-4 flex justify-end">
                <Button
                  onClick={() => metaCsvMutation.mutate(metaCsvFile)}
                  loading={metaCsvMutation.isPending}
                  disabled={!metaCsvFile}
                >
                  Import Meta leads
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Good to know</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm text-muted-foreground">
              <p>
                Meta's lead export includes a unique lead <strong>id</strong>. Re-importing the same
                file will not create duplicates.
              </p>
              <p>
                Leads imported this way use the <strong>Meta ads</strong> source and can still
                trigger Conversions API events.
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {tab === 'sync' && (
        <div className="grid gap-6 lg:grid-cols-3">
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Sync from Facebook</CardTitle>
              <CardDescription>
                Pull leads directly from a Lead Form on one of your connected Pages.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {metaPagesQuery.isLoading ? (
                <div className="flex items-center justify-center py-10">
                  <Spinner className="h-6 w-6" />
                </div>
              ) : !facebookConnected ? (
                <EmptyState
                  icon={GlobeAltIcon}
                  title="Connect Facebook first"
                  description="Link your Facebook account to fetch lead forms and sync leads."
                  className="py-6"
                  action={
                    <Button onClick={handleConnect} loading={connecting}>
                      <LinkIcon className="h-4 w-4" />
                      Connect Facebook
                    </Button>
                  }
                />
              ) : metaPages.length === 0 ? (
                <Alert variant="warning">
                  No Facebook Pages are connected. Reconnect Facebook and grant access to a Page.
                </Alert>
              ) : (
                <form onSubmit={submitSync} className="space-y-4">
                  {formError && (
                    <Alert variant="danger" onDismiss={() => setFormError('')}>
                      {formError}
                    </Alert>
                  )}

                  <FormField label="Facebook Page" htmlFor="meta_page" required>
                    <Select
                      id="meta_page"
                      value={pageId}
                      onChange={(event) => {
                        setPageId(event.target.value)
                        setFormId('')
                        setRefreshToken(0)
                      }}
                    >
                      <option value="">Choose a Page</option>
                      {metaPages.map((page) => (
                        <option key={page.id} value={page.page_id}>
                          {page.page_name} ({page.page_id})
                        </option>
                      ))}
                    </Select>
                  </FormField>

                  <div>
                    <div className="mb-1 flex items-end justify-between gap-2">
                      <label htmlFor="meta_form" className="form-label mb-0">
                        Lead form <span className="text-destructive">*</span>
                      </label>
                      <button
                        type="button"
                        onClick={() => setRefreshToken((value) => value + 1)}
                        disabled={!pageId || formsQuery.isFetching}
                        className="inline-flex items-center gap-1 text-[11px] font-semibold text-highlight transition hover:text-highlight/80 disabled:opacity-50"
                      >
                        <ArrowPathIcon
                          className={`h-3 w-3 ${formsQuery.isFetching ? 'animate-spin' : ''}`}
                        />
                        Refresh forms
                      </button>
                    </div>
                    <Select
                      id="meta_form"
                      value={formId}
                      onChange={(event) => setFormId(event.target.value)}
                      disabled={!pageId || formsQuery.isLoading}
                    >
                      <option value="">
                        {formsQuery.isLoading
                          ? 'Loading forms…'
                          : pageId
                            ? 'Choose a lead form'
                            : 'Choose a Page first'}
                      </option>
                      {pageId && !formsQuery.isLoading && (
                        <option value="all">All forms on this Page</option>
                      )}
                      {(formsQuery.data || []).map((form) => (
                        <option key={form.id} value={form.id}>
                          {form.name} ({form.status || 'active'})
                        </option>
                      ))}
                    </Select>
                    {pageId && !formsQuery.isLoading && formsQuery.data?.length === 0 && (
                      <p className="form-hint">
                        No lead forms were found on this Page. Check that the form exists and the
                        Page token has leads access.
                      </p>
                    )}
                    {formsQuery.isError && (
                      <p className="form-error">{getErrorMessage(formsQuery.error)}</p>
                    )}
                  </div>

                  <div className="flex justify-end border-t border-border pt-4">
                    <Button type="submit" loading={syncMutation.isPending}>
                      <ArrowPathIcon className="h-4 w-4" />
                      Sync leads now
                    </Button>
                  </div>
                </form>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>How sync works</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm text-muted-foreground">
              <p>
                The selected form is linked to this campaign. Future leads can also arrive
                automatically through Meta's webhook.
              </p>
              <p>
                Every sync imports new leads only — existing Meta lead IDs are skipped, so it is safe
                to sync repeatedly.
              </p>
              <Link
                to={`/realtor/campaigns/${id}/meta`}
                className="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
              >
                Manage connection & webhook health
              </Link>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  )
}
