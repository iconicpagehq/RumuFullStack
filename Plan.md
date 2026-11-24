# 🏗️ Rumu V2 — Master Architectural Plan: "Admin", "Realtor" & "Enterprise"

---

## 📌 Executive Summary

**Rumu V2** is architected around **three primary roles and operational tiers**:

```
                                  ┌─────────────────────────────────────────┐
                                  │       1. PLATFORM SUPER ADMIN           │
                                  │         (SaaS & System Control)         │
                                  └────────────────────┬────────────────────┘
                                                       │
                         ┌─────────────────────────────┴─────────────────────────────┐
                         │                                                           │
              ┌──────────▼──────────┐                                     ┌──────────▼──────────┐
              │  2. SOLO REALTOR    │                                     │    3. ENTERPRISE    │
              │  (Single Person)    │                                     │ (Multi-User Team)   │
              └──────────┬──────────┘                                     └──────────┬──────────┘
                         │                                                           │
               [Single Person Login]                              ┌──────────────────┼──────────────────┐
               • Solo Lead Pipeline                               │                  │                  │
               • Personal Campaigns                          ┌────▼────┐        ┌────▼────┐        ┌────▼────┐
               • Catalog Inventory                           │  ADMIN  │        │ MANAGER │        │EMPLOYEE │
               • Personal Billing                            │ (Owner) │        │ (Branch)│        │ (Agent) │
                                                             └─────────┘        └─────────┘        └─────────┘
```

1. **Platform Admin (`role: admin`)**: Global platform operator. Oversees subscriptions, billing gateways, realtors, enterprises, plan upgrade approvals, global analytics, and revenue.
2. **Solo Realtor (`role: realtor`)**: Independent broker / solo agent. Single-person login with a private, distraction-free CRM, personal ad campaigns, and individual pipeline without team overhead.
3. **Enterprise Organization (`role: enterprise`)**: Real estate agency / brokerage team. Multi-user corporate workspace with strict internal Role-Based Access Control (**Enterprise Admin**, **Manager**, and **Employee**), lead distribution engines, and target quotas.

---

## 🏛️ The Three Primary Pillars

### 1. Platform Super Admin (`role: admin`)
- **Route Prefix**: `/admin/*`
- **Guarded By**: `AuthGuard` + `RoleGuard(role="admin")`
- **Target Persona**: Platform Owner, System Administrator, Financial Auditor.
- **Scope & Mission**: System-wide governance and SaaS monetization. Does not sell properties directly; manages the infrastructure and users who do.

#### Core Capabilities:
- **Global Dashboard & Analytics**: High-level platform KPIs, total registered realtors, active enterprises, aggregate lead flow, and churn rates.
- **Financial & Revenue Suite**:
  - Subscription monitoring across all tiers.
  - Payment gateway logs (Razorpay / Stripe / Manual Wire).
  - Invoice generation, tax configuration, and coupon code management.
  - Revenue analytics and payment reconciliations.
- **User & Account Governance**:
  - Complete directory of all Realtors and Enterprise accounts.
  - User detail inspection (activity, plan status, lead counts).
  - Suspend, activate, or adjust account restrictions.
- **Plan & Upgrade Requests Management**:
  - Configure Plan pricing, monthly lead limits, inventory limits, and seat quotas.
  - Review, approve, or reject Enterprise and Pro upgrade requests submitted by users.
- **System Settings & Notifications**:
  - Global broadcast notifications to all realtors/enterprises.
  - Automated billing reminder email/SMS templates.
  - System-wide security rules and API integration keys.

---

### 2. Solo Realtor (`role: realtor`)
- **Route Prefix**: `/realtor/*`
- **Guarded By**: `AuthGuard` + `RoleGuard(role="realtor")`
- **Target Persona**: Independent Property Consultant, Solo Broker, Freelance Real Estate Agent.
- **Scope & Mission**: Streamlined, single-operator productivity. Designed for zero-friction deal closing without multi-user complexities.

