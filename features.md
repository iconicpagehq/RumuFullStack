# 📑 Rumu V2 — Complete Features & Modules Specification

A comprehensive technical and functional catalog of all modules, subsystems, business logic workflows, and user capabilities in **Rumu V2**.

---

## 📑 Table of Contents

1. [Executive Summary & System Architecture](#1-executive-summary--system-architecture)
2. [Role-Based Access Control (RBAC) Matrix](#2-role-based-access-control-rbac-matrix)
3. [Module 1: Authentication & Identity Management](#3-module-1-authentication--identity-management)
4. [Module 2: Realtor CRM Workspace](#4-module-2-realtor-crm-workspace)
   - [2.1 Executive Dashboard](#21-executive-dashboard)
   - [2.2 Marketing Campaigns](#22-marketing-campaigns)
   - [2.3 Lead Acquisition & Pipeline Management](#23-lead-acquisition--pipeline-management)
   - [2.4 Multi-Channel Ingestion & Meta Lead Ads](#24-multi-channel-ingestion--meta-lead-ads)
   - [2.5 Meta Conversions API (CAPI) Integration](#25-meta-conversions-api-capi-integration)
   - [2.6 Hierarchical Real Estate Inventory Engine](#26-hierarchical-real-estate-inventory-engine)
   - [2.7 Customer Directory & Conversion](#27-customer-directory--conversion)
   - [2.8 Subscription, Invoices & Upgrade Requests](#28-subscription-invoices--upgrade-requests)
   - [2.9 Profile & Account Settings](#29-profile--account-settings)
5. [Module 3: Administrator Control Center](#5-module-3-administrator-control-center)
   - [3.1 Platform Operations Dashboard](#31-platform-operations-dashboard)
   - [3.2 User & Realtor Directory](#32-user--realtor-directory)
   - [3.3 Plan & Pricing Engine](#33-plan--pricing-engine)
   - [3.4 Upgrade Request Approval Engine](#34-upgrade-request-approval-engine)
   - [3.5 Enterprise Billing & Financial Suite](#35-enterprise-billing--financial-suite)
   - [3.6 Advanced Analytics & BI Reporting](#36-advanced-analytics--bi-reporting)
   - [3.7 Platform Settings & Notifications](#37-platform-settings--notifications)
6. [Module 4: Checkout & Payment Engine](#6-module-4-checkout--payment-engine)
7. [Module 5: Legal & Public Surfaces](#7-module-5-legal--public-surfaces)
8. [Database Domain Models Reference](#8-database-domain-models-reference)

---

## 1. Executive Summary & System Architecture

Rumu V2 is a specialized, dual-surface Real Estate CRM designed to streamline high-volume property lead conversion, multi-tier inventory availability tracking, and automated ad attribution.

### Core Architecture
- **Backend:** Laravel 12 REST API providing standardized JSON responses via Eloquent API Resources.
- **Frontend:** React 18 Single Page Application (SPA) bundled with Vite 5, Tailwind CSS v3, and Zustand for high-speed client state management.
- **API Communication:** Axios HTTP client configured with request/response interceptors that automatically attach Sanctum Bearer tokens and gracefully intercept `401 Unauthorized` responses.
- **Asynchronous Execution:** Redis / Database background queues decouple external API roundtrips (Meta CAPI dispatch, email dispatches, webhook handling) from user-facing transactions.

---

## 2. Role-Based Access Control (RBAC) Matrix

Rumu V2 enforces strict role boundaries and active subscription verification at both the API middleware and React client routing levels.

| Capability / Module | Guest | Realtor (Active) | Realtor (Suspended) | Administrator |
|---|:---:|:---:|:---:|:---:|
| **Public Landing & Authentication** | ✅ | ✅ | ✅ | ✅ |
| **Subscription Checkout & Razorpay** | ❌ | ✅ | ✅ | ✅ |
| **Realtor Profile & Appearance** | ❌ | ✅ | ✅ | ❌ |
| **Campaigns & Lead Management** | ❌ | ✅ | ❌ *(Read-only banner)* | ❌ |
| **Meta Integration & CAPI Sync** | ❌ | ✅ | ❌ | ❌ |
| **Inventory Hierarchy & Unit CRUD** | ❌ | ✅ | ❌ | ❌ |
| **Customer Index** | ❌ | ✅ | ❌ | ❌ |
| **Realtor Invoices & Upgrade Requests** | ❌ | ✅ | ✅ | ❌ |
| **Global Admin Dashboard & Stats** | ❌ | ❌ | ❌ | ✅ |
| **Realtor Account Management & Suspension**| ❌ | ❌ | ❌ | ✅ |
| **Plan Pricing & Feature Limits CRUD** | ❌ | ❌ | ❌ | ✅ |
| **Coupons, Referrals & Invoicing Engine** | ❌ | ❌ | ❌ | ✅ |
| **Platform Revenue & Analytics Export** | ❌ | ❌ | ❌ | ✅ |
| **Global Settings & Notification Templates**| ❌ | ❌ | ❌ | ✅ |

---

## 3. Module 1: Authentication & Identity Management

Handles registration, session management, multi-provider OAuth, and account security.

### Features
1. **User Registration:**
   - Collects Name, Business Email, Password, and Confirmation.
   - Pre-selects desired subscription plan during registration.
   - Client-side validation powered by **Zod** and **React Hook Form**; server-side validation with comprehensive field error mapping.
2. **Credential Authentication:**
   - Secure login generating unique Laravel Sanctum personal access tokens.
   - Automatic redirection based on role: `admin` ➔ `/admin/dashboard`, `realtor` ➔ `/realtor/dashboard`.
   - Remembered redirect paths via React Router state parameters.
3. **Socialite OAuth Integration:**
   - **Google OAuth 2.0:** One-click registration and sign-in.
   - **Facebook Login:** Single sign-on for Meta advertisers.
   - Server-side state validation to prevent CSRF in OAuth handshakes.
4. **Account Linking & Unlinking:**
   - Realtors can link Google or Facebook credentials to their existing email accounts from their Profile page.
   - Option to disconnect external OAuth providers if a local password has been established.
5. **Token Management & Security:**
   - **Single-Device Sign Out:** Revokes the active device token.
   - **Global Sign Out ("Sign out everywhere"):** Purges all issued Sanctum tokens for the user across mobile, desktop, and tablets.
6. **Session Expiry Protection:**
   - Graceful token expiration detection with informative toast notifications and seamless re-authentication redirects.

---

## 4. Module 2: Realtor CRM Workspace

The dedicated workspace for real estate agents, agencies, and sales teams.

### 2.1 Executive Dashboard
- **Real-Time KPI Cards:** Instant metrics on Total Leads, Active Campaigns, Deals Won, Conversion Rate, and Available Inventory Units.
- **Conversion Funnel Analytics:** Visual breakdown of leads moving through each stage of the sales pipeline.
- **Campaign Performance Overview:** Comparison of lead count and conversion percentage across active campaigns.
- **Recent Activity Feed:** Real-time log of newly ingested leads, note additions, and status transitions.
- **Subscription Usage Meter:** Visual indicator displaying consumption against plan allowances (e.g., `45 / 50 Leads Used`).

### 2.2 Marketing Campaigns
- **Full Campaign CRUD:** Create, update, view, and soft-delete marketing campaigns.
- **Metadata Management:** Campaign name, objective, start date, target end date, allocated budget, and status (`Active`, `Paused`, `Completed`).
- **Plan Limit Enforcement:** Enforces maximum simultaneous campaigns allowed by the Realtor's active subscription tier.
- **Associated Lead Rollup:** Instant count of leads gathered per campaign, with direct links into filtered lead lists.

### 2.3 Lead Acquisition & Pipeline Management
- **Structured Sales Pipeline Stages:**
  1. `New` — Fresh inquiry awaiting qualification.
  2. `Contacted` — Outreach initiated via call/WhatsApp/email.
  3. `Qualified` — Budget, timeline, and requirements confirmed.
  4. `Negotiation` — In active property discussions or site visits.
  5. `Won` — Deal finalized and payment committed (graduates to Customer).
  6. `Lost` — Client declined or purchased elsewhere.
  7. `On Hold` — Transaction deferred to a future date.
- **Lead Detail View:**
  - Contact information (Full Name, Phone, Email, Location).
  - Source tracking (Meta Ad, CSV Import, Direct Walk-in, Referral).
  - Associated Campaign and assigned Property Unit.
  - Interactive status dropdown with instantaneous optimistic UI updates.
- **Activity Timeline & Notes:**
  - Chronological activity log.
  - Rich internal notes thread with timestamps and author attribution.
- **Customer Auto-Graduation:** Marking a lead as `Won` automatically flags `is_customer = true` and indexes the lead in the Customer Directory.

### 2.4 Multi-Channel Ingestion & Meta Lead Ads
- **Manual Lead Entry:** Form modal with duplicate phone/email detection.
- **Standard CSV Bulk Import:**
  - Downloadable standardized sample CSV template.
  - Column mapper supporting custom header variations.
  - Batch validation reporting skipped rows and formatted error summaries.
- **Meta Lead Ads CSV Import:** Direct ingest of raw CSV export files downloaded from Facebook Ads Manager.
- **Direct Meta Lead Gen Form Sync:**
  - Connect Facebook Pages via Graph API OAuth.
  - Fetch available Instant Forms for each connected Facebook Page.
  - Map form fields (Full Name, Phone Number, Email, Custom Questions) directly into Rumu CRM lead attributes.
  - Background synchronization engine pulling new leads on demand.

### 2.5 Meta Conversions API (CAPI) Integration
- **Server-to-Server Event Dispatch:** Automatically notifies Meta whenever a lead status updates in Rumu (e.g., lead qualified or closed).
- **Data Privacy & Cryptographic Hashing:** Normalizes and SHA256-hashes personally identifiable information (email, phone, first name, last name) before dispatching to Meta's Graph API.
- **Event Deduplication:** Uses unique CRM Lead ID and Meta Leadgen ID as event deduplication keys.
- **Resend & Audit Mechanism:** Dedicated "Resend CAPI Event" action with detailed sync logs, response status codes, and error traces.

### 2.6 Hierarchical Real Estate Inventory Engine
A specialized, 5-level nested inventory system designed for complex real estate developments:

$$\text{Project} \longrightarrow \text{Tower} \longrightarrow \text{Wing} \longrightarrow \text{Floor} \longrightarrow \text{Unit}$$

1. **Project Management:** Project name, location address, description, total units, launch date, and completion status.
2. **Towers & Wings:** Define structural blocks or towers (e.g., "Tower A", "East Wing").
3. **Floors:** Create and order floors within towers or wings.
4. **Units Management:**
   - Unit number/label (e.g., "Unit 402").
   - Configuration type (`1 BHK`, `2 BHK`, `3 BHK`, `4 BHK`, `Penthouse`, `Commercial`).
   - Carpet area / Super built-up area in square feet/meters.
   - Pricing (Base Price, Floor Rise, Total Consideration).
   - Real-time availability status: `Available`, `On Hold`, `Booked`, `Sold`.
5. **Bulk Unit Creation:** Generator tool allowing realtors to create an entire floor's worth of units in a single operation.
6. **Unit Search & Lead Assignment:** Live search modal allowing agents to assign a specific unit directly to a prospective lead.

### 2.7 Customer Directory & Conversion
- **Persistent Customer Index:** Paginated list of all qualified purchasers who completed deals.
- **Historical Unit Record:** Tracks past purchases, registered ownership, and total transaction values.
- **Search & Filter:** Search by name, email, phone number, and acquisition date.

### 2.8 Subscription, Invoices & Upgrade Requests
- **Subscription Overview:** Current active tier, renewal date, and live resource consumption gauges.
- **Plan Upgrade Flow:**
  - View tier comparison table.
  - Submit formal upgrade requests directly to administrators with optional business notes.
  - Track pending, approved, or rejected requests in real time.
- **Invoices & Receipts:**
  - Searchable invoice history table.
  - Detailed invoice view with itemized charges, coupon discounts, and tax breakdowns.
  - Instant PDF generation and download powered by DomPDF.
- **Billing Notification Inbox:** Filterable alerts for upcoming renewals, payment receipts, and upgrade reviews.

### 2.9 Profile & Account Settings
- **Profile Customization:** Update personal details, contact number, and company name.
- **Avatar Management:** Upload and crop custom profile pictures with automatic storage link optimization.
- **Security:** Password change tool with current password verification.
- **Theme Preferences:** Instant switching between Dark Mode, Light Mode, and System Default.
- **Notification Preferences:** Fine-grained toggles for email alerts on new leads and billing events.
- **Account Deletion:** Self-service account termination option adhering to data privacy standards.

### 2.10 App Calling & Recording Sync
- **App-only calling:** Salespeople call from their own SIM; the web dashboard shows history and playback but
  never a call button, `tel:` link or cloud bridge.
- **Consent gate:** Before the device dialer opens, the app asks the user to confirm the lead will be told
  that the call may be recorded. Consent is remembered per lead, per day.
- **Call sessions on the phone:** A session is saved before dialing, so a call still appears even if the app
  is killed. On return, the Save Call sheet captures outcome, talk time and notes.
- **Recorder discovery:** The app scans MediaStore, an optional chosen folder and known OEM recorder paths,
  ranks files by phone number, time and duration, and lets the user pick a file manually.
- **Private sync:** Metadata is posted idempotently (`client_call_id`) and the audio uploads separately with
  WorkManager retries. Recordings stay on a private disk and stream only through authenticated, audited
  endpoints; no public URL is exposed.
- **Honest states:** "No recording available", queued, uploading, uploaded and failed states are visible in
  the app; the web keeps **Upload recording** as a fallback.

---

## 5. Module 3: Administrator Control Center

The central management interface for platform administrators and SaaS operators.

### 3.1 Platform Operations Dashboard
- **High-Level Financial KPIs:** Monthly Recurring Revenue (MRR), Annual Recurring Revenue (ARR), Total Platform Revenue.
- **User Growth Metrics:** Total Registered Realtors, Active Subscriptions, Suspended Accounts.
- **Operational Health:** Pending Upgrade Requests counter, unread system alerts, and database queue status.
- **Global Ingestion Metrics:** Total leads captured platform-wide across all connected realtor campaigns.

### 3.2 User & Realtor Directory
- **Realtor 360° Management:**
  - View complete realtor dossiers including active plan, registration date, and campaign count.
  - One-click account suspension and instant account resumption.
  - Manual Plan Assignment: Override or upgrade a realtor's plan without requiring payment gateway interaction.
- **Realtor Detail Deep Dive:**
  - Inspect all campaigns owned by a specific realtor.
  - View aggregate lead statistics and conversion rates.
  - Inspect all historical invoices and payments associated with the account.
- **Administrative Users List:** Master directory of all platform accounts.

### 3.3 Plan & Pricing Engine
- **Comprehensive Plan CRUD:**
  - Plan name, internal slug, and description.
  - Pricing configuration: Monthly price, Annual price, Currency.
  - Granular Resource Limits: Maximum campaigns, maximum leads per month, maximum inventory units.
  - Feature Toggles: CAPI access, CSV export permission, priority support flag.
- **Live Reordering:** Adjust the display sequence of pricing cards via interactive sorting.
- **Public Visibility Toggle:** Hide or reveal plans on the public registration and checkout pages.

### 3.4 Upgrade Request Approval Engine
- **Queue Management:** Dedicated review screen for realtor tier upgrade requests.
- **Action Workflows:**
  - **Approve:** Instantly assigns the requested plan, updates resource caps, and dispatches an approval notification to the realtor.
  - **Reject:** Declines the request with mandatory administrative reason notes logged for auditing.

### 3.5 Enterprise Billing & Financial Suite
- **Subscription Management:**
  - Search and filter all active, trialing, past-due, and cancelled subscriptions.
  - Extend subscription expiry dates manually.
  - Cancel subscriptions immediately or at period end.
- **Payments Ledger:**
  - Audit log of all transactions with Razorpay Payment ID, Order ID, and amount.
  - Transaction status tags (`Completed`, `Pending`, `Failed`, `Refunded`).
- **Invoice Management:**
  - Master invoice archive across all customers.
  - View invoice items, applied taxes, and net amounts.
  - One-click PDF download and resend invoice email action.
- **Discount Coupon Engine:**
  - Create promotional voucher codes (e.g., `SUMMER50`).
  - Discount types: Percentage discount or Flat currency deduction.
  - Constraints: Validity date ranges, minimum cart value, and maximum total redemptions.
  - Live usage tracking and coupon deactivation.
- **Referral Program Tracking:**
  - Track referral links, referring users, and referee sign-ups.
  - Monitor conversion rates and reward allocations.
- **Notification Templates & Logs:**
  - Configure dynamic email templates for subscription renewal, payment received, payment failed, and invoice created.
  - Review historical billing notification logs with resend capability.
- **Billing Settings:**
  - Global business profile: Legal Entity Name, Tax/GST Identification Number, Registered Business Address.
  - Currency symbol and tax rate percentages.
  - Razorpay live and test API credentials.

### 3.6 Advanced Analytics & BI Reporting
- **Revenue Analytics:** Interactive Recharts area graphs displaying revenue velocity over days, weeks, months, or years.
- **Payment Analytics:** Doughnut charts breaking down payment method distribution and transaction success vs failure rates.
- **Customer Acquisition Cohorts:** Charts tracking subscriber retention and churn over time.
- **Custom Report Generator:** Date-range filterable data exporter generating clean CSV reports for financial accounting and tax compliance.

### 3.7 Platform Settings & Notifications
- **Dynamic Appearance Customization:**
  - Customize UI theme tokens, primary brand hex colors, and accent gradients.
  - Global logo and favicon management.
- **Global Meta Conversions API (CAPI) Settings:**
  - Platform-wide Meta Pixel / Dataset ID and System User Access Token configuration.
  - Global Graph API versioning and test event code inputs.
- **Administrative Notifications:**
  - Persistent notification bell with real-time unread badge counts.
  - Instant alerts for new registrations, upgrade requests, and payment failures.
  - Mark single or all notifications as read.

---

## 6. Module 4: Checkout & Payment Engine

A streamlined, conversion-focused subscription acquisition pipeline.

### Checkout Flow
```
┌────────────────────────┐      ┌────────────────────────┐      ┌────────────────────────┐
│  Select Plan Tier      │ ───► │  Review Cart Summary   │ ───► │  Apply Discount Coupon │
│  (Monthly / Annual)    │      │  (Base + Taxes)        │      │  (Real-Time Recalc)    │
└────────────────────────┘      └────────────────────────┘      └───────────┬────────────┘
                                                                            │
                                ┌────────────────────────┐                  │
                                │  Razorpay Modal Popup  │ ◄────────────────┘
                                │  (UPI, Cards, NetBank) │
                                └───────────┬────────────┘
                                            │
                       ┌────────────────────┴────────────────────┐
                       ▼                                         ▼
            [ Signature Verified ]                     [ Signature Failed ]
                       │                                         │
                       ▼                                         ▼
         ┌───────────────────────────┐             ┌───────────────────────────┐
         │  Checkout Success Page    │             │  Checkout Failure Page    │
         │  - Plan Instant Activation│             │  - Retry with Alternative │
         │  - Download Initial Receipt│            │  - Direct Support Links   │
         └───────────────────────────┘             └───────────────────────────┘
```

1. **Cart Summary Calculation:** Computes base price, interval savings, applied coupon discounts, and applicable taxes via `/api/v1/checkout/summary`.
2. **Interactive Coupon Application:** Users can test discount codes before purchase with instantaneous mathematical recalculations.
3. **Razorpay Order Dispatch:** Generates a secure server-side Razorpay Order ID.
4. **Client-Side Payment Modal:** Loads Razorpay's checkout script supporting Credit/Debit Cards, NetBanking, UPI, and Digital Wallets.
5. **Cryptographic Signature Verification:** Verifies payment payload via HMAC SHA256 against `RAZORPAY_SECRET` before elevating user privileges.
6. **Automatic Invoice Generation:** Generates an itemized `Invoice` and `Payment` record and attaches them to the user's account upon successful verification.

---

## 7. Module 5: Legal & Public Surfaces

Essential public-facing compliance and navigation pages.

- **Privacy Policy (`/privacy-policy`):** Comprehensive disclosure on data handling, Meta CAPI data hashing protocols, and GDPR/IT Act compliance.
- **Terms of Service (`/terms-of-service`):** SaaS subscription rules, acceptable use, refund policies, and intellectual property terms.
- **Smart 404 Route (`/404`):** Context-aware error screen guiding unauthenticated users back to `/login` and authenticated users back to their respective dashboards.
- **Home Route Redirector (`/`):** Evaluates authentication state and routes users to their role-appropriate destination.

---

## 8. Database Domain Models Reference

Rumu V2 is powered by **29 specialized Eloquent models**:

| Domain | Eloquent Models | Purpose |
|---|---|---|
| **Identity & Access** | `User` | Stores credentials, roles (`admin`, `realtor`), plan associations, and avatar URLs. |
| **CRM & Leads** | `Campaign` | Marketing campaigns with lead quotas and date boundaries. |
| | `Lead` | Core prospect entity with contact details, status, source, and customer flags. |
| | `LeadNote` | Internal timestamped discussion notes attached to leads. |
| **Meta Integrations** | `MetaPage` | Connected Facebook Pages with OAuth access tokens. |
| | `MetaSyncLog` | Audit log of automated lead synchronization batches. |
| | `MetaCapiEvent` | Queued and dispatched Conversions API event payloads. |
| | `MetaCapiSetting` | Campaign-level overrides for Pixel and Dataset IDs. |
| **Real Estate Inventory** | `InventoryProject` | Parent property development project. |
| | `InventoryTower` | Specific building or tower within a project. |
| | `InventoryWing` | Architectural wing within a tower. |
| | `InventoryFloor` | Floor level within a tower/wing. |
| | `InventoryUnit` | Individual apartment, office, or commercial unit with pricing and specs. |
| | `InventoryUnitStatusLog` | Historical ledger of unit status changes (`Available` ➔ `Sold`). |
| **Billing & Finance** | `Plan` | Subscription tiers, feature quotas, and pricing. |
| | `Subscription` | Active user subscription record with lifecycle timestamps. |
| | `Payment` | Transaction ledger containing Razorpay payment references and amounts. |
| | `Invoice` | Itemized tax invoice with sequential invoice numbering. |
| | `InvoiceItem` | Line-item records belonging to an invoice. |
| | `Coupon` | Promotional voucher definitions with discount rules and limits. |
| | `CouponUsage` | Audit table logging individual coupon redemptions per user. |
| | `Referral` | Records referral link attributions and customer conversions. |
| | `PlanUpgradeRequest` | Formal requests from realtors to upgrade subscription tiers. |
| **Notifications & System**| `Notification` | System alerts for realtors and administrators. |
| | `BillingNotification` | Log of transactional billing emails dispatched. |
| | `BillingNotificationTemplate`| Customizable email layouts for billing events. |
| | `BillingSetting` | Global financial settings (tax ID, currency, company address). |
| | `BillingSettingAudit` | Historical log of changes made to financial settings. |
| | `Setting` | Global system key-value configuration repository. |

---

## 🏁 Summary

Rumu V2 delivers a scalable real estate CRM architecture. By unifying lead acquisition from Meta ads, conversion tracking through server-side CAPI, structured property inventory management, and automated subscription monetization, Rumu V2 provides a modern CRM solution for today's real estate market.
