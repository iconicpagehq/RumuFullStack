# Rumu — App-Only Calling & Recording Upload Plan

| | |
|---|---|
| **Document** | `updateplan.md` |
| **Status** | Planning — replaces the cloud-telephony design in `callplan.md` |
| **Scope** | Web frontend, Laravel backend, Android app |
| **Decision** | No cloud calling. Calls are normal SIM calls started **only from the Android app**. The app uploads local recording files; web and app both show history + playback |
| **Constraint** | Additive migrations only; Realtor Edition and Enterprise Edition both stay intact; no mock data; backend stays the authority |
| **Date** | 1 November 2026 |

---

## 0. TL;DR (Hinglish)

- **Cloud calling hata do.** Exotel bridge, provider webhook, "Call settings" — sab web se nikal jayega.
- **Calling sirf Android app se:** lead detail → `Call` → phone ka normal dialer/SIM call. Web par call ka koi button, `tel:` link, ya "Start call" text nahi.
- **Recording app ke local storage se aayegi:** app device ke recorder folder (MIUI/Samsung/Realme etc.) ya SAF folder se audio file utha kar backend par upload karegi (WorkManager, retry, dedupe).
- **Web + app dono par dikhegi:** Calls page, dashboard Calls card, lead detail **Calls** tab — sab me player aur outcome.
- **Ek hard truth:** Android 9/10 ke baad third-party app se call audio capture **guaranteed nahi** hai. Isliye plan ka reliable path hai **OEM recorder file import + upload**; in-app recording sirf un devices par jahan allowed ho (feature-detected, off by default). "Har phone par automatic recording" ka promise mat karo.
- **Web upload fallback rahega** ("Upload recording") — sirf recovery ke liye, calling ke liye nahi.
- **No `READ_CALL_LOG`, no accessibility service, no stealth recording** — Play policy aur user trust dono ke liye.

---

## 1. Decisions locked in (plan inhe follow karega)

| # | Decision | Reason |
|---|---|---|
| D1 | Call initiation sirf Android app me | User requirement; web par calling option nahi |
| D2 | Normal device call (`ACTION_DIAL` default, optional `ACTION_CALL`) | No provider, no bridge, no per-minute cost |
| D3 | Recording audio app **local storage** se upload hoti hai | User requirement; files backend par private storage me |
| D4 | Upload metadata-first, audio second | Call history turant bane; recording baad me attach ho sake |
| D5 | Web sirf history, playback, outcome, delete + manual upload | Recovery/back-office |
| D6 | Existing call tables reuse; sirf additive columns + one new table | Safe evolution |
| D7 | Telephony code/UI deprecated and removed in phases | No dead buttons, no confusing settings |
| D8 | App never sends unmatched orphan calls | Har call ek visible lead se linked |
| D9 | Consent confirm mandatory before first call per lead/day | Legal + audit |
| D10 | Recording access stays authenticated + audited | Same as today |

---

## 2. Architecture

```
ANDROID APP
  Lead detail
    └─ [Call] → consent sheet → ACTION_DIAL / ACTION_CALL (device SIM)
         └─ save local CallSession (client_call_id, lead, started_at, ended_at)
              └─ recording discovery
                   ├─ OEM recorder folder (MIUI / Samsung / Realme / Vivo / OnePlus)
                   ├─ SAF folder chosen by user
                   ├─ MediaStore audio scan
                   └─ manual file pick
                        └─ WorkManager upload (multipart, retry, idempotent)
                             │
                             ▼
LARAVEL API (/api/v1)
  GET    /mobile/bootstrap                    → edition, calls base, limits, consent copy
  POST   /mobile/devices                      → device registration / last seen
  POST   /{edition}/calls/app                 → create call (metadata, client_call_id)
  POST   /{edition}/calls/{call}/recording    → upload audio (magic-byte checked, sha256)
  GET    /{edition}/calls[/summary|/{id}]     → history (existing)
  GET    /{edition}/calls/{id}/recording      → authenticated stream (existing)
  PATCH  /{edition}/calls/{id}/disposition    → outcome (existing)
                             │
                             ▼
PRIVATE STORAGE  storage/app/private/call-recordings/{edition}/{Y}/{m}/…
                             │
                             ▼
WEB DASHBOARD + APP  → Calls page, dashboard card, lead Calls tab, player
```

---

## 3. Android reality check (must read before building)

### 3.1 What Android actually allows

| Android version | Third-party call audio capture | Consequence |
|---|---|---|
| 8.x and below | Sometimes possible (`VOICE_CALL`) | Not worth targeting; `minSdk 26` |
| 9 | Mostly blocked | — |
| 10–14 | Blocked for normal apps; `VOICE_CALL` reserved/limited | In-app call recording unreliable |
| 13+ | `READ_MEDIA_AUDIO` replaces storage permission | Scan audio files, not call log |

So the product cannot promise "app records every call". The reliable path is:

