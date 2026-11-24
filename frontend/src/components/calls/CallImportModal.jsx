import { useEffect, useState } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FileUploader } from '@/components/ui/FileUploader'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useDebounce } from '@/hooks/useDebounce'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'
import { formatPhone } from '@/utils/formatters'
import { CALL_DISPOSITIONS } from './callMeta'

const ACCEPT = '.mp3,.wav,.m4a,.mp4,.ogg,.amr,.aac,.3gp,.webm,audio/*,video/mp4'

const pad = (value) => String(value).padStart(2, '0')

const localDateTimeValue = (date = new Date()) =>
  `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`

const emptyForm = () => ({
  leadId: '',
  file: null,
  recordedAt: localDateTimeValue(),
  duration: '60',
  direction: 'outbound',
  disposition: '',
  notes: '',
})

export const CallImportModal = ({
  open,
  onClose,
  api,
  edition,
  loadLeads,
  defaultLead = null,
  onImported,
}) => {
  const [form, setForm] = useState(emptyForm)
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState('')
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search, 300)

  useEffect(() => {
    if (open) {
      setForm({ ...emptyForm(), leadId: defaultLead?.id ? String(defaultLead.id) : '' })
      setErrors({})
      setFormError('')
      setSearch('')
    }
  }, [open, defaultLead?.id])

  const pickerEnabled = open && !defaultLead && Boolean(loadLeads)

  const { data: leads = [], isFetching } = useQuery({
    queryKey: ['calls', edition, 'lead-picker', debouncedSearch],
    queryFn: async () => {
      const response = await loadLeads({ search: debouncedSearch || undefined, per_page: 30 })
      return response.data?.data || []
    },
    enabled: pickerEnabled,
    staleTime: 20000,
  })

  const update = (key, value) => {
    setForm((current) => ({ ...current, [key]: value }))
    setErrors((current) => ({ ...current, [key]: undefined }))
  }

  const importMutation = useMutation({
    mutationFn: () =>
      api.importRecording({
        file: form.file,
        leadId: form.leadId,
        recordedAt: new Date(form.recordedAt).toISOString(),
        durationSeconds: Number(form.duration),
        direction: form.direction || undefined,
        disposition: form.disposition || undefined,
        notes: form.notes.trim() || undefined,
      }),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Recording uploaded')
      onImported?.()
      onClose?.()
    },
    onError: (err) => {
      const fieldErrors = serverFieldErrors(err)
      setErrors(fieldErrors)
      setFormError(
        Object.keys(fieldErrors).length
          ? 'Check the highlighted fields and try again.'
          : getErrorMessage(err, 'Could not upload this recording.'),
      )
    },
  })

  const submit = (event) => {
    event.preventDefault()
    setFormError('')

    const nextErrors = {}
    if (!form.leadId) nextErrors.leadId = 'Choose the lead this call belongs to.'
    if (!form.file) nextErrors.file = 'Choose the recording file.'
    if (!form.recordedAt || Number.isNaN(new Date(form.recordedAt).getTime())) {
      nextErrors.recordedAt = 'Choose when the call happened.'
    }
    const duration = Number(form.duration)
    if (!Number.isFinite(duration) || duration < 1 || duration > 14400) {
      nextErrors.duration = 'Enter a duration between 1 second and 4 hours.'
    }

    setErrors(nextErrors)
    if (Object.keys(nextErrors).length) return

    importMutation.mutate()
  }

  const selectedLead = defaultLead || leads.find((lead) => String(lead.id) === String(form.leadId))

  return (
    <Modal
      open={open}
      onClose={() => !importMutation.isPending && onClose?.()}
      title="Upload a call recording"
      description="Use this only if the app upload failed. Attach an existing recording to a lead — the audio stays private and plays from the call history."
      size="lg"
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={importMutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="call-import-form" loading={importMutation.isPending}>
            Upload recording
          </Button>
        </>
      }
    >
      <form id="call-import-form" onSubmit={submit} className="space-y-4">
        {formError && <Alert variant="danger">{formError}</Alert>}

        {defaultLead ? (
          <FormField label="Lead">
            <div className="rounded-lg border border-border bg-muted/30 px-3 py-2.5 text-sm">
              <span className="font-medium text-foreground">{defaultLead.name}</span>
              {defaultLead.phone && (
                <span className="ml-2 text-muted-foreground">{formatPhone(defaultLead.phone)}</span>
              )}
            </div>
          </FormField>
        ) : (
          <div className="grid gap-3 sm:grid-cols-2">
            <FormField label="Find a lead" htmlFor="call-import-search">
              <Input
                id="call-import-search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search by name or phone…"
                disabled={importMutation.isPending}
              />
            </FormField>
            <FormField label="Lead" htmlFor="call-import-lead" required error={errors.leadId}>
              <Select
                id="call-import-lead"
                value={form.leadId}
                onChange={(event) => update('leadId', event.target.value)}
                disabled={importMutation.isPending}
                invalid={Boolean(errors.leadId)}
              >
                <option value="">{isFetching ? 'Searching…' : 'Choose a lead'}</option>
                {selectedLead && !leads.some((lead) => String(lead.id) === String(selectedLead.id)) && (
                  <option value={selectedLead.id}>{selectedLead.name}</option>
                )}
                {leads.map((lead) => (
                  <option key={lead.id} value={lead.id}>
                    {lead.name}
                    {lead.phone ? ` · ${lead.phone}` : ''}
                  </option>
                ))}
              </Select>
            </FormField>
          </div>
        )}

        <FileUploader
          file={form.file}
          onFileChange={(file) => update('file', file)}
          accept={ACCEPT}
          hint="MP3, WAV, M4A, OGG, AMR, AAC, 3GP or WebM · up to 50 MB"
          disabled={importMutation.isPending}
          error={errors.recording || errors.file}
        />

        <div className="grid gap-3 sm:grid-cols-2">
          <FormField label="Call date & time" htmlFor="call-import-recorded" required error={errors.recordedAt}>
            <Input
              id="call-import-recorded"
              type="datetime-local"
              value={form.recordedAt}
              max={localDateTimeValue()}
              onChange={(event) => update('recordedAt', event.target.value)}
              disabled={importMutation.isPending}
              invalid={Boolean(errors.recordedAt)}
            />
          </FormField>
          <FormField
            label="Duration (seconds)"
            htmlFor="call-import-duration"
            required
            error={errors.duration_seconds || errors.duration}
            hint="Used for the call log and analytics."
          >
            <Input
              id="call-import-duration"
              type="number"
              min="1"
              max="14400"
              value={form.duration}
              onChange={(event) => update('duration', event.target.value)}
              disabled={importMutation.isPending}
              invalid={Boolean(errors.duration_seconds || errors.duration)}
            />
          </FormField>
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          <FormField label="Direction" htmlFor="call-import-direction">
            <Select
              id="call-import-direction"
              value={form.direction}
              onChange={(event) => update('direction', event.target.value)}
              disabled={importMutation.isPending}
            >
              <option value="outbound">Outbound — we called</option>
              <option value="inbound">Inbound — they called</option>
            </Select>
          </FormField>
          <FormField label="Outcome" htmlFor="call-import-disposition">
            <Select
              id="call-import-disposition"
              value={form.disposition}
              onChange={(event) => update('disposition', event.target.value)}
              disabled={importMutation.isPending}
            >
              <option value="">Add the outcome later</option>
              {CALL_DISPOSITIONS.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <FormField label="Notes" htmlFor="call-import-notes">
          <Textarea
            id="call-import-notes"
            rows={3}
            value={form.notes}
            onChange={(event) => update('notes', event.target.value)}
            placeholder="What was discussed?"
            disabled={importMutation.isPending}
          />
        </FormField>
      </form>
    </Modal>
  )
}
