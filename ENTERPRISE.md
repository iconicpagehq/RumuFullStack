# Rumu V2 — Enterprise Edition

The Enterprise Edition is an additive workspace inside Rumu V2 for real-estate organisations that need
multiple teams, role-based access, lead distribution, targets, and inventory allocation. It runs on the
same Laravel 12 API and React 18 SPA as the Realtor Edition and shares authentication, billing plans,
notifications, and the design system.

> The Realtor Edition is unchanged. Existing Realtor routes, pages, models, migrations, and API endpoints
> were preserved. Enterprise data is separated by `enterprise_id` and is never returned by Realtor or
> platform-admin endpoints.

---

## 1. What was added

| Area | Additions |
|---|---|
| Database | 14 additive migrations: enterprises, memberships, teams, team memberships, invitations, enterprise columns on leads/campaigns/inventory, sales tables (tasks, site visits, targets, audit logs), enterprise plan limits, `users.role = enterprise`, branding/seat columns, seat requests, Meta tenant scoping, Realtor personal tasks/site visits, admin broadcasts, platform audit logs, and the reporting-line self-reference on memberships/invitations |
| Backend | API controllers, tenant/role middleware guards, resources, enterprise limit/audit/Meta/hierarchy services, platform audit, broadcasts, a reporting-line backfill command, and 5 dedicated enterprise test suites |
| Frontend | Enterprise registration, invitation acceptance, 23 authenticated workspace pages under `/enterprise/*` (including the organization chart), platform-admin enterprise oversight/broadcast/audit pages, and Realtor global lead board, tasks, and site visits |
| Plans | `Enterprise Starter` and `Enterprise Growth` seeders, plan audience `enterprise`, plan-gated advanced reports/Meta, seat add-on requests, plus platform-admin approval of enterprise upgrades |
| Calls (later addition) | Three additive migrations (`2026_11_01_100000_create_call_tables.php`, `2026_11_08_120000_add_app_calling_to_call_logs.php`, `2026_11_08_120100_create_mobile_devices_table.php`); hierarchy-scoped Calls API, app-only call creation (`POST .../calls/app`), authenticated handset recording upload, private recording streaming with access audit, and the Calls page, dashboard card, and lead-detail tab for both editions |

No existing migration was edited destructively. `users.role` was widened with an additive `ALTER` and its
`down()` refuses to narrow the enum while enterprise users exist. No `migrate:fresh` was used against the
existing database; a pre-change SQL backup is at
`Backend/storage/backups/rumu_pre_enterprise_20261025_154836.sql`.

---

## 2. Roles and permissions

An enterprise account is a `users.role = enterprise` user with exactly one **active enterprise membership**
(`admin`, `manager`, or `employee`). Suspended memberships are blocked by `enterprise.api` middleware.

