# Rumu Mobile — Android App Plan (Kotlin · Realtor + Enterprise)

| | |
|---|---|
| **Document** | `androidplan.md` |
| **Status** | Planning only — no Android code exists yet in this repository. The app-calling backend (`/mobile/*`, `calls/app`, recording upload) is already implemented |
| **Version** | 1.1 |
| **Date** | 1 November 2026 |
| **Applies to** | Rumu V2 backend at `/api/v1` (Laravel 12, Sanctum) |
| **Editions** | Realtor Edition + Enterprise Edition (`admin`, `manager`, `employee`) |
| **Platform** | Native Android, Kotlin, phone-first (portrait), single APK |
| **Audience** | Android developer(s), backend developer, QA, product owner |

---

## 0. TL;DR (Hinglish)

- Ek hi Kotlin app banega jo login ke baad user ke role se decide karega — Realtor workspace ya Enterprise workspace. Do alag apps nahi.
- App "simple daily-use companion" hai: leads dekhna, stage badalna, note/activity, follow-up, site visit, customers, inventory read-only, reports read-only, notifications, profile. Poora web dashboard mobile me copy nahi hoga.
- **UI frontend se 1:1 match karega** — same brand colors (`#8A006B` / `#650052`), same Inter font, same cards/buttons/badges/tabs/shadows/radii, light + dark. Section 10 me exact tokens, Compose theme code aur **3 UI iterations** (structure → visual parity → polish) hain.
- Sab kuch existing `/api/v1` endpoints par chalega. Calling ke liye backend ready hai: `GET /mobile/bootstrap`, `POST /mobile/devices`, `POST /{edition}/calls/app`, `POST /{edition}/calls/{id}/recording`. Naya backend kaam sirf **push notifications ke liye device-token registration** (optional) hai — additive.
- Web ka naya lead-detail layout (profile rail, WhatsApp/Note/Activity tiles, Calls tab, stage rail, Activity/Notes/Follow-ups/Audit tabs) mobile me bhi wahi flow follow karega, role-based actions ke saath.
- **Calling app se hoti hai:** user apne SIM se dial karta hai (`ACTION_DIAL`, no `CALL_PHONE`), call ke baad app call record banata hai aur handset ki recording upload karti hai. Cloud/Exotel bridge nahi hai.
- Stack: Kotlin + Jetpack Compose + Material 3, MVVM, Hilt, Retrofit, kotlinx.serialization, Room (read cache), WorkManager, FCM. `minSdk 26`, latest stable `targetSdk` (baseline 36).
- Estimate: **8–10 weeks** for 1 Android developer + ~3–5 backend days + QA. Phases section 17 me hain.
- Admin (super-admin) mobile app me **nahi** aayega. Enterprise self-serve registration aur payments v1 me web par rahenge (Custom Tabs se open honge).

---

## 1. Goal and principles

### 1.1 Goal

Build a simple, fast Android companion app for the two paying editions of Rumu V2:

1. **Realtor Edition** — an individual realtor manages campaigns, leads, follow-ups and site visits from a phone.
2. **Enterprise Edition** — an enterprise admin/manager/employee works the same CRM with organization scoping, assignment and reporting hierarchy enforced by the backend.

The app is a **field tool**, not a tablet dashboard. Core jobs:

- Open a lead within 2 taps and act (call, WhatsApp, note, stage change).
- Never miss a follow-up or site visit.
- See the numbers that matter today (new leads, overdue tasks, visits, won value).
- Stay signed in securely; work on a weak network; recover gracefully.

### 1.2 Principles

| Principle | What it means for the app |
|---|---|
| **Backend is the authority** | Every permission, scope, stage transition, and validation is enforced by Laravel. The app only hides controls for UX; it never assumes it can bypass the API. |
| **Reuse, do not fork business rules** | Reuse existing endpoints and payloads. No mock data, no local-only leads, no parallel status machine that disagrees with the server. |
| **Simple over clever** | One module + feature packages, MVVM with immutable UI state, no MVI framework, no offline write queue in v1. |
| **Read offline, write online** | Cached reads (last fetched lists/details) with a clear stale indicator; all writes require network. |
| **Preserve existing editions** | The app is additive. No existing API response shape changes, no migration rewrites, no Realtor endpoint removals. |
| **Privacy by default** | Token encrypted at rest, no PII in logs/analytics, HTTPS only, screenshot protection optional for lead detail. |

### 1.3 Success criteria (v1)

- Cold start to usable home: **< 2.5 s** on a mid-range device with warm cache.
- Lead list → lead detail (cached): **< 800 ms**.
- A stage change or note succeeds in **≤ 3 taps** after opening a lead.
- Crash-free sessions **≥ 99.5%**.
- Zero client-side authorization assumptions: a suspended/limited role never sees a success it should not get from the API.
- Both editions verified on API 26, 30, 33, 35/36 devices.

---

## 2. Scope

### 2.1 In scope — v1 (MVP)

| Area | Realtor | Enterprise |
|---|---|---|
| Auth | Login, register (realtor), logout, logout-all | Login, invitation accept via deep link |
| Home | Dashboard (campaign/lead counts, 6-month trend, upcoming/overdue tasks, visits, follow-ups, plan expiry) | Role-scoped dashboard (stats, funnel, sources, trend, recent leads/deals, tasks/visits summary, activity) |
| Leads | Global list (search, status/campaign/priority filters), campaign leads, create, edit, detail, note, stage change, delete, resend Meta event | Scoped list (search, stage/priority/source/team/campaign/assignee/unassigned/overdue/today filters), create, edit, detail, note, stage change, assign, delete (admin/manager), bulk distribute (admin/manager) |
| Lead detail | Profile rail, action tiles, stage rail, Activity Timeline, Notes, Follow Ups, Status Audit Trail | Same + Assign, Site Visits tab, enterprise stage rules |
| Calls | SIM dialer from the lead screen, log sheet, recording upload, call history, playback, disposition | Same, role-scoped (own / team / subtree / all) — API in this document §9.21 and `updateplan.md` |
| Tasks | List, create, edit, complete, delete | List, create, edit, complete, delete, assignment where permitted |
| Site visits | List, create, edit, status update, delete | List, create, edit, close, delete |
| Campaigns | List, detail (read/create/edit optional) | List, detail (read) |
| Customers | List | List |
| Inventory | Projects → floors → units, unit search (**read-only**) | Same (**read-only**) |
| Reports | — | Summary + targets + CSV export share (admin/manager) |
| Team | — | Teams + members + org tree (**read-only**) |
| Notifications | Billing notifications inbox + push | Notification inbox + push |
| Profile / Settings | Profile, avatar, password, notification/privacy/appearance prefs | Profile, password, appearance; org settings stay on web |
| Billing | Current plan, invoices (download/share), upgrade request link | Read-only billing (admin), invoices |
| Platform | Dark mode, pull-to-refresh, cached reads, deep links, FCM push, offline banner, INR/IST formatting | Same |

### 2.2 Later (v1.1+)

- Native realtor checkout / plan upgrade (Razorpay SDK) instead of Custom Tabs.
- Enterprise self-serve registration inside the app.
- Bulk CSV/XLSX import from phone (document picker + multipart).
- Inventory editing (admin) and Meta integration setup.
- Offline write queue for notes/tasks.
- Home-screen widgets / quick actions.
- Play Integrity, certificate pinning rotation, root detection.
- Realtor general notification inbox (if not delivered in v1 backend work).

### 2.3 Explicitly out of scope

- **Super-admin** console and platform oversight (admin/realtor/enterprise management, broadcasts, coupons, analytics exports).
- Team/member create/update/suspend/invite inside the app (web only in v1).
- Enterprise org settings, logo upload, billing changes, seat requests.
- Full report builder; only server-provided summaries.
- Tablet-specific two-pane layouts (app will scale, not specialized).

---

## 3. Editions and role matrix

Login returns `user.role` (`realtor` | `enterprise`; `admin` is blocked from the app). Enterprise role comes from `user.enterprise_membership.role` in `GET /auth/me` and from `GET /enterprise/me` (`membership.role`, `hierarchy`, `features`).

| Capability | Realtor | Enterprise admin | Enterprise manager | Enterprise employee |
|---|---|---|---|---|
| Sign in | Yes | Yes | Yes | Yes |
| Data scope | Own leads/campaigns | Whole enterprise | Server-defined org/team scope | Own leads; lead employee also sees reporting subtree |
| Create lead | Yes | Yes | Yes | Yes |
| Edit lead | Own | Scoped | Scoped | Scoped |
| Move stage | All 7 realtor statuses | Allowed transitions | Allowed transitions | Allowed transitions |
| Assign lead | — | Yes | Yes | Only if lead employee with direct reports (server rule) |
| Bulk distribute | — | Yes | Yes | No |
| Delete lead | Own only (policy) | Yes | Yes (scoped) | No |
| Teams / org tree | — | View | View (scoped) | Hidden unless hierarchy flags allow |
| Reports | — | Full | Scoped | Scoped |
| Billing | Own plan + invoices | View/request upgrade | No | No |
| Org settings | — | Web only | — | — |
| Audit log | — | View (admin) | No | No |

Rules already enforced server-side (must be mirrored only as UI affordances):

- `PATCH /enterprise/leads/{lead}/stage` applies `STAGE_TRANSITIONS`.
- `won` requires `deal_value`; `lost` requires `lost_reason`; `on_hold` requires `next_follow_up_at`; `contacted`/`qualified` require a note; `site_visit_scheduled` requires a scheduled visit.
- `POST /enterprise/leads/{lead}/assign` allows admin, manager, or employee with reports.
- `DELETE /api/v1/enterprise/leads/{lead}` is admin/manager only and audit-logged.
- Suspended realtor subscription: reads allowed, writes return `403` with `"Your subscription is suspended. Please renew your plan to continue."`

---

## 4. Technology stack

Pin exact versions in `gradle/libs.versions.toml` at project kickoff; the table below is the baseline and rationale, not a frozen lockfile.

| Layer | Choice | Why |
|---|---|---|
| Language | Kotlin 2.x (K2) | Modern, coroutines-friendly, required for Compose. |
| UI | Jetpack Compose + Material 3 | Fast iteration; the web's CSS tokens map 1:1 to a Compose `ColorScheme` (section 10). |
| Font | **Inter** (bundled, weights 400/500/600/700) | Same typeface as `frontend/tailwind.config.js`; bundle the OFL-licensed Inter files, no runtime download dependency. |
| Icons | Material Symbols + a few hand-ported Heroicons vectors | Web uses Heroicons; copy the 15–20 icons used by MVP screens so the visual language stays identical. Do not mix random icon packs. |
| Navigation | Navigation Compose | Single-activity, type-safe routes (or string routes in v1). |
| Architecture | MVVM + immutable `UiState` + `StateFlow` | Simple, testable, no framework overhead. |
| DI | Hilt (KSP) | Standard Android DI; works with WorkManager and ViewModels. |
| Networking | Retrofit 2.x + OkHttp 4.x + `kotlinx.serialization` converter | Typed API, tolerant JSON parsing, good testability. |
| Async | Coroutines + Flow | Structured concurrency, cancellation on screen exit. |
| Token storage | `EncryptedSharedPreferences` (AndroidX Security Crypto, AES256-GCM) | Sanctum bearer token never stored in plaintext. |
| Prefs | DataStore (Preferences) | Theme, filters, last sync, non-sensitive flags. |
| Cache | Room | Read-only cache of lists/details/dashboard snapshots. |
| Paging | Paging 3 (RemoteMediator optional) | Infinite scroll for leads/tasks/visits/customers. |
| Background | WorkManager | Local follow-up reminders, token refresh, cache cleanup. |
| Push | Firebase Cloud Messaging (`firebase-bom`) | Standard, free, deep-link payloads. |
| Images | Coil Compose | Avatars, campaign/inventory images, memory-safe. |
| Charts | Compose Canvas (or Vico if needed) | Dashboard funnel/bars only; avoid heavy chart deps. |
| Security | BiometricPrompt (optional), R8, Network Security Config | App lock, obfuscation, HTTPS-only. |
| Testing | JUnit4/5 + Truth + MockK + Turbine + MockWebServer + Compose UI Test | Unit, contract, UI, integration. |
| Quality | Android Lint, ktlint, detekt | Consistent code, CI gates. |
| Build | AGP 8.x, Gradle Kotlin DSL, version catalog, JDK 17 toolchain | Modern baseline. |
| SDK | `minSdk 26`, `compileSdk`/`targetSdk` latest stable (baseline 36) | Covers Android 8+, meets Play target requirements. |

