import {
  ArrowTrendingUpIcon,
  ArrowsRightLeftIcon,
  BellIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ChartBarIcon,
  ClipboardDocumentCheckIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  EnvelopeIcon,
  HomeIcon,
  PhoneIcon,
  RectangleStackIcon,
  ShieldCheckIcon,
  Squares2X2Icon,
  UserGroupIcon,
  UsersIcon,
  ChatBubbleLeftRightIcon,
  HeartIcon,
  SparklesIcon,
  ShareIcon,
} from '@heroicons/react/24/outline'

export const enterpriseNavigation = (role, options = {}) => {
  const isAdmin = role === 'admin'
  const isManager = role === 'manager'
  const isEmployee = role === 'employee'
  const canManageTeam = isAdmin || isManager
  const canViewHierarchy = options.canViewHierarchy ?? canManageTeam
  const hasReports = options.hasReports ?? false
  const canViewMembers = canManageTeam || hasReports

  const sections = [
    {
      label: 'Workspace',
      items: [
        { label: 'Overview', to: '/enterprise/dashboard', icon: HomeIcon },
        {
          label: 'Inbox',
          to: '/enterprise/social/inbox',
          icon: ChatBubbleLeftRightIcon,
          children: [
            { label: 'Inbox', to: '/enterprise/social/inbox' },
            { label: 'Comments', to: '/enterprise/social/comments' },
          ],
        },
        { label: isEmployee ? 'My leads' : 'Leads', to: '/enterprise/leads', icon: UsersIcon },
        {
          label: 'Calls',
          to: '/enterprise/calls',
          icon: PhoneIcon,
          children: [
            { label: 'Call Logs', to: '/enterprise/calls/logs' },
            { label: 'Analytics', to: '/enterprise/calls/analytics' },
          ],
        },
        {
          label: 'Follow up',
          to: '/enterprise/tasks',
          icon: ClipboardDocumentCheckIcon,
          children: [
            { label: 'Follow ups', to: '/enterprise/tasks/list' },
            { label: 'Analytics', to: '/enterprise/tasks/analytics' },
          ],
        },
        {
          label: 'Site visits',
          to: '/enterprise/site-visits',
          icon: CalendarDaysIcon,
          children: [
            { label: 'Site visits', to: '/enterprise/site-visits/list' },
            { label: 'Analytics', to: '/enterprise/site-visits/analytics' },
          ],
        },
        {
          label: 'Customers',
          to: '/enterprise/customers',
          icon: UserGroupIcon,
          children: [
            { label: 'Customer List', to: '/enterprise/customers/list' },
            { label: 'Analytics', to: '/enterprise/customers/analytics' },
          ],
        },
      ],
    },
    {
      label: 'Sales',
      items: [
        { label: 'Inventory', to: '/enterprise/inventory', icon: BuildingOffice2Icon },
        { label: 'Campaigns', to: '/enterprise/campaigns', icon: RectangleStackIcon },
        { label: 'Targets', to: '/enterprise/targets', icon: ArrowTrendingUpIcon },
        { label: 'Reports', to: '/enterprise/reports', icon: ChartBarIcon },
      ],
    },
    {
      label: 'Team',
      items: [
        {
          label: isEmployee ? 'My team' : 'Team',
          to: '/enterprise/teams',
          icon: UserGroupIcon,
        },
        ...(canViewHierarchy
          ? [{ label: 'Organization', to: '/enterprise/organization', icon: Squares2X2Icon }]
          : []),
        ...(canViewMembers
          ? [{ label: 'Members', to: '/enterprise/users', icon: UsersIcon }]
          : []),
        ...(canManageTeam
          ? [
              {
                label: 'Lead distribution',
                to: '/enterprise/leads/distribute',
                icon: ArrowsRightLeftIcon,
              },
            ]
          : []),
        ...(isAdmin
          ? [{ label: 'Invitations', to: '/enterprise/invitations', icon: EnvelopeIcon }]
          : []),
      ],
    },
    {
      label: 'Account',
      items: [
        {
          label: 'Notifications',
          to: '/enterprise/notifications',
          icon: BellIcon,
          badgeKey: 'unread',
        },
        ...(isAdmin
          ? [{ label: 'Billing', to: '/enterprise/billing', icon: CreditCardIcon }]
          : []),
        { label: 'Settings', to: '/enterprise/settings', icon: Cog6ToothIcon },
        ...(isAdmin
          ? [{ label: 'Audit log', to: '/enterprise/audit-logs', icon: ShieldCheckIcon }]
          : []),
      ],
    },
  ]

  return sections
}
