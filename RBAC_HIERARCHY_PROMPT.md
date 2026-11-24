# Qoder Prompt: Multi-Level Enterprise RBAC Hierarchy (Rumu V2)

> How to use: open the `rumu-v2` repository in Qoder and paste everything below the line into the chat. Do not implement anything until you have inspected the referenced files.

---

## Mission

You are a senior Laravel + React engineer working on the existing Rumu V2 codebase. Implement a production-safe, multi-level organization hierarchy for Enterprise accounts, with server-enforced authorization. The target hierarchy is:

```text
SaaS Admin
├── Realtor (solo tenant)
└── Enterprise (Admin)
    ├── Manager
    │   ├── Emp 1
    │   ├── Emp 2
    │   └── Emp 3
    └── Emp 1
        └── Emp 2
            └── Emp 3
```

Interpretation confirmed by the product owner:

- The right-side `Emp 1 → Emp 2 → Emp 3` is a **reporting chain**, not a flat list.
- Any enterprise member can have direct reports. An employee with direct reports becomes a **Lead Employee** (derived capability, not a new role).
- The Enterprise Admin is the root of the enterprise hierarchy. A Manager reports to the Enterprise Admin (or to another manager), and employees may report to the admin, a manager, or another employee.
- The SaaS Admin stays outside tenant RBAC: oversight only, no impersonation and no editing enterprise org data.

This is an additive feature. Preserve all existing Realtor, Platform Admin, and Enterprise behavior.

## 1. Inspect before coding

Read and understand these files first. Do not rewrite them blindly.

Backend:

- `Backend/routes/api.php` (enterprise route group)
- `Backend/app/Http/Middleware/EnsureEnterpriseApi.php`
- `Backend/app/Http/Middleware/EnsureEnterpriseRole.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseApiController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseUserController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseInvitationController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseReferenceDataController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseTeamController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseLeadController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseTaskController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseSiteVisitController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseTargetController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseReportController.php`
- `Backend/app/Http/Controllers/Api/Enterprise/EnterpriseDashboardController.php`
- `Backend/app/Models/EnterpriseMembership.php`
- `Backend/app/Models/Team.php`
- `Backend/app/Models/TeamMembership.php`
- `Backend/app/Http/Resources/EnterpriseMembershipResource.php`
- `Backend/app/Http/Resources/EnterpriseTeamResource.php`
- `Backend/app/Services/EnterpriseAuditService.php`
- `Backend/tests/Feature/Api/EnterpriseEditionTest.php`
- `Backend/tests/Feature/Api/EnterpriseParityTest.php`
- `Backend/tests/Concerns/BuildsEnterpriseWorkspace.php`

Frontend:

- `frontend/src/router/index.jsx`
- `frontend/src/utils/enterpriseNavigation.jsx`
- `frontend/src/utils/enterprise.js`
- `frontend/src/hooks/useEnterprise.js`
- `frontend/src/hooks/useEnterpriseReference.js`
- `frontend/src/api/enterprise.js`
- `frontend/src/pages/enterprise/teams/MembersPage.jsx`
- `frontend/src/pages/enterprise/teams/MemberDetailPage.jsx`
- `frontend/src/pages/enterprise/teams/TeamsPage.jsx`
- `frontend/src/components/enterprise/MemberTransferModal.jsx`
- `frontend/src/components/enterprise/MemberRemoveModal.jsx`

## 2. Current baseline (do not break)

- `users.role` is `admin | realtor | enterprise`.
- Enterprise membership role is `admin | manager | employee` (`EnterpriseMembership::ROLES`).
- Membership status is `active | suspended | removed`.
- Teams have `enterprise_id`, `manager_id` (a `users.id`), and a `team_memberships` pivot with `is_primary`.
- `EnsureEnterpriseApi` resolves the active membership and blocks suspended/removed members.
- `EnsureEnterpriseRole` enforces `enterprise.role:admin,manager` route groups.
- `EnterpriseApiController::teamScopeIds()` returns teams where `manager_id = current user`.
- Existing RBAC must stay intact: billing/settings/invitations/audit/removal are admin-only; export is admin/manager; inventory status is role-scoped; Meta connect is admin, mapping/sync is admin/manager.
- All data must remain tenant-isolated: cross-tenant resources return 404, role violations return 403.