| Capability | Admin | Manager | Employee |
|---|---|---|---|
| View dashboard (scoped) | ✅ all | ✅ reporting line + managed teams | ✅ own (or reporting line when a lead) |
| View/create/edit leads and change stage | ✅ all | ✅ owned teams + reporting line + assigned | ✅ assigned (or reporting line when a lead) |
| Assign leads / round-robin | ✅ | ✅ within managed teams | — unless the employee has direct reports, then within their reporting line |
| Bulk-distribute selected leads to a team (round robin or one owner) | ✅ | ✅ within managed teams | — |
| View tasks / site visits | ✅ all | ✅ team + reporting line + own | ✅ own/assigned (or reporting line when a lead) |
| Create tasks and site visits | ✅ | ✅ | ✅ |
| Assign tasks/visits to a member | ✅ | ✅ within managed teams | ✅ within their reporting line when a lead |
| Delete tasks | ✅ | ✅ visible | ✅ only tasks they created |
| Delete site visits | ✅ | ✅ visible (not completed) | ✅ visible (not completed) |
| View customers | ✅ all | ✅ managed teams | ✅ own won/booked customers |
| View reports | ✅ all | ✅ team and reporting-line scoped | ✅ own (or reporting line when a lead) |
| Export reports to CSV | ✅ (plan feature) | ✅ team-scoped (plan feature) | — |
| View teams | ✅ all | ✅ managed | ✅ own team (read-only) |
| View organization chart | ✅ full | ✅ own reporting line + managed teams | ✅ own reporting line when a lead, otherwise restricted |
| Change a member's reporting line (manager) | ✅ | — | — |
| Create/edit/delete teams, add/remove team members | ✅ | — | — |
| View members | ✅ all | ✅ managed teams + reporting line | ✅ self + direct/indirect reports when a lead |
| Add a member directly (create login) | ✅ | — | — |
| Edit/suspend/resume members | ✅ | — | — |
| Remove a member (with lead transfer/unassign) | ✅ | — | — |
| Transfer leads between members | ✅ any member | ✅ within managed teams | — |
| View member activity history | ✅ all | ✅ managed teams | — |
| Invite/resend/revoke invitations | ✅ | — | — |
| Manage inventory hierarchy and units | ✅ | — | — |
| Change unit availability | ✅ any unit | ✅ units for managed-team leads | ✅ own assigned leads (`hold`/`booked` only) |
| View inventory | ✅ | ✅ | ✅ |
| Create/update targets | ✅ company/team/member | ✅ managed team/member | — |
| Delete targets | ✅ | ✅ | — |
| Create/update campaigns | ✅ | ✅ | — |
| Delete campaigns | ✅ | — | — |
| View campaigns | ✅ | ✅ | ✅ |
| Connect/disconnect enterprise Meta pages | ✅ | — | — |
| Map Meta forms, configure CAPI, sync leads | ✅ | ✅ own-team campaigns | — |
| View billing, invoices, and seat usage | ✅ | — | — |
| Request a plan upgrade or seat add-on | ✅ | — | — |
| Download invoice PDFs | ✅ | — | — |
| Edit enterprise settings, branding, and logo | ✅ | — | — |
| Edit own profile/password | ✅ | ✅ | ✅ |
| View audit logs | ✅ | — | — |

Authorization is enforced in Laravel, not just the UI:

- `EnsureEnterpriseApi` requires an active enterprise user, membership, and enterprise.
- `EnsureEnterpriseRole` returns `403` for role violations.
- Every controller re-checks roles and tenant ownership.
- Cross-tenant record IDs return **404**, not 403, to avoid leaking existence.
- Inventory hierarchy mutations stay admin-only; unit availability changes are role-scoped in the
  controller (admin any unit, manager team-lead units, employee own lead `hold`/`booked` only).
- Settings, billing, invitations, audit logs, and member removal are admin-only; managers can transfer
  leads and set targets only within teams they manage. Report export requires `advanced_reports` and
  returns `403` with `code: feature_locked` otherwise.
- Single-use invitation tokens are stored only as SHA-256 hashes, expire after 7 days, and are consumed
  inside a transaction with a row lock.
- Directly added members are created by an admin with an admin-set password; the password is never
  returned by the API, logged, or emailed.

Managers see the members and records of teams they manage (via `teams.manager_id` or team membership),
plus records assigned to them. Employees see only their own assigned leads, tasks, visits, and customers.

### Reporting hierarchy

The organisation is a self-referencing tree on `enterprise_memberships.reports_to_membership_id`.
Company admins are always roots and cannot report to anyone. Every other member has exactly one active
manager and can have any number of direct reports, so chains are multi-level
(Admin → Manager → Employee → Employee). Invitations store the chosen manager the same way so the line is
created at acceptance.

- **"Lead employee" is derived, not a role.** Any `employee` membership with at least one direct report is
  a lead employee. There is no fourth role and no new enum value. The badge appears only for employees;
  managers and admins are never labelled "Lead".
- **Server-side scope** is computed by `EnterpriseHierarchyService`:
  - admin → all non-removed memberships;
  - manager → self + reporting subtree + members of teams they manage and their subtrees;
  - lead employee → self + reporting subtree;
  - plain employee → self only.
  Leads, tasks, site visits, targets, customers, dashboard, reports, member stats, and assignment pickers
  all use these scopes. Cross-tenant IDs still return **404**; role violations return **403**.
- **Hierarchy changes are admin-only.** `PATCH users/{membership}/manager` validates the target manager is
  active, in the same enterprise, is not the member, and is not inside the member's own subtree (cycle
  prevention). Company admins can never be given a manager. Changes are audited as
  `member.manager_changed`.
- **Removing a member with direct reports** requires a `reports_action`: reparent to their manager, reparent
  to the company admin, or reassign to a chosen active member outside the removed subtree. The response
  reports how many lines moved (and these are audited).
