# Rumu MCP

Natural-language control of the **Rumu V2 CRM** from ChatGPT (or any MCP client) through a secure MCP server.

Rumu MCP is a thin, deterministic layer over the existing Rumu `/api/v1` REST API. It **never** touches the database, never places calls, and never streams recordings — Laravel stays the single authority for authentication, RBAC, tenant scoping, plan limits, and business rules.

Full specification: [`../chatgptmcp.md`](../chatgptmcp.md).

## 1. Architecture

```
ChatGPT / MCP client
      │  tool call (stdio)
      ▼
rumu-mcp  (Node.js + TypeScript)
  Zod validation → confirmation registry → retry/error mapping → response normalization
      │  HTTPS  Authorization: Bearer <Sanctum PAT>
      ▼
Rumu Laravel API  /api/v1
```

## 2. Requirements

- Node.js >= 18.17 (developed on Node 24)
- A Rumu account + Sanctum Personal Access Token (per user)

## 3. Installation

```bash
cd mcp
npm install
npm run build
```

## 4. Environment configuration

Copy `.env.example` to `.env` (or export variables in your MCP client config — see below):

| Variable | Default | Notes |
|---|---|---|
| `RUMU_API_BASE` | — | e.g. `https://api.rumu.in/api/v1` (local: `http://127.0.0.1:8000/api/v1`) |
| `RUMU_TOKEN` | — | Sanctum personal access token of the operating user. Never commit. |
| `RUMU_REQUEST_TIMEOUT_MS` | `15000` | Per-request timeout |
| `RUMU_MAX_RETRIES` | `2` | Automatic retries (network / 429 / 5xx, with jitter) |
| `RUMU_PAGE_SIZE` | `20` | Default page size (max 20) |
| `RUMU_MIN_INTERVAL_MS` | `200` | Minimum interval between API calls |
| `MCP_CONFIRM_TTL_SECONDS` | `300` | Confirmation token lifetime |
| `MCP_LOG_RETENTION_DAYS` | `7` | Local audit log retention |
| `MCP_LOG_PATH` | `.mcp-log.jsonl` | Local audit log file |

Production **requires HTTPS** for `RUMU_API_BASE`.

## 5. PAT setup

1. Log in to Rumu (web), create a personal access token for your account (use a least-privilege token; the backend enforces the role either way).
2. Put the token in the client config as `RUMU_TOKEN`.
3. Start with `rumu_auth_me` to verify identity and role (`realtor` | `enterprise` | `admin`).

## 6. ChatGPT configuration

ChatGPT Desktop → Settings → Connectors / MCP, or edit the config JSON:

```json
{
  "mcpServers": {
    "rumu": {
      "command": "node",
      "args": ["c:\\Campany Projects\\rumu-v2.2\\mcp\\dist\\index.js"],
      "env": {
        "RUMU_API_BASE": "https://api.rumu.in/api/v1",
        "RUMU_TOKEN": "REPLACE_WITH_USER_SANCTUM_PAT"
      }
    }
  }
}
```

For local development against the XAMPP backend:

```json
"env": {
  "RUMU_API_BASE": "http://127.0.0.1:8000/api/v1",
  "RUMU_TOKEN": "local-dev-pat"
}
```

Claude Desktop and other stdio MCP clients use the same JSON shape.

## 7. Tool catalog

### Reads (no confirmation)
| Tool | Route |
|---|---|
| `rumu_auth_me` | `GET /auth/me` |
| `rumu_dashboard_today` | `GET /{edition}/dashboard` |
| `rumu_leads_list` / `rumu_leads_get` | `GET /{edition}/leads[/{id}]` |
| `rumu_campaigns_list` | `GET /{edition}/campaigns` |
| `rumu_tasks_list` | `GET /{edition}/tasks` |
| `rumu_visits_list` | `GET /{edition}/site-visits` |
| `rumu_inventory_projects` / `rumu_inventory_units_search` | `GET /{edition}/inventory/...` |
| `rumu_customers_list` | `GET /{edition}/customers` |
| `rumu_calls_summary` / `rumu_calls_list` / `rumu_calls_get` | `GET /{edition}/calls...` (metadata only, never audio) |
| `rumu_reports_snapshot` | `GET /enterprise/reports(+targets)` |

### Safe writes (no confirmation; backend-authorized)
`rumu_leads_create`, `rumu_leads_update_status`, `rumu_leads_add_note`, `rumu_leads_set_followup`, `rumu_tasks_create`, `rumu_tasks_update_status`, `rumu_visits_schedule`, `rumu_visits_close`, `rumu_calls_set_disposition`, `rumu_campaigns_create`

All accept an optional `idempotency_key` (forwarded as `X-Idempotency-Key`); identical repeats within ~2 minutes are deduped.