**Deliberately not used:** XML layouts, RxJava, Dagger without Hilt, Realm, custom DI service locator, WebView for core screens, Play Billing (payments stay on web in v1).

### 4.1 Baseline Gradle outline

```kotlin
// android/app/build.gradle.kts (shape only)
android {
    namespace = "in.rumu.mobile"
    compileSdk = 36
    defaultConfig {
        applicationId = "in.rumu.mobile"
        minSdk = 26
        targetSdk = 36
        versionCode = 1
        versionName = "1.0.0"
        buildConfigField("String", "API_BASE_URL", "\"https://api.rumu.in/api/v1/\"")
    }
    buildFeatures { compose = true; buildConfig = true }
    buildTypes {
        debug { applicationIdSuffix = ".debug"; buildConfigField("String", "API_BASE_URL", "\"https://staging-api.rumu.in/api/v1/\"") }
        release { isMinifyEnabled = true; isShrinkResources = true }
    }
    compileOptions { sourceCompatibility = JavaVersion.VERSION_17; targetCompatibility = JavaVersion.VERSION_17 }
    kotlinOptions { jvmTarget = "17" }
}
```

> `applicationId`, API hostnames, and the deep-link host are **placeholders to confirm with the product owner** before Play Console setup. Package name cannot be changed after the first Play upload.

---

## 5. Repository and module layout

Create a new top-level `android/` directory. Do **not** touch the existing `Backend/` or `frontend/` trees except the additive backend endpoints described in section 13.

```
android/
├── settings.gradle.kts
├── build.gradle.kts
├── gradle.properties
├── gradle/
│   └── libs.versions.toml
├── local.properties                 # not committed (SDK path only)
├── app/
│   ├── build.gradle.kts
│   ├── proguard-rules.pro
│   └── src/
│       ├── main/
│       │   ├── AndroidManifest.xml
│       │   ├── res/ (strings, themes, notification icons, app links)
│       │   └── java/in/rumu/mobile/
│       │       ├── RumuApp.kt                 # @HiltAndroidApp, FCM setup
│       │       ├── MainActivity.kt            # single activity, edge-to-edge
│       │       ├── core/
│       │       │   ├── network/               # ApiClient, interceptors, envelope, errors
│       │       │   ├── auth/                  # SessionManager, TokenStore, role routing
│       │       │   ├── datastore/             # prefs, theme, filters
│       │       │   ├── database/              # Room entities, DAOs, converters
│       │       │   ├── designsystem/          # theme, colors, type, shared components
│       │       │   ├── common/                # Result, formatters, intents, permissions
│       │       │   └── notifications/         # FCM service, channels, deep links, reminders
│       │       └── feature/
│       │           ├── splash/
│       │           ├── auth/                  # login, register, invitation
│       │           ├── home/                  # role-specific home
│       │           ├── leads/                 # list, detail, form, filters, distribute
│       │           ├── tasks/
│       │           ├── visits/
│       │           ├── campaigns/
│       │           ├── customers/
│       │           ├── inventory/
│       │           ├── reports/
│       │           ├── team/
│       │           ├── notifications/
│       │           ├── billing/
│       │           └── profile/
│       ├── test/                              # JVM tests, fixtures, MockWebServer
│       └── androidTest/                       # Compose + instrumented tests
└── README.md                                  # build/run/signing/API notes
```

Each feature package follows the same shape:

```
feature/leads/
├── LeadsApi.kt                # Retrofit interface (edition-specific paths)
├── LeadsRepository.kt         # network + cache + domain mapping
├── LeadsViewModel.kt          # state, events, pagination
├── LeadsScreen.kt             # Compose route + scaffold
├── components/                # LeadCard, StageChip, FilterSheet, ...
└── model/                     # DTOs + domain models + mappers
```

**v1 stays single-module.** Extract `:core:network`, `:core:designsystem`, etc. only if build times or team size justify it later. Premature modularization slows a 8–10 week MVP.

---

## 6. API contract

### 6.1 Base URLs and configuration

| Build | Base URL |
|---|---|
| Staging | `https://staging-api.rumu.in/api/v1/` (confirm) |
| Production | `https://api.rumu.in/api/v1/` (confirm) |

- Base URL lives in `BuildConfig`, set per build type, never hardcoded in feature code.
- No API secrets in the APK. Sanctum tokens are per-user, obtained at runtime.
- The Android app does not use CSRF/cookies; it uses `Authorization: Bearer <token>`.

### 6.2 Standard headers

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>        # after login
X-App-Version: 1.0.0                 # optional, for support/debugging
User-Agent: RumuAndroid/1.0.0 (Android 14; <device>)
```

### 6.3 Response envelope

Success:

```json
{ "success": true, "data": { }, "message": "..." }
```

Paginated:

```json
{
  "success": true,
  "data": [ ],
  "meta": { "current_page": 1, "last_page": 8, "per_page": 20, "total": 147 },
  "message": null
}
```

Realtor global leads meta additionally includes `status_counts` (map of status → count).

Error:

```json
{ "success": false, "message": "The provided credentials do not match our records.",
  "errors": { "email": ["..."] } }
```

`errors` is present for Laravel 422 validation responses. For `403` authorization errors only `message` is present; `abort(403, ...)` may return Laravel's default shape — the client must tolerate both and fall back to a generic message.

### 6.4 Client error model

Map every response into a sealed `ApiError`:

| Type | Trigger | UX |
|---|---|---|
| `Network` | `IOException`, timeout | Retry card + offline banner |
| `Unauthorized` | 401 | Clear session, navigate to login with "Session expired" |
| `Forbidden(message)` | 403 | Snackbar message; if subscription message, show renew banner |
| `Validation(fields)` | 422 | Inline field errors; general message for non-field errors |
| `NotFound` | 404 | "Lead/record not found" with back action |
| `RateLimited` | 429 | "Too many attempts, try again in a minute" |
| `Server` | 5xx | "Something went wrong" + retry |
| `Unknown` | parse/other | Generic message + retry |

### 6.5 Dates, money, timezone

- API sends ISO-8601 (`2026-11-01T09:30:00Z` or with offset). Parse with `kotlinx-datetime` `Instant`; display in **Asia/Kolkata** unless `user.timezone` is present.
- Writes that accept datetimes send **UTC ISO-8601** (`yyyy-MM-dd'T'HH:mm:ss'Z'`), matching the web app's `toUtcDateTime`. Never send device-local strings without an offset.
- All money is INR, format with Indian digit grouping: `₹12,34,567` (not `₹1,234,567`). Central `Formatters.currencyInr()`.
- Phone display: `+91 98765 43210` for 10-digit Indian numbers; otherwise raw. WhatsApp intent prefixes `91` for 10-digit numbers (same rule as web).

### 6.6 Pagination and search

- Lists use `page` + `per_page` (default 20). Read `meta.last_page` and stop.
- Search: `search` query param; debounce **350 ms**; cancel the previous request on new input.
- Realtor campaign-scoped leads (`/realtor/campaigns/{id}/leads`) are fixed at 20/page and have no search; the app uses the global `/realtor/leads?campaign_id=` endpoint when search/filter is active.
- Enterprise lead filters available: `search`, `stage` (comma-separated), `priority`, `source`, `team_id`, `campaign_id`, `assigned_to`, `project_id`, `unassigned`, `overdue`, `follow_up_today`, `sort` (`latest` default, `follow_up`, `oldest`, `name`).

### 6.7 HTTP behavior

- Timeouts: connect 15 s, read 25 s, write 25 s.
- Retry only idempotent GETs on connection failure (max 2, backoff 0.5 s/1.5 s).
- Never auto-retry POST/PATCH/DELETE; show the error and let the user retry.
- Logging interceptor only in debug builds; redact `Authorization`, passwords, tokens.
- Add a network-availability check before writes to fail fast with a friendly message.

---

## 7. Authentication and session

### 7.1 Endpoints used

| Purpose | Endpoint |
|---|---|
| Login | `POST /api/v1/auth/login` `{email, password}` → `{token, token_type, user}` |
| Profile/context | `GET /api/v1/auth/me` |
| Realtor signup | `POST /api/v1/auth/register` `{name, email, password, password_confirmation}` → creates a **realtor** on the default plan |
| Logout (this device) | `POST /api/v1/auth/logout` |
| Logout all devices | `POST /api/v1/auth/logout-all` |
| Enterprise invitation details | `GET /api/v1/enterprise/invitations/{token}` |
| Enterprise invitation accept | `POST /api/v1/enterprise/invitations/{token}/accept` `{name, password, password_confirmation, phone?}` → `{token, user}` |
| Enterprise context | `GET /api/v1/enterprise/me` |

Social login exists on the API (`/auth/social/{provider}`) but is **out of v1**; adding Google/Facebook SDKs is a separate scope.

### 7.2 Login flow

1. Validate email/password locally (non-empty, email shape, password ≥ 8 on register).
2. `POST /auth/login`.
3. Persist token in encrypted storage and cache `user` in memory + non-sensitive prefs.
4. Route by role:
   - `realtor` → Realtor graph.
   - `enterprise` → call `GET /enterprise/me` once; store `membership.role`, `enterprise.name`, `hierarchy` flags, `features`; route to Enterprise graph.
   - `admin` → block: "Admin console is available on the web." Offer logout. (Server also allows admin login, so this must be handled client-side.)
   - Any other/missing membership → show the server message and logout.
5. On `subscription_suspended == true`, still enter the app in **read-only mode**: banner "Plan suspended — renew to edit", disable all write actions, allow dashboard/leads/profile reads and billing.

Do **not** ask "Realtor or Enterprise?" on the login screen. The account already defines the edition; asking creates a wrong-path UX.

### 7.3 Realtor registration

Fields: full name, email, password, confirm password, accept terms checkbox (UI only; the API does not store consent).

On success, the user is created on the default Basic plan and a token is returned. Show a short onboarding sheet:

- "Basic plan active" + "View plans" button that opens the web pricing/checkout in **Custom Tabs**.
- Deep link back via `rumu://auth/upgrade-complete` is optional; on resume, refresh `GET /auth/me` and `GET /realtor/billing`.

No enterprise signup in the app v1: "Register your company" opens the web registration page. This avoids reimplementing the multi-step plan/checkout flow and keeps the 14-day trial logic in one place.

### 7.4 Enterprise invitation (deep link)

Deep link: `https://<host>/invite/{token}` or `rumu://invite/{token}`.

1. `GET /enterprise/invitations/{token}` → show company name, inviter, role, team, reports-to, and `account_exists`.
2. If `account_exists == true`, the API cannot accept (existing accounts cannot be invited); show the server guidance and a login button.
3. Otherwise collect name, phone (optional), password + confirmation → `POST .../accept`.
4. Store returned token, load `/enterprise/me`, enter the app.

Expired/used invitation → `410` → friendly "This invitation is no longer available."

### 7.5 Session storage and lifecycle

- Token: `EncryptedSharedPreferences` (AES256-GCM master key). Never DataStore plaintext, never logcat.
- User profile cache: store non-sensitive fields (id, name, role, enterprise name, membership role) in DataStore for instant splash routing; always revalidate with `/auth/me`.
- Android backup: exclude the token prefs file and Room DB via `data_extraction_rules.xml` / `backup_rules.xml`.
- No refresh token exists. On `401`: clear token, clear Room cache, navigate to login with "Session expired, please sign in again." Preserve the last email for convenience.
- Logout: call `POST /auth/logout` (ignore network failure), unregister FCM token (section 12), clear encrypted token/DataStore/Room, cancel reminder WorkManager work, navigate to login.
- "Logout from all devices": `POST /auth/logout-all` with confirmation dialog.
- Optional app lock: if enabled in Settings, `BiometricPrompt` on cold start/resume after 5 minutes. Fallback to device credential. Never lock the login screen.

### 7.6 Suspended subscription handling

Detect from:

- `user.subscription_suspended` on login/me.
- `403` with message containing "subscription is suspended".

Behavior: persistent banner with "Renew" (opens web billing), disable create/edit/stage/note/task/visit actions, allow reads. Do not silently retry writes.

---

## 8. Navigation and information architecture

### 8.1 Root graph

```
Splash
 ├── no token            → Login
 ├── token + realtor     → RealtorRoot
 ├── token + enterprise  → EnterpriseRoot
 ├── token + admin       → BlockedAdmin
 └── invalid/expired     → Login (session expired message)
```

### 8.2 Realtor bottom navigation