- **Existing workspaces need a backfill** because reporting lines are new. Run
  `php artisan enterprise:backfill-reporting-lines --dry-run` first, then without `--dry-run`. Admins stay
  roots; each non-admin is assigned to their primary team's manager, falling back to the first active admin.
- The organization chart (`/enterprise/organization`) renders the tree the viewer is allowed to see,
  with direct-report and chain counts, search, collapse/expand, and an admin **Change manager** action.

---

## 3. Subscription plans and limits

Enterprise plans use the existing `plans` table with `audience = enterprise`:

| Limit | Enterprise Starter | Enterprise Growth |
|---|---:|---:|
| Managers | 2 | 10 |
| Employees | 15 | 100 |
| Teams | 3 | 20 |
| Monthly leads | 500 | 5,000 |
| Inventory units | 200 | 2,000 |
| Campaigns | 5 | Unlimited |
| Advanced reports (flag) | No | Yes |
| Meta integrations (flag) | No | Yes |
| Price (INR) | ₹4,999 | ₹14,999 |

Limits are checked server-side by `EnterpriseLimitService` when creating members, invitations, teams,
leads, campaigns, and inventory. Null limits mean unlimited. Percentages are returned as `null` (and the
UI shows "Unlimited on your plan") rather than a misleading progress bar.

Two plan flags are enforced: `advanced_reports` gates CSV export (`EnterpriseReportController@export`),
and `meta_integrations` gates every enterprise Meta page/form/CAPI/sync route. Both return `403` JSON with
`code: feature_locked` so the SPA can show an upgrade call-to-action. All other enterprise capabilities —
basic reports, tasks, visits, targets, audit logs — are available on every enterprise plan.

Enterprise admins can request seat add-ons (extra managers/employees) from Billing. Requests are reviewed
by a platform admin at `/admin/upgrade-requests` (Seat requests tab); approval increments
`enterprises.extra_managers` / `extra_employees` inside a transaction, and those extra seats are added to
the plan quota by `EnterpriseLimitService`. Seat add-ons are not charged online.

Enterprise registration starts a **14-day trial** and records the subscription with a 14-day expiry.
There is no self-serve enterprise checkout; an admin submits an upgrade request and a platform admin
approves or rejects it from `/admin/upgrade-requests`. Approval switches the enterprise to the requested
plan and clears the trial. Invoices are platform-managed, but an enterprise admin can download any invoice
PDF from Billing (owner-scoped; cross-tenant invoice IDs return 404).

Use `php artisan db:seed --class=EnterprisePlanSeeder` to (re)create the enterprise plans on an existing
database. `DatabaseSeeder` now calls it automatically for fresh installs.

---

## 4. Workspace features

- **Dashboard** — scoped KPIs, pipeline funnel, team performance, recent leads, upcoming visits/tasks.
- **Leads** — enterprise pipeline (`new`, `contacted`, `qualified`, `negotiation`, `won`, `lost`,
  `on_hold`), assignment, round-robin within a team, notes/activity history, deal value, inventory unit
  association, and won-lead customer graduation. Admins and managers can also **bulk-import leads from
  CSV, XLSX, or XLS files** (`Name, Email, Phone, Company`, up to 1,000 rows / 2 MB) on the Leads page,
  optionally attributing rows to a team and campaign; sample CSV and Excel templates are downloadable from
  the import dialog; duplicates already in the workspace or repeated within the file are skipped, and the
  monthly lead quota is checked before any row is written. The **Lead distribution** page lets admins and managers
  select up to 100 unassigned leads and distribute them to a team in one transaction, either by round
  robin (least recently assigned, with a current-load tie-break) or to one chosen owner.
  The **lead detail workspace** gives each lead a profile rail (stage/priority badges, assigned salesperson
  with inline reassign, Call / WhatsApp / Note / Activity tiles, collapsible About Lead grid, **Convert to
  Customer** and admin/manager-only **Delete Lead**) and a right-hand stage rail where only transitions
  allowed by the server are clickable. Tabs cover Activity Timeline, Notes, Follow Ups, Status Audit Trail
  (`from → to` pills), and Site Visits. Deleting a lead is a soft delete recorded in the audit log as
  `lead.deleted`; the lead disappears from lists, reports, and customers for every member.
- **Tasks** — create/assign, due dates, priorities, status changes, lead/project links, follow-ups created
  when closing a site visit. Assignees are limited to the viewer's hierarchy scope; lead employees can
  assign within their reporting line.
