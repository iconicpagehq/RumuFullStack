import { useEffect, useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/20/solid'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FileUploader } from '@/components/ui/FileUploader'
import { FormField, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { filenameFromResponse, formatNumber, saveBlob } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const extractServerErrors = (err) => {
  const errors = err.response?.data?.errors
  if (errors && typeof errors === 'object' && !Array.isArray(errors)) {
    return Object.values(errors).flat().filter(Boolean)
  }
  return [err.response?.data?.message || getErrorMessage(err, 'Import failed.')]
}

const isImportResult = (payload) =>
  payload && typeof payload === 'object' && !Array.isArray(payload) && 'imported' in payload

const ImportSummary = ({ result, onDismiss }) => {
  const hasIssues = (result.failed || 0) > 0 || (result.errors?.length || 0) > 0

  return (
    <div className="rounded-xl border border-border bg-muted/40 p-3.5">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <span
            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
              hasIssues
                ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
            }`}
          >
            {hasIssues ? (
              <ExclamationTriangleIcon className="h-4 w-4" />
            ) : (
              <CheckCircleIcon className="h-4 w-4" />
            )}
          </span>
          <div>
            <p className="text-sm font-semibold text-foreground">Import finished</p>
            <p className="mt-0.5 text-xs text-muted-foreground">
              {formatNumber(result.imported || 0)} imported · {formatNumber(result.skipped || 0)} skipped ·{' '}
              {formatNumber(result.failed || 0)} failed
            </p>
          </div>
        </div>
        {onDismiss && (
          <button
            type="button"
            onClick={onDismiss}
            className="text-xs font-medium text-muted-foreground transition hover:text-foreground"
          >
            Dismiss
          </button>
        )}
      </div>

      {result.errors?.length > 0 && (
        <ul className="mt-3 max-h-36 space-y-1.5 overflow-y-auto rounded-lg bg-card p-3 text-xs text-muted-foreground scrollbar-thin">
          {result.errors.slice(0, 50).map((message, index) => (
            <li key={index} className="flex gap-2">
              <span className="text-destructive">•</span>
              <span>{typeof message === 'string' ? message : JSON.stringify(message)}</span>
            </li>
          ))}
          {result.errors.length > 50 && <li>…and {result.errors.length - 50} more</li>}
        </ul>
      )}

      {result.warnings?.length > 0 && (
        <details className="mt-2 text-xs text-muted-foreground">
          <summary className="cursor-pointer font-medium text-foreground">
            {result.warnings.length} duplicate row(s) skipped
          </summary>
          <ul className="mt-2 max-h-28 space-y-1 overflow-y-auto rounded-lg bg-card p-3 scrollbar-thin">
            {result.warnings.map((message, index) => (
              <li key={index} className="flex gap-2">
                <span className="text-amber-500">•</span>
                <span>{message}</span>
              </li>
            ))}
          </ul>
        </details>
      )}
    </div>
  )
}

/**
 * CSV bulk import dialog shared by the Realtor and Enterprise lead pages.
 * The owning page supplies the API calls so each edition can target its own
 * import endpoint while the upload UX stays consistent.
 */
export const LeadImportModal = ({
  open,
  onClose,
  campaigns = [],
  teams = [],
  campaignRequired = false,
  teamRequired = false,
  defaultCampaignId = '',
  description = 'Upload a CSV or Excel file to add many leads at once.',
  onImport,
  onSample,
  onImported,
}) => {
  const [file, setFile] = useState(null)
  const [campaignId, setCampaignId] = useState('')
  const [teamId, setTeamId] = useState('')
  const [result, setResult] = useState(null)
  const [formError, setFormError] = useState('')

  useEffect(() => {
    if (open) {
      setCampaignId(defaultCampaignId ? String(defaultCampaignId) : '')
      setTeamId('')
      setFile(null)
      setResult(null)
      setFormError('')
    }
  }, [open, defaultCampaignId])

  const importMutation = useMutation({
    mutationFn: () =>
      onImport({
        file,
        campaignId: campaignId || null,
        teamId: teamId || null,
      }),
    onSuccess: (response) => {
      const payload = response.data?.data || {}
      setResult({
        imported: payload.imported || 0,
        skipped: payload.skipped || 0,
        failed: payload.failed || 0,
        errors: payload.errors || [],
        warnings: payload.warnings || [],
        stopped: Boolean(payload.stopped),
        message: payload.message || '',
      })
      setFile(null)
      if ((payload.imported || 0) > 0) {
        toast.success(payload.message || 'Leads imported')
        onImported?.()
      }
    },
    onError: (err) => {
      setFile(null)
      const raw = err.response?.data?.errors
      if (isImportResult(raw)) {
        setResult({
          imported: raw.imported || 0,
          skipped: raw.skipped || 0,
          failed: raw.failed || 0,
          errors: raw.errors || [],
          warnings: raw.warnings || [],
          stopped: Boolean(raw.stopped),
          message: raw.message || '',
        })
        return
      }
      setResult({
        imported: 0,
        skipped: 0,
        failed: 1,
        errors: extractServerErrors(err),
        warnings: [],
      })
    },
  })

  const sampleMutation = useMutation({
    mutationFn: (format) =>
      onSample({ campaignId: campaignId || null, teamId: teamId || null, format }),
    onSuccess: (response) => {
      saveBlob(response.data, filenameFromResponse(response, 'leads-sample.csv'))
      toast.success('Sample file downloaded')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not download the sample file.')),
  })

  const canDownloadSample = Boolean(onSample) && (!campaignRequired || Boolean(campaignId))

  const submit = () => {
    setFormError('')

    if (campaignRequired && !campaignId) {
      setFormError('Choose a campaign for these leads.')
      return
    }

    if (teamRequired && !teamId) {
      setFormError('Choose a team for these leads.')
      return
    }

    if (!file) {
      setFormError('Choose a CSV file to import.')
      return
    }

    importMutation.mutate()
  }

  const close = () => {
    if (importMutation.isPending) return
    onClose()
  }

  return (
    <Modal
      open={open}
      onClose={close}
      size="lg"
      title="Import leads from CSV or Excel"
      description={description}
      footer={
        <>
          <Button variant="ghost" onClick={close} disabled={importMutation.isPending}>
            {result ? 'Close' : 'Cancel'}
          </Button>
          <Button onClick={submit} loading={importMutation.isPending} disabled={!file}>
            Import leads
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        <Alert variant="info">
          Required header row: <strong>Name, Email, Phone, Company</strong>. CSV, XLSX and XLS files
          are supported; each lead needs a name plus an email or phone number. Up to 2 MB per file.
        </Alert>
        {formError && <Alert variant="danger">{formError}</Alert>}

        {result && <ImportSummary result={result} onDismiss={() => setResult(null)} />}

        {(campaigns.length > 0 || teams.length > 0 || campaignRequired) && (
          <div className="grid gap-4 sm:grid-cols-2">
            {(campaigns.length > 0 || campaignRequired) && (
              <FormField
                label="Campaign"
                htmlFor="import_campaign"
                required={campaignRequired}
                hint={
                  campaignRequired
                    ? campaigns.length === 0
                      ? 'Create a campaign first, then come back to import leads.'
                      : undefined
                    : 'Optional — leave blank to skip attribution.'
                }
              >
                <Select
                  id="import_campaign"
                  value={campaignId}
                  onChange={(event) => setCampaignId(event.target.value)}
                  disabled={importMutation.isPending}
                >
                  <option value="">
                    {campaigns.length === 0
                      ? 'No campaigns yet'
                      : campaignRequired
                        ? 'Choose a campaign'
                        : 'No campaign'}
                  </option>
                  {campaigns.map((campaign) => (
                    <option key={campaign.id} value={campaign.id}>
                      {campaign.name}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}

            {teams.length > 0 && (
              <FormField
                label="Team"
                htmlFor="import_team"
                required={teamRequired}
                hint="Imported leads stay unassigned in this team until you distribute them."
              >
                <Select
                  id="import_team"
                  value={teamId}
                  onChange={(event) => setTeamId(event.target.value)}
                  disabled={importMutation.isPending}
                >
                  <option value="">No team</option>
                  {teams.map((team) => (
                    <option key={team.id} value={team.id}>
                      {team.name}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
          </div>
        )}

        <FileUploader
          file={file}
          onFileChange={setFile}
          accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
          hint="CSV or Excel (.xlsx/.xls) up to 2 MB"
          disabled={importMutation.isPending}
        />

        {onSample && (
          <div className="flex flex-wrap justify-end gap-2">
            <Button
              size="sm"
              variant="outline"
              onClick={() => sampleMutation.mutate('csv')}
              loading={sampleMutation.isPending && sampleMutation.variables === 'csv'}
              disabled={!canDownloadSample || importMutation.isPending}
            >
              <ArrowDownTrayIcon className="h-4 w-4" />
              Sample CSV
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => sampleMutation.mutate('xlsx')}
              loading={sampleMutation.isPending && sampleMutation.variables === 'xlsx'}
              disabled={!canDownloadSample || importMutation.isPending}
            >
              <ArrowDownTrayIcon className="h-4 w-4" />
              Sample Excel
            </Button>
          </div>
        )}
      </div>
    </Modal>
  )
}
