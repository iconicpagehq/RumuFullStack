# Rumu Auto Call Recording & Auto Upload — Implementation Plan

| | |
|---|---|
| **Document** | `autocall.md` |
| **Status** | Plan only — implementation pending |
| **Version** | 1.0 |
| **Date** | 2 November 2026 |
| **Applies to** | Rumu Android app (`androidplan.md`), backend `/api/v1` (already implemented), React web (already shows synced calls) |
| **Editions** | Realtor + Enterprise |
| **Goal** | Lead par **Call** dabao → call connect ho → call end hote hi recording **khud** detect ho, **khud** upload ho, app aur web dono me **automatically** dikhe. Koi manual step nahi. |

---

## 0. TL;DR (Hinglish)

- Flow: **Call button → SIM dialer → (user baat karta hai) → call end → app khud recorder ki file dhoondti hai → call log + recording backend par auto-upload → app me "Synced" → web dashboard automatically update.**
- Recording ka source **phone ka built-in (OEM) call recorder** hoga. App Android 10+ ki call-audio capture limit ki wajah se dono taraf ki audio khud record **nahi** kar sakti — ye Google Play policy hai, koi workaround nahi.
- Isliye **ek baar** user ko apne phone ke recorder on karna hoga (jese Samsung/Xiaomi/Vivo/Oppo me pehle se hota hai). Uske baad sab kuch automatic.
- App `ACTION_DIAL` use karegi (`CALL_PHONE` permission nahi), call state `READ_PHONE_STATE` se track hogi, upload `WorkManager` se hoga (network aane par auto-retry).
- Backend change ki zaroorat **nahi** — `calls/app` + `calls/{id}/recording` abhi se ready hain (idempotent + retry-safe).
- Estimate: calling + auto-recording slice **2–3 weeks** (1 Android dev) + QA on 4–5 real devices.

---

## 1. Decisions (locked)

| # | Decision | Reason |
|---|---|---|
| D1 | **Koi manual upload/import button app me nahi.** Call log + recording dono automatically sync hote hain. | User requirement. |
| D2 | Recording source = **OEM recorder file pickup** (auto-detect), app khud call audio capture nahi karti. | Android 10+ / Play policy. CAPTURE_AUDIO_OUTPUT sirf system apps ko milta hai. |
| D3 | Call **hamesha log hoti hai** (chaahe recording mile ya na mile). Recording missing ho to status "Recording not found" dikhta hai. | Outcome/duration ka data valuable hai; recording OEM recorder par depend karti hai. |
| D4 | Upload **WorkManager** chain se: pehle `POST calls/app` (idempotent `client_call_id`), phir `POST calls/{id}/recording`. Retry automatic; user ko kuch nahi karna. | Offline/weak network safe; duplicate call nahi banega. |
| D5 | Consent **ek baar** onboarding me confirm hoti hai (legal copy backend se `bootstrap` se aati hai). Uske baad har call automatic; consent timestamp backend par store hota hai. | No manual option ke saath bhi consent record zaroori hai. |
| D6 | App-side playback **stream** hoga (Media3 + bearer token), file download/share nahi. | Privacy + audit trail maintain. |
| D7 | Web UI me koi change nahi — existing Calls page / dashboard card / lead Calls tab automatically naye calls dikhate hain (TanStack Query refetch). Sirf Calls page par halka auto-polling add hoga. | Backend authority, zero mock. |

---

## 2. Platform reality (honest limits)

1. **App khud call record nahi kar sakti.** `MediaRecorder` + `VOICE_CALL` source sirf privileged/system apps ke liye hai. Mic se record karna (D2 ka fallback) sirf user-side audio deta hai — isliye plan me nahi.
2. **OEM recorder on hona chahiye.** Samsung/Xiaomi/Redmi/Vivo/Oppo/Realme/OnePlus ke Indian phones me built-in recorder hota hai. Stock Pixel jaise phones me nahi — wahan recording nahi milegi, call log phir bhi hoga (D3).
3. **Play policy:** `READ_PHONE_STATE` runtime permission + declaration (core functionality = call logging) chahiye. `READ_CALL_LOG` **nahi** lenge (sensitive permission, approval mushkil).
4. **Android 14+:** foreground service ko type declare karna hota hai; hum `dataSync` use karenge, `phoneCall` type nahi.
5. **Dual SIM:** `subscriptionId` match karke sahi SIM ka call log hoga.

---

## 3. End-to-end flow (sequence)

