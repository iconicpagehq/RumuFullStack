# 🏡 Rumu V2 — Real Estate CRM & Lead Management System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=flat&logo=react&logoColor=black)](https://react.dev)
[![Vite](https://img.shields.io/badge/Vite-5.x-646CFF?style=flat&logo=vite&logoColor=white)](https://vitejs.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**Rumu V2** is a modern, high-performance Customer Relationship Management (CRM) platform engineered specifically for real estate developers, agencies, brokers, and independent realtors. It bridges the gap between lead acquisition and deal closing by providing end-to-end campaign tracking, automated Meta Lead Ads imports, server-side Conversions API (CAPI) syncing, multi-tier property inventory management, and integrated subscription billing.

---

## 📑 Table of Contents

- [Architectural Overview](#-architectural-overview)
- [Key Features](#-key-features)
- [Enterprise Edition](#-enterprise-edition)
- [Technology Stack](#-technology-stack)
- [Repository Structure](#-repository-structure)
- [Prerequisites & System Requirements](#-prerequisites--system-requirements)
- [Local Development Setup](#-local-development-setup)
  - [1. Backend Setup (Laravel 12 API)](#1-backend-setup-laravel-12-api)
  - [2. Frontend Setup (React 18 + Vite)](#2-frontend-setup-react-18--vite)
- [Default Credentials](#-default-credentials)
- [API Architecture & Routes](#-api-architecture--routes)
- [Third-Party Integrations](#-third-party-integrations)
  - [Meta Lead Ads & Conversions API (CAPI)](#meta-lead-ads--conversions-api-capi)
  - [Google & Facebook OAuth](#google--facebook-oauth)
  - [Razorpay Billing & Webhooks](#razorpay-billing--webhooks)
- [Production Deployment](#-production-deployment)
- [Useful Commands](#-useful-commands)
- [License](#-license)

---

## 🏛️ Architectural Overview

Rumu V2 is structured as a decoupled monorepo featuring a stateless RESTful API backend and a single-page application (SPA) client:

```
┌─────────────────────────────────┐          ┌──────────────────────────────────┐
│   React 18 Single-Page App      │  JSON    │   Laravel 12 REST API Server     │
│   (Vite + Tailwind + Zustand)   │ ◄──────► │   (Sanctum Bearer Token Auth)    │
│   http://localhost:5173         │          │   http://localhost:8000          │
└─────────────────────────────────┘          └───────────────┬──────────────────┘
                                                             │
                             ┌───────────────────────────────┼──────────────────────────────┐
                             ▼                               ▼                              ▼
                 ┌───────────────────────┐       ┌───────────────────────┐      ┌───────────────────────┐
                 │ MySQL / MariaDB       │       │ Meta Lead Ads & CAPI  │      │ Razorpay Gateway      │
                 │ Relational Database   │       │ Background Queue Jobs │      │ Webhook Handlers      │
                 └───────────────────────┘       └───────────────────────┘      └───────────────────────┘
```

- **Stateless Authentication:** Uses Laravel Sanctum issuing secure Bearer tokens with full multi-device session revocation support.
- **Role-Based Access Control (RBAC):** Strict middleware enforcement for `admin`, `realtor`, and `enterprise` roles, with enterprise-internal roles `admin`, `manager`, and `employee`.
- **Multi-Tenancy:** Enterprise records carry an `enterprise_id`; cross-tenant access returns 404 and Realtor/Admin endpoints never expose enterprise data.
- **Subscription Enforcement:** Feature access is verified via active subscription guards (`subscription.active.api`); enterprise plan limits are enforced server-side.

---

## ✨ Key Features

### 🏢 Realtor Workspace
- **Dashboard & Pipeline:** Real-time KPI counters, conversion funnel, recent inquiries, campaign velocity, and upcoming/overdue follow-ups from tasks, site visits, and leads.
- **Global Lead Board:** Kanban and table views across all campaigns, stage changes, search/filters, and formula-safe CSV export scoped to the signed-in realtor.
- **Campaign Management:** Full lifecycle control of marketing campaigns with lead caps, start/end schedules, and target analytics.
- **Lead Pipeline:** Detailed lead qualification workflow (`New` ➔ `Contacted` ➔ `Qualified` ➔ `Negotiation` ➔ `Won` / `Lost` / `On Hold`), historical interaction logs, internal notes, and unit mapping.
- **Lead Detail Workspace:** A profile rail with contact details, campaign source, pastel Call / WhatsApp / Note / Activity tiles, collapsible About Lead grid, one-click **Convert to Customer**, and soft **Delete Lead** (policy-scoped, hidden from customers/reports after deletion). The right panel carries a clickable stage rail plus Activity Timeline, Notes, Follow Ups, and Status Audit Trail tabs with transition pills. Follow-ups created here are linked to the lead; the audit trail is derived from status notes (which record who changed the status and when).
- **Tasks & Site Visits:** Personal task list (due dates, priorities, status, completion notes) and site visits linked to leads and inventory, independent of enterprise teams.
- **Calls & Recordings:** Calls start **only in the Rumu Android app** on the salesperson's own SIM — the web dashboard has no call button. The app syncs the handset's call recording to private storage, and the call appears with playback on the lead's **Calls** tab, the **Calls** page, and the dashboard Calls card. Web keeps an **Upload recording** recovery action (MP3/WAV/M4A/MP4/OGG/AMR/AAC/3GP/WebM, up to 50 MB) so history stays complete if the phone recording could not be uploaded, and each call outcome can be recorded.
- **Multi-Source Ingestion:**
  - Manual lead entry
  - Bulk CSV/Excel import from the Lead Board or campaign import page (`.csv`, `.xlsx`, `.xls`; `Name, Email, Phone, Company`; sample CSV and Excel templates)
  - Meta Lead Ads CSV import
  - Automated Meta Lead Gen Form real-time synchronization
- **Meta Conversions API (CAPI):** Server-side conversion dispatch for lead status transitions (`Contacted`, `Won`, etc.) to optimize Meta Ad delivery.
- **Hierarchical Inventory Engine:** Structured property management:
  $$\text{Project} \longrightarrow \text{Tower} \longrightarrow \text{Wing} \longrightarrow \text{Floor} \longrightarrow \text{Unit}$$
  - Availability state tracking (`Available`, `On Hold`, `Booked`, `Sold`).
  - Unit filtering by BHK configuration, pricing, floor, and area.
- **Customer Directory:** Automatically graduates `Won` leads into a persistent customer index.
- **Billing & Account Management:** Active plan status, invoice PDF downloads, usage counters, and upgrade requests.

### 🛡️ Admin Control Center
- **System Executive Dashboard:** Platform-wide metrics, active subscriptions, platform revenue trends, system notifications, and enterprise KPIs (accounts, active members, monthly enterprise leads, plan split).
- **Realtor & User Directory:** Complete user management, account suspension/reactivation, and manual plan assignment.
- **Enterprise Oversight:** Enterprise directory and detail, usage/member/lead summaries, suspend/resume (blocks enterprise APIs and login), and tenant-filtered platform leads.
- **Plan Management Engine:** Create, edit, reorder, and toggle public status of subscription plans with granular feature limits, including enterprise audience, quotas, and feature flags.
- **Upgrade & Seat Request Queue:** Review, approve, or reject plan upgrades and enterprise seat add-ons with automated notifications and capacity updates.
- **Broadcast Notifications:** Compose in-app announcements to realtor, enterprise-admin, enterprise-member, or specific-enterprise audiences with recipient preview and history.
- **Platform Audit Log:** Filterable record of plan, enterprise, seat-request, broadcast, and member administrative actions.
- **Financial & Billing Suite:**
  - Subscription lifecycle management (extend, cancel, suspend).
  - Payment audit logs and transaction details.
  - Invoicing suite with instant PDF generation and email resending.
  - Coupon code engine (percentage / fixed discounts, expiry dates, usage caps).
  - Referral program management.
- **Analytics & Exports:** Revenue analysis, customer cohort trends, and downloadable CSV financial reports.
- **System Configuration:** Dynamic brand appearance tokens, global Meta CAPI credentials, and customizable billing email templates.

---

## 🏢 Enterprise Edition

Rumu also ships a multi-tenant **Enterprise Edition** workspace for developers and agencies with teams. It is
additive: the Realtor Edition remains fully intact and separate.

### Hierarchical Workspace
- **Roles:** Enterprise Admin, Manager, Employee — enforced by backend middleware and scoped queries.
- **Teams & Members:** Create teams, assign managers, add members directly with a login (from Members or Teams), invite members with expiring single-use links, suspend/resume, transfer leads between members, and remove members with a lead transfer/unassign wizard. The Team tab is visible to every enterprise role with role-scoped data.
- **Registration:** Company + admin + plan signup creates the organisation, membership, settings, 14-day trial, and audit entries transactionally.

### Sales Operations
- **Leads & Pipeline:** Assignment, team round-robin, bulk distribution of unassigned leads to a team (round robin or one owner), stage workflow, notes/activity history, deal value, and inventory-unit association. Admins and managers can bulk-import leads from CSV, XLSX, or XLS files (up to 1,000 rows / 2 MB) with optional team/campaign attribution; duplicate emails/phones are skipped and the monthly lead quota is enforced.
- **Tasks & Site Visits:** Assignment, due dates, status tracking, visit outcomes, and automatic follow-up tasks.
- **Targets & Campaigns:** Monthly targets for the company, teams, or members (managers scope to their teams); enterprise campaign attribution with server-side limits.
- **Inventory:** Project → Tower → Wing → Floor → Unit hierarchy with transactional availability transitions (`Available`, `On Hold`, `Booked`, `Sold`). Admins can change any unit, managers can reserve units for team leads, and employees can hold/book units for their own assigned leads.
- **Customers & Reports:** Won leads and booked units, scoped performance reporting, and CSV export with formula-injection protection. Export is admin/manager-only and gated by the `advanced_reports` plan feature.
- **Calls & Recordings:** Calls placed in the Rumu Android app, scoped by hierarchy — employees see their own calls, managers see their teams' calls, admins see the whole organisation. Call playback and outcome updates use the same private-storage pipeline as the Realtor Edition; the web has an upload fallback but no call button.
- **Enterprise Meta Lead Ads:** Org-level Meta page connection (admin-managed), page/form mapping, CAPI settings, manual sync, and webhook ingestion into the owning enterprise, gated by the `meta_integrations` plan feature.
- **Subscriptions, Branding & Audit:** Plan usage limits (managers, employees, teams, monthly leads, inventory units, campaigns), seat add-on requests, invoice PDF downloads, logo/RERA/working-hours branding, platform-admin upgrade/seat approvals, and an admin audit log.

### Enterprise API & Routes
- API prefix: `/api/v1/enterprise` (125 routes)
- Workspace: `/enterprise/*` (role-aware navigation; Team is available to admin, manager, and employee with scoped views)
- Public entry points: `/register` (3-step stepper: account type → details → plan) and `/enterprise/invite/:token`; the legacy `/enterprise/register` redirects to `/register?type=enterprise`

📘 **Full documentation:** [ENTERPRISE.md](file:///c:/xampp/htdocs/rumu-v2/ENTERPRISE.md) — permissions matrix, API surface,
setup, verification results, and an honest list of known limitations.

---

## 🛠️ Technology Stack

| Layer | Technologies |
|---|---|
| **Backend Framework** | PHP 8.2+, Laravel 12 |
| **Authentication** | Laravel Sanctum (Bearer Token), Laravel Socialite |
| **Database & Cache** | MySQL 8.0+ / MariaDB 10.4+, Redis / Database Queues |
| **Frontend Framework** | React 18, Vite 5 |
| **Styling & Design** | Tailwind CSS v3, PostCSS, Custom CSS Design System |
| **State Management** | Zustand (Client state), TanStack Query v5 (Server state caching) |
| **Routing & Forms** | React Router v6, React Hook Form, Zod schema validation |
| **Data Visualization** | Recharts, Headless UI, Heroicons, TanStack Table |
| **Payment Gateway** | Razorpay SDK & Webhook verification |
| **PDF Generation** | Barryvdh Laravel DomPDF |

---

## 📂 Repository Structure

```
rumu-v2/
├── Backend/                       # Laravel 12 API Application
│   ├── app/
│   │   ├── Http/Controllers/Api/  # API Controllers (Admin, Realtor, Enterprise, Auth, Checkout)
│   │   ├── Http/Middleware/       # Sanctum, Role, Enterprise & Subscription guards
│   │   ├── Http/Resources/        # Consistent JSON Resource Transformers
│   │   ├── Jobs/                  # Asynchronous queue jobs (e.g. SendMetaCapiLeadEventJob)
│   │   ├── Models/                # 39 Eloquent Models
│   │   └── Services/              # Meta Graph API, CAPI, CSV Import & Enterprise limit/audit services
│   ├── config/                    # Laravel configuration (cors, auth, services, etc.)
│   ├── database/
│   │   ├── migrations/            # Database schema definitions (enterprise migrations are additive)
│   │   └── seeders/               # Realtor and Enterprise plans, AdminSeeder
│   └── routes/
│       ├── api.php                # Complete REST API routes (/api/v1/*)
│       └── web.php                # Server-to-server Webhooks & OAuth callbacks
│
├── frontend/                      # React 18 SPA (Vite)
│   ├── public/                    # Static assets, branding, and logos
│   ├── src/
│   │   ├── api/                   # Axios client & modular API service calls
│   │   ├── components/
│   │   │   ├── auth/              # Social auth buttons and login widgets
│   │   │   ├── layout/            # Sidebar, Topbar, HeaderActions, Layout shells
│   │   │   ├── shared/            # Brand, StatusBadge, StatsCard, DataTable
│   │   │   └── ui/                # Base primitives (Button, Input, Modal, Badge, etc.)
│   │   ├── hooks/                 # Custom authentication & utility hooks
│   │   ├── pages/
│   │   │   ├── admin/             # All Admin views (Dashboard, Billing, Analytics, etc.)
│   │   │   ├── auth/              # Login, Register, SocialCallback
│   │   │   ├── checkout/          # Plan selection, Razorpay checkout, Success/Failure
│   │   │   ├── enterprise/        # Enterprise workspace (leads, teams, inventory, billing, etc.)
│   │   │   └── realtor/           # All Realtor views (Dashboard, Leads, Inventory, etc.)
│   │   ├── router/                # React Router setup with Role and Auth guards
│   │   ├── stores/                # Zustand stores (authStore, themeStore, notificationStore)
│   │   └── utils/                 # Formatters, error handlers, and Zod validators
│   ├── tailwind.config.js         # Design system tokens and color mapping
│   └── vite.config.js             # Vite config with dev reverse proxy
│
└── deploy/                        # Production deployment manifests
    └── nginx/
        ├── api.rumu.in.conf       # Nginx config for Laravel Backend
        └── app.rumu.in.conf       # Nginx config for React SPA
```

---

## 📋 Prerequisites & System Requirements

Ensure the following tools are installed on your machine:

- **PHP:** `^8.2` with extensions: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `bcmath`, `xml`
- **Composer:** `^2.x`
- **Node.js:** `^18.x` or `^20.x` LTS
- **Package Manager:** `npm` (`^9.x` or `^10.x`)
- **Database:** MySQL `^8.0` or MariaDB `^10.4` (e.g., via XAMPP)

---

## 🚀 Local Development Setup

Follow these steps to set up Rumu V2 locally:

### 1. Backend Setup (Laravel 12 API)

Open your terminal and navigate to the `Backend` directory:

```bash
cd Backend
```

#### Install PHP dependencies:
```bash
composer install
```

#### Setup Environment File:
```bash
copy .env.example .env
```

#### Generate Application Key:
```bash
php artisan key:generate
```

#### Configure Database:
Create a MySQL database (e.g. `rumu_db` in phpMyAdmin / MySQL CLI) and edit `Backend/.env`:
```env
APP_NAME="Rumu App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rumu_db
DB_USERNAME=root
DB_PASSWORD=

# CORS & SPA Settings
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
```

#### Run Database Migrations & Seeders:
```bash
php artisan migrate --seed
```
> ⚠️ Use additive migrations. **Never run `migrate:fresh` against a database containing real data.**
> Existing installations can add the Enterprise Edition with `php artisan migrate` and
> `php artisan db:seed --class=EnterprisePlanSeeder`.

#### Link Storage Directory:
```bash
php artisan storage:link
```

#### Start the Laravel Server:
```bash
php artisan serve
```
> The API will now be listening on `http://127.0.0.1:8000`.

#### Start Queue Worker (Required for Meta CAPI & Email Notifications):
In a separate terminal window:
```bash
cd Backend
php artisan queue:work --queue=default,meta-leads
```
> Meta lead imports and CAPI events run on the `meta-leads` queue. App call recordings are uploaded directly by
> the Android client, so no call-recording worker or `calls` queue is required.

---

### 2. Frontend Setup (React 18 + Vite)

Open a new terminal and navigate to the `frontend` directory:

```bash
cd frontend
```

#### Install Node modules:
```bash
npm install
```

#### Setup Frontend Environment:
```bash
copy .env.example .env
```
Ensure `frontend/.env` contains:
```env
VITE_API_BASE_URL=/api/v1
```
*(Vite is preconfigured in `vite.config.js` to automatically proxy `/api`, `/sanctum`, and `/storage` requests to `http://localhost:8000`).*

#### Start the Development Server:
```bash
npm run dev
```
> ⚠️ **Important:** Use `npm run dev` (not `npm dev run`).  
> Open your browser at **`http://localhost:5173`**.

---

## 🔑 Default Credentials

After running `php artisan db:seed`, use the default administrator credentials:

| Field | Value |
|---|---|
| **Role** | System Administrator |
| **Email** | `admin@rumu.in` |
| **Password** | `Rumu1100@` |
| **Admin Portal** | `http://localhost:5173/login` ➔ `/admin/dashboard` |

*Registration uses one 3-step stepper at `http://localhost:5173/register`: choose **Realtor** or
**Enterprise**, enter the matching details, then pick a plan. Realtor signup can start on the free plan
(paid plans continue to checkout); Enterprise signup starts a 14-day trial. `/register?type=enterprise`
(and the legacy `/enterprise/register`) preselects Enterprise. No Enterprise workspace or default
enterprise credentials are seeded.*

---

## 🔌 API Architecture & Routes

All API endpoints are versioned under `/api/v1/` and return standardized JSON payloads:

### Public & Authentication
- `POST /api/v1/auth/register` — Realtor registration
- `POST /api/v1/auth/login` — Authentication & Bearer token issue
- `GET  /api/v1/auth/social/{provider}/url` — OAuth redirect generator
- `POST /api/v1/auth/social/{provider}` — OAuth access code token exchange
- `GET  /api/v1/auth/me` — Authenticated user profile `[Auth]`
- `POST /api/v1/auth/logout` — Revoke current token `[Auth]`
- `POST /api/v1/auth/logout-all` — Revoke all device tokens `[Auth]`

### Checkout & Billing
- `GET    /api/v1/checkout/plans` — Available subscription tiers
- `GET    /api/v1/checkout/summary` — Cart calculation with taxes `[Auth]`
- `POST   /api/v1/checkout/coupon` — Apply discount voucher `[Auth]`
- `DELETE /api/v1/checkout/coupon` — Remove voucher `[Auth]`
- `POST   /api/v1/checkout/order` — Create Razorpay order `[Auth]`
- `POST   /api/v1/checkout/verify` — Validate payment signature `[Auth]`

### Realtor Workspace `[Realtor Guard]`
- **Campaigns:** `GET|POST /api/v1/realtor/campaigns`, `GET|PUT|DELETE /api/v1/realtor/campaigns/{id}`
- **Global Leads:** `GET /api/v1/realtor/leads`, `GET /api/v1/realtor/leads/export`, `PATCH /api/v1/realtor/leads/{id}/status`, `DELETE /api/v1/realtor/leads/{id}` (soft delete)
- **Campaign Leads:** `GET|POST /api/v1/realtor/campaigns/{id}/leads`, `PATCH /api/v1/realtor/leads/{id}/status`, `POST /api/v1/realtor/leads/{id}/notes`, `DELETE /api/v1/realtor/leads/{id}` (soft delete)
- **Tasks & Site Visits:** `GET|POST /api/v1/realtor/tasks`, `GET|PUT|DELETE /api/v1/realtor/tasks/{id}`, `PATCH .../tasks/{id}/status`, `GET|POST /api/v1/realtor/site-visits`, `GET|PUT|DELETE /api/v1/realtor/site-visits/{id}`, `PATCH .../site-visits/{id}/status`
- **Lead Imports:** `POST /api/v1/realtor/campaigns/{id}/import/csv`, `POST /api/v1/realtor/campaigns/{id}/import/meta/csv`, `POST /api/v1/realtor/campaigns/{id}/meta/sync`
- **Inventory:** `GET|POST /api/v1/realtor/inventory/projects`, `POST .../towers`, `POST .../wings`, `POST .../floors`, `POST .../units`, `PATCH .../units/{id}/status`
- **Customers:** `GET /api/v1/realtor/customers`
- **Calls (synced from the Android app):** `GET /api/v1/realtor/calls`, `GET .../calls/summary`, `GET .../calls/{id}`, `POST .../calls/app`, `POST .../calls/{id}/recording`, `POST .../calls/import` (web fallback), `PATCH .../calls/{id}/disposition`, `GET|DELETE .../calls/{id}/recording`
- **Billing & Plans:** `GET /api/v1/realtor/billing`, `GET /api/v1/realtor/billing/invoices/{id}/download`, `POST /api/v1/realtor/plans/upgrade`

### Admin Management `[Admin Guard]`
- **Dashboard:** `GET /api/v1/admin/dashboard`, `GET /api/v1/admin/analytics/enterprises`
- **User & Realtor Oversight:** `GET /api/v1/admin/users`, `GET /api/v1/admin/realtors`, `POST /api/v1/admin/realtors/{id}/suspend`
- **Enterprise Oversight:** `GET /api/v1/admin/enterprises`, `GET /api/v1/admin/enterprises/{id}`, `POST /api/v1/admin/enterprises/{id}/suspend|resume`
- **Platform Leads:** `GET /api/v1/admin/leads?tenant=all|realtor|enterprise&enterprise_id=...`
- **Plans & Upgrades:** `GET|POST|PUT /api/v1/admin/plans`, `POST /api/v1/admin/upgrade-requests/{id}/approve`, `GET /api/v1/admin/seat-requests`, `POST /api/v1/admin/seat-requests/{id}/approve|reject`
- **Broadcasts & Audit:** `GET|POST /api/v1/admin/broadcasts`, `POST /api/v1/admin/broadcasts/preview`, `GET /api/v1/admin/audit-logs`
- **Billing Suite:** `GET /api/v1/admin/billing/subscriptions`, `GET /api/v1/admin/billing/invoices`, `CRUD /api/v1/admin/billing/coupons`
- **Analytics:** `GET /api/v1/admin/analytics/revenue`, `GET /api/v1/admin/analytics/payments`, `POST /api/v1/admin/analytics/reports/export`

### Enterprise Workspace `[Enterprise Guard]`
- **Public:** `GET /api/v1/enterprise/plans`, `POST /api/v1/enterprise/register`, `GET|POST /api/v1/enterprise/invitations/{token}[/accept]`
- **Leads:** `GET|POST /api/v1/enterprise/leads`, `GET|PUT /api/v1/enterprise/leads/{id}`, `DELETE /api/v1/enterprise/leads/{id}` (admin/manager, soft delete, audit-logged), `PATCH .../stage`, `POST .../assign|round-robin|notes`, `POST .../leads/distribute`
- **Teams & Members:** `GET|POST /api/v1/enterprise/teams`, `GET|PUT|DELETE /api/v1/enterprise/teams/{id}`, `GET|POST /api/v1/enterprise/users`, `POST .../users/{id}/suspend|resume`, `POST .../users/{id}/transfer` (admin/manager), `POST .../users/{id}/remove` (admin)
- **Invitations:** `GET|POST /api/v1/enterprise/invitations`, `POST .../invitations/{id}/resend|revoke`
- **Tasks & Visits:** `GET|POST|PUT|DELETE /api/v1/enterprise/tasks`, `GET|POST|PUT|DELETE /api/v1/enterprise/site-visits`
- **Inventory:** `GET|POST /api/v1/enterprise/inventory/projects`, `POST .../towers|wings|floors|units`, `PATCH .../units/{id}/status` (role-scoped)
- **Growth:** `GET|POST /api/v1/enterprise/targets`, `GET|POST /api/v1/enterprise/campaigns`, `GET /api/v1/enterprise/customers`, `GET /api/v1/enterprise/reports`, `GET /api/v1/enterprise/reports/export` (admin/manager + `advanced_reports`)
- **Enterprise Meta:** `GET /api/v1/enterprise/meta/pages`, `GET .../meta/connect-url`, `POST .../meta/connect|disconnect`, `GET .../campaigns/{id}/meta-integration`, `GET .../campaigns/{id}/meta/forms`, `POST .../campaigns/{id}/meta/mapping|sync`, `POST .../campaigns/{id}/meta-integration/capi|disconnect-form`
- **Account:** `GET /api/v1/enterprise/settings`, `PUT /api/v1/enterprise/settings`, `POST|DELETE /api/v1/enterprise/settings/logo`, `GET /api/v1/enterprise/billing`, `POST .../billing/upgrade`, `POST .../billing/seat-requests`, `GET .../billing/invoices/{id}/download`, `GET /api/v1/enterprise/audit-logs`
- **Calls (synced from the Android app):** `GET /api/v1/enterprise/calls`, `GET .../calls/summary`, `GET .../calls/{id}`, `POST .../calls/app`, `POST .../calls/{id}/recording`, `POST .../calls/import` (web fallback), `PATCH .../calls/{id}/disposition`, `GET|DELETE .../calls/{id}/recording` (scoped by hierarchy)

### Mobile App `[Auth]`
- `GET  /api/v1/mobile/bootstrap` — Edition, calling mode, recording limits and consent copy for the Android app
- `POST /api/v1/mobile/devices` — Register/refresh a handset (device UUID, model, OS, recorder source)

> `POST /api/v1/{edition}/calls/initiate` is **deprecated** and always returns **410 Gone**; calls cannot be
> started from the web dashboard.

See [ENTERPRISE.md](file:///c:/xampp/htdocs/rumu-v2/ENTERPRISE.md) for the full enterprise API table, role matrix,
and limitations.

---

## 🌐 Third-Party Integrations

### Meta Lead Ads & Conversions API (CAPI)
1. Register a Meta Business App at [developers.facebook.com](https://developers.facebook.com).
2. Configure **OAuth Redirect URIs**:
   - `http://127.0.0.1:8000/auth/facebook/callback`
   - `http://127.0.0.1:8000/connect/facebook/callback`
3. Add the following environment keys in `Backend/.env`:
   ```env
   META_CAPI_ENABLED=true
   META_CAPI_DATASET_ID="your_dataset_id"
   META_CAPI_ACCESS_TOKEN="your_system_user_token"
   META_CAPI_GRAPH_VERSION="v25.0"
   ```

### Google & Facebook OAuth
- **Google Cloud Console:** Create an OAuth 2.0 Web Client with JavaScript origin `http://127.0.0.1:8000` and redirect URI `http://127.0.0.1:8000/auth/google/callback`.
- Configure `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `Backend/.env`.

### Razorpay Billing & Webhooks
1. Obtain test API keys from the [Razorpay Dashboard](https://dashboard.razorpay.com).
2. Set credentials in `Backend/.env`:
   ```env
   RAZORPAY_KEY="rzp_test_..."
   RAZORPAY_SECRET="..."
   RAZORPAY_WEBHOOK_SECRET="..."
   ```
3. Set your webhook endpoint to `https://api.rumu.in/webhooks/razorpay` listening for `payment.captured`, `order.paid`, and `subscription.activated`.

### App Calling & Recording Sync

Rumu does **not** place cloud calls. Salespeople dial leads from their own SIM inside the Rumu Android app,
and the app uploads the handset's call recording to the backend. The web dashboard then shows the call with
playback on the lead's **Calls** tab, the **Calls** page, and the dashboard Calls card. There is no call button
on the web.

How the flow works:

1. The app registers the device: `POST /api/v1/mobile/devices` (device UUID, model, OS, recorder source).
2. When a call ends, the app creates the call record: `POST /api/v1/{edition}/calls/app`. It sends a
   `client_call_id` (UUID), `lead_id`, direction, start/end timestamps, duration, outcome, and
   `consent_confirmed=true`. Retrying with the same `client_call_id` returns the existing call (**200**)
   instead of duplicating it.
3. The app uploads the audio: `POST /api/v1/{edition}/calls/{call_id}/recording` (multipart `recording`).
   Duplicate audio is reported with `duplicate_recording: true`; the same recording cannot be attached to a
   second call, and only the user who created the call may upload.
4. Recordings are validated (magic bytes, extension, 50 MB cap), stored on a **private** disk at
   `storage/app/private/call-recordings/{edition}/{Y}/{m}`, and served only through the authenticated
   `GET .../calls/{id}/recording` endpoint (`Cache-Control: private, no-store`), which writes an access-audit
   row. Raw file paths are never exposed to the browser.

Web keeps a **Upload recording** fallback so history stays complete if the phone could not sync its file.
It accepts MP3, WAV, M4A, MP4, OGG, AMR, AAC, 3GP, and WebM.

Relevant configuration (`Backend/.env`):

```env
CALL_RECORDING_DISK=local
CALL_RECORDING_RETENTION_DAYS=90
CALL_RECORDING_MAX_BYTES=52428800
CALL_DAILY_LIMIT_PER_USER=50
CALL_BACKFILL_DAYS=180
CALL_APP_MIN_VERSION=1.0.0
```

Notes:
- Calling mode is fixed to `app_only` (`config/calls.php`); `POST .../calls/initiate` returns **410 Gone**
  for both editions.
- Android recording is subject to OEM call-recorder availability and local consent law. Rumu does not record
  audio itself or stealthily; the app confirms consent before the dialer opens and only uploads a file the
  user chooses to save.
- The native Android client lives in the Rumu Android project and now includes consent-gated dialing,
  session saving, recorder discovery and background upload. This repository remains the API + web dashboard;
  the web keeps **Upload recording** as a fallback for calls whose audio could not sync from the phone.

---

## 🚢 Production Deployment

Rumu V2 is configured for deployment using isolated subdomains:

- **Frontend Client:** `app.rumu.in` ➔ Serves pre-built static assets from `frontend/dist`.
- **Backend API:** `api.rumu.in` ➔ Serves Laravel API via PHP-FPM from `Backend/public`.

### Building the Frontend for Production:
```bash
cd frontend
npm run build
```
*(The production bundle will be generated in `frontend/dist`).*

### Nginx Virtual Host Setup:
Production Nginx templates are provided in the [deploy/nginx/](file:///c:/xampp/htdocs/rumu-v2/deploy/nginx) directory:
- [app.rumu.in.conf](file:///c:/xampp/htdocs/rumu-v2/deploy/nginx/app.rumu.in.conf) — SPA routing fallback with cache headers for assets.
- [api.rumu.in.conf](file:///c:/xampp/htdocs/rumu-v2/deploy/nginx/api.rumu.in.conf) — FastCGI proxy to `php8.2-fpm.sock` with request payload limits.

### Laravel Production Optimization:
```bash
cd Backend
php artisan migrate
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> `migrate` here is additive: the Calls feature ships as
> `database/migrations/2026_11_01_100000_create_call_tables.php` plus the additive app-calling migrations
> `2026_11_08_120000_add_app_calling_to_call_logs.php` and `2026_11_08_120100_create_mobile_devices_table.php`.
> Never run `migrate:fresh` or `db:wipe` on a database with real data.
>
> Keep a supervised queue worker running for `default,meta-leads`; without it, Meta lead webhooks stop
> importing and CAPI events stall. App call recordings upload synchronously and need no extra worker.

> 🔐 **Security:** Rotate any Meta, Razorpay, Google/Facebook OAuth, and mail credentials that were ever
> stored in a checked-in `.env` or `.env.example` before going live. Keep real values only in the deployment
> environment, and verify `.env` is not web-accessible.

---

## ⚡ Useful Commands

| Task | Command | Directory |
|---|---|---|
| **Run Frontend Dev Server** | `npm run dev` | `frontend/` |
| **Build Frontend Production** | `npm run build` | `frontend/` |
| **Run Laravel Dev Server** | `php artisan serve` | `Backend/` |
| **Run Queue Worker** | `php artisan queue:work --queue=default,meta-leads` | `Backend/` |
| **Run Migrations & Seeders** | `php artisan migrate --seed` | `Backend/` |
| **Run Automated Backend Tests** | `php artisan test` | `Backend/` |
| **Clear Application Cache** | `php artisan optimize:clear` | `Backend/` |

---

## 📄 License

This software is licensed under the [MIT License](LICENSE).
