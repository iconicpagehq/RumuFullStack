import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { BellIcon, CheckIcon } from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { Tabs } from '@/components/ui/Tabs'
import { useNotificationStore } from '@/stores/notificationStore'
import { cn } from '@/utils/cn'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDateTime, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const TABS = [
  { key: 'all', label: 'All' },
  { key: 'unread', label: 'Unread' },
]

export const NotificationsPage = () => {
  const queryClient = useQueryClient()
  const setUnread = useNotificationStore((state) => state.setUnread)
  const decrementUnread = useNotificationStore((state) => state.decrementUnread)
  const [tab, setTab] = useState('all')
  const [page, setPage] = useState(1)

  useEffect(() => {
    setPage(1)
  }, [tab])

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'notifications', { tab, page }],
    queryFn: async () => {
      const response = await enterpriseApi.notifications.list({
        page,
        per_page: PAGE_SIZE,
        unread: tab === 'unread' ? 1 : undefined,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
  })

  const unreadCount = data?.meta?.unread_count ?? 0

  const readMutation = useMutation({
    mutationFn: (notification) => enterpriseApi.notifications.markRead(notification.id),
    onSuccess: (_, notification) => {
      if (!notification.is_read) decrementUnread()
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'notifications'] })
      queryClient.invalidateQueries({ queryKey: ['notification-count', 'enterprise'] })
    },
    onError: (readError) => {
      toast.error(getErrorMessage(readError, 'Could not mark the notification as read.'))
    },
  })

  const readAllMutation = useMutation({
    mutationFn: () => enterpriseApi.notifications.markAllRead(),
    onSuccess: () => {
      toast.success('All notifications marked as read')
      setUnread(0)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'notifications'] })
      queryClient.invalidateQueries({ queryKey: ['notification-count', 'enterprise'] })
    },
    onError: (readAllError) => {
      toast.error(getErrorMessage(readAllError, 'Could not update notifications.'))
    },
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const rows = data?.rows || []

  return (
    <div>
      <PageHeader
        title="Notifications"
        description="Lead assignments, task reminders, visit updates and workspace alerts."
        actions={
          <Button
            size="sm"
            variant="outline"
            onClick={() => readAllMutation.mutate()}
            loading={readAllMutation.isPending}
            disabled={unreadCount === 0}
          >
            <CheckIcon className="h-4 w-4" />
            Mark all read
          </Button>
        }
      />

      <Tabs
        tabs={TABS.map((item) =>
          item.key === 'unread' ? { ...item, count: unreadCount } : item,
        )}
        value={tab}
        onChange={setTab}
        className="mb-4"
      />

      <Card>
        {rows.length ? (
          <div className="divide-y divide-border">
            {rows.map((notification) => (
              <div
                key={notification.id}
                className={cn(
                  'flex items-start gap-3 px-5 py-4 transition',
                  !notification.is_read && 'bg-primary/[0.03]',
                )}
              >
                <span
                  className={cn(
                    'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                    notification.is_read
                      ? 'bg-muted text-muted-foreground'
                      : 'bg-primary/10 text-highlight',
                  )}
                >
                  <BellIcon className="h-4 w-4" />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <p className="text-sm font-semibold text-foreground">{notification.title}</p>
                    <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                      {titleCase(notification.type || 'system')}
                    </span>
                    {!notification.is_read && <span className="h-1.5 w-1.5 rounded-full bg-primary" />}
                  </div>
                  <p className="mt-0.5 text-sm text-muted-foreground">{notification.message}</p>
                  <p className="mt-1 text-[11px] text-muted-foreground">
                    {formatDateTime(notification.created_at)}
                  </p>
                </div>
                {!notification.is_read && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => readMutation.mutate(notification)}
                    loading={readMutation.isPending && readMutation.variables?.id === notification.id}
                  >
                    Mark read
                  </Button>
                )}
              </div>
            ))}
          </div>
        ) : (
          <EmptyState
            icon={BellIcon}
            title={tab === 'unread' ? 'No unread notifications' : 'No notifications yet'}
            description={
              tab === 'unread'
                ? 'You have read every notification.'
                : 'Assignments, reminders and workspace alerts will appear here.'
            }
          />
        )}
      </Card>

      {rows.length > 0 && (
        <Pagination
          page={data?.meta?.current_page || 1}
          lastPage={data?.meta?.last_page || 1}
          total={data?.meta?.total || 0}
          perPage={data?.meta?.per_page || PAGE_SIZE}
          onPageChange={setPage}
        />
      )}
    </div>
  )
}
