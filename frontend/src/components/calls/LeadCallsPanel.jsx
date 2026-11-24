import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import {
  ArrowUpTrayIcon,
  ChevronRightIcon,
  ClockIcon,
  MusicalNoteIcon,
  PhoneIcon,
} from '@heroicons/react/24/outline'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { formatDateTime, formatDuration, timeAgo } from '@/utils/formatters'
import { CallDetailModal } from './CallDetailModal'
import { CallImportModal } from './CallImportModal'
import { CALL_STATUS_LABELS, callSourceLabel, dispositionMeta } from './callMeta'

export const LeadCallsPanel = ({
  edition,
  api,
  lead,
  canDeleteCall = () => false,
  onCount,
}) => {
  const queryClient = useQueryClient()
  const [detailCall, setDetailCall] = useState(null)
  const [importOpen, setImportOpen] = useState(false)

  const leadId = lead?.id

  const { data, isLoading } = useQuery({
    queryKey: ['calls', edition, 'lead', leadId],
    queryFn: async () => {
      const response = await api.list({ lead_id: leadId, per_page: 50 })
      return {
        items: response.data?.data || [],
        total: response.data?.meta?.total || 0,
      }
    },
    enabled: Boolean(leadId),
  })

  const calls = data?.items || []
  const total = data?.total || 0

  useEffect(() => {
    if (data) onCount?.(total)
  }, [data, total, onCount])

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['calls', edition] })
    queryClient.invalidateQueries({ queryKey: [edition, 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: [edition, 'lead'] })
    queryClient.invalidateQueries({ queryKey: [edition, 'leads'] })
  }

  return (
    <div className="space-y-4 px-4 py-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-sm font-semibold text-foreground">Call history</p>
          <p className="text-xs text-muted-foreground">
            Calls started in the Rumu app for this lead.
          </p>
        </div>
        <Button size="sm" variant="outline" onClick={() => setImportOpen(true)}>
          <ArrowUpTrayIcon className="h-4 w-4" />
          Upload recording
        </Button>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          {[1, 2, 3].map((item) => (
            <div key={item} className="skeleton h-16 w-full" />
          ))}
        </div>
      ) : calls.length === 0 ? (
        <EmptyState
          icon={PhoneIcon}
          title="No calls yet"
          description="Calls you start in the Rumu Android app will appear here with their recording."
          action={
            <Button size="sm" variant="outline" onClick={() => setImportOpen(true)}>
              <ArrowUpTrayIcon className="h-4 w-4" />
              Upload recording
            </Button>
          }
        />
      ) : (
        <div className="divide-y divide-border overflow-hidden rounded-xl border border-border">
          {calls.map((call) => {
            const meta = dispositionMeta(call.disposition)
            return (
              <button
                key={call.id}
                type="button"
                onClick={() => setDetailCall(call)}
                className="flex w-full items-center gap-3 px-3 py-3 text-left transition hover:bg-muted/40"
              >
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-highlight">
                  <PhoneIcon className="h-4 w-4" />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="flex flex-wrap items-center gap-2">
                    <StatusBadge
                      status={call.status}
                      label={CALL_STATUS_LABELS[call.status] || call.status}
                    />
                    {meta ? (
                      <Badge variant={meta.tone}>{meta.label}</Badge>
                    ) : call.pending_disposition ? (
                      <Badge variant="outline">Outcome pending</Badge>
                    ) : null}
                    {call.recording?.available && (
                      <MusicalNoteIcon
                        className="h-3.5 w-3.5 text-highlight"
                        aria-label="Recording available"
                      />
                    )}
                    {call.recording?.pending && (
                      <ClockIcon
                        className="h-3.5 w-3.5 text-muted-foreground"
                        aria-label="Recording pending"
                      />
                    )}
                  </span>
                  <span className="mt-1 block text-xs text-muted-foreground">
                    {call.agent?.name ? `${call.agent.name} · ` : ''}
                    <span title={formatDateTime(call.started_at || call.created_at)}>
                      {timeAgo(call.started_at || call.created_at)}
                    </span>
                  </span>
                </span>
                <span className="shrink-0 text-right">
                  <span className="block text-sm font-medium tabular-nums text-foreground">
                    {formatDuration(call.duration_seconds)}
                  </span>
                  <span className="block text-[11px] text-muted-foreground">
                    {callSourceLabel(call.source) || 'App call'}
                  </span>
                </span>
                <ChevronRightIcon className="h-4 w-4 shrink-0 text-muted-foreground/60" />
              </button>
            )
          })}
        </div>
      )}

      <CallDetailModal
        open={Boolean(detailCall)}
        onClose={() => setDetailCall(null)}
        api={api}
        call={detailCall}
        canDelete={Boolean(detailCall) && canDeleteCall(detailCall)}
        onChanged={invalidate}
      />

      <CallImportModal
        open={importOpen}
        onClose={() => setImportOpen(false)}
        api={api}
        edition={edition}
        defaultLead={lead ? { id: lead.id, name: lead.name, phone: lead.phone } : null}
        onImported={invalidate}
      />
    </div>
  )
}