| Tab | Screen | Primary job |
|---|---|---|
| Home | Dashboard | Today's numbers, overdue tasks, visits, follow-ups |
| Leads | Global leads | Find and open a lead |
| Tasks | Follow-ups | Complete/reschedule follow-ups |
| Visits | Site visits | Prepare for and close visits |
| More | Hub | Campaigns, Customers, Calls, Inventory, Billing, Notifications, Settings |

FAB on Leads → New lead. FAB on Tasks → New follow-up. FAB on Visits → Schedule visit.

### 8.3 Enterprise bottom navigation

| Tab | Screen | Primary job |
|---|---|---|
| Home | Dashboard | Role-scoped pulse |
| Leads | Scoped leads | Work assigned/visible leads |
| Tasks | Follow-ups | Complete/reschedule |
| Visits | Site visits | Prepare/close |
| More | Hub | Customers, Campaigns, Calls, Reports, Team, Inventory, Billing (admin), Notifications, Settings |

**More** items are filtered by role/flags from `/enterprise/me` (`hierarchy.can_view_hierarchy`, `can_manage_hierarchy`, membership role). Never show a menu item that will 403.

### 8.4 Deep links

| Link | Destination |
|---|---|
| `rumu://lead/realtor/{leadId}?campaignId={id}` | Realtor lead detail |
| `rumu://lead/enterprise/{leadId}` | Enterprise lead detail |
| `rumu://task/{edition}/{id}` | Task detail sheet |
| `rumu://call/{edition}/{callId}` | Call detail sheet with recording player |
| `rumu://visit/{edition}/{id}` | Visit detail |
| `rumu://notifications` | Notification inbox |
| `rumu://invite/{token}` | Invitation accept |
| `https://<host>/open/...` | App Links equivalents for push/email |

Validate every deep link: allowlisted scheme/host, numeric IDs, edition must match the signed-in user. On mismatch, open the correct home with a toast instead of erroring. Handle "not signed in" by storing the pending link and replaying after login.

### 8.5 Back behavior

- Bottom tabs preserve their own back stack (Navigation Compose `saveState/restoreState`).
- Lead detail → back returns to the list with scroll position and filters intact.
- Forms: confirm discard when dirty.
- Pending deep link replay is single-use.

---

## 9. Screen specifications

Legend: **API** lists the primary calls; role gating is always also enforced server-side.

### 9.1 Splash

- Read token + cached role; show brand mark; call `/auth/me` (5 s timeout).
- On network failure with a valid cached session: enter the app offline (read cache) with an offline banner; do not log out.
- On 401: clear session, go to Login.

### 9.2 Login

- Email, password, show/hide password, "Sign in", "Create realtor account", "Have an invitation? Open link".
- No edition selector. No social buttons in v1. "Forgot password?" → there is **no password-reset API today**; show "Contact support" with the support email/phone (or hide the link). Add the API only if product requires it (section 13).
- Errors: 422 inline under email/password; 403 shows server message (suspended membership, admin blocked, unavailable workspace).
- Loading disables the button; password managers supported (`autofillHints`).

### 9.3 Realtor register

- Full name, email, password, confirm, terms checkbox.
- Inline validation + 422 field mapping.
- Success → onboarding sheet (Basic plan, view plans) → Home.
- The API creates only realtors; do not send a role field.

### 9.4 Enterprise invitation accept

- Load invitation by token; show company, role, team, reports-to, inviter.
- If account exists → explain and offer Login.
- Name, phone (optional), password, confirm; accept → token → `/enterprise/me` → Enterprise Home.
- Handle 410 (expired/used) and 422.

### 9.5 Realtor Home

**API:** `GET /realtor/dashboard`, `GET /realtor/tasks?status=pending...` (only if refreshing counts), `GET /realtor/billing/notifications/unread-count`.

UI blocks:

1. Greeting + plan chip + plan-expiry warning (`plan_expiry_warning`).
2. Stat grid: total leads, new, qualified, won; campaigns active/paused.
3. Six-month leads bar chart (server arrays `months`, `monthly_leads`; simple Compose bars).
4. "Needs attention" list: overdue tasks (`overdue_tasks`, `overdue_tasks_count`), upcoming tasks, upcoming site visits, lead follow-ups. Each row deep-links to the task/visit/lead.
5. Recent campaigns (horizontal cards).
6. Pull-to-refresh; skeletons on first load; cached snapshot if offline with "Updated {timeAgo}".

Suspended plan → renewal banner instead of creation shortcuts.

### 9.6 Enterprise Home

**API:** `GET /enterprise/dashboard`, `GET /enterprise/me` (for role/labels/flags), `GET /enterprise/notifications/unread-count`.

UI blocks:

1. Header: enterprise name, role badge, team/reporting line.
2. Role-scoped stat cards from `stats[]` (server supplies labels and `format: currency`). Use a 2-column grid with horizontal scroll if more than 8.
3. Funnel (`funnel[]`) as horizontal bars; sources (`sources[]`) as chips/list.
4. Trend: 6 months leads/won/deal value (bars; value label in INR compact).
5. Pending assignments card (admin/manager) → filtered Unassigned list.
6. Tasks/visits summary tiles (`tasks.overdue`, `tasks.today`, `tasks.pending`, `visits.upcoming`, `visits.today`).
7. Recent leads / recent deals lists.
8. Activity feed (latest 8).
9. Targets summary if present.
10. Team/employee/campaign performance: compact "top 3" cards; full detail is web-only in v1.

Employee sees their own scoped numbers; labels already say "My leads" / "My team leads".

### 9.7 Leads list (both editions)

**Realtor API:** `GET /realtor/leads?page=&per_page=&search=&status=&campaign_id=&priority=`
**Enterprise API:** `GET /enterprise/leads?page=&per_page=&search=&stage=&priority=&source=&team_id=&campaign_id=&assigned_to=&unassigned=&overdue=&follow_up_today=&sort=`

Shared UI:

- Search field (debounced), filter bottom sheet, sort menu (enterprise), active-filter chips with "Clear all".
- Realtor status chips with counts from `meta.status_counts`; enterprise stage multi-select.
- Lead card: name, company (optional), stage/status pill, priority, source, assignee (enterprise), next follow-up (overdue in red), open task count (enterprise `open_tasks_count`), phone quick-action.
- Long-press/swipe (optional): Call, WhatsApp, Mark follow-up. Keep actions on the detail page for v1.
- Infinite scroll (Paging 3), pull-to-refresh, skeleton rows, empty state with "Add lead", error state with retry.
- FAB → Lead form. Enterprise admin/manager also get "Distribute" in the overflow menu.

Realtor list is global; campaign filtering uses `campaign_id`. The campaign detail screen can open the same list pre-filtered.

### 9.8 Lead detail (both editions)

Mirror the new web layout, adapted for phone:

```
Top app bar: back, title (lead name), overflow (Edit, Resend Meta [realtor], Assign [enterprise], Delete)
Scrollable profile card:
  status/stage pill · priority · customer pill
  name, company
  email (mailto), phone (tel)
  assigned salesperson card (enterprise) / campaign card (realtor)
  action tiles: Call · WhatsApp · Note · Activity
  About Lead (collapsible): source, budget, config, location, project, unit, campaign, createdAt, last contacted, custom fields
  Convert to Customer (green) · Delete Lead (admin/manager or realtor owner)
Stage rail (horizontal scroll)
Tabs: Activity Timeline · Notes · Follow Ups · Calls · Status Audit Trail (+ Site Visits for enterprise)
Content per tab
```

**APIs**

| Need | Realtor | Enterprise |
|---|---|---|
| Detail | `GET /realtor/leads/{id}` | `GET /enterprise/leads/{id}` |
| Note | `POST /realtor/leads/{id}/notes` | `POST /enterprise/leads/{id}/notes` |
| Stage | `PATCH /realtor/leads/{id}/status` | `PATCH /enterprise/leads/{id}/stage` |
| Edit | `PUT /realtor/leads/{id}` | `PUT /enterprise/leads/{id}` |
| Delete | `DELETE /realtor/leads/{id}` | `DELETE /enterprise/leads/{id}` (admin/manager) |
| Assign | — | `POST /enterprise/leads/{id}/assign` |
| Follow-ups | `GET /realtor/tasks?lead_id={id}&per_page=50` | Included in `lead.tasks` |
| Visits | `GET /realtor/site-visits?lead_id={id}` (verify param) | Included in `lead.site_visits` |

**Stage rail**

- Realtor: all 7 statuses clickable; tap → confirm sheet with optional reason? The realtor API only takes `status`, and creates the auto note "Status updated to X". Show a one-line confirmation for terminal statuses (`won`, `lost`).
- Enterprise: only `STAGE_TRANSITIONS[current]` enabled. Tap opens a stage sheet that captures the fields the server requires:
  - `contacted` / `qualified` → required note; qualified also offers budget min/max, location, configuration, next follow-up.
  - `won` → required deal value.
  - `lost` → required lost reason.
  - `on_hold` → required next follow-up.
  - `site_visit_scheduled` → instructs to schedule a visit first, then retry.
- Rail shows a progress line; disabled stages use muted text; pending state locks taps.
- On success: toast, refresh detail + dashboard; if the lead became `won`, show the customer badge.

**Tabs**

- **Activity Timeline**: realtor shows notes and derived status changes; enterprise shows all `activities` mapped to icons/tones (created, updated, assigned/reassigned, status_changed, note, site visit events). Status changes render from → to pills; omit equal transitions.
- **Notes**: composer (disabled when empty/pending/read-only) + note timeline with author and relative time.
- **Follow Ups**: next follow-up card; list of tasks; add/edit; complete; overdue styling. Enterprise uses lead tasks; realtor uses the tasks API filtered by `lead_id`.
- **Status Audit Trail**: enterprise uses `status_changed` activities; realtor derives transitions by replaying `"Status updated to X"` notes chronologically (initial status `new`), newest first. This is a known data-model limitation of the Realtor edition; document it in the app's About/Limitations and in the QA notes.
- **Site Visits** (enterprise): list with scheduled time, project, status, "Close visit" action using the visit close flow.

**Convert / delete**

- Realtor Convert → `PATCH status=won` after confirmation; Disabled if already customer/won.
- Enterprise Convert → open stage sheet with `won` preselected (requires deal value); disabled unless `won` is an allowed transition or already won.
- Delete → confirmation dialog naming the lead; enterprise only for admin/manager; success → pop back to the list; handle 403 with server message.

**Action tiles**

- Call: **Call** — open `ACTION_DIAL` with `tel:{lead.phone}` (no `CALL_PHONE`). On return, show a short "Log this call?" sheet: the user confirms consent, duration/outcome, then the app calls `POST /{edition}/calls/app` with a client-generated UUID and uploads the recording via `POST /{edition}/calls/{id}/recording`. Never request `CALL_PHONE` or `RECORD_AUDIO` unless a future OEM recorder integration is explicitly approved.
- WhatsApp: `https://wa.me/91XXXXXXXXXX`; prefix 91 for 10-digit numbers; fall back to disabled if no phone.
- Note: scroll/focus composer.
- Activity: select Activity tab.

### 9.9 Lead form (create/edit)

**Realtor API:** `POST /realtor/campaigns/{campaignId}/leads`, `PUT /realtor/leads/{id}`
**Enterprise API:** `POST /enterprise/leads`, `PUT /enterprise/leads/{id}`

Fields (aligned with existing validation; hide fields the API does not accept for the role):