## 3. Non-negotiable guardrails

1. Use **additive migrations only**. Never run `migrate:fresh`, `db:wipe`, or drop/rename existing columns. A real database backup exists and must not be touched.
2. Do not remove or rewrite existing Realtor pages, Platform Admin pages, or Enterprise pages.
3. Do not add a fourth membership role that breaks existing frontend checks. Keep `admin | manager | employee`; "Lead Employee" is derived from having direct reports.
4. Enforce every rule in Laravel. Frontend guards are cosmetic.
5. No mock data. Every visible action must call a working endpoint.
6. Keep SQLite test compatibility. Do not use MySQL-only SQL, recursive CTEs, or stored procedures.
7. Preserve `EnterpriseAuditService` logging and add new audit events for hierarchy changes.
8. Run the full test suite and production build before reporting completion.
9. Do not change authentication, Sanctum, plan gating, or billing behavior unless this prompt explicitly says so.
10. If a requirement conflicts with existing behavior, stop and document the conflict instead of silently changing unrelated modules.

## 4. Data model

Add one additive migration, for example:

`Backend/database/migrations/2026_10_31_100000_add_reporting_line_to_enterprise_memberships.php`

```text
enterprise_memberships:
  reports_to_membership_id  BIGINT UNSIGNED NULL
  foreign key -> enterprise_memberships.id, ON DELETE SET NULL
  index (enterprise_id, reports_to_membership_id)
  index (reports_to_membership_id)
```

Do not add a new role column. Do not alter `users`, `teams`, or `team_memberships` for this feature.

Model changes in `EnterpriseMembership`:

- Add `reports_to_membership_id` to `$fillable`.
- Add `reportsTo(): BelongsTo` (`EnterpriseMembership`, self FK).
- Add `directReports(): HasMany` (`EnterpriseMembership`, self FK).
- Add `isLead(): bool` meaning "has at least one non-removed direct report".
- Add an `activeReports()` relation helper or query scope.
- Keep existing `isAdmin()`, `isManager()`, `isEmployee()` unchanged.

Hierarchy invariants:

- Admins have `reports_to_membership_id = null`. Multiple admins are allowed as co-roots.
- A member cannot report to themselves.
- A member cannot report to a member of another enterprise.
- A member cannot report to a removed member.
- Cycles are impossible. Reject any change where the proposed manager is in the member's subtree.
- Suspended managers may remain in the tree, but they must not receive new work assignments. Admins can reparent their reports.
- Removed members are excluded from the tree, visibility scopes, assignment targets, and seat counts.

## 5. Authorization model

Add a dedicated service, for example `Backend/app/Services/EnterpriseHierarchyService.php`, with these responsibilities:

```text
scopeIds(membership): array          // visible membership IDs
canView(viewer, target): bool
canManageHierarchy(viewer): bool      // admin only
canAssignTo(viewer, target): bool     // assignment eligibility
assertValidManager(target, manager): void
descendantIds(membership, includeSelf = false): array
buildTree(enterprise, viewer): array
reparentOrphans(removedMembership, action, destinationId): void
```

Rules:

- `scopeIds(admin)` = all non-removed memberships in the enterprise.
- `scopeIds(manager)` = self + all descendants + members of teams they manage + descendants of those team members.
- `scopeIds(lead employee)` = self + all descendants.
- `scopeIds(employee without reports)` = self only.
- Cache per-request; never issue one query per node.

Capability matrix (adds hierarchy behavior to the existing matrix):