1. Phone's **built-in call recorder** saves a file (many Indian OEMs enable this).
2. Rumu app **discovers** that file and uploads it.
3. If no file exists, the user can import a file manually, or the call is saved without audio.

### 3.2 Recording sources we will support (in priority order)

| Priority | Source | How | Reliability |
|---|---|---|---|
| 1 | OEM recorder folder | SAF tree + known paths + MediaStore scan | High on Xiaomi/Samsung/Realme/Vivo/OnePlus when enabled |
| 2 | User-selected SAF folder | `ACTION_OPEN_DOCUMENT_TREE` persisted permission | High, manual one-time setup |
| 3 | Manual file pick after call | `ACTION_OPEN_DOCUMENT` audio | Always works |
| 4 | In-app recording (experimental) | `MediaRecorder` + capability check | **Device-dependent; off by default; never promised** |

Known recorder locations to probe (best-effort, user-visible in "Recording settings"):

```
MIUI/sound_recorder/call_rec
MIUI/sound_recorder
Recordings/Call
Recordings
Sounds/Call
CallRecordings
Music/Recordings
```

### 3.3 What we will not do

- No `READ_CALL_LOG` (Play-restricted; unnecessary because calls start in-app).
- No AccessibilityService, no overlay tricks, no silent background mic capture.
- No "recording guaranteed on all devices" marketing.
- No recording uploads without consent confirmation.

---

## 4. Backend plan (Laravel)

### 4.1 Additive schema (no drops, no rewrites)

**Migration A — `2026_11_08_120000_add_app_calling_to_call_logs.php`**

```php
Schema::table('call_logs', function (Blueprint $table) {
    $table->string('client_call_id', 64)->nullable()->unique();   // app-generated UUID
    $table->string('device_uuid', 64)->nullable()->index();
    $table->timestamp('consent_confirmed_at')->nullable();
    $table->timestamp('uploaded_at')->nullable();
    $table->string('matched_via', 16)->nullable();                // auto|manual|session
    $table->json('client_metadata')->nullable();                  // app version, device, original filename, recorder
});
```

- Keep `provider`, `provider_call_id`, `call_numbers`, `user_call_profiles`, `call_webhook_events` untouched (unused after cutover; do not drop).
- `source` reuses existing string values: **`app`** (uploaded from app), **`import`** (web fallback). `webhook`/`web` become legacy.
- Add index `call_logs(lead_id, started_at)` if not already covered; keep existing 64-char-safe index names.

**Migration B — `2026_11_08_120100_create_mobile_devices_table.php`**

```php
Schema::create('mobile_devices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('device_uuid', 64);
    $table->string('platform', 16)->default('android');
    $table->string('model', 128)->nullable();
    $table->string('os_version', 32)->nullable();
    $table->string('app_version', 32)->nullable();
    $table->string('recorder_source', 64)->nullable();  // miui|samsung|saf|custom
    $table->boolean('recording_auto_sync')->default(true);
    $table->timestamp('last_seen_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'device_uuid']);
});
```

### 4.2 Models & services

**`App\Models\CallLog`**
- Add to `$fillable`: `client_call_id`, `device_uuid`, `consent_confirmed_at`, `uploaded_at`, `matched_via`, `client_metadata`.
- Casts: `consent_confirmed_at` / `uploaded_at` → `datetime`, `client_metadata` → `array`.
- `SOURCES` stays `['app', 'web', 'import', 'webhook']`; add helper `isAppSource(): bool` and `sourceLabel(): string`.
- Keep status/disposition/recording constants unchanged.

**`App\Models\MobileDevice`** (new)
- `belongsTo(User::class)`; fillable as above; `last_seen_at` cast datetime.

**`App\Services\Calls\CallService`**
- Add:
  - `registerAppCall(User $user, Lead $lead, ?EnterpriseMembership $membership, array $data, string $deviceUuid): CallLog`
    - Validates lead phone, daily limit, consent timestamp.
    - Idempotent on `client_call_id`: return existing call (no duplicate).
    - `source = 'app'`, `provider = null`, `from_number = user phone or null`, `to_number = lead phone`, `started_at/ended_at/duration` from payload, `status` from app outcome (`completed|no_answer|failed`), `disposition` optional, `consent_confirmed_at = now()`, `uploaded_at = now()`.
    - `client_metadata` stores `{ app_version, device_model, os_version, recorder_source, original_filename, match_confidence }`.
  - `attachAppRecording(User $user, CallLog $call, UploadedFile $file, array $meta): CallLog`
    - Only the call's initiator may upload (admin/manager can view but not upload on behalf).
    - Reuse `CallRecordingService::validateUpload()` (magic bytes + 50 MB) and `storeUploaded()`.
    - Dedupe: same `recording_sha256` inside the same scope → return existing call with `duplicate_recording = true` (HTTP 200, not 422) so the app stops retrying.
    - Set `recording_status = available`, `recording_uploaded_at = now()`, `matched_via`.
  - Keep `updateDisposition()` as-is.
- Remove: `initiate()` and `applyEvent()` (provider-specific) after Phase 2. `importRecording()` stays for the web fallback.