#### Core Characteristics:
- **Single-User Workspace**:
  - One login = one user. No sub-accounts, no employee management, no team overhead.
- **Lead Pipeline Management**:
  - All incoming leads (manual entry, CSV upload, Meta Lead Ads integration) belong directly and exclusively to this realtor.
  - Visual Kanban & Table views (`new` → `contacted` → `qualified` → `negotiation` → `won` / `lost`).
  - Personal lead notes, call history, and deal valuations.
- **Marketing & Ad Campaigns**:
  - Create and manage personalized campaigns.
  - Direct Meta (Facebook/Instagram) Lead Ads form integration with automatic webhook sync.
  - Meta Conversions API (CAPI) sync for lead stage progression.
- **Inventory & Unit Selection**:
  - Browse real estate inventory (Projects, Towers, Wings, Floors, and Units).
  - Check live unit availability (`available`, `on_hold`, `booked`, `sold`).
  - Mark units on hold or book units directly for personal buyers.
- **Personal Client Directory**:
  - Converted leads automatically graduate into the Customer Directory.
- **Self-Serve Billing**:
  - Free Basic Plan or Pro Plan (₹999/month).
  - Self-serve online checkout, personal invoice history, and billing notifications.

---

### 3. Enterprise Organization (`role: enterprise`)
- **Route Prefix**: `/enterprise/*`
- **Guarded By**: `AuthGuard` + `RoleGuard(role="enterprise")` + `EnsureEnterpriseApi`
- **Target Persona**: Real Estate Agencies, Developer Sales Offices, Brokerage Franchises.
- **Scope & Mission**: Scalable team collaboration, structured hierarchy, automated lead distribution, quota management, and complete operational transparency.

Within an Enterprise account, users are assigned one of **three internal roles**:

#### A. Enterprise Admin / Owner
- **Role Badge**: `Admin (Purple)`
- **Scope**: Entire Enterprise Organization.
- **Key Capabilities**:
  - **Organization Settings**: Custom company profile, logo, RERA registration, working hours, and auto-distribution rules.
  - **Team Architecture**: Create branches or sales teams (e.g., "South Delhi Luxury Team", "NRI Telesales", "Commercial Leasing").
  - **Member Administration**:
    - Add members directly by setting their email and temporary password.
    - Send cryptographically secure, 7-day expiring email invitations.
    - Suspend, reactivate, or remove members.
    - **Lead Transfer Wizard**: Transfer an entire lead pipeline from a resigning employee to an active agent in one click.
  - **Lead Distribution Engine**:
    - Distribute batches of unassigned leads to teams via **Round-Robin** or specific agent allocation.
  - **Strategic Governance**: Set monthly sales/revenue targets, access full company audit logs, and export reports.
  - **Billing & Licensing**: Manage Enterprise plan (Starter vs Growth), request seat add-ons, and view company invoices.

#### B. Enterprise Manager
- **Role Badge**: `Manager (Blue)`
- **Scope**: Managed Teams & Assigned Subordinates.
- **Key Capabilities**:
  - **Lead Allocation**: Review unassigned or incoming team leads and assign them to subordinate Employees.
  - **Team Oversight**: Monitor all leads, deals, site visits, and scheduled tasks belonging to their managed teams.
  - **Team Target Monitoring**: Track team quotas (leads contacted, site visits completed, revenue closed).
  - **Campaigns & Inventory**: Create and edit team marketing campaigns; reserve inventory units for team deals.
  - *Restrictions*: Cannot view or modify enterprise billing, cannot alter company settings, and cannot delete other managers or admins.