### Sensitive (confirmation token → `rumu_confirm` / `rumu_cancel`)
| Tool | Route | Notes |
|---|---|---|
| `rumu_leads_delete` | `DELETE /{edition}/leads/{id}` | single lead, one-shot 5-min token |
| `rumu_leads_export` | `GET /realtor/leads/export` | saves CSV to `mcp/exports/`, never into chat |
| `rumu_enterprise_targets_set` | `POST/PUT /enterprise/targets` | admin/manager |
| `rumu_enterprise_distribute` | `POST /enterprise/leads/distribute` | ≤100 leads; `dry_run=true` first |
| `rumu_admin_broadcast_send` | `POST /admin/broadcasts` | preview first |
| `rumu_admin_enterprise_suspend` | `POST /admin/enterprises/{id}/suspend|resume` | admin |
| `rumu_admin_plan_upsert` | `POST/PUT /admin/plans[/{id}]` | admin |

### Enterprise/admin reads
`rumu_enterprise_teams_list`, `rumu_enterprise_members_list`, `rumu_enterprise_audit`, `rumu_enterprise_targets_get`, `rumu_admin_users_list`, `rumu_admin_enterprises_list`, `rumu_admin_seat_requests`, `rumu_admin_billing_subscriptions`, `rumu_admin_plans_list`, `rumu_admin_broadcast_preview`

### Prompts & resources
- Prompts: `morning-brief`, `lead-dossier`
- Resources: `rumu://capabilities`, `rumu://crm/status-definitions`, `rumu://crm/inventory-statuses`, `rumu://enterprise/roles`

## 8. Security

- The MCP token represents a real Rumu user; the backend enforces every role/scope decision. Tool hiding is UX only — Laravel remains the boundary.
- Confirmation tokens are single-use, expire after 5 minutes, and are bound to the user context.
- CRM content is treated as untrusted data: a note can never authorize tool execution.
- Recording playback URLs, tokens, and secrets are stripped from every tool response (`src/utils/redaction.ts`).
- Local audit log stores only hashes (`timestamp, tool, args_hash, status`) — never tokens or CRM payloads.
- Calling is Android-app-only. MCP reads call metadata; it does not place calls.

## 9. Troubleshooting

| Symptom | Fix |
|---|---|
| `RUMU_TOKEN is required` | Set the env var in the client config |
| `must use HTTPS in production` | Set `NODE_ENV=development` locally, or use HTTPS |
| `Rumu authentication failed` (401) | Token expired/revoked — create a fresh PAT |
| `does not have permission` (403) | Role mismatch — use `rumu_auth_me`; admins need `rumu_admin_*` tools, realtors need realtor tools |
| `rate_limited` (429) | Automatic bounded backoff; spread large reads across pages |
| Nothing happens on confirm | Tokens expire in 5 minutes and are single-use — re-run the sensitive tool |

## 10. Development

```bash
npm run dev      # tsx watch-style run (stdio)
npm test         # vitest (45 tests: client, errors, retry, confirmation, redaction, tools)
npm run build    # tsc → dist/
```

Layout: `src/client.ts` (HTTP+retry), `src/confirm.ts` (registry), `src/identity.ts` (`/auth/me`), `src/tools/*` (tool catalog), `src/prompts/*`, `src/resources/*`, `src/server.ts` (MCP registration), `tests/*`.

## 11. Testing

`npm test` runs unit + tool tests against a mocked API (no Laravel required). For integration, point `RUMU_API_BASE` at staging and exercise `rumu_auth_me` → reads → a safe write → a confirmed sensitive write, verifying each change in the Rumu web UI.

## 12. Production deployment

- Run as a local stdio process launched by the desktop client (simplest, recommended).
- Never expose this server as a public unauthenticated endpoint.
- Inject `RUMU_TOKEN` via the client env or an approved secret manager; rotate any token that ever appeared in a repo.
- Review MCP SDK upgrades before bumping `package.json`.

## 13. Hosted gateway (ChatGPT web / remote MCP clients)

Besides stdio, the same server runs as a **stateless MCP-over-HTTP gateway** so ChatGPT (web/app, via "Create as a plugin") and other remote MCP clients can connect to a URL instead of a local command.

### Topology

```
ChatGPT  ──HTTPS──▶  https://mcp.rumu.in        (Node gateway, dist/http.js)
                        POST /mcp                MCP JSON-RPC (bearer token required)
                        /.well-known/oauth-*     OAuth + protected-resource metadata
                        /oauth/*                 proxied to the Laravel backend
                     ──HTTP──▶  https://api.rumu.in  (Laravel: /oauth/authorize|token|register)
```

