import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowUpTrayIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  CreditCardIcon,
  GlobeAltIcon,
  MegaphoneIcon,
  NoSymbolIcon,
  PlayIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { PageLoader } from '@/components/ui/Spinner'
import { formatCurrency, formatDate, formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const UsageRow = ({ item }) => {
  const unlimited = item.limit === null
  const percent = item.percentage ?? 0
  return (
    <div>
      <div className="flex items-center justify-between text-xs">
        <span className="font-medium text-foreground">{item.label}</span>
        <span className="text-muted-foreground">
          {formatNumber(item.used)} / {unlimited ? 'Unlimited' : formatNumber(item.limit)}
          {item.extra > 0 ? ` (${item.extra} add-on)` : ''}
        </span>
      </div>
      <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
        <div
          className={
            percent >= 90
              ? 'h-full rounded-full bg-destructive'
              : percent >= 70
                ? 'h-full rounded-full bg-amber-500'
                : 'h-full rounded-full bg-primary'
          }
          style={{ width: unlimited ? '12%' : `${percent}%` }}
        />
      </div>
    </div>
  )
}

export const EnterpriseDetailPage = () => {
  const { id } = useParams()
  const queryClient = useQueryClient()
  const [confirmAction, setConfirmAction] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'enterprise', id],
    queryFn: async () => {
      const response = await adminApi.enterprises.get(id)
      return response.data?.data
    },
    enabled: Boolean(id),
  })

  const actionMutation = useMutation({
    mutationFn: (action) =>
      action === 'suspend'
        ? adminApi.enterprises.suspend(id)
        : adminApi.enterprises.resume(id),
    onSuccess: (response) => {
      toast.success(response.data?.message || 'Enterprise updated')
      setConfirmAction(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'enterprise', id] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'enterprises'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the enterprise.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const enterprise = data?.enterprise
  const plan = data?.plan
  const usage = data?.usage || {}
  const counts = data?.counts || {}
  const members = data?.members || []
  const invoiceRows = data?.invoices || []
  const subscription = data?.subscription

  const memberColumns = [
    {
      key: 'name',
      header: 'Member',
      render: (member) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-foreground">{member.name || 'Unknown'}</p>
          <p className="truncate text-xs text-muted-foreground">{member.email || member.phone || '—'}</p>
        </div>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (member) => <Badge variant="primary">{member.role}</Badge>,
    },
    {
      key: 'team',
      header: 'Team',
      render: (member) => (
        <span className="text-xs text-foreground">{member.team || '—'}</span>
      ),
    },
    {
      key: 'designation',
      header: 'Designation',
      render: (member) => (
        <span className="text-xs text-muted-foreground">{member.designation || '—'}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (member) => <StatusBadge status={member.status} />,
    },
    {
      key: 'joined_at',
      header: 'Joined',
      render: (member) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(member.joined_at)}
        </span>
      ),
    },
  ]

  const leadColumns = [
    {
      key: 'name',
      header: 'Lead',
      render: (lead) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-foreground">{lead.name}</p>
          <p className="truncate text-xs text-muted-foreground">{lead.phone || '—'}</p>
        </div>
      ),
    },
    {
      key: 'stage',
      header: 'Stage',
      render: (lead) => <Badge variant="info">{lead.enterprise_stage || lead.status}</Badge>,
    },
    {
      key: 'team',
      header: 'Team',
      render: (lead) => <span className="text-xs text-foreground">{lead.team || '—'}</span>,
    },
    {
      key: 'assigned_to',
      header: 'Assigned to',
      render: (lead) => <span className="text-xs text-foreground">{lead.assigned_to || 'Unassigned'}</span>,
    },
    {
      key: 'created_at',
      header: 'Received',
      render: (lead) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDateTime(lead.created_at)}
        </span>
      ),
    },
  ]

  const campaignColumns = [
    {
      key: 'name',
      header: 'Campaign',
      render: (campaign) => (
        <span className="text-sm font-semibold text-foreground">{campaign.name}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (campaign) => <StatusBadge status={campaign.status} />,
    },
    {
      key: 'leads_count',
      header: 'Leads',
      render: (campaign) => (
        <span className="text-sm tabular-nums text-foreground">{formatNumber(campaign.leads_count || 0)}</span>
      ),
    },
    {
      key: 'created_at',
      header: 'Created',
      render: (campaign) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(campaign.created_at)}
        </span>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        breadcrumbs={[
          { label: 'Enterprise accounts', to: '/admin/enterprises' },
          { label: enterprise.name },
        ]}
        title={enterprise.name}
        description={`${enterprise.legal_name || enterprise.name} · ${enterprise.slug}`}
        actions={
          <>
            <Link to="/admin/enterprises">
              <Button size="sm" variant="outline">
                Back
              </Button>
            </Link>
            {enterprise.status === 'suspended' ? (
              <Button size="sm" onClick={() => setConfirmAction('resume')}>
                <PlayIcon className="h-4 w-4" />
                Resume
              </Button>
            ) : (
              <Button size="sm" variant="danger" onClick={() => setConfirmAction('suspend')}>
                <NoSymbolIcon className="h-4 w-4" />
                Suspend
              </Button>
            )}
          </>
        }
      />

      <div className="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Active members"
          value={formatNumber(counts.members || 0)}
          icon={UsersIcon}
          hint={`${formatNumber(counts.managers || 0)} managers · ${formatNumber(counts.employees || 0)} employees`}
        />
        <StatsCard
          label="Teams"
          value={formatNumber(counts.teams || 0)}
          icon={UserGroupIcon}
          tone="info"
          hint={`${formatNumber(counts.campaigns || 0)} campaigns`}
        />
        <StatsCard
          label="Leads"
          value={formatNumber(counts.leads || 0)}
          icon={ArrowUpTrayIcon}
          tone="purple"
          hint={`${formatNumber(counts.leads_this_month || 0)} this month`}
        />
        <StatsCard
          label="Audit events"
          value={formatNumber(counts.audit_logs || 0)}
          icon={CheckBadgeIcon}
          tone="success"
          hint={enterprise.is_on_trial ? 'On trial' : 'Full subscription'}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
              <div>
                <CardTitle>Company profile</CardTitle>
                <CardDescription>Registered company details and branding.</CardDescription>
              </div>
              <StatusBadge status={enterprise.status} />
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap items-start gap-4">
                {enterprise.logo_url ? (
                  <img
                    src={enterprise.logo_url}
                    alt=""
                    className="h-16 w-16 rounded-xl border border-border object-cover"
                  />
                ) : (
                  <span className="flex h-16 w-16 items-center justify-center rounded-xl bg-primary/10 text-highlight">
                    <BuildingOffice2Icon className="h-7 w-7" />
                  </span>
                )}
                <div className="grid flex-1 gap-4 sm:grid-cols-2">
                  <div>
                    <p className="text-xs text-muted-foreground">Email</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.email || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Phone</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.phone || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Website</p>
                    <p className="mt-0.5 break-all text-sm text-foreground">{enterprise.website || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">RERA number</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.rera_number || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Business type</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.business_type || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Company size</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.company_size || '—'}</p>
                  </div>
                  <div className="sm:col-span-2">
                    <p className="text-xs text-muted-foreground">Address</p>
                    <p className="mt-0.5 text-sm text-foreground">{enterprise.address || '—'}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Created</p>
                    <p className="mt-0.5 text-sm text-foreground">{formatDate(enterprise.created_at)}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">Trial ends</p>
                    <p className="mt-0.5 text-sm text-foreground">
                      {enterprise.trial_ends_at ? formatDate(enterprise.trial_ends_at) : '—'}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-3">
              <div>
                <CardTitle>Members</CardTitle>
                <CardDescription>Active and suspended memberships, excluding removed users.</CardDescription>
              </div>
              <Badge variant="neutral">{formatNumber(members.length)} total</Badge>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              <DataTable
                className="rounded-none border-x-0 border-b-0"
                columns={memberColumns}
                rows={members}
                rowKey="id"
                emptyState={<p className="py-6 text-center text-sm text-muted-foreground">No members yet.</p>}
              />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent leads</CardTitle>
              <CardDescription>The latest ten leads captured by this enterprise.</CardDescription>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              <DataTable
                className="rounded-none border-x-0 border-b-0"
                columns={leadColumns}
                rows={data?.recent_leads || []}
                rowKey="id"
                emptyState={<p className="py-6 text-center text-sm text-muted-foreground">No leads yet.</p>}
              />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent campaigns</CardTitle>
              <CardDescription>Marketing activity for this enterprise.</CardDescription>
            </CardHeader>
            <CardContent className="px-0 pb-0">
              <DataTable
                className="rounded-none border-x-0 border-b-0"
                columns={campaignColumns}
                rows={data?.recent_campaigns || []}
                rowKey="id"
                emptyState={<p className="py-6 text-center text-sm text-muted-foreground">No campaigns yet.</p>}
              />
            </CardContent>
          </Card>
        </div>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Plan & usage</CardTitle>
              <CardDescription>
                {plan ? `${plan.name} · ${formatCurrency(plan.price)} / month` : 'No plan assigned'}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {plan ? (
                <>
                  <div className="flex flex-wrap gap-1.5">
                    <Badge variant={plan.advanced_reports ? 'success' : 'neutral'}>
                      Advanced reports
                    </Badge>
                    <Badge variant={plan.meta_integrations ? 'success' : 'neutral'}>
                      Meta integrations
                    </Badge>
                    <Badge variant="outline">{plan.audience}</Badge>
                  </div>
                  {Object.values(usage).map((item) => (
                    <UsageRow key={item.label} item={item} />
                  ))}
                </>
              ) : (
                <p className="text-sm text-muted-foreground">
                  This enterprise has no plan, so quotas are not enforced.
                </p>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Owner</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              <p className="font-semibold text-foreground">{enterprise.owner?.name || '—'}</p>
              <p className="text-muted-foreground">{enterprise.owner?.email || '—'}</p>
              <p className="text-muted-foreground">{enterprise.owner?.phone || '—'}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Subscription</CardTitle>
            </CardHeader>
            <CardContent>
              {subscription ? (
                <div className="space-y-2 text-sm">
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Plan</span>
                    <span className="font-medium text-foreground">{subscription.plan_name || plan?.name || '—'}</span>
                  </div>
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Status</span>
                    <StatusBadge status={subscription.status} />
                  </div>
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Started</span>
                    <span className="text-foreground">{formatDate(subscription.start_date)}</span>
                  </div>
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Expires</span>
                    <span className="text-foreground">{formatDate(subscription.expiry_date)}</span>
                  </div>
                  <div className="flex items-center justify-between gap-3">
                    <span className="text-muted-foreground">Price</span>
                    <span className="font-medium text-foreground">{formatCurrency(subscription.price)}</span>
                  </div>
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">No subscription on record.</p>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent invoices</CardTitle>
            </CardHeader>
            <CardContent>
              {invoiceRows.length === 0 ? (
                <p className="text-sm text-muted-foreground">No invoices yet.</p>
              ) : (
                <ul className="divide-y divide-border">
                  {invoiceRows.map((invoice) => (
                    <li key={invoice.id} className="flex items-center justify-between gap-3 py-2.5">
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-foreground">{invoice.invoice_number}</p>
                        <p className="text-xs text-muted-foreground">{formatDate(invoice.created_at)}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-foreground">{formatCurrency(invoice.total)}</p>
                        <StatusBadge status={invoice.status} />
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </CardContent>
          </Card>

          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <GlobeAltIcon className="h-4 w-4" />
            Platform admins have read-only oversight. Manage enterprise data from the workspace.
          </div>
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <MegaphoneIcon className="h-4 w-4" />
            <CreditCardIcon className="h-4 w-4" />
            Suspend or resume the account without deleting any data.
          </div>
        </div>
      </div>

      <ConfirmDialog
        open={Boolean(confirmAction)}
        onClose={() => setConfirmAction(null)}
        onConfirm={() => actionMutation.mutate(confirmAction)}
        loading={actionMutation.isPending}
        title={
          confirmAction === 'suspend'
            ? `Suspend ${enterprise.name}?`
            : `Resume ${enterprise.name}?`
        }
        description={
          confirmAction === 'suspend'
            ? 'All members will be blocked from signing in and using the workspace. Data is preserved and the account can be resumed later.'
            : 'Enterprise members will regain access to their workspace immediately.'
        }
        confirmLabel={confirmAction === 'suspend' ? 'Suspend enterprise' : 'Resume enterprise'}
        variant={confirmAction === 'suspend' ? 'danger' : 'primary'}
      />
    </div>
  )
}