**`App\Services\Calls\CallRecordingService`**
- Move config reads from `config('telephony.recordings.*')` to a new `config/calls.php` (`recordings.disk`, `recordings.retention_days`, `recordings.max_bytes`, `limits.daily_per_user`, `consent.copy_version`).
- Keep `storeDownloaded()` only if legacy webhooks may still fire; otherwise remove in cleanup.

### 4.3 API contract

**`GET /api/v1/mobile/bootstrap`** — `auth:sanctum`

```json
{
  "success": true,
  "data": {
    "user": { "id": 2, "name": "…", "role": "realtor" },
    "edition": "realtor",
    "calls_base": "/realtor/calls",
    "enterprise_membership": null,
    "calling": { "mode": "app_only", "web_calling": false, "dialer": "ACTION_DIAL" },
    "recording": {
      "max_bytes": 52428800,
      "extensions": ["mp3","wav","m4a","mp4","ogg","amr","aac","3gp","webm"],
      "consent_required": true,
      "consent_copy_version": "2026-11-01",
      "retention_days": 90,
      "daily_limit": 50
    },
    "app": { "min_version": "1.0.0" }
  }
}
```

**`POST /api/v1/mobile/devices`** — `auth:sanctum`

```json
{ "device_uuid": "…", "model": "Redmi Note 13", "os_version": "14", "app_version": "1.0.0", "recorder_source": "miui", "recording_auto_sync": true }
```
→ upsert by `(user_id, device_uuid)`, returns `last_seen_at`.

**`POST /api/v1/{realtor|enterprise}/calls/app`** — guard: edition middleware

```json
{
  "client_call_id": "6f0f4d0e-…",
  "device_uuid": "…",
  "lead_id": 42,
  "direction": "outbound",
  "started_at": "2026-11-01T14:20:00+05:30",
  "ended_at": "2026-11-01T14:25:30+05:30",
  "duration_seconds": 330,
  "status": "completed",
  "disposition": "connected",
  "notes": "Interested in 3 BHK",
  "consent_confirmed": true,
  "client_metadata": {
    "app_version": "1.0.0",
    "device_model": "Redmi Note 13",
    "os_version": "14",
    "recorder_source": "miui",
    "original_filename": "MIUI_call_9876543210_20261101_1420.mp3",
    "match_confidence": 0.92
  }
}
```

Responses:
- `201` new call (CallResource)
- `200` existing call for the same `client_call_id` (idempotent retry)
- `422` validation (missing consent, bad dates, duration out of range, lead not in scope → `403`)

**`POST /api/v1/{realtor|enterprise}/calls/{call}/recording`** — multipart

| Field | Rules |
|---|---|
| `recording` | file, max 50 MB, magic bytes must match an allowed audio/video container |
| `matched_via` | optional `auto|manual|session` |
| `original_filename` | optional string max 191 |
| `sha256` | optional client-computed hash (server recomputes anyway) |

Responses:
- `200` attached (`recording.available = true`)
- `200` duplicate (`duplicate_recording = true`, same call)
- `409` call already has a different recording (app asks user to replace explicitly)
- `403` not the initiator

**Deprecate calling**
- `POST /{edition}/calls/initiate` → keep for one release returning **410**:
  `{"success":false,"message":"Calling is available in the Rumu Android app."}`
- Then remove the route and `initiate` from `HandlesCalls`.
- `GET|PUT /{edition}/calls/profile` → remove from web UI now; keep backend read-only for one release or delete with the initiate cleanup.

**Web fallback stays:** `POST /{edition}/calls/import` unchanged.

### 4.4 Authorization & scoping

- Reuse `CallAccessService` / edition controllers. No new scope logic.
- `calls/app` must call `assertLeadAccess($request, $lead)` exactly like import.
- Enterprise: app call belongs to the initiator's membership + primary team; managers see it through the existing subtree scope; employees see only their own.
- Admin/realtor isolation unchanged; every endpoint requires Sanctum; edition middleware enforced.
- Upload permission: initiator only; delete still follows `canDeleteRecording` (realtor all, enterprise admin or initiator).
- Streaming: existing endpoint, audit row per access.

### 4.5 Validation & idempotency rules

| Rule | Value |
|---|---|
| `client_call_id` | UUID string, unique globally; retries return the existing call |
| `started_at` | required, not in the future (> 5 min ahead rejected), not older than 180 days |
| `ended_at` | ≥ `started_at`, ≤ now + 5 min |
| `duration_seconds` | 0–14,400; app may send 0 for no-answer |
| `status` | `completed`, `no_answer`, `failed` only from app |
| `disposition` | existing enum, nullable |
| `consent_confirmed` | must be `true`; store timestamp |
| Recording | magic bytes, ≤ 50 MB, sha256 dedupe |
| Daily limit | `CALL_DAILY_LIMIT_PER_USER` still applies to created app calls |
| Rate limit | `throttle:120,1` on create/upload per user |

