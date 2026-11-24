import {
  ArrowPathIcon,
  ArrowTrendingUpIcon,
  BanknotesIcon,
  BellIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  BuildingStorefrontIcon,
  CalendarDaysIcon,
  ChartBarIcon,
  ChartPieIcon,
  ClipboardDocumentCheckIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  CurrencyRupeeIcon,
  DocumentChartBarIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  HomeIcon,
  MegaphoneIcon,
  PhoneIcon,
  ShareIcon,
  ShieldCheckIcon,
  SparklesIcon,
  Squares2X2Icon,
  TicketIcon,
  UserCircleIcon,
  UserGroupIcon,
  UsersIcon,
  ChatBubbleLeftRightIcon,
  HeartIcon,
} from '@heroicons/react/24/outline'

export const realtorNavigation = [
  {
    label: 'Workspace',
    items: [
      { label: 'Dashboard', to: '/realtor/dashboard', icon: HomeIcon },
      {
        label: 'Inbox',
        to: '/realtor/social/inbox',
        icon: ChatBubbleLeftRightIcon,
        children: [
          { label: 'Inbox', to: '/realtor/social/inbox' },
          { label: 'Comments', to: '/realtor/social/comments' },
        ],
      },
      { label: 'Leads', to: '/realtor/leads', icon: UserGroupIcon },
      {
        label: 'Calls',
        to: '/realtor/calls',
        icon: PhoneIcon,
        children: [
          { label: 'Call Logs', to: '/realtor/calls/logs' },
          { label: 'Analytics', to: '/realtor/calls/analytics' },
        ],
      },
      { label: 'My Campaigns', to: '/realtor/campaigns', icon: MegaphoneIcon },
      {
        label: 'Follow up',
        to: '/realtor/tasks',
        icon: ClipboardDocumentCheckIcon,
        children: [
          { label: 'Follow ups', to: '/realtor/tasks/list' },
          { label: 'Analytics', to: '/realtor/tasks/analytics' },
        ],
      },
      {
        label: 'Site visits',
        to: '/realtor/site-visits',
        icon: CalendarDaysIcon,
        children: [
          { label: 'Site visits', to: '/realtor/site-visits/list' },
          { label: 'Analytics', to: '/realtor/site-visits/analytics' },
        ],
      },
      {
        label: 'Customers',
        to: '/realtor/customers',
        icon: UsersIcon,
        children: [
          { label: 'Customer List', to: '/realtor/customers/list' },
          { label: 'Analytics', to: '/realtor/customers/analytics' },
        ],
      },
      { label: 'Inventory', to: '/realtor/inventory', icon: BuildingOffice2Icon },
    ],
  },
]

export const adminNavigation = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', to: '/admin/dashboard', icon: HomeIcon },
      { label: 'Analytics', to: '/admin/analytics', icon: ChartBarIcon },
      { label: 'Leads', to: '/admin/leads', icon: UserGroupIcon },
      { label: 'Users', to: '/admin/users', icon: UsersIcon },
    ],
  },
  {
    label: 'Management',
    items: [
      { label: 'Realtors', to: '/admin/realtors', icon: BriefcaseIcon },
      { label: 'Enterprise accounts', to: '/admin/enterprises', icon: BuildingOffice2Icon },
      { label: 'Plans', to: '/admin/plans', icon: Squares2X2Icon },
      { label: 'Upgrade requests', to: '/admin/upgrade-requests', icon: ArrowTrendingUpIcon },
    ],
  },
  {
    label: 'Billing',
    items: [
      { label: 'Subscriptions', to: '/admin/billing/subscriptions', icon: ArrowPathIcon },
      { label: 'Payments', to: '/admin/billing/payments', icon: BanknotesIcon },
      { label: 'Invoices', to: '/admin/billing/invoices', icon: DocumentTextIcon },
      { label: 'Coupons', to: '/admin/billing/coupons', icon: TicketIcon },
      { label: 'Referrals', to: '/admin/billing/referrals', icon: ShareIcon },
      { label: 'Notification templates', to: '/admin/billing/notification-templates', icon: DocumentDuplicateIcon },
      { label: 'Billing notifications', to: '/admin/billing/notifications', icon: BellIcon },
      { label: 'Billing settings', to: '/admin/billing/settings', icon: Cog6ToothIcon },
    ],
  },
  {
    label: 'Insights',
    items: [
      { label: 'Revenue', to: '/admin/analytics/revenue', icon: CurrencyRupeeIcon },
      { label: 'Payment analytics', to: '/admin/analytics/payments', icon: ChartPieIcon },
      { label: 'Reports', to: '/admin/analytics/reports', icon: DocumentChartBarIcon },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Broadcast', to: '/admin/broadcasts', icon: MegaphoneIcon },
      { label: 'Notifications', to: '/admin/notifications', icon: BuildingStorefrontIcon },
      { label: 'Audit log', to: '/admin/audit-logs', icon: ShieldCheckIcon },
      { label: 'Settings', to: '/admin/settings', icon: Cog6ToothIcon },
    ],
  },
]