- **Site visits** — schedule against a lead/project/unit, assign any member visible in the hierarchy
  (including an admin scheduling for themselves), record outcomes, create a follow-up task.
- **Inventory** — Project → Tower → Wing → Floor → Unit hierarchy, unit availability transitions
  (`available`, `on_hold`, `booked`, `sold`), status logs, bulk unit creation, search/filtering. Unit
  booking is transactional and tenant-scoped; admins can change any unit, managers can hold/book units for
  leads in their teams, and employees can hold/book units for their own assigned leads.
- **Targets** — monthly revenue/deals/leads targets for the company, a team, or a member, with achievement
  percentages. Managers can set targets for their team and for employees in that team.
- **Customers** — won leads and booked/sold unit holders, scoped by role.
- **Campaigns & Meta Lead Ads** — enterprise campaign attribution plus an org-level Meta connection
  (admin-managed), page/form mapping, CAPI settings, manual sync, and webhook ingestion into the owning
  enterprise. Imported leads inherit the campaign's team and enterprise isolation.
- **Reports** — period filters, team/employee performance, lead source and stage breakdowns, and CSV export
  for leads, deals, visits, and tasks. Export requires the `advanced_reports` plan feature and admin/manager
  role; employees can view their own reports but not export. CSV output is formula-injection safe.
- **Teams** — the Team tab is available to every enterprise role: admins manage all teams, managers see
  teams they manage, and employees get a read-only view of their own team. The admin Team page has direct
  **Add manager** and **Add employee** actions (each opens the member form with the role preselected), plus
  team creation, manager assignment, membership management, team-filtered lead links, and a quick link into
  lead distribution.
- **Organization** — a server-scoped reporting tree with per-person direct-report and chain counts, search
  that keeps ancestor paths, collapse/expand, and an admin **Change manager** dialog. Plain employees see a
  restricted state instead of the chart.
- **Members** — admins can add an employee or manager directly with a login password (from the Members or
  Teams page), or send an email invitation. The form sets **Reports to** (company admin by default), and a
  lead employee is derived automatically when someone gains direct reports. Role/team/status management,
  suspend/resume, lead transfer between members, member removal (with lead transfer or unassign, direct
  reports reparented/reassigned, token revocation, and preserved history), and activity history are
  included. Member detail shows the manager, direct reports, and chain size.
- **Invitations** — email invitations with hashed single-use links, resend, revoke, for members who
  should choose their own password.
- **Notifications** — in-app notifications for assignments, task/visit events, deal wins, and platform-admin
  broadcasts, with unread counts and mark-all-read.
- **Billing** — trial/plan status, usage vs limits (including extra seats), available enterprise plans,
  upgrade requests, seat add-on requests and history, and invoice PDF downloads.
- **Settings** — personal profile/password for every role; company name, logo, RERA number, working hours,
  auto-distribution rules, and billing interval for admins. All members see read-only company branding.
- **Audit log** — admin-only, filterable record of administrative and workflow actions.

---

## 5. API surface

All routes are under `/api/v1/enterprise`. Public registration and invitation acceptance use their own
throttles; everything else requires `auth:sanctum` + `enterprise.api`.