### 4.6 Remove cloud calling (phased, no destructive DB work)

Phase 2 (with web change):
- Remove `initiate` UI + API client.
- Remove `CallSettingsModal` and the profile settings button.
- Keep initiate route returning 410.

Phase 5 (cleanup, after app release):
- Delete `app/Services/Telephony/*`, `app/Http/Controllers/Api/Calls/TelephonyWebhookController.php`, `app/Jobs/DownloadCallRecordingJob.php`, `config/telephony.php` (replaced by `config/calls.php`).
- Remove `POST /webhooks/telephony/{provider}` route.
- Remove `TELEPHONY_*` / `EXOTEL_*` keys from `.env.example`; keep `CALL_*`.
- **Do not drop** `call_numbers`, `user_call_profiles`, `call_webhook_events`; leave them dormant (documented).

### 4.7 Jobs, config, env

- No queue job needed for app uploads (direct multipart). `UploadedFile` is handled in-request; 50 MB max is acceptable.
- Remove the `calls` queue worker requirement from docs once telephony code is gone; keep `default,meta-leads` for Meta.
- New `config/calls.php`:

```php
return [
    'recordings' => [
        'disk' => env('CALL_RECORDING_DISK', 'local'),
        'retention_days' => (int) env('CALL_RECORDING_RETENTION_DAYS', 90),
        'max_bytes' => (int) env('CALL_RECORDING_MAX_BYTES', 52428800),
        'extensions' => ['mp3','wav','m4a','mp4','ogg','amr','aac','3gp','webm'],
    ],
    'limits' => ['daily_per_user' => (int) env('CALL_DAILY_LIMIT_PER_USER', 50)],
    'consent' => ['copy_version' => '2026-11-01', 'required' => true],
];
```

### 4.8 Tests (backend)

`tests/Feature/Api/AppCallsTest.php`
1. Realtor creates an app call for own lead → 201, source `app`, consent stored.
2. Same `client_call_id` retried → 200, one row only.
3. Recording upload → 200, `recording.available`, private file exists, access log created.
4. Duplicate recording hash → 200 `duplicate_recording`, no second file.
5. Realtor cannot attach a call/recording to another realtor's lead → 403.
6. Enterprise employee sees own app call; manager sees subtree; outsider 403 (extend existing hierarchy test).
7. Missing consent → 422. Future date → 422. Bad magic bytes → 422. > 50 MB → 422.
8. Non-initiator upload → 403; admin can delete recording per existing rules.
9. `POST /calls/initiate` → 410 (during deprecation window).
10. Web import still works after the refactor (regression).

Update `CallsApiTest` where it asserts initiate behavior.

### 4.9 Backend file map

| Action | File |
|---|---|
| Add migration | `database/migrations/2026_11_08_120000_add_app_calling_to_call_logs.php` |
| Add migration | `database/migrations/2026_11_08_120100_create_mobile_devices_table.php` |
| Edit | `app/Models/CallLog.php` |
| Add | `app/Models/MobileDevice.php` |
| Edit | `app/Services/Calls/CallService.php` |
| Edit | `app/Services/Calls/CallRecordingService.php` (config key) |
| Edit | `app/Http/Controllers/Api/Calls/HandlesCalls.php` (appCall, uploadRecording, 410 initiate) |
| Add | `app/Http/Controllers/Api/Mobile/MobileBootstrapController.php` |
| Add | `app/Http/Controllers/Api/Mobile/MobileDeviceController.php` |
| Edit | `app/Http/Resources/CallResource.php` |
| Edit | `routes/api.php` |
| Add | `config/calls.php` |
| Add | `tests/Feature/Api/AppCallsTest.php` |
| Later delete | Telephony services, webhook controller, download job, `config/telephony.php` |

---

## 5. Web frontend plan (React)

### 5.1 Remove calling completely

| File | Change |
|---|---|
| `src/components/leads/LeadDetailUI.jsx` | Remove the **Call** tile (and the `tel:` fallback). Grid becomes 3 columns. Drop `onCall`, `callPending`, `PhoneIcon` if unused |
| `src/pages/realtor/leads/LeadDetailPage.jsx` | Remove `callMutation`, `onCall`, `callPending`; keep `LeadCallsPanel` and the Calls tab count |
| `src/pages/enterprise/leads/LeadDetailPage.jsx` | Same removal |
| `src/api/calls.js` | Remove `initiate`, `profile`, `updateProfile` |
| `src/components/calls/CallSettingsModal.jsx` | Delete file |
| `src/components/calls/CallsWorkspace.jsx` | Remove settings state/query/button/modal, telephony alert, "Call settings"; rename Import → **Upload recording** |

### 5.2 Calls page copy & behaviour

- Title: **Calls**
- Description: *"Calls started in the Rumu Android app, with recordings synced to your workspace."*
- Keep: filters, 4 stats, list, pagination, deep link `?call=`, detail modal, playback, disposition, delete recording.
- Keep **Upload recording** as a recovery path (explain it in the modal subtitle: *"Use this only if the app upload failed."*).
- Empty state: *"No calls synced yet. Calls started in the Rumu Android app appear here with playback."* + secondary **Upload recording** + **Open leads**.
- Optional: add a small **Source** filter (`App`, `Uploaded`) and an `App` badge on rows.

