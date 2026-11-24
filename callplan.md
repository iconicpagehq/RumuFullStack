# Rumu Call Recording — Plan (Realtor + Enterprise, Android + Web)

> **SUPERSEDED — 1 November 2026.** This document proposed cloud telephony (Exotel bridge calls) and is kept
> for history only. The product decision changed to **app-only calling**: calls start from the Rumu Android
> app on the user's own SIM, the handset recording is uploaded to private storage, and the web dashboard has
> no call button. See [updateplan.md](file:///c:/xampp/htdocs/rumu-v2/updateplan.md) for the implemented
> architecture and [androidplan.md](file:///c:/xampp/htdocs/rumu-v2/androidplan.md) for the Android build.
> Statements below about provider-side recording, 503 fallback, and "start call from web" are obsolete.

| | |
|---|---|
| **Document** | `callplan.md` (superseded) |
| **Status** | Historical — replaced by app-only calling (`updateplan.md`) |
| **Version** | 1.1 |
| **Date** | 1 November 2026 |
| **Applies to** | Rumu V2 (`/api/v1`), Android app (`androidplan.md`), React web app |
| **Editions** | Realtor + Enterprise (`admin`, `manager`, `employee`) |
| **Goal** | Every lead call made from Rumu is recorded, stored securely, and visible with playback on both dashboards and the lead detail page |

---

## 0. TL;DR (Hinglish)

- **Standard Android par "har call automatic record" karna third-party app ke liye possible/reliable nahi hai.** Android 10 ke baad call-audio capture restrict hai, aur Play policy accessibility/microphone workarounds ko allow nahi karti. Isliye stealth recording ka idea drop karna hoga.
- **Recommended: "Rumu Calling" via cloud telephony** (Exotel India ke liye recommended; Twilio/Plivo alternative). App/web se Call dabao → provider pehle agent ke phone ko call karta hai → agent answer karta hai → lead se bridge hota hai → **recording server-side** hoti hai → webhook Laravel ko aata hai → private storage me copy → dashboard + lead detail me player.
- **Fallback: OEM recorder import.** Jin users ko SIM se call karna hai, woh phone ka built-in recorder on karein; app SAF folder picker se recordings import karke lead se match karega. Ye best-effort hai — device/OEM par depend karta hai, guaranteed nahi.
- Dono editions ke **dashboard cards**, **Calls page**, aur **lead detail "Calls" tab** me call history + recording playback milega. Scope server-side: realtor apni, employee apni, manager team/subtree, admin sab.
- Backend sirf **additive** changes: `call_logs`, `call_recording_access_logs`, `call_numbers`, `user_call_profiles`, `call_webhook_events`; existing tables/response shapes break nahi honge.
- Timeline: engineering ~5–6 weeks; provider KYC + legal review ke saath calendar **7–10 weeks**. Cost per-minute + virtual number + storage provider quote par depend karega.
- **Not in scope:** personal calls record karna, WhatsApp calls, stealth/silent recording, background microphone capture, accessibility-based recording, transcription/AI summary (v1.1+), inbound recording (v1.1+).

---

## Implementation status (1 Nov 2026)

Shipped in the web product:

- **Backend:** additive migration `2026_11_01_100000_create_call_tables.php`; `CallLog`, `CallNumber`,
  `UserCallProfile`, `CallWebhookEvent`, `CallRecordingAccessLog` models; `TelephonyProvider` /
  `TelephonyManager` / `ExotelTelephonyProvider` / `NullTelephonyProvider`; `CallService`,
  `CallMetricsService`, `CallAccessService`, `CallRecordingService`; `DownloadCallRecordingJob` on the
  `calls` queue; signed webhook `POST /webhooks/telephony/status` with HMAC-SHA256 and
  `(provider, event_id)` idempotency; 10 routes per edition under `/api/v1/{realtor,enterprise}/calls`.
- **Frontend:** Calls pages for both editions (filters, stats, pagination, deep link `?call={id}`),
  dashboard Calls cards, lead-detail **Calls** tab, call detail modal with authenticated playback,
  disposition + notes, recording import (50 MB, magic-byte checked), recording delete, and Call settings.
- **Tests:** `tests/Feature/Api/CallsApiTest.php` covers realtor import + stream scoping, webhook signature
  verification and idempotency, enterprise hierarchy visibility, and tenant-safe imports; full suite green.

Still open:

- **Android app** — see `androidplan.md`; nothing has been built.
- **Provider onboarding** — Exotel KYC/credentials and consent-copy review; until then
  `TELEPHONY_ENABLED=false` and the import fallback is the supported path.
- **Automatic retention purge** — `CALL_RECORDING_RETENTION_DAYS` is surfaced in the UI, but recordings are
  deleted only per call; no scheduled cleanup command exists yet.
- **Inbound calls, transcription, AI summaries, WhatsApp calls** — not implemented (as planned).

---

## 1. Android reality check (why the architecture is not "record on device")

"Jab bhi lead ko call jaye, recording ho jaye" — is sentence ko technically do alag problems me todna padta hai:

1. **SIM call ko third-party app record kare** — Android 10+ par restricted; OEM dialer hi reliable hai.
2. **Call ko Rumu ke through route kare** — cloud telephony provider record kar sakta hai, har device par same behaviour.

| Approach | Modern Android | Play Store | Reliability | Decision |
|---|---|---|---|---|
| Third-party app records `VOICE_CALL` / microphone | Mostly blocked since Android 10; OEM/version dependent | High rejection risk / policy violations | Low | **Do not build** |
| AccessibilityService auto-recording | Works on some devices only | Prohibited for remote call recording; security risk | Low + risky | **Do not build** |
| OEM dialer recorder + file import | Works where OEM provides recording | Yes, via SAF folder picker | Medium (device-dependent) | **Fallback** |
| Cloud telephony with server-side recording | Works on every Android/iOS/desktop | Yes | High | **Primary** |
| MDM / device-owner / system app recording | Only managed/custom devices | Not public Play distribution | High but heavy | **Enterprise-only future** |
| Call log metadata only (`CallLog.Calls`) | Yes, but needs restricted permission | Restricted; no audio | No audio | **Not enough** |

**Conclusion:** the product promise should be "calls made through Rumu are recorded and visible", not "your phone's every call is secretly recorded". This is also the safer privacy/compliance position.

> Android and Google Play policies change. Re-verify the current policy and Android API behaviour at build time; do not ship a workaround based on an old blog post.

---

## 2. Goals and scope

### 2.1 Goals

- Start a lead call from the Android app (and web) with one tap.
- Record the call on the server (both sides, dual-channel when the provider supports it).
- Store the recording privately with retention and access control.
- Show call history, duration, outcome, and recording playback:
  - Realtor dashboard
  - Enterprise dashboard (role-scoped)
  - Lead detail "Calls" tab in both editions
  - A dedicated Calls page in both web and Android
- Let the agent log a disposition after the call and optionally schedule the next follow-up.
- Keep the existing lead/task/visit flows working if the telephony provider is down: fall back to the phone dialer and optional recording import.

### 2.2 In scope (v1)

| Area | Realtor | Enterprise |
|---|---|---|
| Initiate recorded call | Own leads | Visible leads (role-scoped) |
| Recording | Server-side, dual-channel if available | Same |
| Dashboard | Today's calls, connected, recordings, avg duration, recent calls | Role-scoped call stats + team performance (admin/manager) + recent calls |
| Calls page | List, filters, playback, download | Same with team/member filters |
| Lead detail | Calls tab + "Call via Rumu" | Calls tab + "Call via Rumu" |
| Disposition | Outcome, notes, next follow-up | Same |
| Import OEM recordings | SAF folder import (fallback) | Same |
| Retention | Configurable, default 90 days | Plan/enterprise configurable |

### 2.3 Out of scope (v1)

- Recording personal/non-Rumu calls automatically.
- WhatsApp/Telegram/in-app VoIP calls.
- Inbound call recording (provider DID → lead matching) — schema keeps `direction`, implementation later.
- Transcription, sentiment, AI summaries, keyword search in audio.
- Live call monitoring/whisper/barge-in for managers.
- WebRTC in-app calling (phase 2 option).
- Super-admin call analytics console (later).
- Call recording on iOS (future; same backend contracts).

### 2.4 Success criteria

- ≥ 95% of connected Rumu calls have a playable recording within **5 minutes** of call end.
- Dashboard call counts match provider/webhook data (idempotent, no duplicates).
- Unauthorized users never receive a recording URL or bytes (403/404 tested).
- Recording playback starts in < 2 s on a normal connection (range streaming).
- Existing lead/task/visit flows work when telephony is disabled (fallback dialer).
- Zero public recording URLs; every playback/download is authorized and audited.

---

## 3. Recommended architecture

### 3.1 High-level flow

```
Android app / React web
        │  POST /calls/initiate {lead_id}
        ▼
Laravel API ── validates lead scope, rate limit, agent number, consent settings
        │  create call_logs row (status=initiated, uuid)
        │  provider adapter: initiate outbound call
        ▼
Cloud telephony provider (Exotel / Twilio / Plivo)
        │  1. calls the agent's verified phone number
        │  2. agent answers
        │  3. provider bridges to the lead's phone
        │  4. records the bridged call + plays consent notice
        ▼
Status / recording webhooks  ──►  Laravel webhook controller (signature verified)
        │  update call_logs (status, timestamps, duration, provider_call_id)
        │  queue job: download recording from provider
        ▼
Private storage (local/S3, no public URL) + call_logs.recording_* metadata
        ▼
Dashboards / Calls page / lead detail / Android player
        │  authenticated streaming endpoint (Range support)
        ▼
Audio playback (web player, Android Media3)
```

### 3.2 Why the agent's phone rings first

- v1 does **not** need microphone, `CALL_PHONE`, or a custom dialer.
- The agent can use their normal phone/SIM and the provider records the bridged leg.
- Works on any Android version and on iOS/web-originated calls later.
- The lead sees a business/virtual number, not the employee's personal number.

Trade-off: the agent answers a provider callback before the lead is dialled. This is familiar in CRM/cloud-calling products and is easy to explain in the UI ("Calling your phone first…"). WebRTC in-app calling can remove this step later.

### 3.3 Sequence (happy path)

```
Agent taps Call
  → app shows consent/recording notice + confirms number
  → POST /calls/initiate
  → call_logs: initiated
  → provider: outbound call to agent
  → webhook: ringing / answered by agent
  → provider: bridge to lead (consent announcement)
  → webhook: in_progress
  → call ends
  → webhook: completed + duration + recording URL
  → job downloads recording → recording_status=available
  → app polling (or push) shows completed → disposition sheet
  → lead last_contacted_at updated + activity/note
  → dashboards refresh
```

### 3.4 Fallback path (provider off / not enabled)

- Call tile shows two options: "Call via Rumu (recorded)" and "Call with phone".
- "Call with phone" uses `ACTION_DIAL` (no permission, no recording).
- After the SIM call, the app offers "Import recording" (SAF folder scan) or "Log call manually".
- If the provider is down, the API returns a clear `503`/`502`; app immediately offers the dialer; lead work is never blocked.

---

## 4. Telephony provider choice

Use an adapter; do not hardwire one provider into controllers.

| Provider | Strength | Watch-outs |
|---|---|---|
| **Exotel** (recommended for India) | Indian numbers/ExoPhone, call masking, recording, webhooks, KYC-friendly | Confirm current API/webhook version, recording retention, signature scheme |
| **Plivo** | India + global, straightforward call API, recording webhooks | Number availability/pricing varies; KYC |
| **Twilio** | Strong docs/SDKs, global | India number/regulatory friction, higher cost, data residency outside India possible |

**Recommendation:** start with Exotel for India-first customers, behind a `TelephonyProvider` interface, so switching to Plivo/Twilio is configuration + one adapter, not a rewrite.

### 4.1 Provider adapter interface

```php
interface TelephonyProvider
{
    public function name(): string;

    /** Start an outbound call: agent first, then bridge to the lead. */
    public function initiate(CallLog $call): ProviderCallResult;

    /** Verify the webhook signature/authenticity for this request. */
    public function verifyWebhook(Request $request): bool;

    /** Normalize the provider payload into our event DTO. */
    public function parseWebhook(Request $request): ProviderCallEvent;

    /** Download the recording; stream to disk without loading it all in memory. */
    public function downloadRecording(CallLog $call): DownloadedRecording;

    /** Optional: poll status for reconciliation when webhooks are delayed. */
    public function fetchStatus(CallLog $call): ?ProviderCallEvent;
}
```

`ProviderCallResult` contains provider call id, normalized status, raw payload. `ProviderCallEvent` contains event id, status, timestamps, duration, recording URL/metadata, consent flag. All provider-specific field names stay inside the adapter.

### 4.2 Configuration (server-side only)

```
TELEPHONY_ENABLED=true
TELEPHONY_PROVIDER=exotel
TELEPHONY_WEBHOOK_SECRET=...
EXOTEL_SID=...
EXOTEL_API_KEY=...
EXOTEL_API_TOKEN=...
EXOTEL_SUBDOMAIN=...
EXOTEL_CALLER_ID=...
CALL_RECORDING_DISK=local            # local | s3
CALL_RECORDING_RETENTION_DAYS=90
CALL_RECORDING_MAX_BYTES=52428800    # 50 MB
CALL_DAILY_LIMIT_PER_USER=50
```

- Never put these in the Android APK or React bundle.
- `.env.example` gets placeholders only (the repo previously had real secrets in `.env.example`; rotate and keep secrets out of VCS).
- Add an admin kill switch (`telephony.enabled`) so calls can fall back to the dialer without a deploy.

---

## 5. Data model (additive migrations only)

All new tables; **no column drops/renames, no `migrate:fresh`**. Existing `leads`, `users`, `enterprises` tables are not altered. Snapshot fields keep call history readable even if a lead/user is deleted.

### 5.1 `call_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `uuid` | uuid unique | Public/opaque call reference; pass to provider if supported |
| `edition` | string(16) index | `realtor` / `enterprise` |
| `realtor_user_id` | FK users nullable | Set for Realtor calls |
| `enterprise_id` | FK enterprises nullable | Set for Enterprise calls |
| `enterprise_membership_id` | FK enterprise_memberships nullable | Agent membership at call time |
| `initiated_by` | FK users nullable | Who tapped Call |
| `lead_id` | FK leads nullable | `nullOnDelete` (leads are soft-deleted today) |
| `lead_name` | string | Snapshot |
| `lead_phone` | string | Snapshot, E.164 |
| `agent_name` | string | Snapshot |
| `agent_number` | string | Verified agent number used |
| `provider` | string(32) | `exotel` / `twilio` / `plivo` |
| `provider_call_id` | string nullable unique | Provider's call SID/id |
| `direction` | string(16) default `outbound` | `inbound` reserved for later |
| `source` | string(16) default `cloud` | `cloud` / `imported` |
| `status` | string(20) index | `initiated`, `ringing`, `in_progress`, `completed`, `no_answer`, `busy`, `failed`, `canceled` |
| `disposition` | string(32) nullable | `connected`, `not_connected`, `wrong_number`, `callback`, `interested`, `not_interested`, `converted`, `lost` |
| `disposition_note` | text nullable | Agent note |
| `next_follow_up_at` | timestamp nullable | Optional |
| `started_at` | timestamp nullable | Provider call start |
| `agent_answered_at` | timestamp nullable | |
| `lead_answered_at` | timestamp nullable | Bridge established |
| `ended_at` | timestamp nullable | |
| `duration_seconds` | unsigned int default 0 | Talk duration, not ring time |
| `recording_status` | string(16) default `none` | `none`, `pending`, `available`, `failed`, `expired` |
| `recording_disk` | string(32) nullable | `local` / `s3` |
| `recording_path` | string nullable | UUID-based private path |
| `recording_mime` | string(64) nullable | `audio/mpeg`, `audio/wav` |
| `recording_size_bytes` | unsigned bigint nullable | |
| `recording_duration_seconds` | unsigned int nullable | |
| `recording_sha256` | char(64) nullable index | Dedupe/import integrity |
| `recording_deleted_at` | timestamp nullable | Retention deletion |
| `retention_hold` | boolean default false | Legal hold |
| `consent_announced` | boolean default false | Provider announcement played |
| `consent_text_version` | string(32) nullable | Which notice version |
| `cost_amount` | decimal(10,4) nullable | Provider cost, if returned |
| `cost_currency` | char(3) default `INR` | |
| `provider_payload` | json nullable | Sanitized raw payload (no secrets) |
| `created_at`, `updated_at`, `deleted_at` | timestamps | Soft delete |

Indexes: `(edition, created_at)`, `(enterprise_id, created_at)`, `(realtor_user_id, created_at)`, `(lead_id, created_at)`, `(status)`, `(recording_status)`, `(disposition)`, unique `provider_call_id`, unique `uuid`.

### 5.2 `call_numbers`

Virtual/caller-ID numbers and agent numbers.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `edition` | string(16) | |
| `enterprise_id` | FK nullable | Dedicated number for an enterprise |
| `assigned_user_id` | FK users nullable | Dedicated agent number, if any |
| `provider` | string(32) | |
| `phone_number` | string unique | E.164 |
| `label` | string(100) nullable | "Sales line", "Mumbai" |
| `purpose` | string(16) | `caller_id` / `agent` |
| `status` | string(16) default `active` | `active`, `inactive` |
| `recording_enabled` | boolean default true | |
| `metadata` | json nullable | |
| timestamps | | |

### 5.3 `user_call_profiles`

One row per user who can make cloud calls.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK unique | |
| `provider` | string(32) nullable | |
| `agent_number` | string nullable | Verified number |
| `agent_number_verified_at` | timestamp nullable | OTP/provider verified |
| `recording_enabled` | boolean default true | User preference within org policy |
| `preferences` | json nullable | Language, reminder, etc. |
| timestamps | | |

### 5.4 `call_webhook_events`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `provider` | string(32) | |
| `event_id` | string unique per provider | Idempotency |
| `event_type` | string(40) | status/recording |
| `call_uuid` | uuid nullable index | Our reference |
| `payload` | json | Sanitized |
| `processed_at` | timestamp nullable | |
| `error` | text nullable | |
| `timestamps` | | |

### 5.5 `call_recording_access_logs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `call_log_id` | FK cascade | |
| `user_id` | FK nullable | |
| `action` | string(16) | `played`, `downloaded`, `deleted` |
| `ip_address` | string(45) nullable | |
| `user_agent` | string(255) nullable | |
| `created_at` | timestamp | No updates |

Playback logs can be deduped per user/call/hour to avoid noise; downloads/deletions always logged.

### 5.6 Model relationships

- `Lead::calls()` hasMany `CallLog`
- `User::callsInitiated()` hasMany `CallLog` (`initiated_by`)
- `Enterprise::calls()` hasMany
- `CallLog::lead()`, `agent()`, `enterprise()`, `recordingAccessLogs()`
- Add `calls_count` / `last_call_at` only via `withCount`/subquery in resources; no new columns on `leads`.

---

## 6. API design

Follow the existing envelope: `{success, data, message, errors?, meta?}`. All endpoints are under `/api/v1`.

### 6.1 Route groups

```
# Realtor (inside auth:sanctum + realtor.api + subscription.active.api)
GET    /realtor/calls/summary
GET    /realtor/calls
POST   /realtor/calls/initiate
POST   /realtor/calls/import
GET    /realtor/calls/{call}
PATCH  /realtor/calls/{call}                 # disposition
GET    /realtor/calls/{call}/recording       # stream (Range)
DELETE /realtor/calls/{call}/recording       # owner only, audit

# Enterprise (inside auth:sanctum + enterprise.api)
GET    /enterprise/calls/summary
GET    /enterprise/calls
POST   /enterprise/calls/initiate
POST   /enterprise/calls/import
GET    /enterprise/calls/{call}
PATCH  /enterprise/calls/{call}
GET    /enterprise/calls/{call}/recording
DELETE /enterprise/calls/{call}/recording    # admin only, audit

# Provider webhook (NO auth middleware; signature required)
POST   /webhooks/telephony/{provider}
```

Route ordering: declare `summary`, `initiate`, `import` **before** `{call}` so `"summary"` is not captured as a call ID. Add both route groups to `routes/api.php`; do not change existing route names.

### 6.2 Initiate call

`POST /api/v1/{realtor|enterprise}/calls/initiate`

```json
{ "lead_id": 482 }
```

Rules:

- Lead must be visible to the caller (realtor ownership / enterprise lead scope). Use the existing lead policy/service; do not create a parallel authorization path.
- Lead must have a valid phone (normalize to E.164; 10-digit Indian numbers get `+91`).
- Caller must have a verified `agent_number`; otherwise `422` with `{ "code": "agent_number_required" }` → app opens setup.
- Provider/recording disabled → `503` with `{ "code": "telephony_unavailable", "fallback": "dialer" }`.
- Rate limit: per-user calls/hour + daily cap; block arbitrary/premium/international numbers (only the lead's number).
- Consent setting must be enabled; if the org disables recording, the API refuses cloud recording (or allows a non-recorded cloud call only if product wants it — default: no).

Response `201`:

```json
{
  "success": true,
  "data": {
    "id": 91,
    "uuid": "0b6b...",
    "status": "initiated",
    "provider": "exotel",
    "lead_id": 482,
    "lead_name": "Priya Sharma",
    "agent_number": "+9198...",
    "recording_expected": true,
    "consent_notice": "This call will be recorded for quality and training."
  },
  "message": "Calling your phone first. Answer to connect the lead."
}
```

Never return provider credentials, raw provider URLs, or another tenant's numbers.

### 6.3 Call list

`GET /api/v1/{edition}/calls?page=1&per_page=20&lead_id=&status=&disposition=&has_recording=1&date_from=&date_to=&user_id=&team_id=&search=&sort=latest`

- Realtor scope: `realtor_user_id = auth.id`.
- Enterprise scope: calls whose `lead_id` is in the caller's visible lead scope; employees additionally see their own calls. Managers can filter `team_id`/`user_id` within scope; admins all.
- `has_recording=1` → `recording_status=available`.
- Search matches lead name/phone snapshot.
- Meta: standard pagination.

### 6.4 Call detail

`GET /calls/{call}` → call resource with:

- status, direction, timestamps, duration, disposition, notes, next follow-up
- lead snapshot + live lead link (if still accessible)
- agent name/number (masked to non-admin? keep internal)
- recording: `{status, duration_seconds, mime, size_bytes, stream_url}` where `stream_url` is our authenticated API path, never the provider URL
- access flags: `can_play`, `can_download`, `can_delete`

### 6.5 Disposition

`PATCH /calls/{call}`

```json
{
  "disposition": "interested",
  "disposition_note": "Wants a 3BHK, follow up Friday",
  "next_follow_up_at": "2026-11-06T10:30:00Z"
}
```

- Only after the call is terminal (`completed`, `no_answer`, `busy`, `failed`, `canceled`).
- Updates `leads.last_contacted_at` when the call connected.
- Creates a lead activity for Enterprise (`call_completed` with duration/disposition) and an optional lead note for Realtor (configurable; avoid auto-spam).
- Does **not** auto-change pipeline stage; the agent chooses that separately.
- Idempotent on repeated submits (same disposition + note does not duplicate activities).

### 6.6 Recording streaming

`GET /calls/{call}/recording` (Authorization required)

- Authorize with the same scope as call detail.
- `Content-Type` from stored MIME; `Accept-Ranges: bytes`; support `206 Partial Content` for seeking.
- `Cache-Control: private, no-store`; no public URL, no CDN public bucket.
- `?download=1` → `Content-Disposition: attachment`, only if `can_download`; logs a `downloaded` access event.
- Missing/expired/failed recording → `404` with a clear message; do not stream a provider URL.
- Web player: fetch with the bearer token → `Blob` → `URL.createObjectURL`; revoke on unmount. (An `<audio src>` cannot send the bearer header.)
- Android: Media3 ExoPlayer with an OkHttp data source that injects the bearer token; stream with Range.
- Optional fallback: a 2-minute signed URL only if a native player cannot send headers. Prefer bearer streaming.

### 6.7 Dashboard extensions (additive)

Add a `calls` block to existing responses; do not rename/remove existing keys.

`GET /realtor/dashboard` and `GET /enterprise/dashboard`:

```json
"calls": {
  "today_total": 12,
  "today_connected": 8,
  "today_recordings": 7,
  "avg_duration_seconds": 142,
  "pending_dispositions": 2,
  "recent": [ { "id": 91, "lead_name": "Priya", "status": "completed", "duration_seconds": 134, "has_recording": true, "created_at": "..." } ],
  "trend": [ { "date": "2026-10-26", "total": 9, "connected": 6 } ]
}
```

- Enterprise also gets `by_user` / `by_team` for admin/manager, scoped by hierarchy.
- Employee sees only their own calls; labels come from the server.
- Web and Android render the same block; no client-side aggregation of another scope.

`GET /calls/summary?period=today|7d|30d` for the Calls page header (optional if dashboard block is reused).

### 6.8 Import endpoint (fallback path)

`POST /api/v1/{edition}/calls/import` (multipart)

Fields: `lead_id`, `recorded_at`, `duration_seconds?`, `file` (mp3/m4a/wav/aac/amr/ogg, max 50 MB).

- Validate MIME + extension + magic bytes; reject video/executables.
- Store private; compute SHA-256 for dedupe.
- Create a `call_logs` row with `source=imported`, `status=completed`, `recording_status=available`, `recording_*` metadata.
- Optional server-side duration probe (`ffprobe`) if available; otherwise trust client metadata with bounds.
- Same RBAC/lead scope as initiate.
- If a cloud call already exists for the same lead and time window, warn/merge instead of duplicating (user confirms).

### 6.9 Webhook contract

`POST /api/v1/webhooks/telephony/{provider}`

- No Sanctum auth. Verify provider signature/HMAC/shared secret and IP allowlist where supported.
- Reject stale timestamps and replays; store `event_id` uniquely.
- Normalize adapter output; update `call_logs` using a forward-only state machine (never move backwards).
- On `completed`/recording-ready: queue `DownloadCallRecording` job; return `200` quickly (no heavy work in the request).
- If our call row is missing, store the event for manual reconciliation; do not create orphan calls with no owner.
- Sanitize and store provider payload (no tokens). Log errors with call UUID, not PII.

Generic normalized event:

```json
{
  "event_id": "evt_123",
  "call_uuid": "0b6b...",
  "provider_call_id": "provider_abc",
  "status": "completed",
  "started_at": "2026-11-01T09:20:00Z",
  "agent_answered_at": "2026-11-01T09:20:08Z",
  "lead_answered_at": "2026-11-01T09:20:20Z",
  "ended_at": "2026-11-01T09:22:34Z",
  "duration_seconds": 134,
  "consent_announced": true,
  "recording": {
    "url": "https://provider.example/rec.mp3",
    "duration_seconds": 134,
    "channels": 2,
    "format": "mp3"
  }
}
```

The raw provider payload shape will differ; the adapter maps it. Do not expose `recording.url` to clients.

### 6.10 Error codes (app/web mapping)

| HTTP | Code | Client behavior |
|---|---|---|
| 422 | `agent_number_required` | Open "Add calling number" |
| 422 | `invalid_lead_phone` | Show edit-lead action |
| 403 | `lead_forbidden` | Hide/disable Call |
| 409 | `call_already_active` | Show existing active call |
| 429 | `call_limit_reached` | Explain limit, allow dialer fallback |
| 503 | `telephony_unavailable` | Offer "Call with phone" + import |
| 404 | `recording_not_available` | Show "No recording" state |
| 410 | `recording_expired` | Show retention message |

---

## 7. Provider integration details

### 7.1 Call initiation

1. Create the `call_logs` row first (status `initiated`, uuid) inside a DB transaction.
2. Call the provider with agent number as the first leg and lead number as the bridge target, `record=true`, consent announcement/recording settings, status/recording callback URLs.
3. Persist `provider_call_id` and raw response; status stays `initiated` until the first webhook.
4. If provider API fails: mark `failed` with a safe error code, do **not** expose the raw provider message; app offers fallback.
5. If provider accepts but no webhook arrives within 60 s, a reconciliation job polls status once per minute for 15 minutes.

If the provider supports passing custom metadata, pass our `uuid` so webhooks match even before `provider_call_id` is stored. Otherwise match by provider call id + normalized numbers + time window.

### 7.2 Consent announcement

- Configure the provider to play an announcement before bridging the lead (e.g., "This call is being recorded for quality and training purposes").
- Store `consent_announced` from the webhook and `consent_text_version` from config.
- If the provider cannot announce per call, the agent must read the notice (UI shows a required confirmation before dialling) and we record that the agent confirmed; prefer the provider announcement.
- Recording is never silent/stealth. If consent cannot be obtained, do not record.

### 7.3 Recording download

- Queue job with 3 retries, exponential backoff (1, 5, 15 min).
- Stream provider → disk (`Storage::disk(config('calls.recording_disk'))->writeStream(...)`), never `file_get_contents` into memory.
- Verify size limit and content type; compute SHA-256; store metadata.
- Path: `call-recordings/{edition}/{yyyy}/{mm}/{call_uuid}.mp3` (no lead name/phone in the path).
- On success: `recording_status=available`, `pending` cleared; dispatch a dashboard/notification event.
- On failure: `recording_status=failed`, keep provider metadata, alert ops; a retry command can re-download.
- Provider recording URLs may expire; download promptly. After success, the provider copy is not needed; optionally delete it if the provider supports deletion.

### 7.4 State machine

```
initiated ──► ringing ──► in_progress ──► completed
    │            │             │
    ├──► no_answer ────────────┤
    ├──► busy ─────────────────┤
    ├──► failed ───────────────┤
    └──► canceled ─────────────┘
```

- Terminal states never move except `failed → completed` if a late corrected webhook arrives (with audit).
- `duration_seconds` is talk time; ring time is computed from timestamps if needed.
- Active-call guard: one active cloud call per agent at a time (`409` otherwise).

### 7.5 Idempotency and reconciliation

- Unique `(provider, event_id)` in `call_webhook_events`; duplicate webhook → 200, no side effects.
- Unique `provider_call_id`; duplicate initiate retries must not create two calls.
- Reconciliation command: find calls `initiated/ringing/in_progress` older than 2 minutes, poll provider, reconcile.
- Scheduler runs every minute; add to the deployment cron (`schedule:run` already required for Laravel).

---

## 8. Product and UI changes

### 8.1 Visibility matrix

| Viewer | Dashboard calls | Calls page | Playback | Download | Delete |
|---|---|---|---|---|---|
| Realtor | Own | Own | Own | Own (configurable) | Own (soft delete) |
| Enterprise employee | Own | Own | Own | Own if policy allows | No |
| Enterprise manager | Team/subtree | Team/subtree | Team/subtree | Configurable | No |
| Enterprise admin | All | All | All | Yes | Yes (audit) |

All enforced server-side. UI hides controls outside the scope; never rely on hiding.

### 8.2 Realtor web dashboard

Add below the existing stat cards (after "Needs attention" or before recent campaigns):

- Row of 4 compact call stats: Calls today · Connected · Recordings · Avg duration.
- "Recent calls" card: last 5 calls with lead name, time, duration, status pill, play button.
- "Recordings available" chip; click → Calls page filtered `has_recording=1`.
- Empty state: "No calls yet — call a lead from its page."
- Data from `dashboard.calls`; refresh with TanStack Query; invalidate after disposition.

### 8.3 Enterprise web dashboard

- Role-scoped call stats from `dashboard.calls`.
- Recent calls card (same component, scoped).
- Admin/manager: "Call performance" compact table (member, calls, connected %, avg duration, recordings) with top 5 and "View all"; employee: own summary only.
- Pending dispositions banner if `pending_dispositions > 0` → filtered Calls page.
- Respect existing role/hierarchy flags; no new colors — use existing tokens and `StatsCard`.

### 8.4 Calls page (both editions, web + Android)

- Header: title, period filter (Today/7d/30d), export (CSV) for admin/manager (web v1; Android share later).
- Filters: search (lead name/phone), status, disposition, has recording, date range, member/team (enterprise).
- Table/list rows: lead, agent, direction icon, status pill, disposition, duration, started time, recording chip/play.
- Click row → call detail drawer/page: metadata, lead link, recording player, disposition form, timeline.
- Empty/loading/error/offline states; skeleton rows; pagination/infinite scroll.
- Visual language: existing cards, badges, tabs, Inter, tokens; no new palette.

### 8.5 Lead detail

- New tab **Calls** after Follow Ups (before Site Visits in enterprise):
  - "Call via Rumu" primary button; "Call with phone" secondary if enabled.
  - Call history list with status, duration, outcome, play; "Log call" for manual entries.
  - Recording player inline (one at a time); mini-player persists while scrolling within the page.
- Lead header phone quick action: if cloud calling enabled, opens the call sheet; otherwise `tel:`/dialer.
- Count badge on the Calls tab.
- Lead "About" can show `last_call_at` (if we expose it additively).
- After a completed call, show a non-blocking disposition prompt; do not auto-change stage.

### 8.6 Call sheet (Android, primary)

```
┌──────────────────────────────┐
│ Call Priya Sharma            │
│ +91 98765 43210              │
│                              │
│ We will call your phone      │
│ (+91 98...) first, then      │
│ connect the lead.            │
│                              │
│ ⓘ This call is recorded.     │
│   [Consent notice]           │
│                              │
│ [ Call via Rumu ]            │
│ [ Call with phone ]          │
│ [ Cancel ]                   │
└──────────────────────────────┘
```

States: preparing → calling your phone → on call (timer) → completed → disposition. If provider fails, switch to fallback options without losing the lead context.

### 8.7 Recording player

- Web: custom minimal player matching the theme (play/pause, seek, time, speed 1×/1.5×, download if allowed), not the default browser control (optional but preferred).
- Android: Media3 with a compact player row; playback continues while the sheet is open; no background service in v1.
- Show "Processing recording…" until available; auto-refresh via polling or push; show "Recording unavailable" if failed.
- Never autoplay with sound.

### 8.8 Wireframes

**Dashboard card (web/Android)**

```
┌ Calls today ─────────────────────┐
│ 12 total · 8 connected · 7 rec.  │
│ Avg 2m 22s                       │
│ Recent: Priya · 2:14  [▶]        │
│         Amit  · 0:48  [▶]        │
│ View all calls →                 │
└──────────────────────────────────┘
```

**Calls page row**

```
┌──────────────────────────────────┐
│ ☎ Priya Sharma        [Completed]│
│   Rohan · Today 10:30 · 2m 14s   │
│   Interested · Recording [▶]     │
└──────────────────────────────────┘
```

**Lead detail Calls tab**

```
┌ Calls ───────────────────────────┐
│ [ Call via Rumu ]  [ Phone ]     │
│ ● Today 10:30 · 2m 14s · Interested
│   [▶ 01:12 / 02:14]              │
│ ● 28 Oct · No answer · 0s        │
│ ● 26 Oct · 3m 02s · Callback     │
└──────────────────────────────────┘
```

---

## 9. Android app changes (details)

This section extends `androidplan.md`; the Call tile behaviour changes from `ACTION_DIAL` to the cloud call flow when enabled.

### 9.1 Permissions

| Permission | Needed? | Why |
|---|---|---|
| `INTERNET` | Yes | API + recording stream |
| `POST_NOTIFICATIONS` | Yes (13+) | Call status/recording ready push |
| `CALL_PHONE` | **No** | Provider calls the agent; we never place a SIM call directly |
| `RECORD_AUDIO` | **No** | Server-side recording; no microphone capture |
| `READ_CALL_LOG` | **No (v1)** | Not needed for cloud calls; Play-restricted |
| `READ_MEDIA_AUDIO` | **No** | SAF folder picker instead |
| Storage permissions | **No** | SAF persistable URI for import |

### 9.2 Call flow

1. Tap Call → check `user.call_profile.agent_verified`; if not, open "Add calling number" screen.
2. Show consent sheet; primary "Call via Rumu", secondary "Call with phone", cancel.
3. `POST /calls/initiate`; show progress; handle 422/503 with clear actions.
4. Poll `GET /calls/{id}` every 3 s while the sheet is open (stop after terminal or 3 min). Optionally update via FCM.
5. Agent answers the incoming provider call on their phone; app shows "On call" and a timer from `lead_answered_at`/`started_at`.
6. On completion, fetch detail; show disposition sheet (outcome, note, next follow-up) → `PATCH /calls/{id}`.
7. Refresh lead detail, calls list, and dashboard query.

If the app is killed, the call still happens; on next open, the Calls page shows the completed call and pending disposition.

### 9.3 Calls screen

- More hub → Calls (and lead detail Calls tab).
- List with filters; cached first page (Room); pull-to-refresh; pagination.
- Tap → call detail bottom sheet: status, timestamps, duration, recording player, disposition, "Open lead".
- Pending disposition badge; filter `pending=1` optional (derived from completed + no disposition).

### 9.4 Agent number setup

- Screen "Calling number": enter number, request OTP/provider verification, show verified state.
- Store via `PUT /calls/profile` (new endpoint) or `PUT /profile` additive field; backend stores in `user_call_profiles`.
- Show policy text: calls are recorded; use company number; no personal recording.
- If verification unavailable, admin can verify in web (later) or disable cloud calling for that user (dialer fallback).

### 9.5 Recording import (fallback)

Flow:

1. User taps "Import recording" on a lead or Calls page.
2. `ACTION_OPEN_DOCUMENT_TREE` → user selects the OEM recorder folder (e.g., `Recordings/Call`); persist read permission.
3. App scans audio files (`mp3`, `m4a`, `wav`, `aac`, `amr`, `ogg`) via `DocumentFile`; reads name/modified time/size.
4. Matching:
   - Parse a 10-digit Indian number from the filename; match the lead phone.
   - Else match recent Rumu call logs for the same lead within ±15 minutes.
   - Else show a picker: "Which lead is this call for?"
5. Show a preview with duration and matched lead; user confirms.
6. Upload multipart to `POST /calls/import`; show progress; dedupe by SHA-256.
7. Backend creates the call log/recording; app refreshes the lead Calls tab.

Limits/behaviour:

- Auto-scan on app resume and via a daily WorkManager job (best-effort); never upload without user confirmation unless the user enables "Auto-import matched recordings".
- No background microphone recording; no AccessibilityService; no reading other apps' private storage.
- Some OEMs save recordings in app-private storage or block access → show a device-specific help note; this is a known limitation.
- File size cap 50 MB; skip corrupted/zero-length files; report a summary ("3 imported, 1 skipped").
- Imported recordings follow the same retention/RBAC rules.

### 9.6 UI states

- Telephony disabled: Call tile opens dialer; "Recording not available for phone calls" note.
- Agent number missing: setup CTA.
- Provider error: fallback sheet with dialer + import.
- Consent disabled by org: cloud call button disabled with explanation; dialer allowed.
- Recording processing: "Recording will appear in a few minutes."
- No recording: "No recording for this call" (no broken player).
- Offline: cached call history read-only; initiate disabled.

### 9.7 Deep links

- `rumu://call/{edition}/{id}` → call detail
- `rumu://calls` → Calls page
- Push payload: `{type:"call_completed", edition, call_id, lead_id, deep_link}`.

---

## 10. Consent, privacy, and retention (must be decided with counsel)

> This section is product/compliance guidance, not legal advice. Get Indian counsel to review before launch.

- **Notice:** Play a clear recording announcement before the lead is bridged; show the same notice to the agent in the app. Record `consent_announced` and the notice version.
- **Legal basis:** India's DPDP Act 2023 and IT Act apply to personal data; employee monitoring and customer recording need notice/policy. Some jurisdictions require all-party consent; if Rumu serves them, gate recording by region or add explicit consent.
- **Purpose limitation:** recordings used for quality, training, dispute resolution, compliance; not marketing or unrelated profiling.
- **Access:** least privilege; managers hear team calls only if the org explicitly enables it. Every download/delete is audited.
- **Retention:** default 90 days for recordings (configurable per plan/enterprise: 30/90/180); metadata can be retained longer for business records. Legal hold flag prevents deletion.
- **Deletion:** lead/account deletion request → delete recordings unless legal hold; nightly retention job; report deletion results.
- **Data residency:** prefer an India region for provider numbers/recordings where the provider supports it; document any cross-border transfer.
- **Employee notice:** update the employment/acceptable-use policy; agents must not use personal numbers for recorded business calls unless approved.
- **DND/outbound rules:** call only leads with a lawful basis/consent; honor Do Not Disturb preferences; do not use recording for unsolicited telemarketing.
- **Privacy policy / Play data safety:** disclose call recording, retention, access, and deletion; declare audio collection accurately in the Play Console data safety form.
- **Recording announcement language:** English + Hindi option later; default English for v1.
- **No stealth recording.** If consent is not obtained or recording is disabled, the call is not recorded (or cloud calling is disabled).

---

## 11. Security and abuse prevention

| Area | Requirement |
|---|---|
| Provider secrets | Server `.env`/secret manager only; never in app/web bundles; rotate after any leak. |
| Webhook auth | Signature/HMAC/shared secret + IP allowlist where available; reject stale timestamps/replays; unique event IDs. |
| Tenant isolation | Every call query filtered by realtor user or enterprise visible-lead scope; tests for cross-tenant IDs. |
| Recording storage | Private disk/bucket; no public URLs; encryption at rest (S3 SSE or encrypted volume); random UUID paths. |
| Streaming | Auth + scope check; Range support; `Cache-Control: private, no-store`; no provider URL exposure. |
| Downloads | Role-gated; `Content-Disposition: attachment`; access log. |
| Audit | Play/download/delete logged; admin delete logged in enterprise audit. |
| Rate limits | Initiate per user/hour + daily cap; one active call per agent; block non-lead/premium/international destinations. |
| Toll fraud | Server picks the destination from the lead; never accept an arbitrary phone from the client; admin kill switch. |
| PII | No phone/email in logs or filenames; redact provider payloads; sanitize error messages. |
| File upload | MIME/magic-byte validation, 50 MB cap, audio only, SHA-256 dedupe, optional antivirus scan. |
| Mobile | Bearer token encrypted; Media3 uses the auth header; no `RECORD_AUDIO`; no Accessibility abuse; R8. |
| Web | Player uses authenticated blob fetch; revoke object URLs; no recording token in localStorage. |
| Play policy | No call-log/recording permissions in v1; no accessibility recording; accurate data safety disclosure. |
| Retention | Nightly deletion job; legal hold; verify storage actually deletes objects. |

---

## 12. Testing and verification

### 12.1 Backend tests (PHPUnit, additive)

- Initiate: realtor own lead succeeds; other realtor's lead 403; enterprise employee scoped lead succeeds; out-of-scope 403; invalid phone 422; missing agent number 422; telephony disabled 503; rate limit 429.
- Webhook: valid signature accepted; invalid rejected 401/403; duplicate event no-op; out-of-order status does not move backwards; missing call stored for reconciliation; recording job queued once.
- Recording download: streams to private disk; size/type validation; retry on failure; metadata saved; no public URL.
- Streaming: authorized 200/206; unauthorized 403/404; expired/deleted 410/404; Range headers correct; download audit row.
- Disposition: updates lead `last_contacted_at` once; creates one activity/note; invalid disposition 422; cannot before terminal state.
- Scoping: employee sees own; manager team/subtree; admin all; realtor own; cross-tenant IDs return 404.
- Dashboard: calls block counts match seeded data; role labels; no keys removed from existing dashboard responses.
- Import: valid audio accepted; oversized/executable rejected; dedupe by hash; wrong lead scope 403.
- Retention: job deletes file and marks expired; legal hold skipped.
- Existing 135 tests / 912 assertions remain green.

### 12.2 Web/browser verification

- Realtor and enterprise admin/manager/employee dashboards show expected scoped call stats.
- Calls page filters, pagination, playback, download permissions, empty states.
- Lead detail Calls tab + call sheet; fallback dialer when telephony disabled.
- Recording stream seeks correctly; no console errors; dark mode tokens intact.
- Cross-tenant playback attempt blocked.
- Existing lead/task/visit/report flows unchanged.

### 12.3 Android verification

- Call flow on API 26, 30, 33, 35/36 devices; provider callback answered; app state updates.
- Recording playback with Range; background/foreground transitions; app killed mid-call.
- Permissions: no CALL_PHONE/RECORD_AUDIO prompts; notification permission flow.
- SAF import from Samsung/Xiaomi/stock recorder folders; unmatched flow; dedupe.
- Offline: history cached, initiate disabled, clear message.
- Deep links and push to call detail.
- TalkBack/font scale/dark mode.

### 12.4 Acceptance scenarios

1. Realtor calls a lead from Android → agent phone rings → lead connects → recording available within 5 min → dashboard + lead Calls tab play it.
2. Enterprise employee calls a scoped lead → recorded; manager sees it in team scope; admin sees all; another team cannot access it.
3. Provider disabled → Call button offers dialer; imported OEM recording attaches to the lead.
4. Call with no answer → call log shows no_answer, no broken player; disposition works.
5. Invalid/tampered webhook → rejected; no state change; no recording download.
6. Retention job removes an expired recording; playback shows "expired".
7. Cross-tenant call ID or recording URL → 403/404.
8. Existing task/visit/lead flows pass on both editions.

---

## 13. Delivery phases and estimates

Assumes 1 backend developer, 1 frontend developer, 1 Android developer (part-time where noted), QA support, and provider onboarding in parallel.

| Phase | Duration | Deliverables | Exit criteria |
|---|---|---|---|
| **0. Product + legal + provider** | 1–2 weeks (parallel) | Provider selected (Exotel recommended), KYC/account, numbers, consent text, retention policy, legal sign-off, DPIA/privacy updates | Provider sandbox call + recording webhook verified; consent/retention approved |
| **1. Schema + domain** | 4 days | Migrations, models, enums, policies, query scoping, factories, unit tests | Migrations run on staging; scope tests green; no existing table altered |
| **2. Telephony adapter** | 6 days | Exotel adapter, initiate, callbacks, signature verification, state machine, recording download job, reconciliation, admin kill switch | Sandbox: initiate → answer → record → download → DB `available`; idempotent webhooks |
| **3. APIs + dashboards** | 4 days | Realtor/enterprise call APIs, summary, dashboard `calls` blocks, lead resource counters, disposition, import | Contract tests; role-scoped data verified |
| **4. Web UI** | 6 days | Dashboard cards, Calls page, call detail drawer, player, lead Calls tab, call sheet, fallback states | Browser verified both editions/roles, light/dark |
| **5. Android** | 8 days | Call sheet/flow, Calls screen, playback (Media3), agent number setup, SAF import, deep links, caching | Device matrix verified; no restricted permissions |
| **6. Privacy/security hardening** | 4 days | Consent enforcement, retention job, access logs, rate limits, upload validation, docs | Security checklist; retention test; Play data safety updated |
| **7. Pilot + rollout** | 5 days | Pilot with 5–10 users, monitoring, cost check, fixes, docs | ≥95% recordings available; fallbacks work; sign-off |
| **Engineering total** | **~37 days (≈7.5 weeks)** | | |
| **Calendar** | **7–10 weeks** | Provider KYC/legal can dominate | |

Costs (request quotes; do not hardcode):

- Per-minute outbound + bridge + recording charges.
- Monthly virtual number/ExoPhone rental (shared pool vs dedicated).
- Storage/egress for recordings (dual-channel MP3 ≈ ~0.5–1 MB/min; 1,000 calls × 5 min/month ≈ 2.5–5 GB).
- Provider KYC/business verification.
- Optional transcription later (per-minute).

Budget guardrails: per-user daily call cap, plan-level minutes, alert at 80% of monthly allowance, admin kill switch.

---

## 14. Definition of done

- Call initiated from web and Android records on the provider and is downloadable to private storage.
- Dashboard cards, Calls page, and lead Calls tab show scoped history and playback for both editions and all roles.
- Every recording access is authorized and audited; no public URLs; cross-tenant tests pass.
- Consent notice is configured and stored; retention job tested; legal hold works.
- Fallback dialer + import works when telephony is unavailable.
- Existing PHPUnit suite, web build, and Android build/tests green.
- No destructive migrations; additive only; `.env.example` has placeholders only.
- Docs updated: this plan's status, `README.md`/`ENTERPRISE.md` endpoint counts, privacy policy, Play data safety, support runbook.
- Honest limitations documented (Android recording reality, OEM import variability, provider dependency, retention).

---

## 15. Risks and limitations

| Risk | Impact | Mitigation |
|---|---|---|
| Expectations of automatic SIM-call recording | Users think the app is broken | Clear copy: "Calls through Rumu are recorded"; offer dialer + import; explain device limits in help |
| Android 10+ recording restrictions | Cannot record third-party calls | Cloud telephony path; no stealth workarounds |
| Play policy rejection | Release blocked | No Accessibility/RECORD_AUDIO/READ_CALL_LOG; data safety disclosure |
| Provider outage | Calls cannot be placed | Dialer fallback; retry/queue; status page |
| Provider KYC delay | Launch slips | Start onboarding in Phase 0; keep adapter/provider swap ready |
| Webhook loss/out-of-order | Wrong call state | Idempotency, state machine, reconciliation polling |
| Recording download failure | Missing recordings | Retry/backoff, alert, manual re-download command, provider fallback |
| Cost overrun | Margin hit | Caps, plan limits, alerts, shared numbers |
| Number spam flags | Lower answer rate | Dedicated numbers, number rotation policy, outbound consent, monitor |
| Consent/legal non-compliance | Regulatory/litigation risk | Counsel review, announcement, policy, retention, access control |
| OEM import variability | Fallback unreliable | Device help, manual "Log call", no guarantee; test top OEMs |
| Store growth | Storage cost | Retention default 90 days, compressed MP3, lifecycle delete |
| Managers listening to employee calls | Privacy/employment concerns | Off-by-default org setting; policy notice; access audit |
| Lead deleted but recording remains | Privacy concern | Null lead, snapshot, retention/deletion policy, legal hold rules |
| Timezone/duration bugs | Wrong dashboard numbers | UTC storage, IST display, provider duration as source of truth |
| Transcript/AI scope creep | Delay | Explicitly v1.1+; schema ready without UI |

---

## 16. Open decisions (needed before Phase 1)

1. **Provider:** Exotel (recommended for India) vs Plivo/Twilio. Confirm KYC, pricing, recording retention, webhook signature.
2. **Calling UX:** provider callback (agent phone rings first) for v1, or invest in WebRTC in-app calling? (Recommend callback v1.)
3. **Numbers:** shared caller-ID pool vs dedicated number per realtor/enterprise; who pays.
4. **Who can listen:** should managers hear employee calls by default, or only if the enterprise enables it? (Recommend opt-in.)
5. **Download permission:** playback for all scoped viewers; downloads only admin/manager? Realtor own?
6. **Retention:** 30/90/180 days default; plan-based; legal hold policy.
7. **Consent text/language:** exact wording, English + Hindi, version storage.
8. **Fallback import:** ship in v1 or v1.1? (Recommend v1 because SIM calls will exist.)
9. **Plan gating:** which plans include recorded calling and how many minutes.
10. **Inbound recording:** v1.1 scope? (Recommend later, after outbound proves out.)
11. **Transcription/AI summary:** v1.1+; provider or self-hosted.
12. **Data residency:** India-only storage/number requirement.

---

## Appendix A — Endpoint map summary

| Purpose | Realtor | Enterprise |
|---|---|---|
| Initiate | `POST /realtor/calls/initiate` | `POST /enterprise/calls/initiate` |
| List | `GET /realtor/calls` | `GET /enterprise/calls` |
| Summary | `GET /realtor/calls/summary` | `GET /enterprise/calls/summary` |
| Detail | `GET /realtor/calls/{call}` | `GET /enterprise/calls/{call}` |
| Disposition | `PATCH /realtor/calls/{call}` | `PATCH /enterprise/calls/{call}` |
| Stream recording | `GET /realtor/calls/{call}/recording` | `GET /enterprise/calls/{call}/recording` |
| Download recording | `?download=1` | `?download=1` |
| Import OEM file | `POST /realtor/calls/import` | `POST /enterprise/calls/import` |
| Delete recording | `DELETE /realtor/calls/{call}/recording` | `DELETE /enterprise/calls/{call}/recording` (admin) |
| Webhook | `POST /webhooks/telephony/{provider}` (shared, no auth) | same |
| Dashboard block | `GET /realtor/dashboard` → `calls` | `GET /enterprise/dashboard` → `calls` |

## Appendix B — Call resource sample

```json
{
  "success": true,
  "data": {
    "id": 91,
    "uuid": "0b6b...",
    "direction": "outbound",
    "status": "completed",
    "disposition": "interested",
    "disposition_note": "Wants 3BHK, follow up Friday",
    "next_follow_up_at": "2026-11-06T10:30:00Z",
    "lead": { "id": 482, "name": "Priya Sharma", "phone": "+919876543210" },
    "agent": { "id": 12, "name": "Rohan Mehta" },
    "started_at": "2026-11-01T09:20:00Z",
    "ended_at": "2026-11-01T09:22:34Z",
    "duration_seconds": 134,
    "recording": {
      "status": "available",
      "duration_seconds": 132,
      "mime": "audio/mpeg",
      "size_bytes": 1843200,
      "stream_url": "/api/v1/realtor/calls/91/recording"
    },
    "permissions": { "can_play": true, "can_download": false, "can_delete": true }
  },
  "message": null
}
```

## Appendix C — Android permission and Play declarations

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<!-- No CALL_PHONE, RECORD_AUDIO, READ_CALL_LOG, READ_MEDIA_AUDIO, storage -->
```

Play Console:

- Data safety: collect phone/email (account/lead), audio recordings (call recordings), app activity; encrypt in transit; deletion available.
- No AccessibilityService use for call recording.
- No background microphone.
- Foreground service not required for cloud calling in v1.
- Declare the call-recording purpose clearly in the listing/help.

## Appendix D — Rollout checklist

- [ ] Provider account/KYC, numbers, consent announcement configured.
- [ ] `.env` production secrets set; `.env.example` placeholders only; secrets rotated if leaked.
- [ ] `php artisan migrate` (additive), scheduler running, queue worker for recording downloads and notifications.
- [ ] Storage disk private, backups encrypted, retention job scheduled.
- [ ] Web build deployed; dashboard/calls/lead detail verified in light/dark.
- [ ] Android build signed and distributed; call flow verified on real devices.
- [ ] Privacy policy, employment notice, DPDP/legal review completed.
- [ ] Play data safety updated; internal testing with 5–10 pilot users.
- [ ] Monitoring: call initiation failures, webhook lag, recording download failures, playback 4xx/5xx, cost per day.
- [ ] Kill switch and dialer fallback tested in production.
- [ ] Support runbook: missing recording, provider outage, disputed recording, deletion request.

---

*End of plan.*