#### C. Enterprise Employee (Sales Agent / Telecaller)
- **Role Badge**: `Employee (Slate/Gray)`
- **Scope**: **Strictly Own Assigned Records Only**.
- **Key Capabilities**:
  - **Assigned Pipeline**: Work exclusively on leads assigned to them by Admin or Manager.
  - **Activity Execution**: Log calls, capture client requirements, write notes, and schedule upcoming site visits and follow-up tasks.
  - **Inventory Consultation**: Search floor plans and unit availability to quote pricing to their assigned prospects.
  - **Personal Performance**: Track individual monthly targets and conversion ratios.
  - *Strict Privacy & Security Firewall*:
    - **Cannot view** leads assigned to other employees or other teams.
    - **Cannot reassign** leads to other people.
    - **Cannot export** raw database records to CSV/Excel.
    - **Cannot access** team management, member directories, invitations, billing, or audit logs.

---

## 📊 Comprehensive 5-Tier RBAC Feature Matrix

| Functional Area | Platform Admin | Solo Realtor | Enterprise Admin | Enterprise Manager | Enterprise Employee |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Workspace Portal** | `/admin/*` | `/realtor/*` | `/enterprise/*` | `/enterprise/*` | `/enterprise/*` |
| **Identity / Role** | `admin` | `realtor` | `enterprise` (Admin) | `enterprise` (Manager) | `enterprise` (Employee) |
| **System Governance & Revenue** | ✅ Full Control | ❌ No Access | ❌ No Access | ❌ No Access | ❌ No Access |
| **Manage SaaS Plans & Approvals** | ✅ Full Control | ❌ No Access | ❌ No Access | ❌ No Access | ❌ No Access |
| **Account Billing & Subscriptions** | 📊 View All | ✅ Personal Plan | ✅ Org Plan | ❌ No Access | ❌ No Access |
| **Team & Member Management** | 👁️ View All Orgs | ❌ No Access | ✅ Full CRUD | 👁️ View Team | 👁️ View Own Team |
| **Send Invitations / Add Logins** | ❌ N/A | ❌ No Access | ✅ Full Access | ❌ No Access | ❌ No Access |
| **Lead Visibility** | 🌐 Platform Logs | 👤 Personal Leads | 🏢 All Org Leads | 👥 Managed Team | 👤 Assigned Only |
| **Lead Assignment / Round-Robin** | ❌ N/A | ❌ N/A | ✅ Across Org | ✅ Within Team | ❌ No Access |
| **Log Call / Notes / Stage Change** | ❌ N/A | ✅ Personal Leads | ✅ Any Org Lead | ✅ Team Leads | ✅ Assigned Leads |
| **Site Visits & Tasks** | ❌ N/A | ✅ Personal | ✅ Org-Wide | ✅ Team-Wide | ✅ Assigned / Own |
| **Inventory Hierarchy CRUD** | ✅ Global Control | 👁️ Catalog View | ✅ Org Projects | 👁️ Catalog View | 👁️ Catalog View |
| **Inventory Unit Booking / Hold** | ❌ N/A | ✅ For Client | ✅ For Org Deal | ✅ For Team Deal | ✅ For Assigned Lead |
| **Campaigns & Meta Lead Ads** | ❌ N/A | ✅ Personal Ads | ✅ Org Campaigns | ✅ Team Campaigns | 👁️ Read Attribution |
| **Monthly Targets & Quotas** | ❌ N/A | ❌ N/A | ✅ Set Org/Team | ✅ Set Employee | 📈 View Own Target |
| **Data Export (CSV)** | ✅ All Datasets | ✅ Personal Data | ✅ Org Data | ⚠️ Team Data Only | ❌ Strictly Blocked |
| **Audit Logs** | ✅ System Audit | ❌ N/A | ✅ Org Audit Log | ❌ No Access | ❌ No Access |

---

## 🗄️ Database Architecture & Multi-Tenancy