```
User: Lead detail → [Call] tap
App : consent already confirmed? (D5) → startActivityForResult(ACTION_DIAL, tel:lead)
      → CallSessionManager.startSession(clientCallId, leadId, dialInstant)
User: SIM dialer me call karta hai
OS  : TelephonyCallback(CALL_STATE) → ringing → offhook (connectedAt) → idle (endedAt)
App : CallSessionManager.onCallEnded():
        duration = endedAt - connectedAt
        status   = connected ? completed : (ringing bina offhook ? no_answer : failed)
        → enqueues WorkManager chain "call_sync_<clientCallId>"
Work: Step A: POST /{edition}/calls/app  {client_call_id, lead_id, started_at, ended_at,
                                           duration_seconds, status, consent_confirmed:true}
      Step B (only if recorder file matched): POST /{edition}/calls/{id}/recording (multipart)
      Step C: Room me status "synced" + UI refresh event
App : Calls list auto-refresh (work output observer) → row dikhta hai "Synced"
Web : Calls page / dashboard / lead Calls tab — naya call agli fetch par automatically
```

**Recorder file matching (Step B detail):**

- `RecorderWatcher.scan(windowStart = dialInstant - 2min, windowEnd = endedAt + 2min)`:
  - API 29+: `MediaStore.Audio` query (`date_modified` in window, `mime` audio/*), sorted desc.
  - API 26–28: known recorder dirs (`/sdcard/Call`, `/sdcard/Recordings/Call`, MIUI/Samsung/Oppo/Vivo paths) via `FileObserver` snapshot.
- `AutoMatcher.pickBest(candidates, session)`:
  - timestamp within window (hard filter),
  - duration ≈ call duration ±30% (soft, scored),
  - filename hints (`number` digits substring / "call" prefix) (soft, scored),
  - best score ≥ threshold → use; else `recording_status = missing` (call still logs, D3).
- Matched file ka `sha256` compute karke upload meta me bhejna (backend dedupe use karta hai).

---

## 4. Components (Android)

| Component | Responsibility | Key APIs |
|---|---|---|
| `CallSessionManager` | Per-dial session: `clientCallId (UUID)`, leadId, timestamps, state machine (`dialing → ringing → in_call → ended`) | `TelephonyManager.registerTelephonyCallback`, `TelephonyCallback.CallStateListener` |
| `DialerLauncher` | `ACTION_DIAL` + `startActivityForResult`; dialer se wapasi par session close (fallback jab callback na mile) | `Intent.ACTION_DIAL`, `ActivityResultContracts.StartActivityForResult` |
| `RecorderWatcher` | OEM recorder files ka recent window scan | `MediaStore.Audio.Media` (API 29+), `FileObserver` (API 26–28) |
| `AutoMatcher` | Best recording pick scoring | pure Kotlin, unit-testable |
| `CallSyncWorker` (WorkManager) | Chain A→B upload, retries (exponential backoff), network constraint `CONNECTED` | `WorkManager.beginUniqueWork`, `CoroutineWorker` |
| `CallSyncRepository` | Room cache + API calls + status updates | Retrofit, Room |
| `CallsViewModel` | UI state: rows with `syncState` badge (`Syncing… / Synced / Recording not found`) | StateFlow |
| `CallRecordingPlayer` | Stream playback with bearer header | Media3 `OkHttpDataSource` |

**Room model `pending_call_syncs`:** `client_call_id (PK), lead_id, lead_name, lead_phone, started_at, ended_at, duration_seconds, status, recording_path?, recording_sha256?, sync_state, retry_count, created_at`.

---

## 5. Permissions

| Permission | Type | Why |
|---|---|---|
| `INTERNET` | install | API calls |
| `POST_NOTIFICATIONS` | runtime (API 33+) | Sync progress / "Call logged" notification |
| `READ_PHONE_STATE` | runtime | Call state (ringing/offhook/idle) — Play declaration: core call-logging feature |
| `FOREGROUND_SERVICE` + `FOREGROUND_SERVICE_DATA_SYNC` | manifest | Upload worker ka foreground service (Android 14) |
| `ACCESS_NETWORK_STATE` | install | WorkManager constraints |

**Nahi lenge:** `CALL_PHONE`, `RECORD_AUDIO`, `READ_CALL_LOG`, `READ_MEDIA_AUDIO`, `WRITE_EXTERNAL_STORAGE`, `QUERY_ALL_PACKAGES`, accessibility services.

---

## 6. API contract (backend — already implemented, no change)

| Call | Purpose |
|---|---|
| `GET /api/v1/mobile/bootstrap` | Calling mode, consent copy, limits, min app version |
| `POST /api/v1/mobile/devices` | Device registration (recorder source detect karke bhejo) |
| `POST /{edition}/calls/app` | Call record (idempotent on `client_call_id`; retry = 200, no duplicate) |
| `POST /{edition}/calls/{call}/recording` | Multipart upload; `duplicate_recording:true` safe; 409/403 handling |
| `GET /{edition}/calls`, `/summary` | App calls list + stats |

**Worker error handling:** 422 (validation) → mark failed, surface message; 403 → re-auth; network → retry backoff; 409 duplicate → treat as success.

---

## 7. UX (frontend colors ke saath, no manual steps)

1. **Lead detail → Call tile** (primary `#8A006B`). Tap → brief "Opening dialer…" state.
2. Call ke dauraan app background me session track karti hai (notification: "Tracking call…").
3. Call end → notification/snackbar: **"Call logged · Recording syncing…"** → few seconds me **"Synced"**.
4. **Calls list:** har row me auto status — `Syncing…` (spinner), `Synced` (✓ + duration), `Recording not found` (grey, tap karke explanation: "Phone ka call recorder off lag raha hai. Settings me recorder on karo — agli call se automatic." + **Enable recorder** deep-link).
5. Call row tap → detail: player auto-stream, outcome picker, notes.
6. Recording play/delete app me bhi wahi rules jo web me (authorised users only).

**States wireframe (text):**

```
[Calls]
┌──────────────────────────────────────────┐
│ Riya S. · 02:18 · Connected              │
│ ✓ Synced · Recording available      ▶    │
├──────────────────────────────────────────┤
│ Manish K. · 01:42 · Interested           │
│ ⟳ Syncing…                               │
├──────────────────────────────────────────┤
│ Aditya B. · 00:37 · Call back            │
│ ⚠ Recording not found · [Enable recorder]│
└──────────────────────────────────────────┘
```

---

## 8. Web (frontend) — automatic visibility

- **Koi code-flow change nahi:** app upload karte hi web ke existing screens naya call dikha denge:
  - Dashboard **Calls card** (recent rows + Synced tile) — TanStack Query `staleTime 30s` + window-focus refetch.
  - **Calls page** — par `refetchInterval: 30000` add karenge (light polling) taaki app se upload hote hi list update ho bina refresh ke.
  - **Lead detail → Calls tab** — same invalidate pattern.
- Optional v2: Laravel broadcast (`calls` channel) se realtime push — abhi zaroorat nahi, polling kaafi hai.

---

## 9. OEM recorder onboarding (one-time, still not "manual upload")

- App pehli baar device register karte waqt `recorder_source` detect kare (known recorder packages/dirs se) aur bootstrap me bheje.
- Agar recorder nahi mila: ek baar ka setup card — "Auto-recording ke liye apne phone ka call recorder on karo" + manufacturer-specific steps + Settings deep-link.
- Ye **enable** karna hai, upload nahi — upload hamesha automatic rahega (D1).

---

## 10. Testing plan

| Level | What |
|---|---|
| Unit | `AutoMatcher` scoring (timestamp/duration/filename), `CallSessionManager` state machine, worker retry mapping |
| Integration (API) | Existing backend tests already cover app-call + upload; add app-side contract tests against local Laravel (`php artisan serve`) |
| Device QA (real phones, 5+) | Samsung (One UI), Xiaomi/Redmi (MIUI/HyperOS), Vivo (Funtouch), Oppo (ColorOS), OnePlus (Oxygen) — recorder file paths + timing windows verify karna |
| Negative | Airplane mode during call (sync queued), OEM recorder off (missing state), dual SIM, incoming call during outgoing session, app killed mid-call (Dialer fallback) |
| Web | Recording uploaded via app → dashboard/calls/lead tab refresh par dikhe (browser verify) |

---

## 11. Acceptance criteria

- [ ] Lead par Call tap → SIM dialer khulta hai; koi extra permission prompt mid-flow nahi.
- [ ] Call end ke 10 seconds ke andar app me "Call logged" dikhta hai (offline ho to network aane par).
- [ ] OEM recorder on hai to recording bina kisi tap ke upload ho kar app + web dono me dikhti hai.
- [ ] Recorder off hai to bhi call log hota hai aur "Recording not found" state sahi dikhta hai.
- [ ] Same call do baar sync nahi hota (idempotency), app kill/restart ke baad bhi.
- [ ] Web: refresh ke bina (≤30s) nayi call Calls page/dashboard/lead tab me dikhti hai.
- [ ] Koi `RECORD_AUDIO`/`CALL_PHONE`/`READ_CALL_LOG` permission nahi.

---

## 12. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Stock Android (Pixel/Moto) me OEM recorder nahi | Call log phir bhi hota hai; "Enable recorder" card; long-term mic-side optional fallback evaluate karna (D2 note) |
| OEM recorder file ka path/timing vary | Manufacturer matrix in QA; matcher loose scoring; `recorder_source` telemetry |
| Play policy rejection on `READ_PHONE_STATE` | Core-functionality declaration + prominent disclosure screen; fallback: dialer-return + elapsed-time estimation |
| Battery killers (MIUI/ColorOS) | WorkManager + foreground service + "Ignore battery optimisation" hint (deep-link) |

---

## 13. Task breakdown (2–3 weeks)

1. **Week 1:** App skeleton login + bootstrap + leads list/detail (reuse `androidplan.md` Phase 1–2), DialerLauncher + CallSessionManager + state tests.
2. **Week 2:** RecorderWatcher + AutoMatcher + manufacturer matrix, WorkManager sync chain, Room cache, notification states.
3. **Week 3:** Calls list/detail + player + missing-state UX, polish pass (colors/typography parity), device QA on 5 phones, web polling verify.