| Group | Routes |
|---|---|
| Public | `GET plans`, `POST register`, `GET invitations/{token}`, `POST invitations/{token}/accept` |
| Context | `GET me`, `GET dashboard`, `GET reference-data` |
| Leads | `GET|POST leads`, `GET|PUT|DELETE leads/{lead}` (delete: admin/manager, soft delete), `PATCH leads/{lead}/stage`, `POST leads/{lead}/notes`, `POST leads/{lead}/assign`, `POST leads/{lead}/round-robin`, `POST leads/distribute` |
| Members | `GET|POST users`, `GET users/{membership}`, `GET users/{membership}/history`, `GET users/{membership}/reports`, `PUT users/{membership}`, `POST users/{membership}/suspend|resume` (admin), `POST users/{membership}/transfer` (admin/manager), `PATCH users/{membership}/manager` (admin), `POST users/{membership}/remove` (admin) |
| Hierarchy | `GET organization/tree` (scoped to the viewer: full organisation, reporting line, or restricted) |
| Invitations | `GET|POST invitations`, `POST invitations/{invitation}/resend|revoke` |
| Teams | `GET|POST teams`, `GET|PUT|DELETE teams/{team}`, `POST/DELETE teams/{team}/members[/{user}]` |
| Tasks | `GET|POST tasks`, `GET|PUT|DELETE tasks/{task}`, `PATCH tasks/{task}/status` |
| Site visits | `GET|POST site-visits`, `GET|PUT|DELETE site-visits/{siteVisit}`, `PATCH site-visits/{siteVisit}/status` |
| Inventory | `GET inventory/projects[/{project}]`, `GET inventory/units/search`, `GET inventory/floors/{floor}/units`; admin-only create/update/delete for projects, towers, wings, floors, and units; `PATCH units/{unit}/status` available to all roles with controller-enforced scope |
| Targets | `GET targets`; `POST|PUT|DELETE` for admin/manager |
| Customers | `GET customers` |
| Campaigns | `GET campaigns[/{campaign}]`; `POST|PUT` for admin/manager; `DELETE` admin |
| Meta (admin/manager, `meta_integrations`) | `GET meta/pages`, `GET meta/connect-url`, `POST meta/connect|disconnect` (admin); `GET campaigns/{campaign}/meta-integration`, `GET campaigns/{campaign}/meta/forms`, `POST campaigns/{campaign}/meta/mapping`, `POST campaigns/{campaign}/meta-integration/capi|disconnect-form`, `POST campaigns/{campaign}/meta/sync` |
| Reports | `GET reports`, `GET reports/targets`; `GET reports/export` admin/manager + `advanced_reports` |
| Notifications | `GET notifications`, `GET notifications/unread-count`, `PATCH notifications/{notification}/read`, `POST notifications/mark-all-read` |
| Settings | `GET settings`, `GET settings/context`, `PUT settings/profile`, `PUT settings/password`; admin-only `PUT settings`, `POST settings/logo`, `DELETE settings/logo` |
| Billing | `GET billing`, `POST billing/upgrade`, `POST billing/seat-requests`, `GET billing/invoices/{invoice}/download` (admin) |
| Audit | `GET audit-logs` admin |
| Calls | `GET calls`, `GET calls/summary`, `GET calls/{call}`, `POST calls/app`, `POST calls/{call}/recording`, `POST calls/import` (web fallback), `PATCH calls/{call}/disposition`, `GET|DELETE calls/{call}/recording` — visibility: employee own, manager team subtree, admin all. `POST calls/initiate` is deprecated and returns 410 |

Count: **125 enterprise routes** (`php artisan route:list --path=api/v1/enterprise`).

Responses follow the existing envelope: `{ success, data, meta?, message }`, with paginated resources
returning pagination metadata.

Platform-admin oversight (under `/api/v1/admin`): enterprise directory and detail, suspend/resume,
tenant-filtered leads (`tenant=all|realtor|enterprise`), enterprise KPIs on the dashboard and analytics,
plan management with enterprise quotas/features, upgrade and seat-request approvals, platform audit log
(`GET audit-logs`), and broadcast notifications (`GET|POST broadcasts`, `POST broadcasts/preview`).

---

## 6. Frontend routes

Public:

- `/register` — unified 3-step registration stepper (account type → details → plan) for both Realtor and
  Enterprise accounts. `/enterprise/register` redirects to `/register?type=enterprise`.
- `/enterprise/invite/:token` — invitation acceptance and account creation.

Authenticated (`role=enterprise`, wrapped in `RoleGuard` + `EnterpriseLayout`):

`/enterprise/dashboard`, `/leads`, `/leads/:id`, `/leads/distribute`, `/calls`, `/tasks`, `/site-visits`,
`/customers`, `/inventory`, `/inventory/:id`, `/campaigns`, `/campaigns/:id`, `/targets`, `/reports`,
`/teams`, `/teams/:id`, `/users`, `/users/:id`, `/organization`, `/invitations`, `/notifications`,
`/billing`, `/settings`, `/audit-logs`.

Navigation is role-aware and hierarchy-aware. The **Team** section shows Organization to admins, managers,
and lead employees; Members to admins, managers, and lead employees; Lead distribution to admins and
managers; and Invitations only to admins. Employees without direct reports see only their own team.
Billing and Audit log are admin-only in the account menu. The platform admin console never shows enterprise
navigation. Pages also guard themselves and show an access-required or feature-locked state rather than
dead buttons.

The workspace reuses the existing design system and supports the light and dark themes. All data is
loaded from the API; there is no mock data and no placeholder action.

---