Every MCP request carries its own `Authorization: Bearer <Sanctum PAT>`; there is no shared server token. Unauthenticated requests get `401` + `WWW-Authenticate: Bearer resource_metadata=...`, which is what triggers the OAuth flow in the client.

### OAuth 2.0 (implemented in Laravel)

- `POST /oauth/register` — RFC 7591 dynamic client registration (`token_endpoint_auth_method: none`).
- `GET|POST /oauth/authorize` — login + consent page (email/password accounts), issues a 10-minute single-use authorization code bound to client, redirect URI, and PKCE challenge.
- `POST /oauth/token` — exchanges code + PKCE verifier for a **Sanctum PAT** (`mcp:<client_id>` name, `mcp` ability, 30-day expiry).
- `GET /.well-known/oauth-authorization-server` (served by the gateway) — discovery metadata with `S256` PKCE and `authorization_code` grant.

### Run it (local)

```bash
cd Backend && php artisan serve            # Laravel on :8000 (mysql running)
cd mcp && npm run build
RUMU_API_BASE=http://127.0.0.1:8000/api/v1 node dist/http.js   # gateway on :8787
```

Then: `POST http://127.0.0.1:8787/mcp` (MCP), `GET http://127.0.0.1:8787/.well-known/oauth-authorization-server` (metadata). `smoke-oauth.cjs` exercises the full flow end-to-end (register → consent → code → token → `rumu_auth_me`).

### Production deployment

1. Point a subdomain (e.g. `mcp.rumu.in`) at the server and terminate TLS (nginx/Caddy). Suggested proxy rule: forward everything to `127.0.0.1:8787` — the gateway serves MCP, metadata, and proxies `/oauth/*` to Laravel.
2. Run the gateway as a persistent process (systemd / pm2 / supervisor), env from `.env.example` § Hosted gateway: `RUMU_MCP_PUBLIC_ORIGIN=https://mcp.rumu.in`, `RUMU_OAUTH_TARGET=https://api.rumu.in`, `RUMU_API_BASE=https://api.rumu.in/api/v1`, `NODE_ENV=production` (enforces HTTPS origins).
3. `php artisan migrate` on the backend creates `mcp_oauth_clients` + `mcp_oauth_authorization_codes` (additive).
4. In ChatGPT: create a connector with server URL `https://mcp.rumu.in/mcp` → OAuth → users approve on the Rumu consent page with their Rumu login.

Notes: approval currently requires an email/password Rumu account (social-only accounts need a password set); access can be revoked by deleting the `mcp:*` token in the Rumu profile; the confirmation registry and audit log live in the gateway process, so restarting it invalidates pending confirm tokens.

## 14. Qoder Desktop plugin packaging

The repo root contains a Qoder plugin wrapper so the server can be installed as a user-level Qoder plugin (`rumu-mcp@local`):

- `plugin-staging/` — plugin source: `.qoder-plugin/plugin.json` (manifest), `.mcp.json` (server launch config with `${QODER_NODE_RUNTIME}` / `${QODER_PLUGIN_ROOT}`), `runtime/` (bundled server), `skills/rumu-crm/SKILL.md`, `README.md`.
- `make-plugin-zip.ps1` — rebuilds `rumu-mcp-plugin.zip` with forward-slash entry names (required — backslash entries and tar-style zips fail Qoder's `yauzl` extractor with "End of central directory record signature not found").

Rebuild after code changes:

```bash
cd mcp && npm run build && npm run bundle   # refresh runtime-bundles/rumu-mcp.cjs
cp mcp/runtime-bundles/rumu-mcp.cjs plugin-staging/runtime/rumu-mcp.cjs
powershell -NoProfile -ExecutionPolicy Bypass -File make-plugin-zip.ps1
```

Then reinstall via Qoder's local plugin install (upload the zip, or `qoder-settings.install_extension` with `source=local, kind=plugin`). After install, edit `RUMU_TOKEN` in `%USERPROFILE%\.qoder\plugins\cache\local\rumu-mcp\1.0.0\.mcp.json` and reload Qoder.

## 15. Changelog

- **1.0.0 (2026-11-07)** — Initial release per `chatgptmcp.md` v2.0: Phase 1 reads, Phase 2 safe writes + confirmation registry + idempotency + audit log, Phase 3 enterprise/admin tools, prompts, resources, 45 tests. Qoder plugin packaging added same day. **Hosted gateway added**: stateless MCP-over-HTTP transport (`dist/http.js`) with per-request OAuth bearer tokens, RFC 8414/9728 metadata, Laravel OAuth 2.0 + PKCE backend (`/oauth/register|authorize|token`) issuing 30-day Sanctum PATs, gateway `/oauth/*` reverse proxy, and 19 new tests (7 gateway + 12 OAuth flow).
