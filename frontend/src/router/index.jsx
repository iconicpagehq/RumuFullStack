import SocialDashboard from '@/modules/social/pages/SocialDashboard'
import SocialAccounts from '@/modules/social/pages/SocialAccounts'
import SocialInbox from '@/modules/social/pages/SocialInbox'
import SocialComments from '@/modules/social/pages/SocialComments'
import SocialPosts from '@/modules/social/pages/SocialPosts'
import SocialMentions from '@/modules/social/pages/SocialMentions'
import SocialAnalytics from '@/modules/social/pages/SocialAnalytics'

import { Navigate, Route, Routes } from 'react-router-dom'
import { AdminLayout } from '@/components/layout/AdminLayout'
import { AuthLayout } from '@/components/layout/AuthLayout'
import { CheckoutLayout } from '@/components/layout/CheckoutLayout'
import { EnterpriseAuthLayout } from '@/components/layout/EnterpriseAuthLayout'
import { EnterpriseLayout } from '@/components/layout/EnterpriseLayout'
import { RealtorLayout } from '@/components/layout/RealtorLayout'
import { HomeRedirect } from '@/pages/HomeRedirect'
import { NotFoundPage } from '@/pages/NotFoundPage'
import { LoginPage } from '@/pages/auth/LoginPage'
import { RegisterPage } from '@/pages/auth/RegisterPage'
import { SocialCallbackPage } from '@/pages/auth/SocialCallbackPage'
import { CheckoutFailurePage } from '@/pages/checkout/CheckoutFailurePage'
import { CheckoutPage } from '@/pages/checkout/CheckoutPage'
import { CheckoutSuccessPage } from '@/pages/checkout/CheckoutSuccessPage'
import { PrivacyPolicyPage } from '@/pages/legal/PrivacyPolicyPage'
import { ContactPage } from '@/pages/legal/ContactPage'
import { TermsOfServicePage } from '@/pages/legal/TermsOfServicePage'
import { DataDeletionPage } from '@/pages/legal/DataDeletionPage'
import { AuditLogsPage as EnterpriseAuditLogsPage } from '@/pages/enterprise/AuditLogsPage'
import { BillingPage as EnterpriseBillingPage } from '@/pages/enterprise/BillingPage'
import { CampaignsPage as EnterpriseCampaignsPage } from '@/pages/enterprise/CampaignsPage'
import { CampaignDetailPage as EnterpriseCampaignDetailPage } from '@/pages/enterprise/campaigns/CampaignDetailPage'
import { EnterpriseMetaConnectCallbackPage } from '@/pages/enterprise/campaigns/MetaConnectCallbackPage'
import {
  CallsPage as EnterpriseCallsPage,
  EnterpriseCallsLogsPage,
  EnterpriseCallsAnalyticsPage,
} from '@/pages/enterprise/CallsPage'
import { CustomersPage as EnterpriseCustomersPage } from '@/pages/enterprise/CustomersPage'
import { DashboardPage as EnterpriseDashboardPage } from '@/pages/enterprise/DashboardPage'
import { EnterpriseInvitationAcceptPage } from '@/pages/enterprise/InvitationAcceptPage'
import { InvitationsPage as EnterpriseInvitationsPage } from '@/pages/enterprise/invitations/InvitationsPage'
import { NotificationsPage as EnterpriseNotificationsPage } from '@/pages/enterprise/NotificationsPage'
import { ReportsPage as EnterpriseReportsPage } from '@/pages/enterprise/ReportsPage'
import { SettingsPage as EnterpriseSettingsPage } from '@/pages/enterprise/SettingsPage'
import { SiteVisitsPage as EnterpriseSiteVisitsPage } from '@/pages/enterprise/SiteVisitsPage'
import { TargetsPage as EnterpriseTargetsPage } from '@/pages/enterprise/TargetsPage'
import { TasksPage as EnterpriseTasksPage } from '@/pages/enterprise/TasksPage'
import { ProjectDetailPage as EnterpriseProjectDetailPage } from '@/pages/enterprise/inventory/ProjectDetailPage'
import { ProjectListPage as EnterpriseProjectListPage } from '@/pages/enterprise/inventory/ProjectListPage'
import { LeadDetailPage as EnterpriseLeadDetailPage } from '@/pages/enterprise/leads/LeadDetailPage'
import { LeadDistributionPage as EnterpriseLeadDistributionPage } from '@/pages/enterprise/leads/LeadDistributionPage'
import { LeadListPage as EnterpriseLeadListPage } from '@/pages/enterprise/leads/LeadListPage'
import { MemberDetailPage as EnterpriseMemberDetailPage } from '@/pages/enterprise/teams/MemberDetailPage'
import { MembersPage as EnterpriseMembersPage } from '@/pages/enterprise/teams/MembersPage'
import { OrganizationPage as EnterpriseOrganizationPage } from '@/pages/enterprise/teams/OrganizationPage'
import { TeamDetailPage as EnterpriseTeamDetailPage } from '@/pages/enterprise/teams/TeamDetailPage'
import { TeamsPage as EnterpriseTeamsPage } from '@/pages/enterprise/teams/TeamsPage'
import { DashboardPage } from '@/pages/realtor/DashboardPage'
import { PlansPage } from '@/pages/realtor/PlansPage'
import { ProfilePage } from '@/pages/realtor/ProfilePage'
import { SettingsPage } from '@/pages/realtor/SettingsPage'
import { BillingNotificationsPage } from '@/pages/realtor/billing/BillingNotificationsPage'
import { BillingPage } from '@/pages/realtor/billing/BillingPage'
import { InvoiceDetailPage } from '@/pages/realtor/billing/InvoiceDetailPage'
import {
  CallsPage as RealtorCallsPage,
  CallsLogsPage as RealtorCallsLogsPage,
  CallsAnalyticsPage as RealtorCallsAnalyticsPage,
} from '@/pages/realtor/calls/CallsPage'
import { CampaignDetailPage } from '@/pages/realtor/campaigns/CampaignDetailPage'
import { CampaignFormPage } from '@/pages/realtor/campaigns/CampaignFormPage'
import { CampaignListPage } from '@/pages/realtor/campaigns/CampaignListPage'
import { MetaConnectCallbackPage } from '@/pages/realtor/campaigns/MetaConnectCallbackPage'
import { MetaImportPage } from '@/pages/realtor/campaigns/MetaImportPage'
import { MetaIntegrationPage } from '@/pages/realtor/campaigns/MetaIntegrationPage'
import { CustomerListPage } from '@/pages/realtor/customers/CustomerListPage'
import { ProjectDetailPage } from '@/pages/realtor/inventory/ProjectDetailPage'
import { ProjectFormPage } from '@/pages/realtor/inventory/ProjectFormPage'
import { ProjectListPage } from '@/pages/realtor/inventory/ProjectListPage'
import { LeadDetailPage } from '@/pages/realtor/leads/LeadDetailPage'
import { LeadFormPage } from '@/pages/realtor/leads/LeadFormPage'
import { LeadBoardPage } from '@/pages/realtor/leads/LeadBoardPage'
import { TasksPage as RealtorTasksPage } from '@/pages/realtor/tasks/TasksPage'
import { SiteVisitsPage as RealtorSiteVisitsPage } from '@/pages/realtor/site-visits/SiteVisitsPage'
import { DashboardPage as AdminDashboardPage } from '@/pages/admin/DashboardPage'
import { LeadsPage as AdminLeadsPage } from '@/pages/admin/LeadsPage'
import { PlansPage as AdminPlansPage } from '@/pages/admin/PlansPage'
import { UpgradeRequestsPage } from '@/pages/admin/UpgradeRequestsPage'
import { UserDetailPage as AdminUserDetailPage } from '@/pages/admin/UserDetailPage'
import { UsersPage as AdminUsersPage } from '@/pages/admin/UsersPage'
import { AuditLogsPage as AdminAuditLogsPage } from '@/pages/admin/audit/AuditLogsPage'
import { BroadcastsPage as AdminBroadcastsPage } from '@/pages/admin/broadcasts/BroadcastsPage'
import { EnterprisesPage as AdminEnterprisesPage } from '@/pages/admin/enterprises/EnterprisesPage'
import { EnterpriseDetailPage as AdminEnterpriseDetailPage } from '@/pages/admin/enterprises/EnterpriseDetailPage'
import { AnalyticsDashboardPage } from '@/pages/admin/analytics/AnalyticsDashboardPage'
import { AnalyticsPaymentsPage } from '@/pages/admin/analytics/PaymentsPage'
import { ReportsPage } from '@/pages/admin/analytics/ReportsPage'
import { RevenuePage } from '@/pages/admin/analytics/RevenuePage'
import { BillingNotificationsPage as AdminBillingNotificationsPage } from '@/pages/admin/billing/BillingNotificationsPage'
import { BillingSettingsPage } from '@/pages/admin/billing/BillingSettingsPage'
import { CouponsPage } from '@/pages/admin/billing/CouponsPage'
import { InvoiceDetailPage as AdminInvoiceDetailPage } from '@/pages/admin/billing/InvoiceDetailPage'
import { InvoicesPage } from '@/pages/admin/billing/InvoicesPage'
import { NotificationTemplatesPage } from '@/pages/admin/billing/NotificationTemplatesPage'
import { PaymentsPage as AdminPaymentsPage } from '@/pages/admin/billing/PaymentsPage'
import { ReferralsPage } from '@/pages/admin/billing/ReferralsPage'
import { SubscriptionsPage } from '@/pages/admin/billing/SubscriptionsPage'
import { NotificationsPage as AdminNotificationsPage } from '@/pages/admin/notifications/NotificationsPage'
import { RealtorDetailPage } from '@/pages/admin/realtors/RealtorDetailPage'
import { RealtorsPage as AdminRealtorsPage } from '@/pages/admin/realtors/RealtorsPage'
import { SettingsPage as AdminSettingsPage } from '@/pages/admin/settings/SettingsPage'
import { AuthGuard } from './guards/AuthGuard'
import { GuestGuard } from './guards/GuestGuard'
import { RoleGuard } from './guards/RoleGuard'

