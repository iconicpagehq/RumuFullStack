import { useEffect } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowRightOnRectangleIcon,
  BellIcon,
  ChevronDownIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  MoonIcon,
  SunIcon,
  UserCircleIcon,
} from '@heroicons/react/24/outline'
import { Avatar } from '@/components/ui/Avatar'
import { Button } from '@/components/ui/Button'
import { Dropdown, DropdownDivider, DropdownItem, DropdownLabel } from '@/components/ui/Dropdown'
import { useAuth, useLogout } from '@/hooks/useAuth'
import { useThemeStore } from '@/stores/themeStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { adminApi } from '@/api/admin'
import { realtorApi } from '@/api/realtor'
import { enterpriseApi } from '@/api/enterprise'
import { roleLabel } from '@/utils/enterprise'

const ThemeToggle = () => {
  const theme = useThemeStore((state) => state.theme)
  const setTheme = useThemeStore((state) => state.setTheme)
  const isDark =
    theme === 'dark' ||
    (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)

  return (
    <Button
      variant="ghost"
      size="icon"
      onClick={() => setTheme(isDark ? 'light' : 'dark')}
      aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
      title={isDark ? 'Light mode' : 'Dark mode'}
      className="h-9 w-9 rounded-full text-muted-foreground hover:text-foreground"
    >
      {isDark ? <SunIcon className="h-[18px] w-[18px]" /> : <MoonIcon className="h-[18px] w-[18px]" />}
    </Button>
  )
}

const NotificationBell = ({ variant }) => {
  const isRealtor = variant === 'realtor'
  const unread = useNotificationStore((state) => (isRealtor ? state.billingUnread : state.unread))
  const setUnread = useNotificationStore((state) => state.setUnread)
  const setBillingUnread = useNotificationStore((state) => state.setBillingUnread)

  const { data } = useQuery({
    queryKey: ['notification-count', variant],
    queryFn: async () => {
      const response =
        variant === 'realtor'
          ? await realtorApi.billing.unreadCount()
          : variant === 'enterprise'
            ? await enterpriseApi.notifications.unreadCount()
            : await adminApi.notifications.unreadCount()
      const payload = response.data?.data
      return Number(isRealtor ? payload?.unread_count : payload?.count) || 0
    },
    refetchInterval: 60000,
    staleTime: 30000,
  })

  useEffect(() => {
    if (typeof data !== 'number') return
    if (isRealtor) setBillingUnread(data)
    else setUnread(data)
  }, [data, isRealtor, setBillingUnread, setUnread])

  const to =
    variant === 'realtor'
      ? '/realtor/billing/notifications'
      : variant === 'enterprise'
        ? '/enterprise/notifications'
        : '/admin/notifications'

  return (
    <Link
      to={to}
      className="relative inline-flex h-9 w-9 items-center justify-center rounded-full text-muted-foreground transition hover:bg-muted hover:text-foreground"
      aria-label="Notifications"
    >
      <BellIcon className="h-[18px] w-[18px]" />
      {unread > 0 && (
        <span className="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[9px] font-semibold leading-none text-primary-foreground ring-2 ring-card">
          {unread > 9 ? '9+' : unread}
        </span>
      )}
    </Link>
  )
}

const planLabel = (user, isAdmin, isEnterprise) => {
  if (isAdmin) return 'Administrator'
  if (isEnterprise) {
    return roleLabel(user?.enterprise_membership?.role) || 'Enterprise member'
  }
  const name = user?.plan?.name
  if (!name) return 'No active plan'
  return /plan$/i.test(name) ? name : `${name} Plan`
}

const UserMenu = () => {
  const { user, isAdmin, isEnterprise } = useAuth()
  const enterpriseRole = user?.enterprise_membership?.role
  const canSeeBilling = !isEnterprise ? !isAdmin : enterpriseRole === 'admin'
  const logout = useLogout()

  const handleLogout = async (everywhere = false) => {
    await logout({ everywhere })
    toast.success(everywhere ? 'Signed out of all devices' : 'Signed out')
  }

  return (
    <Dropdown
      trigger={
        <button
          type="button"
          className="flex items-center gap-2.5 rounded-full border border-border bg-card py-1 pl-1 pr-2.5 text-left shadow-sm transition hover:bg-muted/40"
        >
          <Avatar
            src={user?.avatar_url}
            name={user?.name}
            size="sm"
            className="ring-1 ring-primary/15 dark:ring-highlight/20"
          />
          <span className="hidden min-w-0 sm:block">
            <span className="block max-w-[150px] truncate text-[13px] font-semibold leading-4 text-foreground">
              {user?.name || 'Account'}
            </span>
            <span className="block text-[11px] leading-4 text-muted-foreground">
              {planLabel(user, isAdmin, isEnterprise)}
            </span>
          </span>
          <ChevronDownIcon className="hidden h-3.5 w-3.5 text-muted-foreground sm:block" />
        </button>
      }
    >
      <DropdownLabel>{user?.email}</DropdownLabel>
      <DropdownDivider />
      {!isAdmin && !isEnterprise && (
        <DropdownItem to="/realtor/profile" icon={UserCircleIcon}>
          Profile
        </DropdownItem>
      )}
      {isEnterprise && (
        <DropdownItem to="/enterprise/settings" icon={UserCircleIcon}>
          Profile
        </DropdownItem>
      )}
      {canSeeBilling && (
        <DropdownItem to={isEnterprise ? '/enterprise/billing' : '/realtor/billing'} icon={CreditCardIcon}>
          Billing
        </DropdownItem>
      )}
      <DropdownItem
        to={
          isAdmin
            ? '/admin/settings'
            : isEnterprise
              ? '/enterprise/settings'
              : '/realtor/settings'
        }
        icon={Cog6ToothIcon}
      >
        Settings
      </DropdownItem>
      <DropdownDivider />
      <DropdownItem icon={ArrowRightOnRectangleIcon} onClick={() => handleLogout(false)}>
        Sign out
      </DropdownItem>
      <DropdownItem danger icon={ArrowRightOnRectangleIcon} onClick={() => handleLogout(true)}>
        Sign out everywhere
      </DropdownItem>
    </Dropdown>
  )
}

export const HeaderActions = () => {
  const { isAdmin, isEnterprise } = useAuth()

  return (
    <div className="flex items-center gap-2">
      <div className="flex items-center rounded-full border border-border bg-card p-0.5 shadow-sm">
        <ThemeToggle />
        <NotificationBell
          variant={isAdmin ? 'admin' : isEnterprise ? 'enterprise' : 'realtor'}
        />
      </div>
      <UserMenu />
    </div>
  )
}