### 5.3 Lead detail

- Action tiles: WhatsApp, Note, Activity (3-up).
- Calls tab stays; header: *"Call history"*, sub: *"Calls started in the Rumu app for this lead."*
- Panel empty state: *"No calls yet. Start a call from the Rumu Android app — it will appear here with its recording."*
- No call CTA anywhere on web.

### 5.4 Dashboard

- `CallsSummaryCard` copy: *"Calls from the Rumu app and synced recordings."*
- Recent rows keep lead name, time, duration, recording icon, status.
- Hint text: "Recorded" → "Synced".

### 5.5 Labels

`src/components/calls/callMeta.js`:
```js
export const CALL_SOURCES = {
  app: 'Android app',
  import: 'Uploaded recording',
  web: 'Dashboard (legacy)',
  webhook: 'Provider (legacy)',
}
```

`CallDetailModal`: show source badge + consent line (*"Consent confirmed in app"*) when `consent_confirmed_at` exists; show *"Uploaded from Android"* meta when available.

### 5.6 Verification

- `grep -rn "initiate\|Cloud calling\|Call settings\|Start a call" frontend/src` → zero hits.
- Lead detail has 3 action tiles, no Call.
- Calls page has no settings button, no telephony alert.
- Upload fallback still creates a call with player.
- Dashboard card still renders app-sourced calls.
- `npm run build` passes; browser check both editions.

---

## 6. Android app plan (Kotlin)

### 6.1 Prerequisite

`androidplan.md` describes the full app (auth, role routing, leads, tasks, visits). **That app does not exist yet.** Build order:

1. App shell + auth + splash routing + leads list/detail (from `androidplan.md`).
2. This calling/recording slice.
3. Playback + call history screens.
4. QA + Play release.

If the app base is skipped, calling has no lead context — so this plan assumes Phase 1 of `androidplan.md` is delivered first.

### 6.2 Stack & packages

Kotlin + Compose Material 3, MVVM, Hilt, Retrofit/OkHttp + kotlinx.serialization, Room, WorkManager, DataStore, Media3 (ExoPlayer), Coil. `minSdk 26`, `targetSdk 36`.

```
app/src/main/java/in/rumu/app/
├── core/designsystem/                 # colors/tokens from androidplan.md
├── data/auth/                         # token, EncryptedSharedPreferences
├── data/leads/                        # existing lead APIs
├── data/calls/
│   ├── CallSessionEntity.kt
│   ├── CallSessionDao.kt
│   ├── CallSessionRepository.kt
│   └── CallApi.kt                     # /calls/app, /calls/{id}/recording
├── data/recording/
│   ├── RecordingCandidate.kt
│   ├── RecordingDiscovery.kt          # MediaStore + SAF + known folders
│   ├── OemRecorderPaths.kt
│   ├── RecordingMatcher.kt
│   └── RecorderCapability.kt          # can this device record in-app?
├── data/upload/
│   ├── UploadWorker.kt
│   ├── UploadScheduler.kt
│   └── UploadState.kt
├── feature/calls/
│   ├── LeadCallButton.kt
│   ├── ConsentSheet.kt
│   ├── SaveCallSheet.kt
│   ├── RecordingPickerSheet.kt
│   ├── CallHistoryScreen.kt
│   ├── CallDetailScreen.kt
│   └── RecordingPlayer.kt
├── feature/recordings/RecordingSyncScreen.kt
└── worker/UploadNotification.kt
```

### 6.3 Room model

```kotlin
@Entity(tableName = "call_sessions")
data class CallSessionEntity(
    @PrimaryKey val clientCallId: String,     // UUID generated at call start
    val serverCallId: Long? = null,
    val leadId: Long,
    val leadName: String,
    val phone: String,
    val direction: String = "outbound",
    val startedAt: Long,
    val endedAt: Long? = null,
    val durationSeconds: Int = 0,
    val status: String = "completed",         // completed | no_answer | failed
    val disposition: String? = null,
    val notes: String? = null,
    val consentAt: Long? = null,
    val localRecordingUri: String? = null,
    val uploadState: String = "PENDING",      // PENDING | UPLOADING | UPLOADED | DUPLICATE | FAILED | NO_AUDIO
    val serverSyncedAt: Long? = null,
    val error: String? = null,
)
```

Also cache `RecordingCandidate` (uri, displayName, size, modifiedAt, phoneGuess, used) for the sync screen.

### 6.4 Calling flow (app only)

1. User opens a lead → **Call** button (only if the user can see the lead).
2. First call of the day for that lead → **Consent sheet**:
   *"Calls may be recorded for quality and training. Inform the customer before recording."* → `Consent` continues, `Cancel` stops. Store `consentAt`.
