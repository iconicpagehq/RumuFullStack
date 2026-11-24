export const ENTERPRISE_STAGES = [
  { value: 'new', label: 'New' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'site_visit_scheduled', label: 'Site visit scheduled' },
  { value: 'site_visit_completed', label: 'Site visit completed' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'won', label: 'Won' },
  { value: 'lost', label: 'Lost' },
  { value: 'on_hold', label: 'On hold' },
]

export const STAGE_TRANSITIONS = {
  new: ['contacted', 'qualified', 'site_visit_scheduled', 'lost', 'on_hold'],
  contacted: ['qualified', 'site_visit_scheduled', 'lost', 'on_hold'],
  qualified: ['site_visit_scheduled', 'negotiation', 'won', 'lost', 'on_hold'],
  site_visit_scheduled: ['site_visit_completed', 'negotiation', 'lost', 'on_hold'],
  site_visit_completed: ['negotiation', 'won', 'lost', 'on_hold'],
  negotiation: ['won', 'lost', 'on_hold'],
  on_hold: ['contacted', 'qualified', 'site_visit_scheduled', 'lost'],
  won: [],
  lost: [],
}

export const ENTERPRISE_SOURCES = [
  { value: 'manual', label: 'Manual' },
  { value: 'csv', label: 'CSV import' },
  { value: 'meta', label: 'Meta ads' },
  { value: 'website', label: 'Website' },
  { value: 'walk_in', label: 'Walk-in' },
  { value: 'referral', label: 'Referral' },
  { value: 'other', label: 'Other' },
]

export const LEAD_PRIORITIES = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]

export const TASK_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
]

export const VISIT_STATUSES = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'no_show', label: 'No show' },
]

export const TARGET_METRICS = [
  { value: 'leads_assigned', label: 'Leads assigned', format: 'number' },
  { value: 'leads_contacted', label: 'Leads contacted', format: 'number' },
  { value: 'leads_qualified', label: 'Leads qualified', format: 'number' },
  { value: 'site_visits_completed', label: 'Site visits completed', format: 'number' },
  { value: 'deals_won', label: 'Deals won', format: 'number' },
  { value: 'deal_value', label: 'Deal value', format: 'currency' },
  { value: 'revenue_received', label: 'Revenue received', format: 'currency' },
]

export const ENTERPRISE_ROLES = ['admin', 'manager', 'employee']

export const roleLabel = (role) => {
  if (role === 'admin') return 'Company admin'
  if (role === 'manager') return 'Manager'
  if (role === 'employee') return 'Employee'
  return 'Member'
}

export const enterprisePermissions = (role, hierarchy = {}) => {
  const isAdmin = role === 'admin'
  const isManager = role === 'manager'
  const isEmployee = role === 'employee'
  const hasReports = Boolean(hierarchy.has_reports ?? hierarchy.hasReports)
  const isLeadEmployee = Boolean(hierarchy.is_lead_employee ?? hierarchy.isLeadEmployee) || (isEmployee && hasReports)
  const canViewHierarchy = Boolean(hierarchy.can_view_hierarchy ?? hierarchy.canViewHierarchy ?? (isAdmin || isManager || hasReports))
  const scope = hierarchy.scope || (isAdmin ? 'all' : isManager || hasReports ? 'subtree' : 'self')

  return {
    isAdmin,
    isManager,
    isEmployee,
    canManageUsers: isAdmin,
    canManageTeams: isAdmin,
    canAssignLeads: !isEmployee || hasReports,
    canManageTargets: !isEmployee,
    canManageCampaigns: !isEmployee,
    canManageInventory: isAdmin,
    canChangeInventoryStatus: true,
    canUpdateUnitStatus: true,
    canExportReports: !isEmployee,
    canViewAudit: isAdmin,
    canManageEnterpriseSettings: isAdmin,
    canViewBilling: isAdmin,
    canManageMeta: isAdmin || isManager,
    canConnectMeta: isAdmin,
    canRemoveMembers: isAdmin,
    canTransferMembers: isAdmin || isManager,
    canViewAllMembers: isAdmin || isManager || hasReports,
    canManageTeam: isAdmin || isManager,
    canManageMembers: isAdmin || isManager,
    canDistributeLeads: isAdmin || isManager,
    canViewHierarchy,
    canManageHierarchy: isAdmin,
    hasReports,
    isLeadEmployee,
    hierarchyScope: scope,
    reportsTo: hierarchy.reports_to ?? hierarchy.reportsTo ?? null,
    directReportsCount: hierarchy.direct_reports_count ?? hierarchy.directReportsCount ?? 0,
    subtreeSize: hierarchy.subtree_size ?? hierarchy.subtreeSize ?? 1,
    reportsActionRequired: isAdmin,
  }
}

export const stageLabel = (stage) =>
  ENTERPRISE_STAGES.find((item) => item.value === stage)?.label || 'New'

export const metricLabel = (metric) =>
  TARGET_METRICS.find((item) => item.value === metric)?.label || metric

export const metricFormat = (metric) =>
  TARGET_METRICS.find((item) => item.value === metric)?.format || 'number'

export const daysLeft = (date) => {
  if (!date) return null
  const diff = new Date(date).getTime() - Date.now()
  return Math.max(0, Math.ceil(diff / 86400000))
}

export const enterpriseHomePath = '/enterprise/dashboard'

export const homePathForRole = (role) => {
  if (role === 'admin') return '/admin/dashboard'
  if (role === 'enterprise') return enterpriseHomePath
  return '/realtor/dashboard'
}

export const canAccessPathForRole = (role, path) => {
  if (!path) return false
  if (role === 'admin') return path.startsWith('/admin')
  if (role === 'enterprise') return path.startsWith('/enterprise')
  return !path.startsWith('/admin') && !path.startsWith('/enterprise')
}