- Name, phone, email, company, source, priority.
- Campaign (realtor: required when creating from a campaign; enterprise: optional/select).
- Enterprise: assigned to (only if assign permission), team, project/unit (optional).
- Realtor: custom fields supported by the campaign are shown dynamically if returned by the API; otherwise omit in v1.
- Notes are not part of create; use the note endpoint after creation (or the API's optional initial note if supported — verify in Phase 0).

Behavior: client-side required checks, 422 field mapping, unsaved-changes guard, optimistic success toast + navigate to detail. Never invent a lead ID locally.

### 9.10 Tasks / follow-ups

**Realtor API:** `GET /realtor/tasks`, `POST`, `GET/{id}`, `PUT`, `PATCH /status`, `DELETE`
**Enterprise API:** same under `/enterprise/tasks`

- List: segmented filter (Open / Overdue / Completed), due date, priority, linked lead, assignee (enterprise).
- Create/edit: title, description, priority, due date/time (date + time pickers), linked lead (searchable picker; prefilled/locked when opened from a lead), assignee (enterprise, only when permitted; default self).
- Complete: checkbox/swipe → `PATCH status=completed`; undo snackbar optionally re-opens (`PATCH status=pending`) within 5 s.
- Delete: confirm.
- Overdue: `is_overdue` from API if present; otherwise compare `due_at` to now in IST.
- Local reminder scheduled on create/edit (section 12.3).

### 9.11 Site visits

**Realtor API:** `/realtor/site-visits` (list/create/show/update/status/delete)
**Enterprise API:** `/enterprise/site-visits` (same shape)

- List: Upcoming / Today / Completed / Cancelled filters, scheduled time, lead, project, status.
- Create/edit: lead, project/unit (optional), scheduled date/time, notes, assignee (enterprise).
- Status: scheduled → completed / cancelled / no-show (use only statuses accepted by the server; verify allowed values in Phase 0).
- Enterprise "Close visit": outcome, notes, next step (mirror the web `VisitCloseModal` fields).
- Call/WhatsApp shortcuts to the lead phone.

### 9.12 Campaigns

**Realtor:** `GET /realtor/campaigns`, `GET /realtor/campaigns/{id}`; create/edit optional in v1 (web recommended for Meta setup).
**Enterprise:** `GET /enterprise/campaigns`, `GET /enterprise/campaigns/{id}` (read-only).

- Card: name, status, platform/source, lead count, created date.
- Detail: summary + "View leads" (pre-filtered list) + recent leads.
- Realtor "New campaign" may be included if the API fields are simple; otherwise deep-link to web. Do not implement Meta form mapping/connect in v1.

### 9.13 Customers

- Realtor: `GET /realtor/customers`; Enterprise: `GET /enterprise/customers`.
- List with search if supported (verify), name, phone, deal value (enterprise), won date, assigned (enterprise).
- Tap → lead/customer detail (read-only or standard detail; verify the customer payload includes a lead id).
- Empty state: "No customers yet — convert a won lead."

### 9.14 Inventory (read-only)

- Realtor: `GET /realtor/inventory/projects`, `GET /realtor/inventory/projects/{id}`, `GET /realtor/inventory/floors/{floor}/units`, `GET /realtor/inventory/units/search`.
- Enterprise: `GET /enterprise/inventory/projects`, `GET /enterprise/inventory/projects/{id}`, `GET /enterprise/inventory/floors/{floor}/units`, `GET /enterprise/inventory/units/search`.
- Drill-down: Project → Tower/Wing/Floor → Units. Unit card: number, type, area, status/availability, price if provided.
- Search units by number/project; show availability chips.
- No create/edit in v1 (admin web only). Hide controls accordingly.

### 9.15 Reports (enterprise)

- `GET /enterprise/reports` (summary metrics), `GET /enterprise/reports/targets`.
- Show server-provided KPIs, funnel, source mix, team/employee performance; use simple tables/cards, not a chart builder.
- Export: admin/manager → `GET /enterprise/reports/export` (CSV). Download to cache, then `ACTION_SEND`/share sheet or `ACTION_VIEW`. Never claim success without a saved file. Respect the server's CSV-safety (do not re-parse/modify).
- If the response is not a file, surface the server message.

### 9.16 Team (enterprise, read-only)

- `GET /enterprise/teams`, `GET /enterprise/teams/{id}`, `GET /enterprise/users`, `GET /enterprise/organization/tree`, `GET /enterprise/users/{membership}`.
- Tabs: Teams | Members | Org chart.
- Team card: name, manager, member count, active status.
- Member row: name, role, designation, team, reporting line, status.
- Org tree: indented list (not a canvas) with expand/collapse; a plain employee sees only what the API returns (and the nav item is hidden if `can_view_hierarchy` is false).
- No edit/invite/suspend actions in v1; show "Manage on web" hint for admins.

### 9.17 Notifications

- Enterprise: `GET /enterprise/notifications`, `GET /enterprise/notifications/unread-count`, `PATCH /enterprise/notifications/{id}/read`, `POST /enterprise/notifications/mark-all-read`.
- Realtor v1: `GET /realtor/billing/notifications` + unread count + read/mark-all. A general realtor inbox requires a new additive endpoint (section 13.3); until then, lead/task pushes deep-link without an inbox entry.
- List grouped by Today / Earlier; unread dots; tap → mark read + deep link; "Mark all read".
- Badge on the More tab (enterprise) / bell icon.
- Billing notifications also reachable from Billing.

### 9.18 Billing

- Realtor: `GET /realtor/billing`, `GET /realtor/billing/invoices/{id}`, `GET /realtor/billing/invoices/{id}/download`, `GET /realtor/plans`, `POST /realtor/plans/upgrade`.
- Show current plan, usage limits if returned, renewal date, invoice list with download/share.
- "Upgrade / renew" → open web plans/checkout in Custom Tabs; on resume refresh billing. Upgrade request can also be submitted if the UI supports the fields the API expects (verify in Phase 0).
- Enterprise admin: `GET /enterprise/billing`, invoice download, `POST /enterprise/billing/upgrade`, `POST /enterprise/billing/seat-requests`. Keep forms minimal and map 422 errors.
- Non-admin enterprise members: no Billing menu.

### 9.19 Profile / Settings

- Realtor: `GET/PUT /realtor/profile`, `POST /realtor/profile/avatar` (multipart camera/gallery), `PUT /realtor/profile/password`, `GET /realtor/settings`, `PUT .../notifications`, `PUT .../privacy`, `PUT .../appearance`, `DELETE /realtor/settings/account` (double confirmation + password if required by the API).
- Enterprise: `PUT /enterprise/settings/profile`, `PUT /enterprise/settings/password`; org settings and logo stay on web.
- Local preferences (theme, reminder lead time, biometric lock) in DataStore; server preferences for notifications/privacy.
- Account deletion: destructive, explicit confirmation, then logout all local state. Verify the realtor API's required payload in Phase 0.

### 9.20 Global UX states

Every data screen defines:

- **Loading**: skeleton (lists/cards) or spinner only for actions.
- **Empty**: icon + one sentence + primary action where allowed.
- **Error**: message from `ApiError` + Retry.
- **Offline**: cached content + "Offline · showing saved data" banner; writes disabled with a toast.
- **Forbidden**: inline explanation, not a blank screen.
- **Refreshing**: pull-to-refresh indicator without clearing current content.

### 9.21 Calls (both editions)

The implemented app-only calling API is the source of truth: `updateplan.md` (architecture) and `README.md`
(API). Android scope:

- **Bootstrap** (`GET /mobile/bootstrap`): edition, `calling.mode = app_only`, dialer intent, recording limits, consent copy, minimum app version.
- **Device registration** (`POST /mobile/devices`): send `device_uuid`, model, OS, app version, recorder source on first launch and refresh `last_seen_at` periodically.
- **Call action** on lead detail/list → `ACTION_DIAL` with the lead number. After returning, show a log sheet (consent + outcome + duration) and call `POST /{edition}/calls/app` with a UUID `client_call_id`. Retry with the same ID is safe (200, no duplicate).
- **Recording upload** (`POST /{edition}/calls/{id}/recording`): multipart `recording` from the handset's recorder output, plus optional `matched_via`/`sha256`. Handle `duplicate_recording: true`, 409 (another recording already attached), 403 (not the call creator), and retry with WorkManager.
- **Calls list** (`GET /{edition}/calls`): filter by source/status/disposition/date (agent filter only where the API allows it), rows show lead name, duration, disposition, recording icon. Paging 3 + pull-to-refresh.
- **Call detail** (`GET /{edition}/calls/{id}`): lead snapshot, agent, timeline, disposition picker, notes; recording player via Media3 with the bearer header on the OkHttp data source.
- **Recording playback** (`GET /{edition}/calls/{id}/recording`): stream with `Range`; never store the file in shared storage.
- **Permissions**: `INTERNET`, `POST_NOTIFICATIONS` only. No `CALL_PHONE`; `RECORD_AUDIO` only if a future in-app recorder is explicitly approved. No `READ_CALL_LOG` or broad storage permissions.
- **Dashboard**: render the additive `calls` block (today totals, connected, recordings, avg duration, pending dispositions, recent calls). Hide it when absent so older backends keep working.
- **Offline**: call list and metadata read from Room with banner; create/upload fail fast and queue for retry when connectivity returns.

Calling is a separate workstream: ~8 Android days for the calling/recording slice on top of the app estimate. There
is no provider account, KYC, or webhook setup because calls are placed on the user's own SIM.

---

## 10. Design system — exact parity with the web frontend

### 10.1 Non-negotiable rule

The Android app must look like the same product, not a "brand-inspired" fork. Source of truth:

- `frontend/tailwind.config.js` (color names, font, radius, shadows)
- `frontend/src/index.css` (`:root` and `.dark` CSS variables, `sidebar-surface`, `auth-grid`)
- `frontend/src/components/ui/*` (Button, Card, Badge, Tabs, Input)
- `frontend/src/components/enterprise/StageBadge.jsx`, `frontend/src/components/shared/StatusBadge.jsx`
- `frontend/src/components/leads/LeadDetailUI.jsx` (stage rail, action tiles, timeline, about, composer)

**No ad-hoc colors.** All colors, shapes, and type styles live in `core/designsystem/`. A CI check greps for `Color(0x` outside the theme package (allowlist: chart palette and status tones in one `Tone` file).

> The web is **light-first**: light background `#FFFFFF`, dark background `#080408`. The dark theme is a plum-black, not neutral gray. Do not reuse generic Material defaults.

### 10.2 Color tokens (exact values from `index.css`)

| Token | Light | Dark | Where it is used on web |
|---|---|---|---|
| `background` | `#FFFFFF` | `#080408` | Page background |
| `foreground` | `#0F0F12` | `#FFFFFF` | Primary text |
| `card` | `#FFFFFF` | `#171117` | Cards, topbar, sheets |
| `card-foreground` | `#0F0F12` | `#FFFFFF` | Text on cards |
| `muted` | `#F6F6F8` | `#1F171E` | Skeletons, muted tiles, secondary rows |
| `muted-foreground` | `#4A4952` | `#C9C5D0` | Labels, hints, secondary text |
| `secondary` | `#F7F7F9` | `#221A21` | Secondary buttons, subtle surface |
| `secondary-foreground` | `#0F0F12` | `#FFFFFF` | Text on secondary |
| `accent` | `#F8EEF6` | `#2A0A24` | Soft brand tint |
| `accent-foreground` | `#650052` | `#E9B8DF` | Text/icons on accent |
| `border` / `input` | `#E6E4EB` | `#2A2229` | Dividers, input borders, card outline |
| `ring` | `#8A006B` | `#E9B8DF` | Focus ring |
| `primary` | `#8A006B` | `#8A006B` | Filled buttons, active tab underline, stage rail active |
| `primary-foreground` | `#FFFFFF` | `#FFFFFF` | Text on primary |
| `highlight` | `#650052` | `#E9B8DF` | Links, active labels, icon accents |
| `destructive` | `#DC2626` | `#E5484D` | Delete, errors, overdue |
| `destructive-foreground` | `#FFFFFF` | `#FFFFFF` | Text on destructive |
| `success` | `#059669` | `#059669` | Won, completed, convert button |
| `success-foreground` | `#ECFDF5` | `#ECFDF5` | Text on success |
| Sidebar surface | `#260720` + gradients | same | Brand drawer / More header |
| Brand ink | `#080408` | `#080408` | Logo ink / splash |
| Brand highlight | `#E9B8DF` | `#E9B8DF` | Active icon in dark nav, sidebar accents |

**Brand palette (for charts, decorations, gradients):**

```
primary-50  #FDF2FA   primary-100 #FCE7F6   primary-200 #F9CFF0
primary-300 #F3A6E0   primary-400 #E769C7   primary-500 #D333AA
primary-600 #A80083   primary-700 #8A006B   primary-800 #650052
primary-900 #4A003C   primary-950 #2B0023
```

**Chart palette (exact from dashboards):**

- Source donut: `#8A006B, #A855F7, #3B82F6, #10B981, #F59E0B, #F43F5E, #94A3B8`
- Enterprise stages: new `#3B82F6`, contacted `#6366F1`, qualified `#8B5CF6`, site_visit_scheduled `#A855F7`, site_visit_completed `#C084FC`, negotiation `#F59E0B`, won `#10B981`, lost `#F43F5E`, on_hold `#94A3B8`
- Trend bars: primary `#8A006B` for leads; success `#10B981` for won; muted grid `#E6E4EB` / `#2A2229`

**Sidebar / brand gradient (must be reproduced in Compose):**

```
base: #260720
overlay 1 (radial, 115% × 70% at 8% 0%):
  rgba(138, 0, 107, 0.40) 0% → rgba(38, 7, 32, 0) 62%
overlay 2 (linear, 180deg):
  rgba(101, 0, 82, 0.24) 0% → rgba(38, 7, 32, 0) 42%
```

**Auth dotted grid:** radial dot pattern, dot color `#E9B8DF` at 18% opacity, 22dp pitch, used behind login/register hero only.

### 10.3 Compose Material 3 theme (copy-ready baseline)

```kotlin
// core/designsystem/RumuColors.kt
import androidx.compose.ui.graphics.Color

object RumuColors {
    val Magenta = Color(0xFF8A006B)
    val Plum = Color(0xFF650052)
    val Ink = Color(0xFF080408)
    val Sidebar = Color(0xFF260720)
    val Highlight = Color(0xFFE9B8DF)
    val Success = Color(0xFF059669)
    val SuccessFg = Color(0xFFECFDF5)

    // Light
    val LightBackground = Color(0xFFFFFFFF)
    val LightForeground = Color(0xFF0F0F12)
    val LightCard = Color(0xFFFFFFFF)
    val LightMuted = Color(0xFFF6F6F8)
    val LightMutedFg = Color(0xFF4A4952)
    val LightSecondary = Color(0xFFF7F7F9)
    val LightAccent = Color(0xFFF8EEF6)
    val LightBorder = Color(0xFFE6E4EB)
    val LightRing = Magenta
    val LightDestructive = Color(0xFFDC2626)

    // Dark
    val DarkBackground = Color(0xFF080408)
    val DarkForeground = Color(0xFFFFFFFF)
    val DarkCard = Color(0xFF171117)
    val DarkMuted = Color(0xFF1F171E)
    val DarkMutedFg = Color(0xFFC9C5D0)
    val DarkSecondary = Color(0xFF221A21)
    val DarkAccent = Color(0xFF2A0A24)
    val DarkBorder = Color(0xFF2A2229)
    val DarkRing = Highlight
    val DarkDestructive = Color(0xFFE5484D)
}

// core/designsystem/RumuTheme.kt
private val LightScheme = lightColorScheme(
    primary = RumuColors.Magenta,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFFCE7F6),
    onPrimaryContainer = RumuColors.Plum,
    secondary = RumuColors.LightSecondary,
    onSecondary = RumuColors.LightForeground,
    secondaryContainer = RumuColors.LightMuted,
    onSecondaryContainer = RumuColors.LightForeground,
    tertiary = RumuColors.LightAccent,
    onTertiary = RumuColors.Plum,
    background = RumuColors.LightBackground,
    onBackground = RumuColors.LightForeground,
    surface = RumuColors.LightCard,
    onSurface = RumuColors.LightForeground,
    surfaceVariant = RumuColors.LightMuted,
    onSurfaceVariant = RumuColors.LightMutedFg,
    outline = RumuColors.LightBorder,
    outlineVariant = RumuColors.LightBorder,
    error = RumuColors.LightDestructive,
    onError = Color.White,
    inverseSurface = RumuColors.LightForeground,
    inverseOnSurface = Color.White,
    surfaceTint = Color.Transparent,
    scrim = Color.Black,
)

private val DarkScheme = darkColorScheme(
    primary = RumuColors.Magenta,
    onPrimary = Color.White,
    primaryContainer = RumuColors.DarkAccent,
    onPrimaryContainer = RumuColors.Highlight,
    secondary = RumuColors.DarkSecondary,
    onSecondary = Color.White,
    secondaryContainer = RumuColors.DarkMuted,
    onSecondaryContainer = Color.White,
    tertiary = RumuColors.DarkAccent,
    onTertiary = RumuColors.Highlight,
    background = RumuColors.DarkBackground,
    onBackground = RumuColors.DarkForeground,
    surface = RumuColors.DarkCard,
    onSurface = RumuColors.DarkForeground,
    surfaceVariant = RumuColors.DarkMuted,
    onSurfaceVariant = RumuColors.DarkMutedFg,
    outline = RumuColors.DarkBorder,
    outlineVariant = RumuColors.DarkBorder,
    error = RumuColors.DarkDestructive,
    onError = Color.White,
    inverseSurface = RumuColors.DarkForeground,
    inverseOnSurface = RumuColors.DarkBackground,
    surfaceTint = Color.Transparent,
    scrim = Color.Black,
)
```

- Use `surfaceTint = Color.Transparent` so Material 3 does not tint cards with the primary color; the web has flat cards with borders.
- Dark mode keeps `primary = #8A006B` (same as web) for filled buttons; `highlight = #E9B8DF` is the text/icon accent. Never use `#8A006B` as small text on the dark background.
- Status bar: light theme white with dark icons; dark theme `#080408` with light icons. Use `enableEdgeToEdge()` and `WindowInsets`.

### 10.4 Typography — Inter, same scale

Bundle `Inter-Regular/Medium/SemiBold/Bold` in `res/font/` (SIL OFL). Fallback to system sans only if a glyph is missing.

| Style | Size / line | Weight | Use |
|---|---|---|---|
| Display | 28 / 34 | 600 | Stat values (with `-0.03em` tracking), hero numbers |
| Title | 20 / 26 | 600 | Screen titles, lead name |
| Section | 16 / 22 | 600 | Card titles, section headers |
| Card title | 15 / 24 | 600 | `CardTitle` equivalent |
| Body | 14 / 20 | 400–500 | Default text, list rows |
| Label | 13 / 18 | 500 | Stat labels, form labels |
| Caption | 12 / 16 | 400–500 | Hints, timestamps |
| Micro | 10–11 / 14 | 600 | Uppercase section labels (`tracking 0.16em`), tab counts, badge text 12sp |

Rules: numbers use tabular figures; money/stat values use Display or Section weight 600; do not use all-caps for data, only for section labels. Web `font-sans` is Inter, so the Android app must not default to Roboto for titles.

### 10.5 Shape, spacing, elevation (match Tailwind)

| Element | Web class | Android |
|---|---|---|
| Button, input | `rounded-lg` | **8dp** corner, height 40dp (sm 36, lg 44) |
| Card, action tile, composer | `rounded-xl` | **12dp** corner, 1dp border |
| Large sheet / brand panel | `rounded-2xl` | 16dp corner |
| Pill / badge / avatar | `rounded-full` | 50% |
| Page padding | `px-4` / `p-5` | 16dp screen, 20dp card padding |
| Section gap | `gap-4` / `gap-5` | 16dp / 20dp |
| Sidebar width | `w-64` | 256dp |
| Top bar | `h-16` / `h-[76px]` | 64dp app bar, 76dp brand header |
| Bottom nav | — (web uses drawer) | 64dp + system insets |

Shadows:

```
soft: 0 1px 2px rgba(16,24,40,.04)                    → Compose elevation 1dp, no ambient tint
card: 0 1px 3px rgba(16,24,40,.06),
      0 18px 40px -28px rgba(16,24,40,.18)            → Compose elevation 2dp or custom draw
dark soft: 0 1px 2px rgba(0,0,0,.35)
dark card: 0 1px 3px rgba(0,0,0,.4), 0 20px 48px -28px rgba(0,0,0,.7)
```

Prefer border + a low custom shadow over Material's default elevation overlays; otherwise cards turn purple-gray and break parity.

### 10.6 Components — web to Compose mapping

| Component | Exact web behavior | Compose implementation |
|---|---|---|
| Primary button | `bg-primary text-white`, 8dp radius, h-10, semibold 14sp, 8dp icon gap, disabled 60% | `Button` with `RumuColors.Magenta`, white content, 8dp shape, 40dp height, ripple |
| Secondary / outline / ghost / danger / danger-outline / link | As `Button.jsx` variants | One `RumuButton` with a `variant` enum; danger uses destructive; link uses highlight |
| Card | white/dark card, 1dp border, 12dp radius, soft shadow | `Surface` + border + `Modifier.shadow(1–2dp)` |
| Input / Select / Textarea | 40dp, 8dp radius, card bg, border, focus primary + ring 20%, error destructive, disabled 60% | `OutlinedTextField` customized or a `BasicTextField` shell to control 40dp height and padding; labels above (13sp) |
| Badge | full pill, 12sp medium, 10dp x / 2dp y padding, 1dp inset ring, 6dp dot | `RumuBadge(tone, dot)`; ring via border |
| Tabs | horizontal scroll, bottom divider, active highlight + 2dp primary underline inset 8dp, count chip | `ScrollableTabRow`-like custom row; animated underline 150ms |
| Filter chips | rounded full, muted when idle, primary/10 + highlight when active | `FilterChip` styled with tokens |
| Stage rail | container `p-1.5` (6dp), gap 4dp, buttons 8dp radius, py 10dp, active primary with white text, disabled muted 45% | `LazyRow` in a 48dp container; never wrap |
| Action tiles | 4-column grid, 8dp gap, pastel tinted border/bg, 36dp icon circle, 11sp label | `Row` of 4 equal tiles; tones primary/success/warning/info |
| Timeline | 32dp icon circle on a 1dp vertical line, 12dp card per item, relative time, transition pills 4dp radius | `LazyColumn` with a drawn connector; tone map |
| Note composer | muted/20 bg, 12dp radius, transparent textarea, right-aligned sm button | Column inside a bordered surface; disable Post when blank |
| Stats card | p-5, label 13sp muted, value 28sp semibold, 40dp tone tile top-right | `StatsCard` composable; loading skeleton 24×96dp |
| Modal | 12dp card, overlay, title + actions | `ModalBottomSheet` for mobile; full-screen only for long forms |
| Skeleton | `animate-pulse bg-muted` | Shimmer/alpha pulse 1.2s, respects reduced motion |
| Toast | top-right on web; mobile uses bottom snackbar | `SnackbarHost` above bottom nav, 3s, card surface |
| Avatar | image or initials fallback | Coil `AsyncImage` + initials on muted circle |
| Pagination | page buttons on web | Infinite scroll + "Load more" error retry (mobile-native) |

Icon set: port the exact Heroicons used by the web screens (outline 24px for actions, solid 20px for nav) into `res/drawable`; do not substitute filled Material icons for outline ones in the same context.

### 10.7 Status, stage and priority tones (exact maps)

Tone background = color at 10% alpha; ring = 20%; text values below.

| Tone | Background | Light text | Dark text |
|---|---|---|---|
| success | `#10B981` @10% | `#047857` | `#6EE7B7` |
| warning | `#F59E0B` @10% | `#B45309` | `#FCD34D` |
| danger | `#EF4444` @10% | `#B91C1C` | `#FCA5A5` |
| info | `#0EA5E9` @10% | `#0369A1` | `#7DD3FC` |
| purple | `#8B5CF6` @10% | `#6D28D9` | `#C4B5FD` |
| primary | `#8A006B` @10% | `#650052` | `#E9B8DF` |
| neutral | muted | muted-foreground | muted-foreground |

**StatusBadge map (realtor/tasks):** new, contacted, open = info; qualified = primary; negotiation = purple; won, completed, paid, active, approved = success; pending, in_progress, booked, processing = warning; lost, cancelled, failed, overdue, suspended = danger; on_hold, draft, paused, closed = neutral. `proposal` is not in the web map and therefore renders neutral — mirror that, do not invent a color.

**StageBadge map (enterprise):** new, contacted = info; qualified = primary; site_visit_scheduled, site_visit_completed = purple; negotiation = warning; won = success; lost = danger; on_hold = neutral.

**Priority:** low = neutral, medium = info, high = warning, urgent = danger.

### 10.8 Navigation chrome and brand surfaces

- **Top app bar (64dp):** surface/card background, 1dp bottom border, title 16sp semibold, back/overflow icons 24dp in muted-foreground. The web topbar uses `bg-card/90` + blur; Android uses solid card + border (no expensive blur).
- **Bottom navigation:** 64dp + insets, card surface, top border, 24dp outline icons, 11sp labels. Active: highlight text/icon + a `primary/10` rounded-12 indicator; inactive: muted-foreground. Unread badge: highlight background, `#260720` text (same as sidebar badge).
- **More hub:** brand header using the `sidebar-surface` gradient, `rumu-logo.png`, white title; menu rows 48dp with 20dp icons, active left 2dp `#E9B8DF` bar, `white/55` idle labels, `white` active — exactly like the web sidebar.
- **Login/register:** white/dark background with the dotted `auth-grid`; a plum gradient hero panel (mobile version of the web auth sidebar) with the white logo; form card on surface.
- **Splash:** `#260720` → `#080408` gradient with centered white Rumu logo; Android 12 splash icon background `#260720`.
- **FAB:** 56dp, 16dp corner, primary fill, white 24dp icon, low shadow; sits above bottom nav and hides on scroll if needed.
- **Dialogs:** card surface, 12dp corner, title 16sp semibold, body 14sp muted, actions right-aligned (outline Cancel + primary/destructive Confirm). Web `ConfirmDialog` parity.
- **Logo:** reuse `frontend/public/rumu-logo.png` (on plum/dark) and `rumu-logo-ink.png` (on light). Do not redraw or recolor the mark.

### 10.9 Interaction and motion

| Interaction | Spec |
|---|---|
| Screen push | 220ms slide-in from right + fade; pop reverse. Bottom tabs use 150ms fade with preserved state. |
| Tab switch (lead detail) | 150ms crossfade, no horizontal swipe hijack (horizontal scroll belongs to the stage rail). |
| Button press | Bounded ripple in primary 12%; no scale bounce; disabled uses 60% alpha and no ripple. |
| Bottom sheet | 240ms slide-up, scrim 50% black, drag handle 32×4dp muted; drag-to-dismiss. |
| Pull-to-refresh | Primary indicator; keep content visible; stop on completion/error. |
| Stage change | Confirm sheet → optimistic disabled state (not optimistic data) → success haptic + snackbar → refresh. Failure restores the previous state and shows the server message. |
| Task complete | Checkbox animation 180ms; 5s undo snackbar (re-opens via API). |
| Search | 350ms debounce; show inline progress; cancel previous request; keyboard search action triggers immediately. |
| Forms | `imePadding`; Next/Done IME actions; unsaved-changes dialog on back. |
| Toast/Snackbar | 3s, above bottom nav, includes Undo only for reversible actions. |
| Haptics | Light impact on successful stage/task/visit change; medium on destructive confirm. Respect system haptics setting. |
| Reduced motion | If animator duration scale is 0, skip slide/fade animations; keep functional state changes instant. |
| Offline | Slim top banner; cached content stays; write buttons disabled with tooltip/snackbar explaining. |

Every interactive element has a pressed, focused, disabled, and loading state. Loading a button must not resize it (spinner replaces the leading icon, keep label width stable).

### 10.10 UI iteration plan (3 review-gated iterations)

UI is built in iterations, not once at the end. Each iteration has a visible artefact and a sign-off before the next starts.

| Iteration | Duration | What is produced | Review gate |
|---|---|---|---|
| **Iteration 1 — Structure** | 2 days | Low-fi wireframes at 390×844dp for: login, register, invitation, realtor home, enterprise home, leads list, lead detail, lead form, tasks, visits, More, settings. Navigation map + per-screen primary action. | Product + QA walkthrough: every MVP screen reachable; no dead ends; stage/assign/delete actions visible only where allowed. |
| **Iteration 2 — Visual parity** | 3 days | Figma/Compose previews using the exact tokens above; component inventory (button, card, badge, tabs, chips, input, stage rail, action tiles, timeline, stat card). Side-by-side with web screenshots at 390px, light + dark. | Design sign-off: zero off-token colors; all radii/type/spacing match section 10; component states complete. |
| **Iteration 3 — Build polish** | Runs through phases 2–5 | Real Compose screens with previews for every component; weekly screenshot review vs the web; empty/loading/error/offline states; dark mode; font scale; TalkBack. | Visual QA checklist + device screenshots approved before Play internal testing. |

Rules for the iterations:

- No screen coding starts before Iteration 2 token sign-off; otherwise rework is guaranteed.
- Every screen PR includes `@Preview` light + dark and a screenshot in the PR description.
- Compare against the **running web app at a 390px viewport**, not memory or a static mock.
- Copy in Hinglish? No — v1 UI copy stays English, matching the current web; keep it in `strings.xml` for later Hindi.
- Changes to a shared token require a one-line note in the PR and re-check of all affected screens.

### 10.11 Screen parity checklist (visual QA)

For each screen, verify side-by-side with the web:

- [ ] Background/card/border/text exactly the tokens above (eyedropper check).
- [ ] Inter font, correct weights and tabular numbers.
- [ ] Card radius 12dp, button/input radius 8dp, shadows and 1dp borders.
- [ ] Stage/status/priority pills match the tone maps (not generic Material colors).
- [ ] Active tab: highlight text + 2dp primary underline + count chip.
- [ ] Stage rail: active primary with white label; disabled stages at 45% muted.
- [ ] Action tiles: four equal tiles with the correct pastel tones and 36dp icon circles.
- [ ] Lead detail timeline: 32dp icon circle, connector line, transition pill red → emerald.
- [ ] Bottom nav active indicator and unread badge colors.
- [ ] Dark mode uses `#080408` background and `#171117` cards, not black/gray defaults.
- [ ] Empty, loading, error, offline, and forbidden states exist and use the same tokens.
- [ ] No hardcoded colors outside `core/designsystem`.
- [ ] Font scale 200% and TalkBack pass without clipping the primary action.

### 10.12 Assets and accessibility

- App icon: adaptive icon derived from the Rumu mark (foreground), `#260720` background, monochrome layer for Android 13 themed icons.
- Splash: Android 12 SplashScreen API with `#260720` background + white logo.
- Notification icon: white monochrome silhouette (Android requires it); do not use the colored logo in the status bar.
- Minimum 48dp touch targets even when a visual is 36dp; content descriptions for icon-only buttons.
- Never color-only status: pair the dot/text with a label.
- Contrast: filled primary buttons are white-on-magenta (passes); `highlight` is used for text, not `primary`, in dark mode; secondary text uses muted-foreground.
- Support 200% font scale; allow list rows to grow instead of truncating critical values.
- TalkBack: announce stage rail selection ("Current stage: Negotiation"), tab counts, and timeline transitions ("Qualified to Negotiation").
- Localization-ready: all strings in `strings.xml`; use `plurals` for counts.

### 10.13 Wireframe targets (Iteration 1 baseline)

These are structural references for the low-fi iteration; Iteration 2 replaces them with exact-token Figma/Compose previews. All measurements use the tokens in 10.2–10.6.

**Login**

```
┌──────────────────────────────┐
│ [Rumu logo]                  │
│                              │
│ Welcome back                 │
│ Sign in to your workspace    │
│                              │
│ Email                        │
│ [__________________________] │
│ Password              Show   │
│ [__________________________] │
│                              │
│ [         Sign in         ]  │
│                              │
│ Create realtor account       │
│ Have an invitation?          │
│                              │
│ dotted brand background      │
└──────────────────────────────┘
```

**Home (Realtor / Enterprise share the shell)**

```
┌──────────────────────────────┐
│ Rumu                 🔔  (A) │  64dp surface + border
├──────────────────────────────┤
│ Good morning, Rohan          │
│ [Plan: Growth · renews 12 Dec]│
│ ┌──────────┐ ┌──────────┐    │
│ │ 128      │ │ 24       │    │  StatsCard: 28sp
│ │ Leads    │ │ New      │    │
│ └──────────┘ └──────────┘    │
│ ┌──────────┐ ┌──────────┐    │
│ │ ₹1.2Cr   │ │ 6        │    │
│ │ Won      │ │ Overdue  │    │
│ └──────────┘ └──────────┘    │
│ Needs attention              │
│ • Call Priya · 10:30   (red) │
│ • Visit Skyline · 4:00 PM    │
│ Recent campaigns / activity  │
│ ...                          │
├──────────────────────────────┤
│ Home  Leads  Tasks  Visits  More │
└──────────────────────────────┘
```

**Leads list**

```
┌──────────────────────────────┐
│ Leads                    [+] │
│ [Search name, phone...]      │
│ [New] [Contacted] [Qualified]│
│ ┌──────────────────────────┐ │
│ │ Priya Sharma       [Qual]│ │
│ │ Skyline Heights · High   │ │
│ │ Due 10:30 (red) · 2 open │ │
│ │ (A) Rohan Mehta          │ │
│ └──────────────────────────┘ │
│ ┌──────────────────────────┐ │
│ │ Amit Verma        [New]  │ │
│ │ ...                      │ │
│ └──────────────────────────┘ │
│                              │
├──────────────────────────────┤
│ Home  Leads  Tasks  Visits  More │
└──────────────────────────────┘
```

**Lead detail**

```
┌──────────────────────────────┐
│ ←  Priya Sharma          ⋮   │
├──────────────────────────────┤
│ [Qualified] [High] [Customer]│
│ Priya Sharma                 │
│ Skyline Heights              │
│ ✉ priya@example.com          │
│ ☎ +91 98765 43210            │
│ ┌ Assigned to ─────────────┐ │
│ │ (A) Rohan · Sales Team   │ │
│ └──────────────────────────┘ │
│ ┌Call─┐ ┌WA───┐ ┌Note┐ ┌Act.┐│  4 pastel tiles
│ └─────┘ └─────┘ └────┘ └────┘│
│ ▸ About Lead                 │
│ [   Convert to Customer   ]  │  success green
│   Delete Lead                │  destructive text
│──────────────────────────────│
│ New  Contacted [Qualified] … │  stage rail, scroll
│ Activity | Notes | Follow-ups│  active highlight
│ [Write a note.............]  │
│ ● Status changed             │
│   Qualified → Negotiation    │
│ ● Note added                 │
│   Follow up after Diwali     │
│ ...                          │
└──────────────────────────────┘
```

**More hub**

```
┌──────────────────────────────┐
│ #260720 + magenta gradient   │
│ Rumu                         │
│ Rohan Mehta · Enterprise     │
├──────────────────────────────┤
│ Customers                 >  │
│ Campaigns                 >  │
│ Calls                     >  │
│ Reports                   >  │
│ Team                      >  │
│ Inventory                 >  │
│ Billing                   >  │
│ Notifications            (3) │
│ Settings                  >  │
│ Sign out                     │
└──────────────────────────────┘
```

---

## 11. Data layer and offline behavior

### 11.1 Layers

```
Compose Screen → ViewModel (UiState + events)
             → Repository (edition-aware)
             → RemoteDataSource (Retrofit) + LocalDataSource (Room)
```

- Repositories return domain models, never raw DTOs.
- DTOs use `@Serializable` + `ignoreUnknownKeys = true` + `explicitNulls = false` so additive backend fields never crash the app.
- One `Result`-style wrapper (`AppResult<T>`) carries `ApiError`; do not throw across layers.
- Edition-specific API interfaces share DTOs where shapes match (`LeadDto`, `TaskDto`, `VisitDto`) but keep separate endpoints.

### 11.2 Caching strategy (read-only)

| Data | Cache | Refresh |
|---|---|---|
| Dashboard | Room snapshot per edition + user id | On open if older than 2 min; pull-to-refresh |
| Lead list page 1 | Room keyed by edition + filter hash | On open; next pages not cached |
| Lead detail | Room by edition + lead id | On open; always revalidate |
| Tasks / visits (first page) | Room | On open |
| Customers / campaigns / inventory | Room (first page/detail) | On open |
| Profile / context | DataStore non-sensitive fields | `/auth/me` on splash |

Rules:

- Never show cached data from a different user; cache rows are namespaced by user id and cleared on logout.
- Show "Updated 5 min ago" when serving cache.
- No writes while offline (v1). A queued-write design is a later phase and needs conflict rules.
- On 401 or account deletion: clear all tables.

### 11.3 Paging

- Paging 3 with a custom `PagingSource` per filter; invalidate on pull-to-refresh, filter change, and successful mutation.
- Keep scroll position when returning from detail (remember LazyListState per tab).
- Page size 20; prefetch distance 3.

### 11.4 Local reminders

- WorkManager unique work per task/visit (`reminder-task-{id}`), one-time request at `due_at - leadTime` (default 15 min; user can choose 15 min / 1 h / 1 day).
- Notification channel `follow_ups` (high importance) and `visits` (default).
- Reschedule on edit; cancel on complete/delete.
- Doze may delay exact delivery; copy says "around" not "exactly". Do not request `SCHEDULE_EXACT_ALARM` in v1.
- On device reboot WorkManager restores work automatically.

---

## 12. Push notifications (FCM)

### 12.1 Client

- Add `google-services.json` per build (kept out of VCS if the repo policy requires; otherwise restrict access).
- `FirebaseMessagingService` handles:
  - `onNewToken` → register with backend.
  - `onMessageReceived` → build a notification only for foreground/data messages; use channels and deep link.
- Android 13+ request `POST_NOTIFICATIONS` contextually (after first successful login, with rationale).
- Create channels: `leads`, `follow_ups`, `visits`, `billing`, `general`.
- Deep link data keys: `type`, `edition`, `lead_id`, `task_id`, `visit_id`, `deep_link`.
- Tapping marks the notification read via the relevant API when applicable, then navigates.
- On logout: call the unregister endpoint, then delete the FCM token locally.
- Never show PII-heavy content on the lock screen by default? Keep title/body generic ("New lead assigned", "Follow-up due") and open the app for details. Allow a Settings toggle for detail previews later.

### 12.2 Backend additions (additive)

1. **`device_tokens` migration** (new table only):

```
id, user_id (FK users, cascade), token (string 512, unique), platform (string 16),
device_name (string 100 nullable), app_version (string 20 nullable),
last_seen_at (timestamp nullable), created_at, updated_at
```

2. **Endpoints** (inside `auth:sanctum`, both roles):

```
POST   /api/v1/devices          { token, platform, device_name?, app_version? }  # upsert by token
DELETE /api/v1/devices          { token }                                        # this device
DELETE /api/v1/devices/all                                                       # logout-all
```

3. **Push service** — Firebase HTTP v1 (or `kreait/firebase-php`) with credentials supplied via environment/secret file path, never committed. Queue job `SendPushNotification` on the existing queue infrastructure.
4. **Events to notify** (start small):
   - Enterprise: lead assigned, leads distributed, stage changed on an assigned lead, site visit scheduled, task assigned/due.
   - Realtor: new lead (manual/Meta sync/import), task due, site visit scheduled, plan expiring, billing notice.
   - Admin broadcasts are web-only; no need to push to mobile in v1 unless product asks.
5. **Queue worker**: add the notification queue to the production worker set (existing workers run `meta-leads` and the broadcast queue). Confirm with the rollout doc; do not silently change existing queue names.
6. **Throttling/cleanup**: delete tokens that FCM reports as unregistered; update `last_seen_at` on app open.

### 12.3 Realtor inbox gap

Today `/realtor/notifications` does not exist; only billing notifications are exposed. Two options:

- **v1 (cheap):** billing inbox + push deep links; no general in-app inbox for realtor lead/task events.
- **v1.1 (recommended):** add additive endpoints mirroring the admin pattern over the existing `notifications` table (`index`, `unread-count`, `read`, `mark-all-read`) scoped to `user_id`. No schema change, no breaking change. Realtor Home bell then shows a full inbox.

Document whichever is chosen in the app's Settings → About and in the release notes.

---

## 13. Backend work required (additive only)

| # | Change | Files / shape | Risk | Needed by |
|---|---|---|---|---|
| 13.1 | `device_tokens` table + model | New migration, `DeviceToken` model, controller, routes under `auth:sanctum` | Low (new table) | Phase 5 push |
| 13.2 | FCM send service + queued job + event hooks | New service/job; observer or explicit calls in assignment/distribution/task/visit flows | Medium (must not block requests; queue) | Phase 5 |
| 13.3 | Realtor general notification endpoints (optional) | New controller + routes over existing `notifications` table | Low (additive) | v1.1 |
| 13.4 | Password reset endpoints (optional) | Standard Laravel password broker routes; mail template | Medium (email infra) | Only if product requires |
| 13.5 | Login rate limiting (hardening) | `throttle` middleware on auth login/register | Low, applies to web too | Phase 1/6 |
| 13.6 | Mobile version/config endpoint (optional) | `GET /api/v1/app/config` (min version, support contacts, force-update flag) | Low | Phase 6 |
| 13.7 | App-only calling + recording sync | **Backend already implemented** (`/mobile/*`, `calls/app`, recording upload, private streaming, dashboard `calls` block). Android slice: device registration, log sheet, recorder pickup, WorkManager upload | Medium (OEM recorder variance, consent) | Android Phase 7 |

Hard rules for these changes:

- **Additive migrations only.** No `migrate:fresh`, no column drops/renames, no changes to existing response shapes.
- Add new tests in the existing PHPUnit suites; keep the current 135 tests / 912 assertions green.
- Push sending must run on a queue and fail soft (a push failure never fails lead assignment or stage change).
- Do not change Sanctum token behavior or existing abilities; the app uses the same bearer tokens as the web.
- Update `README.md` / `ENTERPRISE.md` only for genuinely new endpoints and route counts.

### 13.1 Sample API contract verification (Phase 0)

Before writing UI, verify each endpoint with a real account on staging:

```bash
# Login
curl -s -X POST https://staging-api.rumu.in/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"realtor.check@skyline-demo.test","password":"Realtor#2026"}'

# Enterprise context (role, hierarchy, features)
curl -s https://staging-api.rumu.in/api/v1/enterprise/me \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"

# Lead list + detail
curl -s "https://staging-api.rumu.in/api/v1/enterprise/leads?per_page=5&stage=qualified" \
  -H "Authorization: Bearer <token>"
```

Record the actual JSON in `android/app/src/test/resources/fixtures/` and use those fixtures for contract tests. Do not hand-write payload shapes from assumptions.

---

## 14. Security

| Area | Requirement |
|---|---|
| Transport | HTTPS only; `usesCleartextTraffic=false`; network security config disallows user CAs in release. |
| Token | EncryptedSharedPreferences; excluded from backup; never logged; cleared on logout/401. |
| Secrets | No API keys, Firebase server credentials, or signing secrets in the repo/APK. `google-services.json` handled per org policy. |
| AuthZ | Server authoritative. Client hides UI only. Every write handles 403/422 gracefully. |
| Input | Validate locally for UX; rely on server validation for truth. Never construct URLs from unvalidated user strings. |
| Deep links | Allowlist scheme/host; validate numeric IDs; verify the edition matches the session; exported components kept minimal. |
| Web links | Use Custom Tabs (not WebView) for checkout/registration; verify the URL host. |
| Screenshots | Optional `FLAG_SECURE` on lead detail/profile for enterprise admins; make it a remote/local setting, not always-on (it blocks sharing, which field teams may need). |
| Clipboard | Do not auto-copy phone/email; if added, show a toast and avoid clipboard listeners. |
| Logging | Release builds: no HTTP bodies, no tokens, no PII. Crash reports scrubbed of emails/phones. |
| Build | R8 + resource shrinking; keep rules for Retrofit/kotlinx.serialization; Play App Signing; upload-key separation. |
| Integrity | Play Integrity/root detection deferred (documented risk), not silently claimed. |
| Permissions | Minimal: INTERNET, POST_NOTIFICATIONS (13+), camera/read-media only when avatar upload is used. No contacts/SMS/location in v1. |

---

## 15. Performance and UX details

- Compose stability: immutable data classes, `remember` keys, `LazyColumn` keys by id; avoid recomposing whole lists on timer ticks.
- Images: Coil with memory/disk cache; request sized thumbnails.
- Startup: no network on the main thread; splash route decision off cached role, then refresh.
- Lists: 20/page; skeletons instead of blank screens; keep last content during refresh.
- Forms: disable submit while pending; prevent double taps; show inline errors.
- Offline: detect via `ConnectivityManager`; show a slim banner; disable writes; keep reads.
- Search: 350 ms debounce; trim; ignore empty.
- Time: all comparisons in UTC internally, display IST; "Today" is device-local IST.
- Currency: Indian grouping; compact for cards (`₹12.3L`, `₹1.2Cr`) with full value on detail/tooltip.
- Phone/WhatsApp: use intents; handle `ActivityNotFoundException` by disabling/showing a message.
- Sharing invoices/reports: `FileProvider` with a cache path; correct MIME types; revoke URI permission after share.
- App size target: < 25 MB release AAB (no heavy chart/media libs).
- Battery: no polling; refresh on open/resume/pull; WorkManager only for reminders and token upkeep.

---

## 16. Testing strategy

### 16.1 Test pyramid

| Level | Tools | Coverage |
|---|---|---|
| Unit | JUnit, Truth, MockK, Turbine | ViewModels, mappers, formatters, stage-transition rules, error mapping |
| Contract | MockWebServer + recorded fixtures | Every endpoint envelope, pagination, 401/403/422, suspended subscription |
| Compose UI | `createComposeRule` | Login validation, lead card rendering, stage rail enable/disable, note composer, empty/error states |
| Instrumented | AndroidX Test, Espresso where needed | Room DAOs, encrypted prefs, deep links, FCM payload handling, file sharing |
| E2E (staging) | Manual scripted matrix + optional Maestro | Realtor and enterprise admin/manager/employee journeys |
| Regression | Existing PHPUnit suite | Backend additions must not break web; run full suite |

### 16.2 Acceptance scenarios (must pass before release)

1. Realtor login → dashboard → open lead → change stage → note → complete follow-up → schedule visit, on staging data.
2. Enterprise employee login → sees only scoped leads → stage rules enforced (won requires value, lost requires reason) → assignment hidden if not lead employee.
3. Enterprise manager login → assign a lead → distribute selected leads → filtered list reflects changes.
4. Enterprise admin login → delete lead (audit log row created) → reports export share → team read-only pages.
5. Admin account login → blocked with web-console message; no data exposed.
6. Suspended realtor: reads load, writes blocked with the server message; renew link opens web.
7. Offline: last cached dashboard/leads render with banner; writes fail fast; reconnect refreshes.
8. Session expiry: 401 clears token and returns to login; pending deep link replays after re-login.
9. Invitation deep link: valid → accept → enterprise home; expired/used → friendly error; existing account → guidance.
10. Push: token registered; assignment/task push deep-links to the correct record; logout unregisters.
11. Rotation, dark mode, font scale 200%, TalkBack on lead detail.
12. Back navigation preserves filters/scroll; unsaved form warns.
13. Visual parity: side-by-side screenshots (app vs web at 390px) for login, both homes, leads list, lead detail, More — light and dark; the section 10.11 checklist passes.

### 16.3 CI gates

```
./gradlew ktlintCheck detekt lintDebug testDebugUnitTest
./gradlew assembleDebug
# scheduled/nightly with emulator:
./gradlew connectedDebugAndroidTest
```

- Run backend PHPUnit on every backend change.
- Fail the build on lint errors (not warnings), detekt issues above the agreed threshold, or unit-test failures.
- Keep a device matrix: API 26 (low), API 30, API 33, API 35/36; at least one Samsung and one stock-Android device.

---

## 17. Delivery phases and estimates

Assumes 1 Android developer full-time, backend support on demand, QA part-time, designer available for tokens/states. Estimates are working days.

| Phase | Duration | Deliverables | Exit criteria |
|---|---|---|---|
| **0. Contract & design lock** | 4 days | Staging accounts, endpoint fixture recordings, final screen map, **UI Iteration 1 wireframes**, exact token sheet (section 10), package name/domain confirmation, push architecture approval | Fixtures checked in; every MVP screen has an API + empty/error design; wireframes signed off |
| **1. Foundation + auth** | 7 days | Gradle project, **Compose theme + shared component library**, navigation shell, network layer, error model, encrypted session, login/register/invitation, splash routing, admin block; **UI Iteration 2 (visual parity) sign-off** | Login for both editions on staging; token persists; 401 flow works; light/dark previews match web tokens; auth unit tests |
| **2. Realtor core** | 9 days | Home, leads list + filters, lead form, lead detail tabs/actions, tasks, visits; per-screen light/dark previews (Iteration 3 starts) | Realtor journey 1 passes; build + tests green; visual QA checklist for these screens |
| **3. Enterprise core** | 12 days | Home, scoped leads + filters, lead detail (stage rules/assign/delete), tasks, visits, campaigns, customers | Admin/manager/employee scenarios pass; stage gating verified; visual QA checklist |
| **4. Secondary modules** | 7 days | Inventory read, reports read + export share, team read, notifications, billing, profile/settings, dark mode | Role-based menus; no 403-only entries; invoices/share work |
| **5. Platform polish** | 8 days | Room cache, offline banner, reminders, FCM client + backend token registration, deep links, accessibility, performance pass, **final Iteration 3 UI QA** | Offline + push scenarios pass; cold start target met; full section 10.11 checklist signed off |
| **6. Hardening + release** | 6 days | Full regression, staging E2E, Play Console setup, privacy/data safety, screenshots, internal testing, staged rollout plan | Release checklist complete; crash-free internal test; docs updated |
| **Total** | **~53 working days (8–10 weeks)** | | |

UI iteration days are included in the phases above: Iteration 1 in Phase 0, Iteration 2 in Phase 1, Iteration 3 continuously through Phases 2–5 with the final gate at the end of Phase 5.

Backend parallel work: ~3–5 days for device tokens + push job + tests; optional realtor inbox +2 days.

Buffer: add 15% for staging/API surprises. If timeline is tight, cut in this order: reports/team read-only → billing screens → inventory (deep-link to web) → realtor campaign create.

---

## 18. Definition of done

A phase is done only when:

- Feature works against the **real staging API** with real accounts, not mocks.
- Unit + contract tests cover happy path and 401/403/422/offline.
- Compose previews/screenshots reviewed in light and dark.
- **Visual parity signed off** against the running web app at a 390px viewport using the section 10.11 checklist; only design-system tokens are used (no hardcoded colors outside the theme).
- Empty, loading, error, offline, and forbidden states exist.
- Role gating verified with actual admin/manager/employee/realtor accounts.
- No secrets or tokens in code/logs; release build minified and tested.
- Accessibility pass (TalkBack labels, 48dp targets, font scale).
- Docs updated: `android/README.md` (setup, build, signing, API hosts) and this plan's status/decisions.
- Existing backend tests and web frontend build remain green after any backend addition.

---

## 19. Release plan

### 19.1 Pre-release

- Confirm `applicationId`, app name ("Rumu"), API hosts, privacy policy URL, support email/phone.
- Play Console: app created, App Signing enabled, internal testing track, testers added.
- Store listing: short/full description, screenshots (phone), feature graphic, icon, category (Business), content rating, data safety form (collects name/email/phone? app itself does not collect beyond account; declares network + notifications), target audience.
- Verify all backend migrations applied on production (additive), queue worker includes the notification queue, FCM credentials configured.
- Version: `versionCode 1`, `versionName 1.0.0`; tag the release source.

### 19.2 Rollout

1. Internal testing (team + 3 real users per edition) for 3–5 days.
2. Closed testing (20–50 users) for 1 week; watch crashes, API 403/5xx rates, login failures.
3. Staged production rollout 10% → 50% → 100%, minimum 48 h per step.
4. Monitor: crash-free rate, ANR, login success, lead-detail load, note/stage success, push delivery.

### 19.3 Post-release

- Hotfix path: patch `versionCode`, rebuild, internal test, staged rollout.
- Keep the previous AAB for rollback; the backend remains compatible (additive only).
- Review analytics/privacy after 2 weeks; plan v1.1 items from section 2.2.

---

## 20. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| API response shapes differ from assumptions | Rework in screens | Phase 0 fixture recording; tolerant DTOs; contract tests |
| Realtor has no status-history table | Audit trail is derived, may show an assumed initial "new" | Label as "Derived from notes"; document; consider an additive activity table later |
| No refresh tokens | Session expires → forced login | Clear 401 handling; biometric to speed re-login; consider long-lived token policy with backend (do not silently extend) |
| Realtor general notifications missing | Push without inbox | v1 billing inbox + deep links; additive realtor inbox in v1.1 |
| Push tokens multiply per reinstall | Stale tokens, duplicate pushes | Upsert by token, prune FCM "unregistered", unregister on logout |
| Suspended plan UX confusion | Users think app is broken | Banner + disabled writes + renew link, matching server message |
| Role drift between client and server | Hidden/visible mismatch | Use `/enterprise/me` flags; handle 403 everywhere; never assume |
| Doze delays reminders | Missed exact-time alerts | Copy "around"; in-app overdue lists; optional exact-alarm later with Play policy care |
| Play policy (data safety, permissions) | Rejection/delay | Minimal permissions; accurate data safety; no background location/contacts |
| Large enterprise lead volume | Slow lists | Server pagination, 20/page, indexed filters; no client-side full loads |
| Timezone/date bugs | Wrong due/visit times | UTC writes, IST display, tests around midnight/month boundaries |
| Certificate pinning mistakes | App bricks on cert rotation | Skip pinning in v1; if added, include backup pins and a remote kill switch |
| Backend push work delayed | No push at launch | Ship v1 without push if needed; local reminders still work; deep links ready |

---

## 21. Open decisions (needed before Phase 1)

1. **Application ID and domain** — proposed `in.rumu.mobile`, hosts `api.rumu.in` / `app.rumu.in`; confirm ownership.
2. **Who is the v1 audience?** Both editions at launch, or Realtor first then Enterprise? (Plan assumes both; Realtor-first cuts ~2 weeks.)
3. **Realtor general notification inbox** — include additive backend endpoints in v1 or defer to v1.1?
4. **Password reset** — add API + email flow, or route users to support/web?
5. **Enterprise self-registration in app** — confirm web-only for v1.
6. **Offline writes** — confirm "no" for v1.
7. **Screenshot protection** — required by enterprise customers or optional?
8. **Analytics/crash tooling** — Firebase Crashlytics + Analytics, or none/self-hosted? Affects privacy disclosures.
9. **Minimum Android version** — `minSdk 26` proposed; confirm field-device reality.
10. **Push provider** — FCM only (proposed); no SMS/WhatsApp Business API in this scope.

---

## Appendix A — Realtor API map

| Screen | Method & path | Notes |
|---|---|---|
| Login | `POST /auth/login` | Returns token + user |
| Register | `POST /auth/register` | Realtor only, default plan |
| Session | `GET /auth/me` | `subscription_suspended`, plan |
| Logout | `POST /auth/logout`, `/auth/logout-all` | |
| Home | `GET /realtor/dashboard` | Stats, 6-month trend, tasks, visits, follow-ups, plan warning |
| Leads | `GET /realtor/leads` | `search,status,campaign_id,priority,per_page`; `meta.status_counts` |
| Campaign leads | `GET /realtor/campaigns/{campaign}/leads` | Fixed 20/page, no search |
| Lead detail | `GET /realtor/leads/{lead}` | Notes, campaign, unit, custom fields |
| Create lead | `POST /realtor/campaigns/{campaign}/leads` | Campaign required |
| Update lead | `PUT /realtor/leads/{lead}` | |
| Delete lead | `DELETE /realtor/leads/{lead}` | Policy: own lead |
| Stage | `PATCH /realtor/leads/{lead}/status` | Any of 7 statuses; auto note |
| Note | `POST /realtor/leads/{lead}/notes` | |
| Resend Meta | `POST /realtor/leads/{lead}/meta-capi/resend` | Optional action |
| Campaigns | `GET /realtor/campaigns`, `GET /realtor/campaigns/{id}` | Create/edit optional v1 |
| Tasks | `GET/POST /realtor/tasks`, `GET/PUT/DELETE /realtor/tasks/{id}`, `PATCH /realtor/tasks/{id}/status` | Filter by `lead_id` |
| Visits | `/realtor/site-visits` CRUD + status | |
| Customers | `GET /realtor/customers` | |
| Inventory | `GET /realtor/inventory/projects`, `/projects/{id}`, `/floors/{floor}/units`, `/units/search` | Read-only |
| Profile | `GET/PUT /realtor/profile`, `POST /profile/avatar`, `PUT /profile/password` | |
| Settings | `GET /realtor/settings`, `PUT .../notifications`, `.../privacy`, `.../appearance`, `DELETE .../account` | |
| Billing | `GET /realtor/billing`, invoices list/download, `GET /realtor/plans`, `POST /realtor/plans/upgrade` | Upgrade via web checkout |
| Billing inbox | `/realtor/billing/notifications` (+ unread/read/mark-all) | v1 notification inbox |
| Meta | `/realtor/meta/*` | Not in app v1 |
| Import | `/realtor/campaigns/{id}/import/*` | Not in app v1 |

## Appendix B — Enterprise API map

| Screen | Method & path | Notes |
|---|---|---|
| Login | `POST /auth/login` | Role/context from user |
| Context | `GET /enterprise/me` | Enterprise, membership, hierarchy flags, plan, features |
| Invitation | `GET /enterprise/invitations/{token}`, `POST .../accept` | Deep link |
| Home | `GET /enterprise/dashboard` | Role-scoped stats/funnel/trend/recent/activity |
| Reference data | `GET /enterprise/reference-data` | Filter options (verify shape) |
| Leads | `GET /enterprise/leads` | Full filter set, scoped |
| Lead detail | `GET /enterprise/leads/{lead}` | Activities, tasks, visits, assignee, team |
| Create/update/delete | `POST /enterprise/leads`, `PUT/DELETE /enterprise/leads/{lead}` | Delete admin/manager |
| Stage | `PATCH /enterprise/leads/{lead}/stage` | Transition rules |
| Note | `POST /enterprise/leads/{lead}/notes` | |
| Assign | `POST /enterprise/leads/{lead}/assign` | Admin/manager/lead employee |
| Distribute | `POST /enterprise/leads/distribute` | Admin/manager |
| Round robin | `POST /enterprise/leads/{lead}/round-robin` | Admin/manager |
| Import | `POST /enterprise/leads/import` | Not in app v1 |
| Campaigns | `GET /enterprise/campaigns`, `GET /enterprise/campaigns/{id}` | Read |
| Tasks | `/enterprise/tasks` CRUD + status | |
| Visits | `/enterprise/site-visits` CRUD + status | |
| Customers | `GET /enterprise/customers` | |
| Inventory | `GET /enterprise/inventory/projects`, `/projects/{id}`, `/floors/{floor}/units`, `/units/search` | Read |
| Reports | `GET /enterprise/reports`, `/reports/targets`, `/reports/export` | Export admin/manager |
| Team | `GET /enterprise/teams`, `/teams/{id}`, `/users`, `/users/{membership}`, `/organization/tree` | Read |
| Notifications | `/enterprise/notifications` + unread/read/mark-all | |
| Profile | `PUT /enterprise/settings/profile`, `PUT /enterprise/settings/password` | |
| Billing | `GET /enterprise/billing`, invoice download, upgrade, seat request | Admin |
| Audit | `GET /enterprise/audit-logs` | Admin; optional More item |
| Meta | `/enterprise/meta/*` | Not in app v1 |

## Appendix C — Core DTO sketch (verify against fixtures)

```kotlin
@Serializable
data class Envelope<T>(
    val success: Boolean,
    val data: T? = null,
    val message: String? = null,
    val errors: Map<String, List<String>>? = null,
    val meta: PaginationMeta? = null
)

@Serializable
data class PaginationMeta(
    val current_page: Int,
    val last_page: Int,
    val per_page: Int,
    val total: Int,
    val status_counts: Map<String, Int>? = null
)

@Serializable
data class UserDto(
    val id: Int,
    val name: String,
    val email: String,
    val phone: String? = null,
    val role: String,
    val avatar_url: String? = null,
    val timezone: String? = null,
    val subscription_suspended: Boolean = false,
    val plan: PlanDto? = null,
    val enterprise_membership: EnterpriseMembershipDto? = null
)

@Serializable
data class LeadDto(
    val id: Int,
    val name: String,
    val email: String? = null,
    val phone: String? = null,
    val company: String? = null,
    val status: String? = null,
    val stage: String? = null,
    val priority: String? = null,
    val source: String? = null,
    val campaign: CampaignDto? = null,
    val assignee: UserDto? = null,
    val team: TeamDto? = null,
    val next_follow_up_at: String? = null,
    val is_customer: Boolean? = null,
    val open_tasks_count: Int? = null,
    val created_at: String? = null,
    val updated_at: String? = null
)
```

## Appendix D — Deep link and FCM payload contract

```json
{
  "notification": { "title": "New lead assigned", "body": "Priya Sharma · Skyline Heights" },
  "data": {
    "type": "lead_assigned",
    "edition": "enterprise",
    "lead_id": "482",
    "deep_link": "rumu://lead/enterprise/482"
  }
}
```

Client rules: ignore unknown `type`; if `edition` mismatches the session, open Home with an info toast; if the record was deleted (404), show "This item is no longer available" and stay on Home.

## Appendix E — Naming and versioning

- App name: **Rumu**; package `in.rumu.mobile` (confirm).
- Version: semver `MAJOR.MINOR.PATCH`; `versionCode` monotonically increasing.
- API compatibility: the app sends no version-negotiation header in v1; additive backend changes only. If a breaking change is ever needed, add a new `/api/v2` path rather than changing `/v1`.
- Feature flags: server-provided `features` (advanced reports, Meta integrations) are respected for menu visibility; do not hardcode plan capabilities beyond what `/enterprise/me` returns.
- Documentation: this file is the source of truth for scope; implementation decisions that change it must update this file and the app README.

---

*End of plan.*