export const AppRoutes = () => (
  <Routes>
    <Route path="/" element={<HomeRedirect />} />

    <Route element={<GuestGuard />}>
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>
      <Route element={<EnterpriseAuthLayout />}>
        <Route path="/enterprise/register" element={<Navigate to="/register?type=enterprise" replace />} />
        <Route path="/enterprise/invite/:token" element={<EnterpriseInvitationAcceptPage />} />
      </Route>
    </Route>

    <Route path="/auth/callback/:provider" element={<SocialCallbackPage />} />
    <Route path="/privacy-policy" element={<PrivacyPolicyPage />} />
    <Route path="/terms-of-service" element={<TermsOfServicePage />} />
    <Route path="/data-deletion" element={<DataDeletionPage />} />
    <Route path="/contact" element={<ContactPage />} />

    <Route element={<AuthGuard />}>
      <Route element={<RoleGuard role="realtor" />}>
        <Route path="/realtor" element={<RealtorLayout />}>
          <Route index element={<Navigate to="/realtor/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="leads" element={<LeadBoardPage />} />
          <Route path="calls" element={<Navigate to="/realtor/calls/logs" replace />} />
          <Route path="calls/logs" element={<RealtorCallsLogsPage />} />
          <Route path="calls/analytics" element={<RealtorCallsAnalyticsPage />} />
          <Route path="tasks" element={<Navigate to="/realtor/tasks/list" replace />} />
          <Route path="tasks/list" element={<RealtorTasksPage view="list" />} />
          <Route path="tasks/analytics" element={<RealtorTasksPage view="analytics" />} />
          <Route path="site-visits" element={<Navigate to="/realtor/site-visits/list" replace />} />
          <Route path="site-visits/list" element={<RealtorSiteVisitsPage view="list" />} />
          <Route path="site-visits/analytics" element={<RealtorSiteVisitsPage view="analytics" />} />
          <Route path="campaigns" element={<CampaignListPage />} />
          <Route path="campaigns/new" element={<CampaignFormPage />} />
          <Route path="campaigns/:id" element={<CampaignDetailPage />} />
          <Route path="campaigns/:id/edit" element={<CampaignFormPage />} />
          <Route path="campaigns/:id/import" element={<MetaImportPage />} />
          <Route path="campaigns/:id/meta" element={<MetaIntegrationPage />} />
          <Route path="campaigns/:id/leads/new" element={<LeadFormPage />} />
          <Route path="campaigns/:id/leads/:leadId" element={<LeadDetailPage />} />
          <Route path="campaigns/:id/leads/:leadId/edit" element={<LeadFormPage />} />
          <Route path="customers" element={<Navigate to="/realtor/customers/list" replace />} />
          <Route path="customers/list" element={<CustomerListPage view="list" />} />
          <Route path="customers/analytics" element={<CustomerListPage view="analytics" />} />
          <Route path="inventory" element={<ProjectListPage />} />
          <Route path="inventory/new" element={<ProjectFormPage />} />
          <Route path="inventory/:id" element={<ProjectDetailPage />} />
          <Route path="inventory/:id/edit" element={<ProjectFormPage />} />
          <Route path="plans" element={<Navigate to="/realtor/profile?tab=plans" replace />} />
          <Route path="billing" element={<Navigate to="/realtor/profile?tab=billing" replace />} />
          <Route path="billing/invoices/:id" element={<InvoiceDetailPage />} />
          <Route path="billing/notifications" element={<Navigate to="/realtor/profile?tab=notifications" replace />} />
          <Route path="profile" element={<ProfilePage />} />
          <Route path="settings" element={<Navigate to="/realtor/profile?tab=settings" replace />} />
          <Route path="meta/callback" element={<MetaConnectCallbackPage />} />
          {/* Social Media Module */}
          <Route path="social" element={<SocialDashboard basePath="/realtor/social" />} />
          <Route path="social/dashboard" element={<SocialDashboard basePath="/realtor/social" />} />
          <Route path="social/accounts" element={<SocialAccounts />} />
          <Route path="social/inbox" element={<SocialInbox />} />
          <Route path="social/comments" element={<SocialComments />} />
          <Route path="social/posts" element={<SocialPosts basePath="/realtor/social" />} />
          <Route path="social/mentions" element={<SocialMentions />} />
          <Route path="social/analytics" element={<SocialAnalytics />} />
        </Route>

        <Route element={<CheckoutLayout />}>
          <Route path="/checkout" element={<CheckoutPage />} />
          <Route path="/checkout/success" element={<CheckoutSuccessPage />} />
          <Route path="/checkout/failure" element={<CheckoutFailurePage />} />
        </Route>
      </Route>

      <Route element={<RoleGuard role="enterprise" />}>
        <Route path="/enterprise" element={<EnterpriseLayout />}>
          <Route index element={<Navigate to="/enterprise/dashboard" replace />} />
          <Route path="dashboard" element={<EnterpriseDashboardPage />} />
          <Route path="leads" element={<EnterpriseLeadListPage />} />
          <Route path="calls" element={<Navigate to="/enterprise/calls/logs" replace />} />
          <Route path="calls/logs" element={<EnterpriseCallsLogsPage />} />
          <Route path="calls/analytics" element={<EnterpriseCallsAnalyticsPage />} />
          <Route path="leads/distribute" element={<EnterpriseLeadDistributionPage />} />
          <Route path="leads/:id" element={<EnterpriseLeadDetailPage />} />
          <Route path="tasks" element={<Navigate to="/enterprise/tasks/list" replace />} />
          <Route path="tasks/list" element={<EnterpriseTasksPage view="list" />} />
          <Route path="tasks/analytics" element={<EnterpriseTasksPage view="analytics" />} />
          <Route path="site-visits" element={<Navigate to="/enterprise/site-visits/list" replace />} />
          <Route path="site-visits/list" element={<EnterpriseSiteVisitsPage view="list" />} />
          <Route path="site-visits/analytics" element={<EnterpriseSiteVisitsPage view="analytics" />} />
          <Route path="customers" element={<Navigate to="/enterprise/customers/list" replace />} />
          <Route path="customers/list" element={<EnterpriseCustomersPage view="list" />} />
          <Route path="customers/analytics" element={<EnterpriseCustomersPage view="analytics" />} />
          <Route path="inventory" element={<EnterpriseProjectListPage />} />
          <Route path="inventory/:id" element={<EnterpriseProjectDetailPage />} />
          <Route path="campaigns" element={<EnterpriseCampaignsPage />} />
          <Route path="campaigns/:id" element={<EnterpriseCampaignDetailPage />} />
          <Route path="meta/callback" element={<EnterpriseMetaConnectCallbackPage />} />
          {/* Social Media Module */}
          <Route path="social" element={<SocialDashboard basePath="/enterprise/social" />} />
          <Route path="social/dashboard" element={<SocialDashboard basePath="/enterprise/social" />} />
          <Route path="social/accounts" element={<SocialAccounts />} />
          <Route path="social/inbox" element={<SocialInbox />} />
          <Route path="social/comments" element={<SocialComments />} />
          <Route path="social/posts" element={<SocialPosts basePath="/enterprise/social" />} />
          <Route path="social/mentions" element={<SocialMentions />} />
          <Route path="social/analytics" element={<SocialAnalytics />} />
          <Route path="targets" element={<EnterpriseTargetsPage />} />
          <Route path="reports" element={<EnterpriseReportsPage />} />
          <Route path="teams" element={<EnterpriseTeamsPage />} />
          <Route path="teams/:id" element={<EnterpriseTeamDetailPage />} />
          <Route path="users" element={<EnterpriseMembersPage />} />
          <Route path="users/:id" element={<EnterpriseMemberDetailPage />} />
          <Route path="organization" element={<EnterpriseOrganizationPage />} />
          <Route path="invitations" element={<EnterpriseInvitationsPage />} />
          <Route path="notifications" element={<EnterpriseNotificationsPage />} />
          <Route path="billing" element={<EnterpriseBillingPage />} />
          <Route path="settings" element={<EnterpriseSettingsPage />} />
          <Route path="audit-logs" element={<EnterpriseAuditLogsPage />} />
        </Route>
      </Route>

      <Route element={<RoleGuard role="admin" />}>
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<Navigate to="/admin/dashboard" replace />} />
          <Route path="dashboard" element={<AdminDashboardPage />} />
          <Route path="analytics" element={<AnalyticsDashboardPage />} />
          <Route path="analytics/revenue" element={<RevenuePage />} />
          <Route path="analytics/payments" element={<AnalyticsPaymentsPage />} />
          <Route path="analytics/reports" element={<ReportsPage />} />
          <Route path="leads" element={<AdminLeadsPage />} />
          <Route path="enterprises" element={<AdminEnterprisesPage />} />
          <Route path="enterprises/:id" element={<AdminEnterpriseDetailPage />} />
          <Route path="broadcasts" element={<AdminBroadcastsPage />} />
          <Route path="audit-logs" element={<AdminAuditLogsPage />} />
          <Route path="users" element={<AdminUsersPage />} />
          <Route path="users/:id" element={<AdminUserDetailPage />} />
          <Route path="realtors" element={<AdminRealtorsPage />} />
          <Route path="realtors/:id" element={<RealtorDetailPage />} />
          <Route path="plans" element={<AdminPlansPage />} />
          <Route path="upgrade-requests" element={<UpgradeRequestsPage />} />
          <Route path="notifications" element={<AdminNotificationsPage />} />
          <Route path="settings" element={<AdminSettingsPage />} />
          <Route path="billing/subscriptions" element={<SubscriptionsPage />} />
          <Route path="billing/payments" element={<AdminPaymentsPage />} />
          <Route path="billing/invoices" element={<InvoicesPage />} />
          <Route path="billing/invoices/:id" element={<AdminInvoiceDetailPage />} />
          <Route path="billing/coupons" element={<CouponsPage />} />
          <Route path="billing/referrals" element={<ReferralsPage />} />
          <Route path="billing/notification-templates" element={<NotificationTemplatesPage />} />
          <Route path="billing/notifications" element={<AdminBillingNotificationsPage />} />
          <Route path="billing/settings" element={<BillingSettingsPage />} />
        </Route>
      </Route>
    </Route>

    <Route path="*" element={<NotFoundPage />} />
  </Routes>
)