| Capability | SaaS Admin | Enterprise Admin | Manager | Lead Employee (employee with reports) | Employee |
|---|---|---|---|---|---|
| Platform oversight / suspend enterprise | Yes | No | No | No | No |
| Enterprise billing, settings, invitations, audit | No | Yes | No | No | No |
| Add / remove / suspend members, change roles | No | Yes | No | No | No |
| Change reporting manager | No | Yes | No | No | No |
| View organization tree | No | Entire enterprise | Own subtree + managed teams | Own subtree | Self only / hidden when no reports |
| View members | No | All | Scoped subtree + managed teams | Scoped subtree | Self only |
| Assign / distribute leads | No | Anywhere in enterprise | Scoped subtree + managed teams | Scoped subtree only | No |
| Tasks / site visits | No | All | Scoped subtree + managed teams | Scoped subtree | Own only |
| Targets | No | All | Scoped subtree + managed teams | View own/subtree, no editing | Own only |
| Reports / export | No | Yes | Yes, scoped | No | No |
| Inventory status | No | Any unit | Team lead-linked units (existing) | Own assigned lead only (existing) | Own assigned lead only (existing) |
| Meta connect / mapping | No | Yes / Yes | No / own team campaigns | No | No |

Do not widen existing permissions. If an employee with reports is not a manager, they still cannot invite, remove, change roles, access billing/settings/audit, or export.

## 6. Backend endpoints

Add these inside the authenticated `Route::prefix('enterprise')` group in `Backend/routes/api.php`:

```text
GET    enterprise/organization/tree
GET    enterprise/users/{membership}/reports
PATCH  enterprise/users/{membership}/manager
```

Suggested behavior:

- `organization/tree`: all enterprise roles. Returns the tree scoped to the viewer. Each node includes `membership_id`, `user_id`, `name`, `email`, `role`, `status`, `designation`, `employee_code`, `team`, `is_lead`, `direct_reports_count`, `reports_to_membership_id`, and `children`. Include `scope: all|subtree|self` and `can_manage_hierarchy` at the top level.
- `users/{membership}/reports`: returns direct reports (optionally `?depth=all` for the full subtree), scoped; 404 for cross-tenant.
- `PATCH users/{membership}/manager`: **admin only**. Body `{ "reports_to_membership_id": 12 | null }`. Validate same enterprise, active target manager, no self-reference, no cycle. Reject with 422 and a clear message. Write `member.manager_changed` audit metadata (old/new manager, subtree size). Return the updated membership resource.

Extend existing endpoints:

- `GET enterprise/users`: add filters `reports_to_id`, `role`, `search`; include `reports_to`, `direct_reports_count`, `is_lead`, `subtree_size` in the resource. Keep pagination and scoping.
- `GET enterprise/users/{membership}`: load `reportsTo.user` and `directReports.user` when permitted; keep 404 cross-tenant.
- `GET enterprise/reference-data`: add permissions `can_manage_hierarchy`, `can_view_hierarchy`, `has_reports`, `is_lead_employee`, and include the viewer's `reports_to` summary.
- `GET enterprise/me`: include the same hierarchy flags and the viewer's manager/direct-report counts.
- `POST enterprise/users` (direct add): accept optional `reports_to_membership_id`; validate; default to the acting admin's membership.
- `POST enterprise/invitations`: accept optional `reports_to_membership_id`; persist the intended manager in the invitation payload or a nullable column if one already exists; validate at send time. On acceptance, fall back to the enterprise admin if the intended manager is removed or no longer active. Never fail acceptance because of a stale manager.
- `POST enterprise/users/{membership}/remove`: require a reports action when the member has direct reports:
  - `reports_action=reparent_to_manager` (default): re-parent to the removed member's manager, or enterprise admin if none.
  - `reports_action=reparent_to_admin`: re-parent to the enterprise admin.
  - `reports_action=reassign`: require `reassign_to_membership_id`, validate same enterprise, active, no cycle.
  - Revoke tokens, remove team memberships, and audit `member.removed` with `direct_reports_count` and `reports_action`.