3. Launch:
   - Default: `Intent.ACTION_DIAL` with `tel:` (no permission, safest for Play).
   - Optional setting **Direct call** → `ACTION_CALL` with runtime `CALL_PHONE`.
4. On launch: insert `CallSessionEntity` with `clientCallId = UUID`, `startedAt = now`.
5. When the user returns to the app (or taps "I'm back"), show **Save call** sheet:
   - Duration prefilled from elapsed time, editable.
   - Outcome chips: Connected / No answer / Busy / Wrong number.
   - Note field.
   - **Recordings found:** list of candidates (name, time, duration, play preview).
   - If none: actions **Choose file**, **Scan folders**, **Save without recording**.
6. Save → `POST /calls/app` (metadata) → then upload recording (`POST /calls/{id}/recording`) via WorkManager.
7. Everything is visible immediately in the app's call history as `PENDING`/`UPLOADING`.

No lead-free dialer in v1 — calling always starts from a lead (requirement: "leads ke sath jo baat hui").

### 6.5 Recording discovery & matching

**Discovery sources**
- Persisted SAF tree (`takePersistableUriPermission`), recursive `DocumentFile` scan in a worker (limit depth, skip > 50 MB).
- `MediaStore.Audio` query: `MIME_TYPE like audio/%`, `DATE_MODIFIED` within last N days, name contains `call|record|rec`.
- Known OEM paths via `MediaStore`/direct file access where permitted.
- Manual picker.

**Matching algorithm** (`RecordingMatcher`)

```
score = 0
if filename contains last10digits(lead.phone)      → +60
else if filename contains last7digits               → +35
if modifiedAt within [start-2min, end+10min]        → +30
if source == same OEM recorder used previously      → +5
if audio duration within ±20% of call duration      → +5
candidate accepted if score >= 50; sorted desc
```

Rules:
- Never auto-upload a file that is already `used` by another `clientCallId` (dedupe by uri + sha256).
- If best score < 50, show it as "Needs review", not auto-attached.
- User confirmation overrides scoring.

**What the app shows when no recording exists**
- "No recording found on this phone."
- Steps: enable the phone's built-in call recorder → **Scan now** → or **Choose a file**.
- Device-specific help links for MIUI/Samsung/Realme/Vivo/OnePlus (static copy, no external claim).

### 6.6 Upload pipeline

`CallUploadWorker` (WorkManager, one unique work per `clientCallId`):

1. Read session; if `serverCallId == null` → `POST /{edition}/calls/app` with metadata + consent.
2. Read recording URI → copy to a temp cache file (content resolver), enforce ≤ 50 MB.
3. `POST /{edition}/calls/{id}/recording` multipart:
   - `recording` = file
   - `matched_via`, `original_filename`, `sha256` (client-side for logging only)
4. Handle responses:
   - `200` attached → `UPLOADED` (store `serverCallId`)
   - `200 duplicate_recording` → `DUPLICATE` (treated as success)
   - `409` → `FAILED` with "A different recording already exists for this call"
   - `401` → stop, ask re-login
   - `422/413` → `FAILED` permanent, show reason
   - network/5xx → retry with exponential backoff (30s, 2m, 10m), max 5 → `FAILED`
5. Constraints: `NetworkType.CONNECTED`; foreground notification for uploads on Android 14 (`FOREGROUND_SERVICE_DATA_SYNC`).
6. After success: keep the local file by default; optional setting **"Free space after upload"** deletes the app's copy only (never the OEM recorder's original).

Upload queue UI: `RecordingSyncScreen` lists `PENDING / UPLOADING / UPLOADED / FAILED / NO_AUDIO` with retry per item and "Retry all".

### 6.7 Playback in the app

- **Local, not uploaded yet:** play from content URI.
- **Uploaded:** stream via Retrofit/OkHttp with the bearer token (`Media3` + custom `DataSource.Factory`), URL `GET /{edition}/calls/{id}/recording`.
- Cache metadata in Room; do not cache audio by default (privacy + size).
- Player in lead detail **Calls** tab and in `CallDetailScreen`, same visual language as the web player (play/pause, seek, duration, source badge).

### 6.8 Permissions (manifest)

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.READ_MEDIA_AUDIO" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE"
    android:maxSdkVersion="32" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE_DATA_SYNC" />
<!-- optional, only when Direct call is enabled -->
<uses-permission android:name="android.permission.CALL_PHONE" />
<!-- optional experimental in-app recording flavor only -->
<!-- <uses-permission android:name="android.permission.RECORD_AUDIO" /> -->
```

Explicitly **not** requested: `READ_CALL_LOG`, `READ_PHONE_STATE`, `SYSTEM_ALERT_WINDOW`, Accessibility.

### 6.9 Screens & states

| Screen | Contents |
|---|---|
| Lead detail | Call button (primary), WhatsApp/Note/Activity, calls tab with player |
| Consent sheet | Plain-language notice + Continue/Cancel; remember per day per lead |
| Save call sheet | Duration, outcome chips, note, recording candidates, file picker, save |
| Recording sync | Queue list, retry, storage used, folder setup, device help |
| Call history | Filter by lead/date/source, upload badges, player |
| Call detail | Meta, player, outcome edit, delete recording (role rules) |
| Settings | Direct call toggle, auto-sync toggle, delete-after-upload toggle, folder selection |

Design: reuse tokens from `androidplan.md` (`#8A006B`, `#650052`, `#E9B8DF`, `#059669`, radius 12/8, Inter). Empty/error/loading states for every screen; no dead buttons.