## 7. Setup

Existing installations:

```bash
cd Backend
php artisan migrate
php artisan db:seed --class=EnterprisePlanSeeder
php artisan enterprise:backfill-reporting-lines --dry-run   # review
php artisan enterprise:backfill-reporting-lines             # apply
```

The migrations are additive; run them against the existing database **without** `migrate:fresh`. The
backfill is idempotent for already-set lines, runs in a transaction, and only fills members with no manager
(admins stay roots). It accepts `--enterprise={id}` to target one workspace.

Optional demo data: create an enterprise through the registration stepper at `/register?type=enterprise`,
or use the seeded plans when registering. There is no seeded Enterprise workspace and no default enterprise
credentials.

Frontend:

```bash
cd frontend
npm run dev      # development
npm run build    # production
```

The SPA proxies `/api` to the Laravel server as before. No new environment variables are required for the
Enterprise Edition.

---

## 8. Verification

Automated tests live in `Backend/tests/Feature/Api/EnterpriseEditionTest.php` and cover:

- public plan availability and transactional registration (organisation, membership, settings, trial, audit)
- tenant isolation for leads, inventory, and cross-enterprise IDs (404)
- employee-only access to assigned leads
- invitation acceptance, hash-only storage, single use, expiry, and existing-account rejection
- admin direct member creation with team assignment, login, duplicate-email rejection, admin-only access,
  tenant-scoped teams, and seat limits
- bulk lead distribution: balanced round robin, manual owner assignment, manager team scope, employee
  rejection, cross-tenant lead rejection, and audit summary
- plan limits for managers/employees and campaigns
- lead stage transition rules and won-lead customer graduation
- site-visit scheduling by an admin for themselves
- inventory hierarchy creation and booking transitions
- CSV formula-injection sanitisation

`EnterpriseParityTest.php` adds RBAC and billing parity:

- settings/billing admin-only, employee export 403, manager export 200
- manager employee targets within managed teams, outside-team rejection
- employee inventory hold/book on own lead, foreign-lead 403, manager team-lead scope
- manager lead transfer within team and cross-team 403
- member removal with lead transfer/unassign, revoked tokens, preserved won leads, self-removal block,
  login rejection, and enterprise/platform audit entries
- branding (logo/RERA/working hours), feature-locked export/Meta responses, seat add-on approval/rejection
  with capacity updates, and tenant-scoped invoice PDF download

`AdminEnterpriseOversightTest.php` covers the enterprise directory/detail, suspend/resume (including API and
login rejection), tenant-filtered platform leads, enterprise dashboard/analytics KPIs, enterprise plan
quotas/features, broadcast audiences and notification fan-out, and platform audit filtering.

`EnterpriseMetaTest.php` covers enterprise page scoping, manager access without employee access, foreign
page/campaign rejection, form listing, mapping and CAPI settings, sync into the enterprise campaign, and
webhook ingestion with cross-tenant page rejection.

`RealtorWorkspaceTest.php` covers the global reel of leads (own-only, enterprise leads excluded), CSV
formula safety, personal task/site-visit CRUD with foreign-record 404, dashboard follow-ups, and won-lead
customer graduation.

`EnterpriseLeadImportTest.php` covers the CSV/XLSX/XLS bulk import: team/campaign attribution, duplicate
skipping (workspace and within-file), manager team scoping/defaulting, employee rejection, monthly quota
blocking, header validation, per-row contact errors, legacy XLS and XLSX workbook reading, and the sample
CSV/XLSX downloads.

`EnterpriseHierarchyTest.php` covers the reporting hierarchy end to end:

- multi-level chains and subtree scoping for managers and lead employees
- organization tree shape, viewer root, and counts
- rejection of self-parent, admin-as-report, cross-tenant manager, and reporting cycles
- admin-only manager changes, suspended/removed manager rejection
- manager union scope (reporting line + managed teams)
- lead-employee assignment of leads/tasks/visits/targets within the subtree
- member removal with reparent-to-manager, reparent-to-admin, reassign, and audit entries
- add/invitation manager persistence with fallback
- `/me` hierarchy flags and restricted organization page payload
- backfill dry-run and real run

Results at the time of writing:

- Full backend suite: **135 passed, 912 assertions**
- Enterprise + oversight + Meta + hierarchy + import suites: **72 passed**
- Realtor workspace suite: **7 passed**
- Frontend production build: **success** (Vite 5; chunk-size warning only)

