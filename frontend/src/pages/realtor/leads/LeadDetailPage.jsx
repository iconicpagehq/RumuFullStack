import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  PencilSquareIcon,
  PhoneIcon,
  SignalIcon,
  UserGroupIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { Avatar } from '@/components/ui/Avatar'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Textarea } from '@/components/ui/Input'
import { Select } from '@/components/ui/Input'
import { PageLoader } from '@/components/ui/Spinner'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { DetailItem } from '@/components/shared/DetailItem'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { CUSTOM_FIELD_LABELS, LEAD_STATUSES, LEAD_SOURCES } from '@/utils/constants'
import { formatDate, formatDateTime, timeAgo, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const sourceLabel = (source) => LEAD_SOURCES.find((item) => item.value === source)?.label || source

const displayValue = (key, value) => {
  if (value === undefined || value === null || value === '') return '—'
  if (key === 'preferred_visit_date') return formatDate(value)
  if (value === 'yes') return 'Yes'
  if (value === 'no') return 'No'
  return titleCase(String(value))
}

export const LeadDetailPage = () => {
  const { id: campaignId, leadId } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [note, setNote] = useState('')

  const { data: lead, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'lead', leadId],
    queryFn: async () => {
      const response = await realtorApi.leads.get(leadId)
      return response.data?.data
    },
  })

  const invalidateLead = () => {
    queryClient.invalidateQueries({ queryKey: ['realtor', 'lead', leadId] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'campaign', campaignId] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'customers'] })
    queryClient.invalidateQueries({ queryKey: ['realtor', 'dashboard'] })
  }

  const statusMutation = useMutation({
    mutationFn: (status) => realtorApi.leads.updateStatus(leadId, status),
    onSuccess: () => {
      toast.success('Pipeline stage updated')
      invalidateLead()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the stage.')),
  })

  const noteMutation = useMutation({
    mutationFn: () => realtorApi.leads.addNote(leadId, note.trim()),
    onSuccess: () => {
      setNote('')
      toast.success('Note added')
      queryClient.invalidateQueries({ queryKey: ['realtor', 'lead', leadId] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not add the note.')),
  })

  const metaMutation = useMutation({
    mutationFn: () => realtorApi.leads.resendMetaCapi(leadId),
    onSuccess: () => toast.success('Meta event sent'),
    onError: (err) => toast.error(getErrorMessage(err, 'Could not resend the Meta event.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const customFields = Object.entries(lead.custom_fields || {}).filter(
    ([, value]) => value !== null && value !== undefined && value !== '',
  )

  return (
    <div>
      <PageHeader
        breadcrumbs={[
          { label: 'Campaigns', to: '/realtor/campaigns' },
          { label: lead.campaign?.name || 'Campaign', to: `/realtor/campaigns/${campaignId}` },
          { label: lead.name },
        ]}
        title={
          <span className="flex flex-wrap items-center gap-2.5">
            {lead.name}
            <StatusBadge status={lead.status} />
            {lead.is_customer && <StatusBadge status="won" label="Customer" variant="success" />}
          </span>
        }
        description={lead.company || lead.email || 'Lead details and activity'}
        actions={
          <>
            <Button
              size="sm"
              variant="outline"
              onClick={() => navigate(`/realtor/campaigns/${campaignId}`)}
            >
              <ArrowLeftIcon className="h-4 w-4" />
              Campaign
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => metaMutation.mutate()}
              loading={metaMutation.isPending}
              title="Resend this lead to Meta Conversions API"
            >
              <SignalIcon className="h-4 w-4" />
              Resend Meta
            </Button>
            <Link to={`/realtor/campaigns/${campaignId}/leads/${leadId}/edit`}>
              <Button size="sm">
                <PencilSquareIcon className="h-4 w-4" />
                Edit lead
              </Button>
            </Link>
          </>
        }
      />

      <div className="grid gap-5 xl:grid-cols-3">
        <div className="space-y-5 xl:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Contact</CardTitle>
              <StatusBadge status={lead.status} />
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-4">
                <Avatar name={lead.name} size="lg" />
                <div className="min-w-0">
                  <p className="text-base font-semibold text-foreground">{lead.name}</p>
                  <p className="text-sm text-muted-foreground">{lead.company || 'Individual buyer'}</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Added {formatDateTime(lead.created_at)} · {sourceLabel(lead.source) || 'Manual'}
                  </p>
                </div>
              </div>
              <dl className="mt-6 grid gap-5 sm:grid-cols-2">
                <DetailItem label="Email">
                  {lead.email ? (
                    <a href={`mailto:${lead.email}`} className="inline-flex items-center gap-1.5 text-highlight hover:underline">
                      <EnvelopeIcon className="h-4 w-4" />
                      {lead.email}
                    </a>
                  ) : (
                    '—'
                  )}
                </DetailItem>
                <DetailItem label="Phone">
                  {lead.phone ? (
                    <a href={`tel:${lead.phone}`} className="inline-flex items-center gap-1.5 text-highlight hover:underline">
                      <PhoneIcon className="h-4 w-4" />
                      {lead.phone}
                    </a>
                  ) : (
                    '—'
                  )}
                </DetailItem>
                <DetailItem label="Company">{lead.company || '—'}</DetailItem>
                <DetailItem label="Campaign">
                  <Link
                    to={`/realtor/campaigns/${campaignId}`}
                    className="text-highlight hover:underline"
                  >
                    {lead.campaign?.name || '—'}
                  </Link>
                </DetailItem>
              </dl>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div>
                <CardTitle>Qualification</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  Preferences and buying signals captured with this lead.
                </p>
              </div>
            </CardHeader>
            <CardContent>
              {customFields.length ? (
                <dl className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                  {customFields.map(([key, value]) => (
                    <DetailItem key={key} label={CUSTOM_FIELD_LABELS[key] || titleCase(key)}>
                      {displayValue(key, value)}
                    </DetailItem>
                  ))}
                </dl>
              ) : (
                <EmptyState
                  icon={UserGroupIcon}
                  title="No qualification details"
                  description="Edit this lead to add budget, property type and other preferences."
                  className="py-8"
                />
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <div>
                <CardTitle>Notes & activity</CardTitle>
                <p className="mt-1 text-sm text-muted-foreground">
                  {lead.notes?.length || 0} note{lead.notes?.length === 1 ? '' : 's'} on this lead
                </p>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex flex-col gap-2 sm:flex-row">
                <Textarea
                  rows={2}
                  value={note}
                  onChange={(event) => setNote(event.target.value)}
                  placeholder="Add a call summary, follow-up or objection…"
                  className="min-h-[64px] flex-1"
                />
                <Button
                  className="sm:self-end"
                  onClick={() => noteMutation.mutate()}
                  disabled={!note.trim()}
                  loading={noteMutation.isPending}
                >
                  <PaperAirplaneIcon className="h-4 w-4" />
                  Add note
                </Button>
              </div>

              <div className="mt-5">
                {lead.notes?.length ? (
                  <ol className="relative space-y-5 border-l border-border pl-5">
                    {[...lead.notes].reverse().map((item) => (
                      <li key={item.id} className="relative">
                        <span className="absolute -left-[26px] top-1.5 h-2 w-2 rounded-full bg-primary ring-4 ring-card" />
                        <div className="flex flex-wrap items-center gap-2">
                          <Avatar name={item.user?.name || 'Team'} size="xs" />
                          <span className="text-xs font-semibold text-foreground">
                            {item.user?.name || 'Team'}
                          </span>
                          <span className="text-[11px] text-muted-foreground">
                            {timeAgo(item.created_at)}
                          </span>
                        </div>
                        <p className="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                          {item.note}
                        </p>
                      </li>
                    ))}
                  </ol>
                ) : (
                  <p className="rounded-lg bg-muted/40 px-4 py-6 text-center text-sm text-muted-foreground">
                    No notes yet. The stage changes you make will also be recorded here.
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="space-y-5">
          <Card>
            <CardHeader>
              <CardTitle>Pipeline stage</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <Select
                value={lead.status}
                onChange={(event) => statusMutation.mutate(event.target.value)}
                disabled={statusMutation.isPending}
              >
                {LEAD_STATUSES.map((status) => (
                  <option key={status.value} value={status.value}>
                    {status.label}
                  </option>
                ))}
              </Select>
              <p className="text-xs leading-relaxed text-muted-foreground">
                Changing the stage updates the lead instantly and records an automatic note.
              </p>
              {statusMutation.isError && (
                <Alert variant="danger">{getErrorMessage(statusMutation.error)}</Alert>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Details</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="space-y-4">
                <DetailItem label="Source">{sourceLabel(lead.source) || 'Manual'}</DetailItem>
                <DetailItem label="Campaign">{lead.campaign?.name || '—'}</DetailItem>
                <DetailItem label="Added on">{formatDateTime(lead.created_at)}</DetailItem>
                <DetailItem label="Last updated">{formatDateTime(lead.updated_at)}</DetailItem>
              </dl>
            </CardContent>
          </Card>

          {lead.inventory_unit && (
            <Card>
              <CardHeader>
                <CardTitle>Interested unit</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-start gap-3">
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-highlight">
                    <BuildingOffice2Icon className="h-4 w-4" />
                  </span>
                  <div className="min-w-0">
                    <p className="text-sm font-semibold text-foreground">
                      {lead.inventory_unit.full_path || lead.inventory_unit.label}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                      {lead.inventory_unit.layout ? titleCase(lead.inventory_unit.layout) : '—'}
                    </p>
                  </div>
                </div>
                <StatusBadge status={lead.inventory_unit.availability_status} />
                <Link
                  to="/realtor/inventory"
                  className="inline-flex items-center gap-1.5 text-xs font-semibold text-highlight hover:underline"
                >
                  <ArrowPathIcon className="h-3.5 w-3.5" />
                  Open inventory
                </Link>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle>Quick actions</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button
                variant="outline"
                className="w-full justify-start"
                onClick={() => metaMutation.mutate()}
                loading={metaMutation.isPending}
              >
                <SignalIcon className="h-4 w-4" />
                Resend Meta CAPI event
              </Button>
              <Button
                variant="outline"
                className="w-full justify-start"
                onClick={() => navigate(`/realtor/campaigns/${campaignId}/import`)}
              >
                <CalendarDaysIcon className="h-4 w-4" />
                Import more leads
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}
