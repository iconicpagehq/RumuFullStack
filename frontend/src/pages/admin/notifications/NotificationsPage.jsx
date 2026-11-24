import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { BellIcon, CheckIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { PageLoader } from '@/components/ui/Spinner'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { useNotificationStore } from '@/stores/notificationStore'
import { PAGE_SIZE } from '@/utils/constants'
import { cn } from '@/utils/cn'
import { formatDateTime, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

export const NotificationsPage = () => {
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const setUnread = useNotificationStore((state) => state.setUnread)
  const decrementUnread = useNotificationStore((state) => state.decrementUnread)
  const unreadCount = useNotificationStore((state) => state.unread)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'notifications', page],
    queryFn: async () => {
      const response = await adminApi.notifications.list({ page, per_page: PAGE_SIZE })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const readMutation = useMutation({
    mutationFn: (notification) => adminApi.notifications.markRead(notification.id),
    onSuccess: (_, notification) => {
      if (!notification.is_read) decrementUnread()
      queryClient.invalidateQueries({ queryKey: ['admin', 'notifications'] })
      queryClient.invalidateQueries({ queryKey: ['notification-count', 'admin'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not mark the notification as read.')),
  })

  const readAllMutation = useMutation({
    mutationFn: () => adminApi.notifications.markAllRead(),
    onSuccess: () => {
      toast.success('All notifications marked as read')
      setUnread(0)
      queryClient.invalidateQueries({ queryKey: ['admin', 'notifications'] })
      queryClient.invalidateQueries({ queryKey: ['notification-count', 'admin'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update notifications.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div>
      <PageHeader
        title="Notifications"
        description="Platform activity and system alerts."
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

      <Card>
        {data?.rows?.length ? (
          <div className="divide-y divide-border">
            {data.rows.map((notification) => (
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
                      {titleCase(notification.type?.replace(/_/g, ' ') || 'system')}
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
            title="No notifications"
            description="You are all caught up."
          />
        )}
      </Card>

      <Pagination
        page={data?.meta?.current_page || 1}
        lastPage={data?.meta?.last_page || 1}
        total={data?.meta?.total || 0}
        perPage={data?.meta?.per_page || PAGE_SIZE}
        onPageChange={setPage}
      />
    </div>
  )
}