Browser verification was performed against the real MySQL/MariaDB database for the admin workspace
(dashboard, leads, tasks, visits, inventory, targets, customers, campaigns, reports/export, teams, members,
direct member creation, invitations, billing, settings, audit logs, notifications, dark mode, bulk lead
distribution, team-filtered lead links, organization chart, change-manager dialog, member reporting-line
card, add-member "Reports to"), for a manager account (scoped organization view without change controls),
and for employee accounts (restricted organization chart for a plain employee; Organization/Members
navigation plus "Assign to" task/visit pickers for a lead employee), plus Realtor registration and
dashboard to confirm the Realtor Edition is intact. The demo org was restored to Admin → Manager → Employee
after these checks. Temporary test data created during verification was removed.

The redesigned lead detail workspace was verified in both editions in light and dark mode: quick-action
tiles, stage rail clicks, note posting, lead-scoped follow-up creation, audit trails with `from → to` pills,
Convert to Customer (enabled only when the stage allows `won`), and Delete Lead with its confirm dialog
(Realtor policy-scoped; Enterprise admin/manager only). Temporary verification leads, campaigns, tasks,
notes, and audit rows were deleted afterwards.

---

## 9. Known limitations

These are intentionally documented rather than hidden:

1. **Trial expiry is not automatically enforced.** The workspace continues to work after
   `trial_ends_at` until a platform admin upgrades or suspends the enterprise. There is no scheduler or
   middleware gate for expired trials or expired subscriptions.
2. **Only two plan features are gated.** `advanced_reports` (CSV export) and `meta_integrations`
   (enterprise Meta pages/forms/CAPI/sync) are enforced. Basic reports, tasks, visits, targets, and audit
   logs are available on every enterprise plan.
3. **Notifications are in-app only.** Assignment/task/visit/deal notifications and platform-admin
   broadcasts are persisted and shown in the bell; there are no email/SMS/push reminders and no WebSocket
   real-time updates. Invitation and welcome emails are sent via SMTP (logged on failure).
4. **No self-serve enterprise checkout or online payment.** Enterprise plans and seat add-ons are activated
   only after a platform admin approves the request. Approval is manual; nothing is charged online.
5. **Enterprise lead ingestion supports manual, CSV/Excel, and Meta.** Admins and managers can upload a
   CSV, XLSX, or XLS file from the Leads page (up to 1,000 rows / 2 MB per file, `Name, Email, Phone,
   Company`), with optional team and campaign attribution. Duplicate emails/phones are skipped, rows are
   capped by the `monthly_leads` plan quota, and employees cannot import. Public intake forms remain
   Realtor-only.
6. **Team quick links are only partly filtered.** Team leads and team distribution links carry `team_id`,
   but team tasks and targets still open the unfiltered list pages. Those pages support local team filters,
   but the navigation does not apply them.
7. **Single enterprise per user.** One membership per user, and the enterprise owner cannot be suspended or
   transferred from the UI. Realtor accounts cannot be converted into enterprise members; both direct add
   and invitations require an email address that is not already registered. Removing a member keeps the
   user row and historical leads/activities but revokes all tokens and blocks login; the last active
   administrator cannot be removed.
8. **Production bundle is not code-split.** The SPA builds into a single large JS chunk (chunk-size warning;
   no route-level lazy loading).
9. **Platform admin enterprise oversight is read-only.** Admins can list, inspect, suspend, and resume
   enterprises; they cannot impersonate enterprise members, edit org data, or log in as an enterprise user.
10. **Directly added members are active immediately.** There is no forced password change on first login,
    no welcome email, and no email-verification step for admin-created accounts. The admin must share the
    password securely.
11. **Distribution batches are capped at 100 leads.** The API validates `lead_ids` to 1–100 entries and the
    page loads the 100 oldest unassigned leads per batch. Larger backlogs need multiple batches or the
    per-lead assignment flow.
12. **Enterprise Meta is org-level and admin-managed.** Only enterprise admins can connect/disconnect pages;
    managers map forms and sync for campaigns in their teams using those connected pages. Each Meta page can
    belong to only one tenant, and webhook imports require the `meta-leads` queue worker.
13. **Branding files use the public disk.** Logos are stored under `storage/app/public/enterprise-logos` and
    require `php artisan storage:link`; they are publicly readable by URL, so do not upload sensitive files.
