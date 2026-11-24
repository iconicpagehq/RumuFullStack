import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  BanknotesIcon,
  BellIcon,
  CheckIcon,
  DocumentTextIcon,
  SparklesIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { useNotificationStore } from '@/stores/notificationStore'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { BILLING_NOTIFICATION_TYPES } from '@/utils/constants'
import { cn } from '@/utils/cn'
import { formatCurrency, formatDate, timeAgo, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const typeLabel = (type) =>
  BILLING_NOTIFICATION_TYPES.find((item) => item.value === type)?.label || titleCase(type)

const NotificationIcon = ({ type }) => {
  if (type?.startsWith('payment')) return BanknotesIcon
  if (type?.startsWith('invoice')) return DocumentTextIcon
  return SparklesIcon
}

const notificationSummary = (notification) => {
  const parts = []
  if (notification.subscription?.plan_name) {
    parts.push(`${notification.subscription.plan_name} plan`)
  }
  if (notification.payment) {
    parts.push(`${formatCurrency(notification.payment.amount)} ${notification.payment.payment_status}`)
  }
  if (notification.invoice) {
    parts.push(`Invoice ${notification.invoice.invoice_number}`)
  }
  return parts.join(' · ') || 'Billing update'
}

export const BillingNotificationsPage = ({ hideHeader = false }) => {
  const queryClient = useQueryClient()
  const setBillingUnread = useNotificationStore((state) => state.setBillingUnread)
  const decrementBillingUnread = useNotificationStore((state) => state.decrementBillingUnread)
  const [filter, setFilter] = useState('all')
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['realtor', 'billing', 'notifications', { page, filter, type }],
    queryFn: async () => {
      const response = await realtorApi.billing.notifications({
        page,
        unread: filter === 'unread' ? 1 : undefined,
        type: type || undefined,
      })
      return {
        notifications: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    placeholderData: (previous) => previous,
  })

  const unreadCount = data?.meta?.unread_count || 0

  useEffect(() => {
    if (data) setBillingUnread(data.meta?.unread_count || 0)
  }, [data, setBillingUnread])

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['realtor', 'billing', 'notifications'] })
  }

  const markRead = useMutation({
    mutationFn: (id) => realtorApi.billing.markRead(id),
    onSuccess: () => {
      decrementBillingUnread()
      invalidate()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not mark the notification as read.')),
  })

  const markAllRead = useMutation({
    mutationFn: () => realtorApi.billing.markAllRead(),
    onSuccess: () => {
      setBillingUnread(0)
      invalidate()
      toast.success('All billing notifications marked as read')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not mark all as read.')),
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const notifications = data?.notifications || []
  const meta = data?.meta || {}

  return (
    <div className="mx-auto max-w-4xl">
      {!hideHeader && (
        <PageHeader
          breadcrumbs={[{ label: 'Billing', to: '/realtor/billing' }, { label: 'Notifications' }]}
          title="Billing notifications"
          description="Payment receipts, invoice updates and subscription reminders."
          actions={
            <Button
              size="sm"
              variant="outline"
              onClick={() => markAllRead.mutate()}
              loading={markAllRead.isPending}
              disabled={unreadCount === 0}
            >
              <CheckIcon className="h-4 w-4" />
              Mark all as read
            </Button>
          }
        />
      )}

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <Tabs
          tabs={[
            { key: 'all', label: 'All' },
            { key: 'unread', label: 'Unread', count: unreadCount },
          ]}
          value={filter}
          onChange={(value) => {
            setFilter(value)
            setPage(1)
          }}
          className="sm:min-w-64"
        />
        <select
          value={type}
          onChange={(event) => {
            setType(event.target.value)
            setPage(1)
          }}
          className="h-9 rounded-lg border border-border bg-card px-2.5 text-xs font-medium text-foreground outline-none focus:border-highlight"
        >
          <option value="">All notification types</option>
          {BILLING_NOTIFICATION_TYPES.map((item) => (
            <option key={item.value} value={item.value}>
              {item.label}
            </option>
          ))}
        </select>
      </div>

      <Card>
        {isLoading && notifications.length === 0 ? (
          <div className="divide-y divide-border">
            {[1, 2, 3, 4].map((row) => (
              <div key={row} className="flex gap-3 p-4">
                <div className="skeleton h-9 w-9 rounded-lg" />
                <div className="flex-1 space-y-2">
                  <div className="skeleton h-4 w-40 rounded" />
                  <div className="skeleton h-3 w-64 rounded" />
                </div>
              </div>
            ))}
          </div>
        ) : notifications.length === 0 ? (
          <EmptyState
            icon={BellIcon}
            title={filter === 'unread' ? 'You are all caught up' : 'No billing notifications yet'}
            description={
              filter === 'unread'
                ? 'There are no unread billing notifications.'
                : 'Notifications about payments and invoices will appear here.'
            }
            className="py-12"
          />
        ) : (
          <div className="divide-y divide-border">
            {notifications.map((notification) => {
              const Icon = NotificationIcon(notification.type)
              return (
                <div
                  key={notification.id}
                  className={cn(
                    'flex gap-3 p-4 sm:p-5',
                    !notification.is_read && 'bg-primary/[0.035]',
                  )}
                >
                  <span
                    className={cn(
                      'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                      notification.type?.startsWith('payment')
                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                        : notification.type?.startsWith('invoice')
                          ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400'
                          : 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
                    )}
                  >
                    <Icon className="h-4 w-4" />
                  </span>

                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                          {typeLabel(notification.type)}
                          {!notification.is_read && (
                            <span className="h-1.5 w-1.5 rounded-full bg-primary" aria-label="Unread" />
                          )}
                        </p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                          {notificationSummary(notification)}
                          {notification.channel && ` · via ${titleCase(notification.channel)}`}
                        </p>
                        {notification.error_message && (
                          <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                            {notification.error_message}
                          </p>
                        )}
                        <div className="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground">
                          <span title={formatDate(notification.created_at)}>
                            {timeAgo(notification.created_at)}
                          </span>
                          {notification.invoice && (
                            <Link
                              to={`/realtor/billing/invoices/${notification.invoice.id}`}
                              className="font-semibold text-highlight hover:underline"
                            >
                              View invoice
                            </Link>
                          )}
                          {notification.status && (
                            <span className="capitalize">{notification.status}</span>
                          )}
                        </div>
                      </div>
                      {!notification.is_read && (
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => markRead.mutate(notification.id)}
                          loading={markRead.isPending && markRead.variables === notification.id}
                        >
                          Mark read
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        )}

        <div className="px-4">
          <Pagination
            page={meta.current_page || 1}
            lastPage={meta.last_page || 1}
            total={meta.total || 0}
            perPage={meta.per_page || 15}
            onPageChange={setPage}
          />
        </div>
      </Card>
    </div>
  )
}
