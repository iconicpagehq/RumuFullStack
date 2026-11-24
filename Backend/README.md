# Rumu App — CRM Backend

Laravel-based CRM for real-estate freelancers and admins. Manage campaigns and leads, import Meta Lead Ads, and sync lead status back via Meta Conversions API.

## Features

- **Role-based access** — Admin and Freelancer dashboards
- **Campaigns & leads** — Create campaigns, track lead status, notes, and CSV import
- **Meta Lead Ads** — Connect Facebook, import leads from Lead Gen forms, sync via API
- **Meta Conversions API (CAPI)** — Send CRM lead status updates back to Meta for optimization
- **Social login** — Google and Facebook OAuth (login + account linking)
- **Plans & upgrades** — Freelancer plans with campaign/lead limits and upgrade requests

## Requirements

- PHP 8.2+
- Composer
- MySQL (or MariaDB)
- Node.js (optional — only if you add frontend build tooling later)

## Local setup (XAMPP / Windows)

### 1. Clone and install dependencies

```bash
cd backend-crm-app
composer install
```

### 2. Environment

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` with your database and app URL:

```env
APP_NAME="Rumu App"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=
```

> Use `http://127.0.0.1:8000` (not `localhost`) for OAuth redirect URLs to match Meta/Google settings.

### 3. Database

Create the MySQL database, then run:

```bash
php artisan migrate
php artisan db:seed
```

Default admin (from `AdminSeeder`):

| Field    | Value           |
|----------|-----------------|
| Email    | `admin@rumu.in` |
| Password | `Rumu1100@`     |

Change this password after first login in production.

### 4. Storage link

```bash
php artisan storage:link
```

### 5. Run the app

```bash
php artisan serve
```

Open [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login).

### 6. Queue worker (Meta CAPI)

Meta Conversions API events and lead imports are queued. Run a worker alongside the dev server:

```bash
php artisan queue:work --queue=default,meta-leads
```

App call recordings are uploaded directly by the Android client, so no `calls` queue is required. Calling is
app-only; the legacy telephony adapter stays dormant and unconfigured.

## Environment variables

| Variable | Purpose |
|----------|---------|
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google OAuth login |
| `GOOGLE_REDIRECT_URL` | Default: `{APP_URL}/auth/google/callback` |
| `FACEBOOK_CLIENT_ID` / `FACEBOOK_CLIENT_SECRET` | Facebook OAuth |
| `FACEBOOK_REDIRECT_URL` | Default: `{APP_URL}/auth/facebook/callback` |
| `META_CAPI_ENABLED` | Enable Conversions API (`true` / `false`) |
| `META_CAPI_DATASET_ID` | CRM Dataset ID from Meta Events Manager |
| `META_CAPI_ACCESS_TOKEN` | System User access token |
| `META_CAPI_GRAPH_VERSION` | Graph API version (e.g. `v25.0`) |
| `META_CAPI_LEAD_EVENT_SOURCE` | Event source label sent to Meta |
| `CALL_RECORDING_DISK` | Private disk for recordings (default `local`) |
| `CALL_RECORDING_RETENTION_DAYS` | Retention window for recordings (default 90) |
| `CALL_RECORDING_MAX_BYTES` | Max uploaded recording size (default 50 MB) |
| `CALL_DAILY_LIMIT_PER_USER` | Max app-created calls per user per day (default 50) |
| `CALL_BACKFILL_DAYS` | How far back the app may sync calls (default 180) |
| `CALL_APP_MIN_VERSION` | Minimum Android app version advertised by bootstrap (default `1.0.0`) |

## Facebook / Meta setup

### Meta Developer app

1. Create an app at [developers.facebook.com](https://developers.facebook.com) with use case **Facebook Login**.
2. Under **Facebook Login for Business → Settings**, add **Valid OAuth Redirect URIs**:

   ```
   http://127.0.0.1:8000/auth/facebook/callback
   http://127.0.0.1:8000/connect/facebook/callback
   ```

3. In **App settings → Basic**, set **App Domains** (`127.0.0.1` for local) and a **Privacy Policy URL**.
4. Add your Facebook account under **App roles** as Admin/Developer/Tester (required while the app is unpublished).
5. Copy **App ID** and **App Secret** into `.env`, then run `php artisan config:clear`.

### Permissions used

The app requests: `email`, `pages_show_list`, `pages_read_engagement`, `leads_retrieval`.

- **Development mode** — Only app roles (testers/admins) can log in.
- **Live mode** — Requires Meta App Review for `leads_retrieval` and related permissions.

### Connect flow

- **Login:** `/auth/facebook` → callback at `/auth/facebook/callback`
- **Link while logged in:** `/connect/facebook` → callback at `/connect/facebook/callback`

## Google OAuth setup

1. Create credentials in [Google Cloud Console](https://console.cloud.google.com/) (OAuth 2.0 Client ID, Web application).
2. **Authorized JavaScript origins:** `http://127.0.0.1:8000`
3. **Authorized redirect URIs** (add both):
   - `http://127.0.0.1:8000/auth/google/callback` (login / register)
   - `http://127.0.0.1:8000/connect/google/callback` (link account from profile)
4. Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URL` in `.env`, then run `php artisan config:clear`.

> Use `127.0.0.1` (not `localhost`) so URLs match `.env` and Google Console exactly.

If Google returns **deleted_client** or **invalid_client**, the OAuth client was removed or the secret rotated — create new credentials and update `.env`.

## Running tests

```bash
php artisan test
```

## Project structure (high level)

```
app/
  Http/Controllers/     # Auth, campaigns, leads, admin, freelancer
  Services/             # Meta Lead API, Meta CAPI, CSV mappers
  Jobs/                 # SendMetaCapiLeadEventJob
resources/views/        # Blade templates (admin, freelancer, auth)
routes/web.php          # All web routes
database/migrations/    # Schema
database/seeders/       # Plans + default admin
```

## Production checklist

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Strong `APP_KEY` and database credentials
- [ ] Change default admin password
- [ ] Configure OAuth redirect URIs for production domain (HTTPS)
- [ ] Run `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Run `php artisan migrate` (additive only; never `migrate:fresh` on existing data)
- [ ] Set up queue worker (supervisor/systemd) for
      `php artisan queue:work --queue=default,meta-leads`
- [ ] Apply the app-calling migrations with `php artisan migrate`; recordings stay private under
      `storage/app/private/call-recordings`. Calling happens only in the Android app and
      `POST .../calls/initiate` returns 410; do not configure legacy telephony credentials
- [ ] Meta app published + App Review completed for lead permissions
- [ ] Never commit `.env` or API secrets to version control

## License

MIT