- `POST enterprise/users/{membership}/transfer` (team transfer): keep team/lead behavior; do not change `reports_to_membership_id` unless a future requirement explicitly asks.
- `GET enterprise/leads`, tasks, site visits, targets, dashboard, reports: replace any "team-only" scope with the hierarchy-aware scope. Preserve existing admin behavior and existing role restrictions.
- `POST enterprise/leads/{lead}/assign`, `leads/distribute`, `leads/{lead}/round-robin`: only allow targets inside `scopeIds(viewer)` and active status. Managers keep managed-team access. Lead employees may assign only within their subtree. Never assign to admins from a non-admin actor.
- `EnterpriseLeadController@updateStage`, notes, tasks, visits: assert the viewer can view the lead/record first.

Do not add hierarchy routes under the Platform Admin console. The SaaS Admin does not manage enterprise reporting lines.

## 7. Frontend

Navigation (`frontend/src/utils/enterpriseNavigation.jsx`):

- Add `Organization` to the Team section for admin and manager.
- For an employee with reports, label it `My team` or `My reports`; for an employee without reports, hide it.
- Keep `Members`, `Lead distribution`, `Invitations`, `Billing`, and `Audit log` exactly as they are today (admin/manager/admin-only as applicable).
- Do not show enterprise hierarchy navigation to Platform Admin or Realtor roles.

New page: `frontend/src/pages/enterprise/organization/OrganizationPage.jsx`, route `/enterprise/organization`.

- Render a nested org chart from `organization/tree`.
- Node card: avatar, name, role badge (`Admin`, `Manager`, `Lead Employee`, `Employee`), designation, team, direct-report count, status.
- Expand/collapse branches; search by name/email.
- Admin action: `Change manager` opens a searchable member picker, excludes self and the member's current subtree, calls the PATCH endpoint, shows validation errors.
- Manager and lead employee see a read-only tree scoped to them.
- Employee without reports sees an access-required state, not an empty tree.
- Responsive and dark-mode safe using existing UI primitives.

Member pages:

- `MembersPage.jsx`: add a `Reports to` column and a `Role` badge; show a `Lead` indicator when `is_lead`; admin row action `Change manager`.
- `MemberDetailPage.jsx`: add a `Reporting line` card showing manager, direct reports, full subtree count, and a `Change manager` action for admins. Managers see it read-only for their scope.
- Do not remove existing Transfer or Remove actions; extend the remove confirmation with the reports action selector when direct reports exist.

State and API:

- `frontend/src/utils/enterprise.js`: add role-independent flags that will be overridden by membership data: `canViewHierarchy`, `canManageHierarchy`, `hasReports`, `isLeadEmployee`. Do not hardcode hierarchy permissions from the role alone.
- `useEnterprise.js`: merge the `/me` hierarchy flags into the returned permissions.
- `frontend/src/api/enterprise.js`: add `organization.tree()`, `users.reports(id)`, and `users.changeManager(id, payload)`.
- Update `useEnterpriseReference` only if it needs hierarchy member options; otherwise leave it alone.

Route guards are cosmetic. The backend remains authoritative and must return 403/404 for direct URL abuse.

## 8. Edge cases to implement and test

- Self-manager rejected.
- Two-member cycle rejected.
- Deep cycle rejected.
- Cross-tenant manager rejected with 404 when the membership is not in the enterprise.
- Removed/suspended members cannot be selected as manager or assignment target.
- Admin cannot remove the last active enterprise admin (existing rule preserved).
- Removing a member with reports requires a valid reports action.
- Removing a manager does not leave orphan reports.
- Suspending a manager leaves the tree readable but blocks new assignments to them.
- Employee with reports sees the subtree; employee without reports never sees other members.
- Manager's scope is the union of downline and managed teams, deduplicated.
- Team manager (`teams.manager_id`) must reference an active admin/manager membership. Employee leads do not become team managers unless the existing rules already allow it.
- Invitation acceptance with a removed intended manager falls back to the entering admin; no 500.
- Cross-tenant queries return 404, not 403 with data.
- Counts (seats, usage, dashboard) exclude removed memberships.

