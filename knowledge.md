# Rumu — SaaS Website Knowledge Base (Realtor + Enterprise)

| | |
|---|---|
| **Document** | `knowledge.md` |
| **Use for** | Building the public marketing website (PHP) for Rumu |
| **Audience of the site** | Indian real-estate **realtors** (individual agents / small brokerages) and **enterprise builders/agencies** (sales teams) |
| **Evidence base** | Actual shipped code: `README.md`, `ENTERPRISE.md`, `features.md`, `callplan.md`, `androidplan.md` |
| **Rule** | Every claim on the website must map to a shipped feature below. Anything else is labelled **Roadmap** or removed. |
| **Date** | 1 November 2026 |

---

## 0. TL;DR (Hinglish)

- Website ka kaam sirf "features list" karna nahi hai — **buyer ke dimaag me chal rahe 3 darr** ko khatam karna hai: *lead chhoot jayega*, *team kaam nahi kar rahi*, *data/recording leak ho jayega*.
- Realtor ko **speed + simplicity** bechni hai ("5 minute me start, phone se chalta hai, free se shuru").
- Enterprise ko **visibility + control + accountability** bechni hai ("kaun kaunsa lead touch kar raha hai, kya call hui, kya recording hai, kis team ka number kam hai").
- **Web panel** hi aaj ka product hai — usko hero banao, real screenshots dikhao.
- **Android app** abhi **nahi bana** hai. Site par "Coming soon / Roadmap" hi likho, download button ya "app available" claim mat karo.
- Pricing, limits aur trial numbers **DB se** aate hain (`GET /api/v1/checkout/plans`) — website par hardcode na karo.
- Har CTA ek **real** destination par jaye (`app.rumu.in/register`, `/login`) — decorative "Book demo" form mat lagao jab tak uska backend endpoint na ho.

---

## 1. Product truth (jo claim kar sakte ho)

### 1.1 Do editions, ek hi account system

| Edition | Kiske liye | Structure |
|---|---|---|
| **Realtor Edition** | Individual agent, freelancer, small brokerage | Ek user = apna workspace. Campaign → leads → follow-ups → customers |
| **Enterprise Edition** | Builder, developer, agency with a sales team | Organisation → teams → members (admin / manager / employee), hierarchy-scoped data |

Both use the same login (`/api/v1/auth/*`), the same design language, and the same database — enterprise is **additive**, the realtor product stays intact.

### 1.2 Web panel — shipped modules (yehi highlight karo)

**Realtor workspace** (`/realtor/*`)

| Module | What it actually does |
|---|---|
| Dashboard | KPI counters, conversion funnel, recent inquiries, campaign velocity, upcoming/overdue follow-ups (tasks + site visits + leads) |
| Global Lead Board | Kanban + table view across all campaigns, stage changes, search/filters, formula-safe CSV export |
| Campaigns | Create/edit/pause campaigns, lead caps, schedules, per-campaign analytics |
| Lead detail | Contact rail, WhatsApp / Note / Activity tiles, Calls tab with playback, convert to customer, soft delete, stage rail, activity timeline, notes, follow-ups, status audit trail |
| Lead capture | Manual entry, CSV/XLSX/XLS bulk import, Meta Lead Ads CSV import, real-time Meta Lead Gen sync |
| Meta CAPI | Server-side conversion events on stage changes (better ad optimisation) |
| Inventory | Project → Tower → Wing → Floor → Unit, availability states (Available / On Hold / Booked / Sold), filters by BHK, price, floor, area |
| Customers | Won leads become a persistent customer directory |
| Tasks & Site Visits | Due dates, priorities, completion notes; visits linked to leads and inventory |
| Calls & recordings | Calls synced from the Rumu Android app, call log, outcome, playback, web upload fallback when the app sync fails |
| Billing | Plan status, invoices (PDF), usage counters, upgrade request |
| Account | Profile, password, notifications, theme, language/timezone |

**Enterprise workspace** (`/enterprise/*`)

