import { Link, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeftIcon, EnvelopeIcon, PhoneIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { DetailItem } from '@/components/shared/DetailItem'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { PageLoader } from '@/components/ui/Spinner'
import { formatDate, formatDateTime, formatNumber, formatPhone, titleCase } from '@/utils/formatters'

export const UserDetailPage = () => {
  const { id } = useParams()
  const { data: user, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'users', id],
    queryFn: async () => {
      const response = await adminApi.users.get(id)
      return response.data?.data
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div>
      <PageHeader
        title="User profile"
        breadcrumbs={[
          { label: 'Users', to: '/admin/users' },
          { label: user?.name || 'Profile' },
        ]}
        actions={
          <Link to="/admin/users">
            <Button variant="outline" size="sm">
              <ArrowLeftIcon className="h-4 w-4" />
              Back to users
            </Button>
          </Link>
        }
      />

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardContent className="pt-5">
            <div className="flex flex-wrap items-start gap-4">
              <Avatar src={user?.avatar_url} name={user?.name} size="xl" />
              <div className="min-w-0 flex-1">
                <h2 className="text-lg font-bold text-foreground">{user?.name}</h2>
                <p className="text-sm text-muted-foreground">{user?.company || 'Independent'}</p>
                <div className="mt-2 flex flex-wrap items-center gap-2">
                  <Badge variant={user?.role === 'admin' ? 'purple' : 'info'}>{titleCase(user?.role)}</Badge>
                  {user?.subscription_suspended ? (
                    <StatusBadge status="suspended" />
                  ) : (
                    <StatusBadge status="active" />
                  )}
                  {user?.plan && <StatusBadge status={user.plan.name} variant="primary" />}
                </div>
              </div>
              <div className="text-right text-xs text-muted-foreground">
                <p>Joined {formatDate(user?.created_at)}</p>
                <p className="mt-0.5">{formatNumber(user?.campaigns_count || 0)} campaigns</p>
              </div>
            </div>

            <dl className="mt-6 grid gap-5 sm:grid-cols-2">
              <DetailItem label="Email">
                <span className="inline-flex items-center gap-1.5">
                  <EnvelopeIcon className="h-3.5 w-3.5 text-muted-foreground" />
                  {user?.email}
                </span>
              </DetailItem>
              <DetailItem label="Phone">
                <span className="inline-flex items-center gap-1.5">
                  <PhoneIcon className="h-3.5 w-3.5 text-muted-foreground" />
                  {formatPhone(user?.phone)}
                </span>
              </DetailItem>
              <DetailItem label="Timezone">{user?.timezone || '—'}</DetailItem>
              <DetailItem label="Language">{user?.language || '—'}</DetailItem>
              <DetailItem label="Referral code">{user?.referral_code || '—'}</DetailItem>
              <DetailItem label="Theme">{titleCase(user?.theme || 'auto')}</DetailItem>
            </dl>

            {user?.subscription_suspended && (
              <div className="mt-5 rounded-lg border border-destructive/20 bg-destructive/5 p-3">
                <p className="text-xs font-semibold text-destructive">
                  Suspended {formatDateTime(user.subscription_suspended_at)}
                </p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                  {user.subscription_suspend_reason || 'No reason provided.'}
                </p>
              </div>
            )}

            {user?.bio && (
              <div className="mt-5">
                <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Bio</p>
                <p className="mt-1 text-sm text-foreground">{user.bio}</p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Account</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Plan</span>
              <span className="font-semibold text-foreground">{user?.plan?.name || 'No plan'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Max campaigns</span>
              <span className="font-semibold text-foreground">{user?.plan?.max_campaigns ?? '—'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Leads / campaign</span>
              <span className="font-semibold text-foreground">{user?.plan?.max_leads_per_campaign ?? '—'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Email notifications</span>
              <span className="font-semibold text-foreground">{user?.email_notifications ? 'On' : 'Off'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Marketing emails</span>
              <span className="font-semibold text-foreground">{user?.marketing_emails ? 'On' : 'Off'}</span>
            </div>
            <div className="flex items-center justify-between gap-3">
              <span className="text-muted-foreground">Public profile</span>
              <span className="font-semibold text-foreground">{user?.public_profile ? 'On' : 'Off'}</span>
            </div>
            <div className="flex flex-wrap gap-1.5 border-t border-border pt-3">
              {user?.google_linked && <Badge variant="success">Google linked</Badge>}
              {user?.facebook_linked && <Badge variant="info">Facebook linked</Badge>}
              {!user?.google_linked && !user?.facebook_linked && (
                <span className="text-xs text-muted-foreground">No social accounts linked.</span>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
