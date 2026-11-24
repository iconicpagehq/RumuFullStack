import { Link } from 'react-router-dom'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  ClockIcon,
  MusicalNoteIcon,
  PhoneIcon,
} from '@heroicons/react/24/outline'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { cn } from '@/utils/cn'
import { formatDuration, formatNumber, formatPhone, formatTime, timeAgo } from '@/utils/formatters'
import { CALL_STATUS_LABELS } from './callMeta'

const tiles = (today) => [
  {
    key: 'total',
    label: 'Calls today',
    value: formatNumber(today?.total || 0),
    icon: PhoneIcon,
    tone: 'bg-primary/10 text-highlight',
  },
  {
    key: 'connected',
    label: 'Connected',
    value: formatNumber(today?.connected || 0),
    icon: CheckCircleIcon,
    tone: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
  },
  {
    key: 'recorded',
    label: 'Synced',
    value: formatNumber(today?.recorded || 0),
    icon: MusicalNoteIcon,
    tone: 'bg-violet-500/10 text-violet-600 dark:text-violet-300',
  },
  {
    key: 'duration',
    label: 'Avg. duration',
    value: formatDuration(today?.avg_duration_seconds || 0),
    icon: ClockIcon,
    tone: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
  },
]

export const CallsSummaryCard = ({ calls, isLoading = false, to, className }) => {
  const today = calls?.today || {}
  const recent = (calls?.recent || []).slice(0, 4)

  return (
    <Card className={cn('mt-5', className)}>
      <CardHeader>
        <div>
          <CardTitle>Calls</CardTitle>
          <p className="mt-1 text-sm text-muted-foreground">
            Calls from the Rumu app and synced recordings.
          </p>
        </div>
        <Link
          to={to}
          className="inline-flex items-center gap-1 text-sm font-semibold text-highlight hover:underline"
        >
          Open calls
          <ArrowRightIcon className="h-3.5 w-3.5" />
        </Link>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
          {tiles(today).map((tile) => {
            const Icon = tile.icon
            return (
              <div key={tile.key} className="rounded-lg border border-border bg-muted/20 p-3">
                <div className="flex items-center gap-2">
                  <span className={cn('flex h-7 w-7 items-center justify-center rounded-md', tile.tone)}>
                    <Icon className="h-4 w-4" />
                  </span>
                  <span className="text-[11px] font-medium text-muted-foreground">{tile.label}</span>
                </div>
                <p className="mt-2 text-lg font-semibold leading-none tabular-nums text-foreground">
                  {isLoading ? '—' : tile.value}
                </p>
              </div>
            )
          })}
        </div>

        <div className="mt-4">
          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3].map((item) => (
                <div key={item} className="skeleton h-12 w-full" />
              ))}
            </div>
          ) : recent.length === 0 ? (
            <div className="flex flex-col items-center gap-1 py-5 text-center">
              <PhoneIcon className="h-7 w-7 text-muted-foreground/60" />
              <p className="text-sm font-medium text-foreground">No calls yet</p>
              <p className="text-xs text-muted-foreground">
                Calls started in the Rumu Android app appear here with playback.
              </p>
              <Link to={to} className="mt-1 text-xs font-semibold text-highlight hover:underline">
                Upload recording
              </Link>
            </div>
          ) : (
            <div className="-mx-2 divide-y divide-border/70">
              {recent.map((call) => (
                <Link
                  key={call.id}
                  to={`${to}?call=${call.id}`}
                  className="flex items-center gap-3 rounded-lg px-2 py-2.5 transition hover:bg-muted/60"
                >
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-highlight">
                    <PhoneIcon className="h-4 w-4" />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium text-foreground">
                      {call.lead?.name || 'Unknown lead'}
                    </span>
                    <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                      {formatPhone(call.lead?.phone)}
                      {call.agent?.name ? ` · ${call.agent.name}` : ''} · {timeAgo(call.started_at || call.created_at)}
                    </span>
                  </span>
                  <span className="hidden shrink-0 text-xs tabular-nums text-muted-foreground sm:block">
                    {formatDuration(call.duration_seconds)}
                  </span>
                  <span className="shrink-0 text-[11px] text-muted-foreground sm:hidden">
                    {formatTime(call.started_at || call.created_at)}
                  </span>
                  <span className="flex shrink-0 items-center gap-2">
                    {call.recording?.available && (
                      <MusicalNoteIcon className="h-4 w-4 text-highlight" aria-label="Recording available" />
                    )}
                    <StatusBadge
                      status={call.status}
                      label={CALL_STATUS_LABELS[call.status] || call.status}
                    />
                  </span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
