import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useSearchParams } from 'react-router-dom'
import {
  ArrowRightIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ClockIcon,
  MusicalNoteIcon,
  PhoneIcon,
  ArrowUpTrayIcon,
} from '@heroicons/react/24/outline'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Select } from '@/components/ui/Input'
import { Pagination } from '@/components/ui/Pagination'
import { Switch } from '@/components/ui/Switch'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useDebounce } from '@/hooks/useDebounce'
import { formatDateTime, formatDuration, formatNumber, formatPhone, timeAgo } from '@/utils/formatters'
import { CallDetailModal } from './CallDetailModal'
import { CallImportModal } from './CallImportModal'
import { CALL_DISPOSITIONS, CALL_STATUS_LABELS, CALL_STATUS_OPTIONS, dispositionMeta } from './callMeta'

const PER_PAGE = 20

export const CallsWorkspace = ({
  edition,
  api,
  loadLeads,
  canDeleteCall = () => false,
  view = 'logs',
}) => {
  const queryClient = useQueryClient()
  const [searchParams, setSearchParams] = useSearchParams()

  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search, 300)
  const [status, setStatus] = useState('')
  const [disposition, setDisposition] = useState('')
  const [recordedOnly, setRecordedOnly] = useState(false)

  const [detailCall, setDetailCall] = useState(null)
  const [importOpen, setImportOpen] = useState(false)

  const callParam = searchParams.get('call')
  const isAnalytics = view === 'analytics'
  const basePath = edition === 'realtor' ? '/realtor/calls' : '/enterprise/calls'

  const listParams = {
    page,
    per_page: PER_PAGE,
    search: debouncedSearch || undefined,
    status: status || undefined,
    disposition: disposition || undefined,
    with_recording: recordedOnly ? 1 : undefined,
  }

  const summaryQuery = useQuery({
    queryKey: ['calls', edition, 'summary'],
    queryFn: async () => {
      const response = await api.summary()
      return response.data?.data || {}
    },
    staleTime: 30000,
  })

  const listQuery = useQuery({
    queryKey: ['calls', edition, 'list', listParams],
    queryFn: async () => {
      const response = await api.list(listParams)
      return { items: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const { data: deepCall } = useQuery({
    queryKey: ['calls', edition, 'detail', callParam],
    queryFn: async () => {
      const response = await api.get(callParam)
      return response.data?.data
    },
    enabled: Boolean(callParam) && !detailCall,
    retry: false,
  })

  useEffect(() => {
    if (deepCall) setDetailCall(deepCall)
  }, [deepCall])

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status, disposition, recordedOnly])

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['calls', edition] })
    queryClient.invalidateQueries({ queryKey: [edition, 'dashboard'] })
    queryClient.invalidateQueries({ queryKey: [edition, 'lead'] })
  }

  const closeDetail = () => {
    setDetailCall(null)
    if (searchParams.has('call')) {
      const next = new URLSearchParams(searchParams)
      next.delete('call')
      setSearchParams(next, { replace: true })
    }
  }

  const summary = summaryQuery.data || {}
  const today = summary.today || {}
  const calls = listQuery.data?.items || []
  const meta = listQuery.data?.meta || {}
  const leadsPath = edition === 'realtor' ? '/realtor/leads' : '/enterprise/leads'
  const hasFilters = Boolean(search || status || disposition || recordedOnly)

  const resetFilters = () => {
    setSearch('')
    setStatus('')
    setDisposition('')
    setRecordedOnly(false)
  }

  if (isAnalytics) {
    const byStatus = summary.by_status || {}

    return (
      <div>
        <PageHeader
          title="Calls"
          description="Calls started in the Rumu Android app, with recordings synced to your workspace."
          actions={
            <Button size="sm" onClick={() => setImportOpen(true)}>
              <ArrowUpTrayIcon className="h-4 w-4" />
              Upload recording
            </Button>
          }
        />

        <div className="mb-5 flex gap-2 border-b border-border pb-3">
          <Link
            to={`${basePath}/logs`}
            className="rounded-lg px-3.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-muted/50 hover:text-foreground"
          >
            Call Logs
          </Link>
          <Link
            to={`${basePath}/analytics`}
            className="rounded-lg bg-primary/10 px-3.5 py-1.5 text-xs font-semibold text-highlight"
          >
            Analytics
          </Link>
        </div>

        {Number(summary.pending_dispositions || 0) > 0 && (
          <Alert variant="warning" className="mb-5">
            {formatNumber(summary.pending_dispositions)} finished call
            {Number(summary.pending_dispositions) === 1 ? '' : 's'} still need an outcome.{' '}
            <Link to={`${basePath}/logs`} className="underline font-semibold">
              Open call logs
            </Link>{' '}
            to save what happened.
          </Alert>
        )}

        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
          <StatsCard
            label="Calls today"
            value={formatNumber(today.total || 0)}
            icon={PhoneIcon}
            tone="primary"
            hint="Calls started or imported today"
            loading={summaryQuery.isLoading}
          />
          <StatsCard
            label="Connected"
            value={formatNumber(today.connected || 0)}
            icon={CheckCircleIcon}
            tone="success"
            hint="Answered by the lead"
            loading={summaryQuery.isLoading}
          />
          <StatsCard
            label="Recorded"
            value={formatNumber(today.recorded || 0)}
            icon={MusicalNoteIcon}
            tone="purple"
            hint="Playable recordings"
            loading={summaryQuery.isLoading}
          />
          <StatsCard
            label="Avg. duration"
            value={formatDuration(today.avg_duration_seconds || 0)}
            icon={ClockIcon}
            tone="info"
            hint="Across connected calls today"
            loading={summaryQuery.isLoading}
          />
        </div>

        {/* Detailed Analytics Cards */}
        <div className="mt-6 grid gap-5 lg:grid-cols-2">
          {/* Status Breakdown Card */}
          <Card className="p-5">
            <h3 className="text-sm font-semibold text-foreground flex items-center justify-between">
              <span>Call Status Breakdown</span>
              <span className="text-xs font-normal text-muted-foreground">All-time</span>
            </h3>
            <p className="text-xs text-muted-foreground mt-0.5">
              Distribution of calls across terminal connection statuses.
            </p>
            <div className="mt-4 space-y-3">
              {CALL_STATUS_OPTIONS.map((opt) => {
                const count = Number(byStatus[opt.value] || 0)
                const totalCalls = Object.values(byStatus).reduce((a, b) => Number(a) + Number(b), 0)
                const pct = totalCalls > 0 ? Math.round((count / totalCalls) * 100) : 0
                return (
                  <div key={opt.value}>
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span className="font-medium text-foreground">{opt.label}</span>
                      <span className="text-muted-foreground">
                        {count} ({pct}%)
                      </span>
                    </div>
                    <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                      <div
                        className="h-full rounded-full bg-primary transition-all duration-300"
                        style={{ width: `${pct}%` }}
                      />
                    </div>
                  </div>
                )
              })}
            </div>
          </Card>

          {/* Call Dispositions Card */}
          <Card className="p-5">
            <h3 className="text-sm font-semibold text-foreground flex items-center justify-between">
              <span>Call Outcomes</span>
              <span className="text-xs font-normal text-muted-foreground">Configured outcomes</span>
            </h3>
            <p className="text-xs text-muted-foreground mt-0.5">
              Categorized dispositions saved by team members after calling leads.
            </p>
            <div className="mt-4 grid grid-cols-2 gap-2.5">
              {CALL_DISPOSITIONS.map((disp) => {
                const meta = dispositionMeta(disp.value)
                return (
                  <div
                    key={disp.value}
                    className="flex items-center justify-between p-2.5 rounded-lg border border-border bg-muted/20 text-xs"
                  >
                    <span className="font-medium truncate">{disp.label}</span>
                    <Badge variant={meta?.tone || 'neutral'} className="text-[10px]">
                      {meta?.label || disp.label}
                    </Badge>
                  </div>
                )
              })}
            </div>
            <div className="mt-5 pt-4 border-t border-border flex items-center justify-between">
              <span className="text-xs text-muted-foreground">View detailed call recordings & history</span>
              <Link
                to={`${basePath}/logs`}
                className="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
              >
                Go to Call Logs
                <ArrowRightIcon className="h-3.5 w-3.5" />
              </Link>
            </div>
          </Card>
        </div>

        <CallImportModal
          open={importOpen}
          onClose={() => setImportOpen(false)}
          api={api}
          edition={edition}
          loadLeads={loadLeads}
          onImported={() => {
            invalidate()
          }}
        />
      </div>
    )
  }

  return (
    <div>
      <PageHeader
        title="Calls"
        description="Calls started in the Rumu Android app, with recordings synced to your workspace."
        actions={
          <Button size="sm" onClick={() => setImportOpen(true)}>
            <ArrowUpTrayIcon className="h-4 w-4" />
            Upload recording
          </Button>
        }
      />

      <div className="mb-5 flex gap-2 border-b border-border pb-3">
        <Link
          to={`${basePath}/logs`}
          className="rounded-lg bg-primary/10 px-3.5 py-1.5 text-xs font-semibold text-highlight"
        >
          Call Logs
        </Link>
        <Link
          to={`${basePath}/analytics`}
          className="rounded-lg px-3.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-muted/50 hover:text-foreground"
        >
          Analytics
        </Link>
      </div>

      <Card className="overflow-hidden">
        <div className="border-b border-border p-4">
          <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_190px_auto]">
            <FormField label="Search">
              <SearchInput
                value={search}
                onChange={setSearch}
                placeholder="Search by lead name or phone…"
              />
            </FormField>
            <FormField label="Status" htmlFor="calls-filter-status">
              <Select
                id="calls-filter-status"
                value={status}
                onChange={(event) => setStatus(event.target.value)}
              >
                <option value="">All statuses</option>
                {CALL_STATUS_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormField>
            <FormField label="Outcome" htmlFor="calls-filter-disposition">
              <Select
                id="calls-filter-disposition"
                value={disposition}
                onChange={(event) => setDisposition(event.target.value)}
              >
                <option value="">All outcomes</option>
                {CALL_DISPOSITIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </FormField>
            <div className="flex items-end pb-1">
              <Switch
                checked={recordedOnly}
                onChange={setRecordedOnly}
                label="Recordings only"
                className="md:min-w-[150px]"
              />
            </div>
          </div>
          {hasFilters && (
            <button
              type="button"
              onClick={resetFilters}
              className="mt-2 text-xs font-semibold text-highlight hover:underline"
            >
              Clear filters
            </button>
          )}
        </div>

        {listQuery.isLoading ? (
          <div className="space-y-2 p-4">
            {[1, 2, 3, 4, 5].map((item) => (
              <div key={item} className="skeleton h-16 w-full" />
            ))}
          </div>
        ) : listQuery.isError ? (
          <div className="p-6">
            <Alert variant="danger" title="Could not load calls">
              Please refresh the page and try again.
            </Alert>
          </div>
        ) : calls.length === 0 ? (
          <EmptyState
            icon={PhoneIcon}
            title={hasFilters ? 'No calls match these filters' : 'No calls synced yet'}
            description={
              hasFilters
                ? 'Try a different status, outcome or search term.'
                : 'Calls started in the Rumu Android app appear here with playback.'
            }
            action={
              hasFilters ? (
                <Button size="sm" variant="outline" onClick={resetFilters}>
                  Clear filters
                </Button>
              ) : (
                <div className="flex flex-wrap items-center justify-center gap-2">
                  <Button size="sm" onClick={() => setImportOpen(true)}>
                    <ArrowUpTrayIcon className="h-4 w-4" />
                    Upload recording
                  </Button>
                  <Link to={leadsPath}>
                    <Button size="sm" variant="outline">
                      Open leads
                    </Button>
                  </Link>
                </div>
              )
            }
          />
        ) : (
          <div className="divide-y divide-border">
            {calls.map((call) => {
              const metaInfo = dispositionMeta(call.disposition)
              return (
                <button
                  key={call.id}
                  type="button"
                  onClick={() => setDetailCall(call)}
                  className="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-muted/40"
                >
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-highlight">
                    <PhoneIcon className="h-4 w-4" />
                  </span>

                  <span className="min-w-0 flex-1">
                    <span className="flex items-center gap-1.5">
                      <span className="truncate text-sm font-semibold text-foreground">
                        {call.lead?.name || 'Unknown lead'}
                      </span>
                      {call.recording?.available && (
                        <MusicalNoteIcon
                          className="h-3.5 w-3.5 shrink-0 text-highlight"
                          aria-label="Recording available"
                        />
                      )}
                      {call.recording?.pending && (
                        <ClockIcon
                          className="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                          aria-label="Recording pending"
                        />
                      )}
                    </span>
                    <span className="mt-0.5 block truncate text-xs text-muted-foreground">
                      {formatPhone(call.lead?.phone)}
                      {call.agent?.name ? ` · ${call.agent.name}` : ''}
                      {call.team?.name ? ` · ${call.team.name}` : ''}
                    </span>
                  </span>

                  <span className="hidden shrink-0 items-center gap-2 sm:flex">
                    <StatusBadge
                      status={call.status}
                      label={CALL_STATUS_LABELS[call.status] || call.status}
                    />
                    {metaInfo ? (
                      <Badge variant={metaInfo.tone}>{metaInfo.label}</Badge>
                    ) : call.pending_disposition ? (
                      <Badge variant="outline">Outcome pending</Badge>
                    ) : null}
                    {call.source === 'app' && <Badge variant="neutral">App</Badge>}
                  </span>

                  <span className="hidden w-16 shrink-0 text-right sm:block">
                    <span className="block text-sm font-medium tabular-nums text-foreground">
                      {formatDuration(call.duration_seconds)}
                    </span>
                    <span
                      className="block truncate text-[11px] text-muted-foreground"
                      title={formatDateTime(call.started_at || call.created_at)}
                    >
                      {timeAgo(call.started_at || call.created_at)}
                    </span>
                  </span>

                  <ChevronRightIcon className="h-4 w-4 shrink-0 text-muted-foreground/60" />
                </button>
              )
            })}
          </div>
        )}

        {!listQuery.isLoading && !listQuery.isError && calls.length > 0 && (
          <Pagination
            className="px-4"
            page={meta.current_page || page}
            lastPage={meta.last_page || 1}
            total={meta.total || calls.length}
            perPage={meta.per_page || PER_PAGE}
            onPageChange={setPage}
          />
        )}
      </Card>

      <CallDetailModal
        open={Boolean(detailCall)}
        onClose={closeDetail}
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
        loadLeads={loadLeads}
        onImported={() => {
          setPage(1)
          invalidate()
        }}
      />

      <div className="mt-4 flex justify-end">
        <Link
          to={leadsPath}
          className="inline-flex items-center gap-1 text-xs font-semibold text-highlight hover:underline"
        >
          Open leads
          <ArrowRightIcon className="h-3.5 w-3.5" />
        </Link>
      </div>
    </div>
  )
}