| Module | What it actually does |
|---|---|
| Hierarchy & roles | Admin / Manager / Employee, reporting lines, org chart, backend-enforced scoping |
| Teams & members | Create teams, assign managers, add or invite members, suspend/resume, transfer leads, remove with lead hand-over |
| Lead operations | Assignment, round-robin, bulk distribution, stage workflow, notes, deal value, inventory-unit linking |
| Bulk import | CSV/XLSX/XLS up to 1,000 rows / 2 MB, duplicate skip, monthly-lead quota enforced, team/campaign attribution |
| Tasks & visits | Assignment, due dates, visit outcomes, automatic follow-up tasks |
| Targets & campaigns | Monthly targets for company / team / member; campaign attribution with server-side limits |
| Inventory | Same hierarchy, role-scoped availability transitions (admin any, manager team, employee own leads) |
| Customers & reports | Won deals, booked units, scoped reports, CSV export (admin/manager + `advanced_reports` plan flag) |
| Enterprise Meta | Org-level page connection, form mapping, CAPI settings, manual sync, webhook ingestion (gated by `meta_integrations`) |
| Calls & recordings | Calls placed in the app: employee sees own, manager sees team subtree, admin sees all; playback, outcomes, web upload fallback |
| Governance | Branding (logo, RERA number, working hours), audit log, seat add-on requests, invoices, plan limits |
| Notifications | In-app bell notifications; admin broadcast messages |

**Platform admin** (`/admin/*`, internal — trust ke liye mention kar sakte ho, sell mat karo)

Plan management, user/realtor directory, enterprise oversight, upgrade + seat approvals, coupons, referrals, payment/invoice audit, platform audit log, broadcast notifications, analytics.

### 1.3 Calls & recordings (app-only calling)

- **Calls start only in the Rumu Android app** on the salesperson's own SIM. The web dashboard has **no call button** and `POST .../calls/initiate` returns **410 Gone**.
- After a call ends, the app creates the call record (idempotent on `client_call_id`) and uploads the handset's recording file to private storage. The call then appears with playback on the **Calls** page, the **dashboard Calls card**, and the lead-detail **Calls** tab.
- Outcomes: connected, not connected, busy, wrong number, call back, interested, not interested, converted, other.
- **Web fallback:** upload a recording manually when a phone sync fails (MP3/WAV/M4A/MP4/OGG/AMR/AAC/3GP/WebM, up to 50 MB).
- Recording access is authenticated and writes an audit row; raw storage paths are never exposed to the browser.
- **Honest limits:** the Android client is implemented in the separate Rumu Android project but is **not published on the Play Store yet**; the web upload fallback remains for history. Recording capture depends on the handset's OEM call-recorder support and local consent law; there is no stealth recording, transcription, AI summary, or automatic retention purge.

### 1.4 Mobile app — kya sach hai

- **Android app exists as an internal build (separate project), not a Play Store release.** It is the calling surface: consent-gated SIM dialing, local call sessions, recorder discovery, background upload, and playback.
- Website par app ko **Roadmap / Coming soon** ke roop me dikhao jab tak Play listing live na ho.
- Koi "Download on Play Store", "App available", star rating, ya fake app screenshots **nahi**.
- App me aaj ye hai: same account login (email + Google), role-aware navigation, lead view/updates, tasks/visits, per-lead/per-day consent phir `ACTION_DIAL`, call outcome/notes, recorder file discover/copy, retryable upload, aur call history with playback. "Har phone automatically record karta hai" aisa claim mat karo — OEM recorder par depend karta hai.

### 1.5 Plans & pricing (DB truth)

| Plan | Audience | Price | Key limits |
|---|---|---|---|
| Basic | Realtor | ₹0 | 5 campaigns, 100 leads/campaign |
| Pro | Realtor | ₹999 | No campaign/lead caps in plan row |
| Enterprise Starter | Enterprise | ₹0 (14-day trial) | 5 campaigns, 2 managers, 15 employees, 3 teams, 500 leads/month, 200 units |
| Enterprise Growth | Enterprise | ₹14,999 | 10 managers, 100 employees, 20 teams, 5,000 leads/month, 2,000 units, advanced reports + Meta integrations |

Rules:
- Prices/limits **fetch** from `GET /api/v1/checkout/plans` at render time (server-side cURL), cache ~5 minutes.
- If the API fails, **hide prices** or show "See current pricing" — never print stale numbers from a hardcoded array.
- Trial: enterprise registration creates a **14-day trial**. Do not promise automatic lockout at day 15 — trial expiry is not yet hard-enforced.
- Annual billing discounts do **not** exist in the product today. Don't advertise them.