14. **Reporting lines are not inferred retroactively.** Existing memberships need
    `php artisan enterprise:backfill-reporting-lines`; until it runs, non-admin members have no manager and
    plain "self" scope. New members and invitations always store the chosen manager.
15. **SQLite cannot add the self-FK to existing tables.** The migration adds the foreign key only on drivers
    other than SQLite (tests use SQLite). Manager validity, tenancy, and cycle prevention are enforced in
    `EnterpriseHierarchyService` for every write, so integrity does not depend on the DB constraint.
16. **Hierarchy changes are admin-only.** Managers and lead employees cannot move reporting lines, even
    inside their own subtree, and there is no drag-and-drop chart editing. Use the admin **Change manager**
    dialog (or the member detail card).
17. **Removing a member with direct reports needs a decision.** The API and UI require
    `reports_action` (`reparent_to_manager`, `reparent_to_admin`, or `reassign`); there is no silent orphan
    handling and no bulk removal. If no active manager or admin exists, removal is rejected.
18. **"Lead employee" is a derived capability, not a role.** It disappears when the last direct report is
    removed; there is no separate permission to grant, and plain employees cannot view the organization
    chart or the Members list.
19. **Scoped member stats are computed in PHP.** Non-admin member counts and organization trees load the
    enterprise's non-removed memberships and walk the tree; this is fine at current plan sizes (≤ 100
    employees) but is not a recursive SQL query.
20. **Calls start only in the Rumu Android app.** Cloud calling was removed: `POST .../calls/initiate`
    returns **410 Gone** for every edition and the web UI has no call button. The app dials from the
    salesperson's own SIM, creates the call with `POST .../calls/app` (idempotent on `client_call_id`), and
    uploads the handset recording to `POST .../calls/{call}/recording`. Only the user who created a call may
    upload its audio. Web keeps an **Upload recording** fallback for failed syncs. Android background
    recording depends on OEM call-recorder support and local consent law; there is no stealth recording,
    transcription, sentiment, or call-quality analytics. `CALL_RECORDING_RETENTION_DAYS` (default 90) is
    configured, but no scheduler deletes expired files yet — recordings are removed manually per call. The
    Android calling/upload slice lives in the separate Rumu Android project and is not part of this
    repository; it prefers `ACTION_DIAL`, stores a local session, and attaches only a recorder file the user
    chooses.

---

## 10. Operational notes

- Back up the database before deploying. Never run `migrate:fresh` on a database with real data.
- `.env.example` previously contained production-style integration secrets. Rotate those credentials before
  launch and keep real values only in the deployment environment.
- The pre-change database backup is stored at
  `Backend/storage/backups/rumu_pre_enterprise_20261025_154836.sql`; treat it as sensitive and remove it from
  version control.
- Run `php artisan storage:link` so enterprise logos and invoice PDFs are reachable.
- Run `composer install` when deploying this change: XLSX/XLS import uses `phpoffice/phpspreadsheet`
  (already in `composer.json`/`composer.lock`). No database migration is required for Excel support.
- All 14 enterprise/parity migrations are additive and can be applied with `php artisan migrate`; they can
  be rolled back individually without dropping existing business tables.
- Run `php artisan enterprise:backfill-reporting-lines --dry-run` after deploying the reporting-line
  migration, then apply it once the output looks correct.
- Queue workers must run the existing `meta-leads` queue and the broadcast queue used by
  `SendAdminBroadcastJob`. Enterprise notifications are written synchronously in the request. App call
  recordings are uploaded synchronously, so the legacy `calls` queue is not needed.
- The Calls feature ships three additive migrations: `2026_11_01_100000_create_call_tables.php` (new tables),
  `2026_11_08_120000_add_app_calling_to_call_logs.php` (nullable columns + indexes), and
  `2026_11_08_120100_create_mobile_devices_table.php` (new table). Apply with `php artisan migrate`. Run
  `php artisan queue:work --queue=default,meta-leads`. Recordings stay private on
  `storage/app/private/call-recordings` (no `storage:link` exposure) and are streamed by the authenticated
  recording endpoint. Phone→app sync additionally uses `GET /api/v1/mobile/bootstrap` and
  `POST /api/v1/mobile/devices`.
- `php artisan db:seed --class=EnterprisePlanSeeder` (re)creates the enterprise plans; `DatabaseSeeder` calls
  it automatically for fresh installs.
