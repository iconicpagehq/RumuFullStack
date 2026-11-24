import { useEffect, useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { PhoneIcon, TrashIcon } from '@heroicons/react/24/outline'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { FormField, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { CALL_DISPOSITIONS, CALL_STATUS_LABELS, callSourceLabel, dispositionMeta } from './callMeta'
import { CallRecordingPlayer } from './CallRecordingPlayer'
import { formatDateTime, formatDuration, formatPhone } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const CallDetailModal = ({
  open,
  onClose,
  api,
  call,
  canDelete = false,
  onChanged,
}) => {
  const [disposition, setDisposition] = useState('')
  const [notes, setNotes] = useState('')
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!open || !call) return
    setDisposition(call.disposition || '')
    setNotes(call.disposition_notes || '')
    setError('')
  }, [open, call?.id])

  const saveMutation = useMutation({
    mutationFn: () => api.updateDisposition(call.id, {
      disposition,
      notes: notes.trim() || null,
    }),
    onSuccess: () => {
      toast.success('Call outcome saved')
      onChanged?.()
      onClose?.()
    },
    onError: (err) => setError(getErrorMessage(err, 'Could not save the call outcome.')),
  })

  const deleteMutation = useMutation({
    mutationFn: () => api.deleteRecording(call.id),
    onSuccess: () => {
      toast.success('Recording deleted')
      setDeleteOpen(false)
      onChanged?.()
      onClose?.()
    },
    onError: (err) => {
      setDeleteOpen(false)
      toast.error(getErrorMessage(err, 'Could not delete this recording.'))
    },
  })

  if (!call) return null

  const meta = dispositionMeta(call.disposition)
  const sourceLabel = callSourceLabel(call.source)
  const uploadedFromAndroid = Boolean(call.client_metadata?.original_filename || call.original_filename)

  return (
    <>
      <Modal
        open={open}
        onClose={onClose}
        title={call.lead?.name || 'Call details'}
        description={[formatPhone(call.lead?.phone), formatDateTime(call.started_at || call.created_at)]
          .filter(Boolean)
          .join(' · ')}
        size="lg"
      >
        <div className="space-y-5">
          <div className="flex flex-wrap items-center gap-2">
            <StatusBadge
              status={call.status}
              label={CALL_STATUS_LABELS[call.status]}
            />
            {meta ? (
              <Badge variant={meta.tone}>{meta.label}</Badge>
            ) : (
              <Badge variant="outline">Outcome pending</Badge>
            )}
            {sourceLabel && <Badge variant="neutral">{sourceLabel}</Badge>}
            {call.consent_confirmed_at && (
              <Badge variant="success">Consent confirmed in app</Badge>
            )}
            {uploadedFromAndroid && <Badge variant="neutral">Uploaded from Android</Badge>}
            {call.team?.name && <Badge variant="outline">{call.team.name}</Badge>}
          </div>

          <div className="grid gap-3 sm:grid-cols-3">
            <div className="rounded-xl border border-border bg-muted/30 p-3">
              <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                Duration
              </p>
              <p className="mt-1 text-sm font-semibold text-foreground">
                {formatDuration(call.duration_seconds)}
              </p>
            </div>
            <div className="rounded-xl border border-border bg-muted/30 p-3">
              <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                Salesperson
              </p>
              <p className="mt-1 truncate text-sm font-semibold text-foreground">
                {call.agent?.name || '—'}
              </p>
            </div>
            <div className="rounded-xl border border-border bg-muted/30 p-3">
              <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                Numbers
              </p>
              <p className="mt-1 flex items-center gap-1 truncate text-sm font-semibold text-foreground">
                <PhoneIcon className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                {formatPhone(call.from_number)} → {formatPhone(call.to_number)}
              </p>
            </div>
          </div>

          <CallRecordingPlayer api={api} call={call} autoPlay />

          <form
            onSubmit={(event) => {
              event.preventDefault()
              if (!disposition) {
                setError('Select the call outcome.')
                return
              }
              saveMutation.mutate()
            }}
            className="space-y-3 rounded-xl border border-border p-3"
          >
            <div className="grid gap-3 sm:grid-cols-2">
              <FormField label="Call outcome" htmlFor="call-disposition" required>
                <Select
                  id="call-disposition"
                  value={disposition}
                  onChange={(event) => setDisposition(event.target.value)}
                >
                  <option value="">Select outcome…</option>
                  {CALL_DISPOSITIONS.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </Select>
              </FormField>
              <FormField label="Call date" htmlFor="call-started">
                <input
                  id="call-started"
                  className="input-base"
                  value={formatDateTime(call.started_at || call.created_at)}
                  disabled
                  readOnly
                />
              </FormField>
            </div>
            <FormField label="Notes" htmlFor="call-notes" hint="Visible to everyone who can see this call.">
              <Textarea
                id="call-notes"
                rows={3}
                value={notes}
                onChange={(event) => setNotes(event.target.value)}
                placeholder="What did the customer say?"
              />
            </FormField>
            {error && <p className="form-error">{error}</p>}
            <div className="flex flex-wrap items-center justify-between gap-2">
              {canDelete && call.recording?.available ? (
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  className="text-destructive hover:bg-destructive/10"
                  onClick={() => setDeleteOpen(true)}
                >
                  <TrashIcon className="h-4 w-4" />
                  Delete recording
                </Button>
              ) : (
                <span />
              )}
              <Button type="submit" size="sm" loading={saveMutation.isPending} disabled={!disposition}>
                Save outcome
              </Button>
            </div>
          </form>
        </div>
      </Modal>

      <ConfirmDialog
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title="Delete this recording?"
        description="The audio file will be removed permanently. The call entry and its outcome stay in the history."
        confirmLabel="Delete recording"
      />
    </>
  )
}