### 1.6 What NOT to claim (compliance + honesty)

- "Automatic call recording on every Android phone" — false; capture depends on OEM recorder support, and the app itself is not shipped yet.
- "100% accurate AI call summaries / transcription" — not built.
- "SOC 2 / ISO 27001 certified" — not audited.
- "Works as an ERP / accounting software" — no.
- "Available on Play Store" — no.
- "Instant WhatsApp API / official WhatsApp integration" — the UI opens WhatsApp links; there is no WhatsApp Business API integration.
- "RERA compliant" — do not claim regulatory compliance; you may say "RERA number aur working hours aapke workspace me store hote hain" and "audit-friendly activity history".

---

## 2. Audience psychology

### 2.1 Realtor persona — "Rohit, 34, Nagpur"

**Context:** 1–3 people, spends on Meta/portal leads, closes 3–8 deals a month, phone is the office, WhatsApp is the CRM, Excel is the backup.

**Day in the life:** 7 AM check Meta leads → calls 12 people → 3 say "call later" → forgets 2 → a site visit gets double-booked → evening tries to remember which campaign gave the good lead.

| Layer | What is actually inside his head |
|---|---|
| Surface want | "Mujhe sasta CRM chahiye jo phone pe chale" |
| Real want | "Mere ad ke paise waste na jayein aur lead chhoote na" |
| Fear | Lead kisi aur ko chala jaye; software seekhne me time waste; monthly fees ka bojh; data lock-in |
| Identity | "Main salesman hoon, software wala nahi" — the site must make him feel **smart**, not stupid |
| Distrust | Big-company software, hidden charges, 1-hour demo calls, "team will contact you" black hole |
| Decision style | Fast, mobile-first, price-sensitive, proof-driven (screenshots > adjectives) |
| Trigger words | "Free", "5 minutes", "phone", "lead chhoote na", "Meta leads", "site visit" |

**Objections → what the site must answer**

| Objection | Page element |
|---|---|
| "Excel/WhatsApp se kaam chal raha hai" | Show the cost of a missed follow-up (one lost deal ≈ many months of subscription) |
| "Software sikhna padega" | 3-step signup + 60-second screen tour + "no training needed" |
| "Mehenga hoga" | Free Basic plan visible above the fold of pricing; Pro at ₹999 |
| "Mera data safe hai?" | Trust strip: private workspace, no lead sharing, export anytime |
| "Meta leads aate hain kya?" | Meta Lead Ads sync + CAPI section with real screenshot |

### 2.2 Enterprise persona — three buyers, one page

| Buyer | What they care about | What they fear |
|---|---|---|
| **Owner / Director** | Revenue per project, ad spend ROI, whether teams are actually working | Paying salaries for unattended leads; no single source of truth |
| **Sales head / Manager** | Which team is behind, which rep is idle, follow-up discipline | Blame without data; reps hiding pipeline in personal WhatsApp |
| **Admin / Ops** | Data hygiene, access control, reports, audit trail | Employee exits with the lead list; cross-team data leakage |

**Emotional core:** *"Main office me baitha hoon, lekin mujhe pata hi nahi kaun kaam kar raha hai."* The site sells **visibility and control**, not "more features".

**Enterprise objections → proof**

| Objection | Proof to show |
|---|---|
| "Team adopt nahi karegi" | Role-aware UI: employee sees only their leads; manager sees team; nobody sees noise |
| "Data leak ho jayega" | Tenant-scoped queries, role middleware, audit log, private recordings + access log |
| "Reporting me hours jaate hain" | One-click reports + CSV export, targets vs actual |
| "Pehle se ERP hai" | "Rumu sales side chalata hai; ERP ko replace nahi karta" |
| "Custom setup chahiye" | Start on the free trial, import your real leads, decide with your own data |
| "Recording legal hai?" | App-side creation with explicit consent confirmation, private storage, role-based playback access, access audit |

### 2.3 Psychology levers (use deliberately, never deceptively)