### 6.10 Edge cases

| Case | Behaviour |
|---|---|
| Call cancelled before connecting | Duration < 5 s → default `no_answer`/`not_connected`; user can change |
| Two recordings for one call | Show both; user picks; server `409` on second attach |
| Recording file deleted before upload | Mark `FAILED` with "File moved or deleted"; offer re-scan |
| App killed during call | Session persists in Room; on next launch ask "Save your last call?" |
| Duplicate sync run | `client_call_id` unique → server returns existing; no duplicates |
| Offline | Everything stays `PENDING`; uploads resume on network |
| Logout | Cancel upload workers, clear token + Room session cache, keep no recordings |
| Enterprise role change/suspend | 401/403 → stop queue, show "Access changed" |
| Device has no recorder | Show honest "not available" state + manual import |
| Call from second SIM | App records `from_number = null`; lead number is the to_number |

### 6.11 Play Store & legal

- Data safety: audio files collected, encrypted in transit, user-initiated upload, deletable.
- In-app disclosure + consent sheet before calls.
- Store listing must not claim "records all calls automatically".
- If you enable in-app `RECORD_AUDIO`, declare it and expect review scrutiny; keep it off by default.
- Provide a deletion path (web + app) and retention policy (90 days default, manual purge today).

### 6.12 Tests

- Unit: `RecordingMatcher` (phone suffix, time window, score thresholds), filename parsing across OEM formats, duration formatting.
- Room: session insert/update, upload state transitions, dedupe.
- Network: MockWebServer → idempotent create, upload success, duplicate, 409, 422, 401.
- WorkManager: retry/backoff (test driver), constraint behaviour.
- Instrumented: SAF picker persistence, consent gate, upload notification.
- Manual matrix: Pixel (no recorder), Redmi (MIUI), Samsung, Realme, Vivo, OnePlus; Android 8/10/13/14.
- QA script per device: place call → find file → save call → verify web + app playback.

### 6.13 Phases & effort

| Phase | Deliverable | Estimate |
|---|---|---|
| A | App base: auth, role routing, leads list/detail (from `androidplan.md`) | 2–3 weeks |
| B | Call session + consent + dialer + call history | 3–4 days |
| C | Recording discovery (SAF, MediaStore, OEM paths) + matcher | 4–5 days |
| D | Upload worker + retry + sync screen | 3–4 days |
| E | In-app playback + lead Calls tab | 2–3 days |
| F | Device QA + Play submission | 1–2 weeks |

If the app base already exists, B–E ≈ **2 weeks**. Without it, budget **5–7 weeks** total.

---

## 7. Consent, privacy & retention

- Consent sheet copy version stored with the call (`consent_copy_version` in `client_metadata`).
- Backend rejects `consent_confirmed != true`.
- Recording access: authenticated only, `Cache-Control: private, no-store`, every stream/download writes `call_recording_access_logs`.
- Retention: display `CALL_RECORDING_RETENTION_DAYS`; no automatic purge yet (honest limitation). Add a scheduled purge in a later task.
- Local copies: app keeps them by default; optional delete-after-upload applies only to its own cache copy.
- No third-party analytics SDK reads audio or call data.

---

## 8. Docs & marketing updates (required, not optional)

| File | Change |
|---|---|
| `callplan.md` | Mark **superseded by `updateplan.md`**; keep for history |
| `knowledge.md` | Remove "cloud calling" positioning; add **"calls start in the Android app"**; app status changes from vague roadmap to **the calling surface** (still not released until built); remove Exotel env/pricing mentions |
| `README.md` | Remove Exotel section, telephony env vars, `calls` queue; add app-only flow + upload fallback |
| `ENTERPRISE.md` | Update Calls row/limitation: app-only calling, recording upload, hierarchy unchanged |
| `Backend/README.md` | Replace telephony env table with `CALL_*` only; drop `calls` queue |
| `.env.example` | Remove `TELEPHONY_*` / `EXOTEL_*`; keep `CALL_RECORDING_*`, `CALL_DAILY_LIMIT_PER_USER` |
| `features.md` | Update call feature description + app calling |

Keep the website claim honest: **"Web panel: history and playback. App: calling + recording sync."**

---

## 9. Release order