```
                               ┌────────────────────────────────────────────────────────┐
                               │                        users                           │
                               │ id, name, email, role ['admin', 'realtor', 'enterprise']│
                               └───────────┬────────────────────────────────┬───────────┘
                                           │                                │
                 ┌─────────────────────────┘                                └─────────────────────────┐
                 │ (if role == 'realtor')                                                             │ (if role == 'enterprise')
                 ▼                                                                                    ▼
┌─────────────────────────────────┐                                              ┌─────────────────────────────────────────┐
│     Direct Entity Ownership     │                                              │               enterprises               │
│ leads.user_id = users.id        │                                              │ id, name, slug, owner_id, plan_id       │
│ campaigns.user_id = users.id    │                                              └────────────────────┬────────────────────┘
│ (enterprise_id is NULL)         │                                                                   │
└─────────────────────────────────┘                                      ┌────────────────────────────┴───────────────────────────┐
                                                                         ▼                                                         ▼
                                                       ┌──────────────────────────────────┐                      ┌─────────────────────────────────┐
                                                       │        enterprise_members        │                      │              teams              │
                                                       │ enterprise_id, user_id,          │                      │ enterprise_id, name,            │
                                                       │ role ['admin','manager','emp']   │                      │ manager_id (users.id)           │
                                                       └──────────────────────────────────┘                      └────────────────┬────────────────┘
                                                                                                                                  │
                                                                                                                                  ▼
                                                                                                                 ┌─────────────────────────────────┐
                                                                                                                 │          team_members           │
                                                                                                                 │ team_id, user_id                │
                                                                                                                 └─────────────────────────────────┘
```

### 1. Data Isolation Guardrails
- **Realtor Isolation**:
  ```php
  // Realtor leads query
  Lead::where('user_id', auth()->id())
      ->whereNull('enterprise_id')
      ->get();
  ```
- **Enterprise Isolation (Enforced by Middleware & Policy)**:
  ```php
  // Enterprise Admin: sees all leads in org
  Lead::where('enterprise_id', $enterpriseId)->get();

  // Enterprise Manager: sees leads belonging to managed team
  Lead::where('enterprise_id', $enterpriseId)
      ->whereIn('team_id', $managedTeamIds)
      ->get();

  // Enterprise Employee: sees only assigned leads
  Lead::where('enterprise_id', $enterpriseId)
      ->where('assigned_to', auth()->id())
      ->get();
  ```
- **Zero Cross-Leak Guarantee**: Any direct route request targeting an ID belonging to a different tenant returns an HTTP **404 Not Found** (preventing user enumeration or existence probing).

---

## 🔄 Lead Lifecycle Comparison

### Solo Realtor Pipeline
```
[Meta Lead Ad / Web Form]
          │
          ▼
   Directly Saved
(leads.user_id = Realtor)
          │
          ▼
Instant Notification ──> Phone Call / WhatsApp ──> Site Visit ──> Deal Won / Customer Created
```

### Enterprise Team Pipeline
```
[Meta Lead Ad / Webhook / Ingestion]
          │
          ▼
  Enterprise Pool
(enterprise_id = Org_ID, assigned_to = NULL)
          │
          ├─────────────────────────────────────────────────┐
          ▼                                                 ▼
[Automated Round-Robin]                           [Manager / Admin Manual Dispatch]
Engine selects next available                     Manager reviews lead budget/locality
active Employee in assigned Team                  and assigns to specific Employee
          │                                                 │
          └────────────────────────┬────────────────────────┘
                                   ▼
                   Assigned to Employee (Agent)
                   • Push & In-App Notification
                   • Lead appears in "My Assigned Leads"
                   • Employee logs call, schedules site visit
                   • Deal Won graduates to Customer Directory
```

---

## 💳 Monetization & Plan Tiering

| Tier | Plan Name | Target Role | Monthly Fee | Seat / User Limit | Monthly Leads | Key Capabilities |
| :--- | :--- | :--- | :---: | :---: | :---: | :--- |
| **Realtor Tier 1** | **Basic** | Solo Realtor | Free | 1 User | 100 Leads | 5 Campaigns, Catalog Inventory, Personal CRM |
| **Realtor Tier 2** | **Pro** | Solo Realtor | ₹999 / mo | 1 User | Unlimited | Unlimited Campaigns, Meta Ads Sync, CAPI Sync |
| **Enterprise Tier 1**| **Starter**| Small Agency | ₹4,999 / mo | 1 Admin + 2 Mgrs + 15 Emps (3 Teams) | 500 Leads | Lead Distribution, Round-Robin, Tasks, Site Visits |
| **Enterprise Tier 2**| **Growth** | Large Brokerage | ₹14,999 / mo| 1 Admin + 10 Mgrs + 100 Emps (20 Teams) | 5,000 Leads | Advanced Reports, Target Engine, CAPI Sync, Audit Logs |