| Lever | How to apply on the site | Where |
|---|---|---|
| **Loss aversion** | "Ek bhoola hua follow-up = ek lost deal." Show the leaked-lead cost, not just the gain | Home, For Realtors |
| **Speed to value** | "5 minutes me pehla lead import" — count the actual steps | Hero, signup microcopy |
| **Social proof** | Only real proof. If you have no named customers yet, use "Built for Indian real-estate teams" + product facts, not fake logos | Home, all pages |
| **Authority** | Show the product surface (role matrix, audit log) instead of "India's #1" | For Enterprises, Security |
| **Reciprocity** | Free plan, free CSV/Excel template, free lead-import checker | Pricing, Resources |
| **Commitment** | Micro-yes: "Realtor ya Enterprise?" → details → plan (matches the real 3-step signup) | CTA flow |
| **Anchoring** | Order plans Basic → Pro, Starter → Growth so Growth looks like the serious option | Pricing |
| **Cognitive load** | One primary CTA per screen; max 3 benefit bullets above the fold | All pages |
| **Status** | Enterprise page tone: "board-ready visibility", "audit trail" | For Enterprises |
| **Peak-end** | Signup ends on a working dashboard with sample-free next step ("Import your first lead") | Post-signup |
| **Scarcity** | Only if real (e.g. "14-day trial"). No fake "3 seats left" | Pricing |

---

## 3. Positioning & messaging

### 3.1 Positioning statement

> **Rumu is the real-estate sales workspace that turns enquiries into site visits.** Realtors get one place for every lead, call, and follow-up. Builders get hierarchy-level visibility into which team, which rep, and which project is actually moving.

### 3.2 Message pyramid

**Realtor**
1. **Promise:** Koi lead ya follow-up chhoote na.
2. **Mechanism:** Meta + manual + Excel leads ek inbox me; har lead ka owner, stage, call aur next follow-up.
3. **Proof:** Lead board, call history with recordings, inventory + site visits, dashboard funnel.
4. **Action:** Free start.

**Enterprise**
1. **Promise:** Har lead ka owner, har call ka proof, har team ka number.
2. **Mechanism:** Admin/manager/employee hierarchy with scoped data, distribution, targets, reports, audit log.
3. **Proof:** Org chart, team targets vs actual, call recordings with access audit, exportable reports.
4. **Action:** 14-day trial.

### 3.3 Headline bank

| Slot | English | Hinglish |
|---|---|---|
| Home hero | Every lead. Every call. Every follow-up. In one place. | Lead aaye, bhool na jayein. |
| Home sub | Rumu turns property enquiries into site visits — without spreadsheets, WhatsApp chaos, or missed callbacks. | Meta, portal aur Excel ke leads ek jagah. Call, follow-up aur site visit — sab track. |
| Realtor hero | Your whole business, in your pocket. | Aapka pura business, ek screen pe. |
| Realtor sub | Capture leads, plan follow-ups, manage inventory, and close more — starting free. | Lead capture se booking tak — free se shuru karo. |
| Enterprise hero | See every lead your team is working. And every one they're not. | Team ka har lead, har call, har number — sab dikhega. |
| Enterprise sub | Role-based visibility, team distribution, targets, and audit-ready activity across every project. | Admin, manager, employee — har level ko apna view. |
| Calls | Calls that leave proof, not memory. | Recording ho, toh baat yaad rehti hai. |
| Web panel | The cockpit. | Poora control, ek dashboard me. |
| App (roadmap) | Your desk, in your pocket. Coming soon. | Aapka desk, aapki jeb me. Jald aa raha hai. |
| Pricing | Start free. Upgrade when the pipeline pays for it. | Free se shuru, growth pe upgrade. |
| Security | Your leads are yours. | Aapka data, aapka control. |

### 3.4 CTA bank

| Context | Primary CTA | Secondary |
|---|---|---|
| Home / Realtor | **Start free** → `app.rumu.in/register?type=realtor` | Login |
| Enterprise | **Start 14-day trial** → `app.rumu.in/register?type=enterprise` | See how it works |
| Pricing | **Choose Basic / Pro / Starter / Growth** (deep-link to the same register stepper) | Compare features |
| Feature pages | **See it in the app** → login | Talk to us (only with a real endpoint) |
| App page | **Join the waitlist** (only if a real endpoint exists) — otherwise **Start on web** | Roadmap |

Microcopy rules: no "Submit", no "Enquire now". Use "Start free", "Start the trial", "See pricing", "Open the workspace".