## 9. Required tests

Add `Backend/tests/Feature/Api/EnterpriseHierarchyTest.php` using `RefreshDatabase` and the existing `BuildsEnterpriseWorkspace` trait. Cover at minimum:

1. Admin can build `Admin → Manager → Emp 1 → Emp 2 → Emp 3` and the tree nests correctly.
2. Admin can also build direct `Admin → Emp 1 → Emp 2 → Emp 3`.
3. Cycle, self-reference, and cross-tenant manager assignments are rejected.
4. Manager sees self + downline + managed team, but not another team.
5. Lead employee sees own subtree; plain employee sees only self.
6. Manager cannot change the hierarchy (403); admin can.
7. Invitation/direct add persists the reporting manager; acceptance fallback works.
8. Member removal reparents reports according to the action and writes audits.
9. Leads/tasks/visits/targets visibility follows the hierarchy scope.
10. Suspended and removed members are excluded from assignment and counts.
11. `/me` and `reference-data` expose the hierarchy flags.
12. Full existing suite remains green; SQLite compatible.

Also update existing tests that assume managers can see only their team or employees can see only themselves, where the new hierarchy intentionally changes visibility. Do not weaken tenant isolation assertions.

Frontend verification:

- `cd frontend && npm run build`.
- Browser-check admin, manager, lead employee, plain employee, and ensure Platform Admin still has no enterprise nav.
- Check the org chart at desktop and narrow widths, light and dark themes.

## 10. Rollout

1. Add the migration and model changes.
2. Add an idempotent backfill command, for example `php artisan enterprise:backfill-reporting-lines --dry-run`, then run it without `--dry-run`:
   - Admins stay roots.
   - Managers report to the enterprise admin unless an existing team manager mapping is more specific.
   - Employees report to their primary team's manager membership when valid; otherwise to the enterprise admin.
   - Never create cycles; log skipped rows.
3. Run `php artisan migrate` (additive only). Do not use `migrate:fresh`.
4. Run `php artisan test` and `npm run build`.
5. Update `ENTERPRISE.md` with the hierarchy model, capability matrix, endpoints, and rollout notes.
6. Report any gaps, skipped backfills, or tests that could not be run. Do not claim success without evidence.

## 11. Acceptance criteria

- The org chart supports both structures shown in the diagram, including multi-level employee reporting chains.
- Enterprise Admin can view and edit the entire hierarchy.
- Manager sees and manages work only within their subtree plus managed teams.
- Lead Employee sees a read-only org view and can work only within their subtree.
- Plain Employee sees only themselves; no other member, lead, target, or report data leaks.
- SaaS Admin never appears inside enterprise RBAC and never gains tenant data access.
- Cross-tenant access is 404; role violations are 403.
- Existing Realtor, Platform Admin, Enterprise billing, inventory, Meta, targets, reports, and audit behavior still passes the full suite.
- The production frontend build is green.

## 12. Out of scope

- Impersonation or "login as" for SaaS Admin.
- A new membership role, custom permission builder, or per-user permission matrix.
- Multiple enterprises per user.
- Drag-and-drop org chart editing.
- Online payment or billing changes.
- Replacing Sanctum or the existing authentication flow.
- Rebuilding existing pages that are not required by this hierarchy.

## 13. Output expectations

When you finish, report:

- Files added/modified.
- Migration name and backfill result.
- New endpoints and authorization rules.
- Test results (counts and assertions).
- Frontend build result.
- Any behavior you intentionally left unchanged.
- Any remaining limitation, especially around suspended managers or multiple admins.