*Note: Enterprise upgrades require submission of an upgrade request, reviewed and approved directly by the **Platform Super Admin**.*

---

## 🖥️ Frontend Navigation Structure

### 1. Platform Admin Navigation (`/admin/*`)
- 📊 **Executive Overview**: Dashboard, Global Metrics, Platform Health
- 💰 **Billing & Subscriptions**: Subscriptions, Invoices, Payments, Coupons, Gateway Settings
- 👥 **User Management**: Realtors Directory, Enterprise Accounts, Upgrade Requests
- 📣 **Communications**: Broadcast Notifications, Notification Templates
- ⚙️ **Platform Settings**: General Settings, Security

### 2. Solo Realtor Navigation (`/realtor/*`)
- 📊 **Dashboard**: Personal sales funnel & upcoming follow-ups
- 🎯 **Marketing**: Campaigns, Meta Ad Forms, CAPI Webhook Connect
- 👥 **Leads**: Kanban Pipeline & List View, Activity Notes
- 📅 **Site Visits & Tasks**: Personal schedule
- 🏢 **Inventory**: Project Catalog, Tower/Unit Status
- 🤝 **Customers**: Won Lead Directory
- ⚙️ **Account**: Profile, Personal Billing & Invoices

### 3. Enterprise Workspace Navigation (`/enterprise/*`)
- 📊 **Executive / Team Dashboard**: Organization or Team KPIs
- 👥 **Leads**:
  - *All Leads* (Admin/Manager) / *My Assigned Leads* (Employee)
  - *Lead Distribution* (Admin/Manager console for single & batch round-robin)
- 📅 **Sales Operations**: Tasks & Site Visit schedules
- 🏢 **Inventory**: Master Projects, Unit Availability, Booking Engine
- 📈 **Targets & Performance**: Quotas, Achievement % by Team / Agent
- 👥 **Team & Organization** *(Admin & Manager Only)*:
  - *Teams / Branches*: Department setup & manager assignments
  - *Members*: User accounts, login creation, role assignments
  - *Invitations*: Pending email invitations, token resend/revoke
- 🛡️ **Enterprise Administration** *(Admin Only)*:
  - *Audit Logs*: Traceability on lead assignments and status transitions
  - *Enterprise Billing*: Seat usage gauge, plan upgrade requests, invoices
  - *Company Settings*: Organization profile, branding, RERA info

---

## 🔒 Security & Data Integrity Principles

1. **Role Boundary Enforcement**:
   - Role checks occur in both **Route Guards** (React) and **Middleware / Policies** (Laravel). An Employee tampering with client-side code will receive a `403 Forbidden` from the API.
2. **Strict Scoping (Anti-Leak)**:
   - Queries are automatically scoped to tenant ID and role boundaries. Direct ID manipulation returns `404 Not Found`.
3. **Safe Credential Management**:
   - Direct user creation uses one-way hashed passwords; plaintext credentials are never written to audit trails or logs.
   - Email invitation tokens are stored as SHA-256 hashes with 7-day expiration.
4. **Resignation & Reassignment Safety**:
   - Deactivating an Employee account preserves all historical notes, call records, and site visit logs.
   - The Admin or Manager utilizes the **Lead Transfer Tool** to seamlessly transition leads to another active agent.
5. **CSV Injection Defense**:
   - All exported data across Admin, Realtor, and Enterprise modules prefixes formula control characters (`=`, `+`, `-`, `@`) with single quotes to prevent formula execution in spreadsheet software.