### 3.5 Above-the-fold formula (use on every page)

1. One-line outcome headline (≤ 9 words).
2. One-line mechanism subhead (≤ 20 words).
3. Two CTAs (primary + secondary).
4. One real product screenshot or a 3-tile proof strip.
5. Trust microcopy under the button: "Free plan · No credit card · Your data stays yours".

---

## 4. Website information architecture

### 4.1 Sitemap

```
/                       Home
/for-realtors           Realtor edition
/for-enterprises        Enterprise edition
/features               Full capability map (tabbed by edition)
/web-panel              The desktop workspace (hero product)
/app                    Android app — ROADMAP
/calls                  Calls & recordings
/pricing                Plans, limits, comparison
/security               Trust centre (access, recordings, data)
/why-rumu               Comparison vs Excel/WhatsApp/legacy CRM
/resources              CSV/Excel template, lead-checklist (optional)
/contact                Real contact paths only
/login                  → app.rumu.in/login
/register               → app.rumu.in/register
/privacy, /terms        Legal (reuse existing policy copy)
```

Navigation: Logo · For Realtors · For Enterprises · Features · Pricing · **Start free** (button).
Footer: Product / Editions / Trust / Company / Legal + "Web panel available now · Android app in development".

### 4.2 Page blueprints

#### Home
1. **Hero** — "Every lead. Every call. Every follow-up. In one place." + edition toggle (Realtor / Enterprise) that swaps subhead, screenshot, and CTA. The toggle is the single strongest personalisation device on the site.
2. **Problem strip** — 3 cards: "Leads WhatsApp me kho jaate hain", "Follow-up yaad nahi rehta", "Team ka kaam dikhta nahi".
3. **Outcome strip** — 3 numbers you can honestly support from the product: one workspace, one lead owner, one call history. (No invented percentages.)
4. **Web panel showcase** — large screenshot + 4 hotspots: Dashboard, Lead board, Calls, Inventory.
5. **Editions split** — two cards; Realtor (start free) and Enterprise (trial).
6. **Calls section** — playback UI screenshot; "call placed in the app, recording synced, outcome + audit".
7. **App teaser** — muted card, clearly labelled **Coming soon**, link to `/app`.
8. **Pricing preview** — Basic free + Starter trial; "See all plans".
9. **FAQ** — 6 real questions (data safety, Meta, recording, app, migration, cancellation).
10. **Final CTA**.

#### For Realtors
- Hero, "a day without Rumu vs with Rumu" two-column story, 6 benefit blocks (Speed, Follow-up, Ad ROI, Inventory, Calls, Free start), one full lead-detail screenshot, pricing, FAQ, CTA.

#### For Enterprises
- Hero, "three views, one truth" (Owner / Manager / Employee cards), hierarchy diagram, distribution + targets + reports screenshots, governance strip (roles, audit, private recordings), plan limits table, trial CTA, procurement FAQ.

#### Features
- Tabs: **Realtor** | **Enterprise**. Grid of modules with one-line benefit + link to the relevant page section. Every card must correspond to Section 1.2 — nothing aspirational.

#### Web Panel
- "This is the cockpit" — 5 annotated screenshots (Dashboard, Lead Board, Lead Detail, Calls, Reports). Short caption per screenshot. CTA: Start free.

#### App (Roadmap)
- State clearly: "Android app is in development. The web workspace is live today."
- Show the intended experience in **wireframe/mock** style (labelled "Preview"), not fake screenshots of a shipped app.
- Planned: same login, role-aware home, lead updates, tasks/visits, SIM dialer, automatic recording sync, notifications.
- CTA: **Start on web** + optional **Join the waitlist** (only with a working endpoint).

#### Calls
- The flow: call the lead from the Rumu Android app → the handset records → the app syncs the recording to the workspace → the call appears with outcome and playback on web and in the app.
- Privacy: private storage, authenticated playback, access audit, consent confirmation before the call is logged.
- Honest note: "Calls start in the Android app (in development). On web you can upload a recording if the phone sync fails; there is no call button on the dashboard."

#### Pricing
- Toggle Realtor / Enterprise. Cards in DB order. Feature comparison table with exact limits. "What's included in every plan". FAQ on upgrades, seat add-ons, invoices, cancellation.