1. **Backend additive** — migrations, bootstrap/devices, app call create, recording upload, 410 initiate. Deploy with web unchanged (no visible break).
2. **Web** — remove call initiation/settings; update copy; keep upload fallback. Deploy and verify both editions.
3. **Android** — build calling/upload slice on the app base; internal testing (APK sideload).
4. **Pilot** — 5–10 real devices across OEMs; verify upload, matching, playback, hierarchy.
5. **Cleanup** — remove telephony code/config/routes, docs, marketing.
6. **Play release** — store listing + data safety + consent disclosure.

Never run `migrate:fresh` / `db:wipe` on a database with real data. All migrations above are additive and can be rolled back safely (down drops only the new columns/table).

---

## 10. Acceptance criteria

**Web**
- [ ] No Call tile, no `tel:` link, no initiate call, no Call settings, no "Cloud calling" text anywhere.
- [ ] Calls page, dashboard card, lead Calls tab show app-uploaded calls with playback.
- [ ] Upload fallback still works and is labelled "Upload recording".
- [ ] Other actions (WhatsApp, Note, Activity, stage, tasks) unchanged.
- [ ] `npm run build` passes; both editions verified in browser.

**Backend**
- [ ] App call create is idempotent by `client_call_id`.
- [ ] Recording upload validates magic bytes, size, consent, and scope.
- [ ] Duplicate file returns `duplicate_recording` without creating a second row.
- [ ] Enterprise hierarchy scoping holds for app calls (employee/manager/admin).
- [ ] Initiate returns 410 during deprecation; no provider calls remain.
- [ ] Full test suite green; add `AppCallsTest`.

**Android**
- [ ] Call from lead starts the device dialer; consent is confirmed first.
- [ ] Session saved even if the app is killed; recording discoverable and uploadable.
- [ ] Upload retries on network failure and never duplicates on the server.
- [ ] App calls tab shows history + playback; failed uploads are visible and retryable.
- [ ] No `READ_CALL_LOG`, no accessibility, no silent recording.
- [ ] Tested on at least 5 OEM/OS combinations; honest "no recording available" state.

---

## 11. Risks & mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Phone has no built-in recorder | No audio | Manual file pick, SAF setup, honest messaging; never promise universal recording |
| OEM saves to a hidden/non-indexed path | File not found | SAF folder picker + per-brand help + manual import |
| Play review rejects recording flows | Release delay | Default `ACTION_DIAL`, no `RECORD_AUDIO` in release flavor, clear disclosure |
| Large files / slow network | Failed uploads | 50 MB cap, WorkManager backoff, foreground notification, retry UI |
| Duplicate uploads | Messy history | `client_call_id` uniqueness + sha256 dedupe |
| App base not built | Calling has no context | Build app base first (Phase A); do not ship partial calling |
| Web cache shows old Call button | Confusing | Remove in one deploy; bump build; verify grep + browser |
| Consent/legal gaps | Compliance risk | Consent gate + stored version + legal review before pilot |

---

## 12. Open questions (answer before Phase B)

1. **Direct call vs dialer:** default `ACTION_DIAL` (safe) or `ACTION_CALL` (one tap, permission)? Recommend dialer default + setting.
2. **Manual upload on web:** keep forever or remove after app adoption? Recommend keep (recovery).
3. **Recording deletion after upload:** keep local file (default) or delete? Recommend keep, opt-in delete.
4. **Unmatched recording:** require a lead before upload? Recommend yes (no orphan calls).
5. **Backfill window:** allow syncing calls up to 30/90/180 days old? Recommend 180 days.
6. **Enterprise managers uploading on behalf:** never in v1? Recommend initiator-only upload, manager delete only.
7. **Consent copy:** legal must approve English + Hindi/Hinglish wording.
8. **Retention purge:** schedule later; until then manual deletion only.

---

## 13. Task breakdown (repo-sized)

**Backend (5 tasks)**
1. Add migrations (`call_logs` app columns, `mobile_devices`).
2. Add models/services (`MobileDevice`, `registerAppCall`, `attachAppRecording`, config `calls.php`).
3. Add bootstrap/devices endpoints + edition app-call/upload routes; 410 initiate.
4. Update `CallResource` + scoping checks.
5. Tests (`AppCallsTest`, update `CallsApiTest`, full suite).

**Web (4 tasks)**
1. Remove Call tile + mutations from both lead detail pages; delete Call settings modal.
2. Update CallsWorkspace/LeadCallsPanel/CallsSummaryCard copy + source badges + empty states.
3. Clean `api/calls.js`, `callMeta.js`; grep for initiate/cloud text.
4. Build + browser verify both editions.

**Android (6 tasks)**
1. App base (auth, role routing, leads list/detail).
2. Call session + consent + dialer + history.
3. Recording discovery (SAF/MediaStore/OEM) + matcher + review sheet.
4. Upload worker + retry + sync screen + notifications.
5. Playback (local + authenticated stream) + lead Calls tab.
6. Device QA + Play Store data-safety/disclosure.

**Cleanup/docs (2 tasks)**
1. Remove telephony code/config/routes/env; keep dormant tables.
2. Update `README`, `ENTERPRISE`, `Backend/README`, `knowledge.md`, `callplan.md`, `features.md`.
