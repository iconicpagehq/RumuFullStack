import { adminNavigation } from '@/utils/navigation'
import { DashboardLayout } from './DashboardLayout'

export const AdminLayout = () => (
  <DashboardLayout sections={adminNavigation} homePath="/admin/dashboard" />
)