#### Security
- Access control (roles + scoped queries), tenant isolation for enterprises, private recordings + access log, audit trail, formula-injection-safe exports, token-based auth, no third-party trackers on the app.
- **No certification badges** you don't hold.

#### Why Rumu
- Comparison table vs Excel, WhatsApp, and "legacy CRM": setup time, lead ownership, call history, team visibility, inventory, price of entry. Keep each cell factual.

---

## 5. Feature highlight rules

### 5.1 Feature → benefit → proof (only shipped features)

| Feature | Say this (benefit) | Show this (proof) |
|---|---|---|
| Lead Board | "Har lead ka ek owner aur ek stage" | Kanban screenshot with 3 real columns |
| Lead detail timeline | "Kaun, kab, kya bola — sab ek jagah" | Activity + notes + status audit screenshot |
| Meta Lead Ads sync | "Meta form se lead seedha workspace me" | Campaign → Meta integration screenshot |
| Meta CAPI | "Ad platform ko wapas quality signal" | Toggle + event description (no fake uplift %) |
| Follow-ups + tasks | "Next follow-up bhoolna band" | Overdue/upcoming list screenshot |
| Site visits | "Visit schedule + outcome" | Visit card with outcome |
| Inventory hierarchy | "Project se unit tak availability" | Tower/floor/unit tree screenshot |
| Customers | "Won lead = permanent customer" | Customer list |
| Calls + recordings | "Call ka proof, memory nahi" | Player UI + outcome badges |
| Recording upload fallback | "App sync fail hua? Web se recording upload karo" | Upload modal screenshot |
| Enterprise hierarchy | "Admin sab dekhe, manager apni team, employee apna" | Org chart + role cards |
| Bulk distribution | "100 leads, 1 click, round-robin" | Distribution screen |
| Targets & reports | "Target vs actual, bina Excel" | Report screenshot |
| Audit log | "Kaun kya badla, record rehta hai" | Audit table screenshot |
| Seat add-ons | "Team badhe, seats add karo" | Billing → seat request |
| Private recordings | "Recording sirf authorised log sun sakte hain" | Security page diagram |

### 5.2 Screenshot shot list (capture from the real app, light theme, 1440px+)

1. Realtor dashboard (full)
2. Lead Board (kanban)
3. Lead detail — right panel tabs
4. Lead detail — Calls tab with player
5. Calls page with stats + list
6. Call detail modal (playback + outcome)
7. Campaign detail with Meta connected
8. Inventory project tree
9. Enterprise dashboard
10. Org chart
11. Lead distribution
12. Reports / targets
13. Audit log
14. Billing / plan screen (mask amounts if needed)
15. Mobile-width web view (for the "phone pe chalta hai" claim — it's responsive)

Rules: real data only, mask names/phones, no chrome bookmarks, no fake "customer" logos inside screenshots.

### 5.3 Web panel vs app split on the site

| Claim area | Web panel | Android app |
|---|---|---|
| Status | **Live** | **Roadmap** |
| Message | "Aaj kaam karo" | "Jald aapki jeb me" |
| CTA | Start free | Start on web / waitlist |
| Visual | Real screenshots | Wireframe preview, clearly labelled |
| Features to list | Everything in 1.2 | Only planned scope from `androidplan.md` |

---

## 6. Pricing psychology

1. **Free first.** Basic ₹0 removes the "price" objection before it forms. Put it first, not hidden.
2. **Anchor with Growth.** Enterprise Starter (₹0, 14-day trial) next to Growth (₹14,999) makes Growth read as "for serious teams".
3. **Show limits, not "unlimited".** Honest caps (5 campaigns / 100 leads per campaign, 15 employees, 3 teams) build trust and reduce refund risk.
4. **Reframe cost per deal.** "Pro costs less than one missed site visit" — only if you can defend the numbers to a real agent; avoid invented averages.
5. **No fake urgency.** Trial length, seat caps, and upgrade rules are real; countdown timers are not.
6. **Cancellation clarity.** Say what happens to data on downgrade/cancel (export available; access limits apply). Don't promise refunds the product doesn't implement.
7. **Enterprise procurement block.** Invoices with PDF download, plan/seat change requests, audit log — these reduce the "can we even buy this?" friction for a builder's ops team.

---

## 7. Trust, security, legal (say only what is true)

- **Auth:** Laravel Sanctum bearer tokens; per-device logout and logout-all.
- **Authorisation:** backend middleware + policies; enterprise data scoped by membership, role, and reporting hierarchy. The UI hides what the API refuses.
- **Tenant isolation:** enterprise leads/calls/inventory are filtered server-side by `enterprise_id`; cross-tenant access is rejected in tests.
- **Recordings:** private disk (`storage/app/private`), authenticated stream, `Cache-Control: private, no-store`, access-audit table; raw file paths never sent to the browser.
- **Exports:** CSV values starting with `= + - @` are prefixed to block formula injection.
- **Audit:** enterprise audit log for administrative/member actions; call access audit; lead status audit trail.
- **Legal copy:** recording consent is the customer's responsibility; the app asks for explicit consent before a call is logged. Publish a recording-policy paragraph on `/calls` and `/security`.
- **Do not** use SOC 2 / ISO / PCI badges, "bank-grade", or "military-grade" language.

---

## 8. Visual system for the marketing site

Match the product exactly so the site and app feel like one brand.

| Token | Value | Use |
|---|---|---|
| Primary | `#8A006B` | CTAs, active tabs, links |
| Highlight | `#E9B8DF` | Soft backgrounds, chart accents |
| Success | `#059669` | Connected, won, available |
| Surface | White / `#F8FAFC` | Cards and page bands |
| Text | Slate 900 / 600 | Headings / body |
| Card radius | 12px | All cards |
| Button radius | 8px | All buttons |
| Font | Inter (400/500/600/700/800) | Whole site |
| Shadows | Soft, low-contrast | Cards only |

Rules: light theme first (trust + readability); dark mode optional and must reuse product tokens. No gradient "AI SaaS" purple-blue. Use real property/agent photography sparingly; screenshots are the hero. Indian context in imagery (Bengaluru/Mumbai/Pune skyline, Indian agent on phone) — no Western stock handshakes.

---

## 9. SEO & content plan

**Primary keywords:** real estate CRM India, property lead management software, realtor CRM, builder CRM software, Meta lead ads CRM, real estate call recording CRM, site visit tracking software, real estate sales CRM for builders.

**Meta title patterns**
- Home: `Rumu — Real Estate CRM for Realtors & Builders | Leads, Calls, Follow-ups`
- Realtors: `CRM for Indian Realtors — Capture Leads, Track Calls, Close More | Rumu`
- Enterprises: `Enterprise Real Estate CRM — Team Visibility, Targets & Reports | Rumu`
- Calls: `Call Recording & Playback for Real Estate Sales Teams | Rumu`
- Pricing: `Rumu Pricing — Free Plan, Pro ₹999, Enterprise Trial`

**Meta description rule:** benefit + mechanism + edition + CTA, ≤ 155 chars.

**Content ideas (only what the product supports)**
1. "Meta lead se site visit tak: 5-step workflow"
2. "Follow-up checklist: 7 questions har lead ke liye"
3. "Builder sales team ke liye 6 reports jo weekly review me chahiye"
4. "Call recording consent: India me kya dhyan rakhein" (legal review required)
5. "Excel se CRM migration: duplicate leads kaise bachayein"

Add `Organization`, `SoftwareApplication`, and `FAQPage` structured data. Do **not** mark up ratings/reviews you don't have.

---

## 10. Conversion & measurement

**Primary conversion:** register start (realtor or enterprise).
**Secondary:** pricing view, feature-page CTA, app waitlist (if real), login.

Events to track (privacy-friendly, no ad pixels by default):
`edition_toggle`, `cta_click` (with page + edition), `pricing_view`, `plan_cta` (plan key), `register_start`, `login_click`, `faq_open`, `screenshot_open`.

A/B ideas (run one at a time):
1. Hero headline: outcome vs fear ("Every lead in one place" vs "Lead chhoote na").
2. Edition-first vs single combined hero.
3. CTA label: "Start free" vs "Start free — no card".
4. Screenshot above vs below the benefit bullets.
5. Pricing default tab: Realtor vs Enterprise.

Guardrails: no fake countdowns, no fake testimonials, no invented metrics.

---

## 11. PHP implementation guide

### 11.1 Recommended structure

Two safe options:

| Option | How | Trade-off |
|---|---|---|
| **A. Standalone PHP site** (recommended for marketing) | New `website/` folder, PHP 8.2, served at `rumu.in`, calls the Laravel API over HTTPS | Clean separation, marketing deploys don't touch the app |
| B. Laravel Blade inside `Backend/` | `routes/web.php` public routes + Blade views, reuse layouts | One deployment, but mixes marketing with API/session code and changes current `/` behaviour |

If you choose B, keep `/` as the landing page and move the current root redirect to `/login` only for authenticated users.

### 11.2 File layout (Option A)

```
website/
├── index.php                  Home
├── for-realtors.php
├── for-enterprises.php
├── features.php
├── web-panel.php
├── app.php
├── calls.php
├── pricing.php
├── security.php
├── why-rumu.php
├── contact.php
├── 404.php
├── legal/{privacy,terms,recording}.php
├── partials/{head,header,footer,cta,pricing-cards}.php
├── lib/{config.php,api.php,helpers.php}
├── assets/{css/site.css, js/site.js, img/...}
└── .htaccess
```

### 11.3 Server-side data rules

- `lib/api.php`: one `api_get($path)` helper using cURL, 3-second timeout, no secrets, JSON decode, cache to file for 5 minutes.
- Pricing: `GET https://api.rumu.in/api/v1/checkout/plans` → render real plan cards and limit table. On failure, hide numbers.
- Never call authenticated endpoints from the marketing site.
- Never put API tokens, `.env`, or DB credentials in the website folder or JS.
- CTA links: `https://app.rumu.in/register?type=realtor|enterprise&utm_...` and `https://app.rumu.in/login`. Carry UTM parameters through to signup.

### 11.4 Forms

- Do **not** ship a "Book a demo" or "Contact sales" form unless a real endpoint stores it.
- Allowed now: links to register/login, `mailto:` for support, and a phone/WhatsApp link if the number is real.
- If a contact form is required, add an additive backend endpoint and table first (`POST /api/v1/public/contact`): honeypot field, timestamp check, rate limit, validation, mail notification, no PII in logs. Document it before the site goes live.

### 11.5 Security & performance checklist

- HTTPS only, HSTS, `X-Content-Type-Options`, `Referrer-Policy`, CSP without inline scripts where possible.
- `.htaccess`: deny dotfiles, disable directory listing, pretty-URL fallback.
- Escape all output (`htmlspecialchars`), validate all input, no dynamic includes from user input.
- Compress images (WebP), lazy-load below the fold, preload the hero image and Inter.
- Cache plan data and static pages; purge on deploy.
- Accessibility: one `h1` per page, focus states, 4.5:1 contrast, alt text on screenshots describing the screen.

---

## 12. Roadmap copy (paste-ready, honest)

**App page banner**
> The Rumu Android app is in development. Everything on this site works today in the web workspace — same account, same data, no migration later.

**Calls disclaimer**
> Calling happens inside the Rumu Android app (in development), on your own SIM, with the recording synced back to your workspace. Until the app ships, you can upload recordings on web — the dashboard has no call button.

**Trial disclaimer**
> Enterprise accounts start with a 14-day trial. Plan changes and seat additions go through your workspace billing.

**Data disclaimer**
> Your leads and recordings stay in your workspace. Recordings are private and only play for authorised users; every access is logged.

---

## 13. Launch checklist

- [ ] Every claim traced to Section 1 (no roadmap claim presented as shipped)
- [ ] App marked **Coming soon**; no store badges or fake screenshots
- [ ] Pricing fetched from the API; failure state hides numbers
- [ ] All CTAs point to real register/login URLs with `type` + UTM
- [ ] No decorative forms without a real backend endpoint
- [ ] Real screenshots captured, masked, compressed
- [ ] Brand tokens match the product (`#8A006B`, `#E9B8DF`, `#059669`, radius 12/8, Inter)
- [ ] Security page has no certification claims
- [ ] Recording-consent paragraph reviewed by legal
- [ ] Meta titles/descriptions + structured data validated
- [ ] Lighthouse mobile ≥ 90 performance / 100 accessibility
- [ ] 404, legal links, and support email working
- [ ] Analytics events fire once and carry the edition
