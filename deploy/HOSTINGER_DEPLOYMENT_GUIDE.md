# Hostinger Deployment Guide for Rumu 2.0

This guide provides end-to-end instructions for deploying Rumu 2.0 across two subdomains on Hostinger:
- **Backend API:** `https://api.rumu.in` (Laravel 11 / PHP 8.2+)
- **Frontend App:** `https://app.rumu.in` (React + Vite SPA)

---

## 1. Quick Architecture & Configuration Summary

| Service | Subdomain | Technology | Root Directory to Serve |
| :--- | :--- | :--- | :--- |
| **Backend API** | `api.rumu.in` | Laravel 11 / PHP 8.2+ | `Backend/public` (or root `Backend/` with included `.htaccess`) |
| **Frontend App** | `app.rumu.in` | React + Vite (SPA) | Contents of `frontend/dist/` (includes `.htaccess`) |

### Key Environment Variables

#### Backend (`Backend/.env`)
```env
APP_NAME=Rumu
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.rumu.in
FRONTEND_URL=https://app.rumu.in

# CORS & Sanctum Domains
CORS_ALLOWED_ORIGINS=https://app.rumu.in
SANCTUM_STATEFUL_DOMAINS=app.rumu.in,api.rumu.in
SESSION_DOMAIN=.rumu.in

# Hostinger MySQL Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uXXXX_rumu_v2
DB_USERNAME=uXXXX_dbuser
DB_PASSWORD=your_strong_password
```

#### Frontend (`frontend/.env.production`)
```env
VITE_API_BASE_URL=https://api.rumu.in/api/v1
VITE_STORAGE_BASE_URL=https://api.rumu.in/storage
```
*(The production build in `frontend/dist` has already been pre-compiled with these URLs).*

---

## 2. Option A: Hostinger Shared / Cloud Hosting (hPanel)

### Step 1: Create the Subdomains in hPanel
1. Log in to your Hostinger **hPanel**.
2. Navigate to **Domains** -> **Subdomains**.
3. Create the two subdomains under `rumu.in`:
   - **Frontend:**
     - Subdomain: `app`
     - Custom folder: check the box and set custom folder to `domains/rumu.in/public_html/app` (or `public_html/app.rumu.in`)
   - **Backend:**
     - Subdomain: `api`
     - Custom folder: check the box and set custom folder to `domains/rumu.in/public_html/api` (or `public_html/api.rumu.in`)
4. Go to **Security** -> **SSL** and issue free Let's Encrypt SSL certificates for both `app.rumu.in` and `api.rumu.in`.
5. Under **Advanced** -> **PHP Configuration**, ensure **PHP 8.2** or **PHP 8.3** is selected.

---

### Step 2: Create MySQL Database in hPanel
1. Navigate to **Databases** -> **Management** / **MySQL Databases**.
2. Create a new database:
   - Database name: e.g. `rumu_v2` (Hostinger will prefix it with your username, e.g., `u123456789_rumu_v2`).
   - Username: e.g. `admin` (e.g., `u123456789_admin`).
   - Password: Choose or generate a strong password.
3. Save these credentials for the backend `.env`.

---

### Step 3: Deploy Frontend (`app.rumu.in`)
1. In `rumu-v2/frontend`, the production build is already generated in the **`frontend/dist/`** directory.
2. In Hostinger **File Manager**, open the folder for `app.rumu.in` (e.g., `public_html/app`).
3. Upload **all files and folders from inside `frontend/dist/`** into this directory:
   ```text
   public_html/app/
   ├── assets/
   ├── favicon.svg
   ├── index.html
   ├── .htaccess        <-- (Crucial for React client routing!)
   └── rumu-logo.png
   ```
4. Verify that `.htaccess` is uploaded (enable "Show hidden files" in File Manager if needed). This guarantees that refreshing URLs like `https://app.rumu.in/realtor/dashboard` will not trigger 404 errors.

---

### Step 4: Deploy Backend API (`api.rumu.in`)
1. In Hostinger **File Manager**, upload the entire **`Backend`** folder into your subdomain folder (e.g., `public_html/api` or place `Backend` alongside `public_html` for higher isolation).
2. **Document Root Configuration:**
   - **Recommended:** In hPanel Subdomain settings, set Document Root of `api.rumu.in` to `.../Backend/public`.
   - **Fallback:** If hPanel forces the root to the parent folder, the project already includes a secure root `Backend/.htaccess` that automatically forwards requests to `public/` while protecting `.env` and `storage/`.
3. Create your production `.env` file:
   - Copy `Backend/.env.production.example` to `Backend/.env`.
   - Update `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` with the credentials created in Step 2.
   - Keep `APP_KEY` (or generate a fresh one if desired).
4. Set folder permissions:
   - Make sure `Backend/storage` and `Backend/bootstrap/cache` are writable (permissions `775` or `755`).
5. Run migrations & storage symlink:
   - Open **Advanced** -> **SSH Access** / **Terminal** in hPanel:
     ```bash
     cd domains/rumu.in/public_html/api/Backend   # or your upload path
     php artisan migrate --force
     php artisan storage:link
     php artisan config:cache
     php artisan route:cache
     ```
   - *If SSH is not available:* You can import your local database export (`rumu_v2.sql`) via Hostinger phpMyAdmin, and create a symlink in File Manager pointing `public/storage` to `storage/app/public`.

---

### Step 5: Schedule Recurring Cron Job in hPanel
For scheduled tasks, subscription renewals, and background sync:
1. In hPanel, go to **Advanced** -> **Cron Jobs**.
2. Select **Custom** and enter every minute: `* * * * *`
3. Command:
   ```bash
   cd /home/uXXXXXXX/domains/rumu.in/public_html/api/Backend && php artisan schedule:run >> /dev/null 2>&1
   ```
   *(Replace `/home/uXXXXXXX/...` with your actual Hostinger account path).*

---

## 3. Option B: Hostinger VPS (Ubuntu + Nginx)

If deploying to a Hostinger VPS (Linux):

1. **Copy Nginx site configurations:**
   - `deploy/nginx/api.rumu.in.conf` -> `/etc/nginx/sites-available/api.rumu.in.conf`
   - `deploy/nginx/app.rumu.in.conf` -> `/etc/nginx/sites-available/app.rumu.in.conf`
2. **Enable sites and reload Nginx:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/api.rumu.in.conf /etc/nginx/sites-enabled/
   sudo ln -s /etc/nginx/sites-available/app.rumu.in.conf /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```
3. **Obtain SSL certificates:**
   ```bash
   sudo certbot --nginx -d api.rumu.in -d app.rumu.in
   ```
4. **Deploy code:**
   - Backend at `/var/www/rumu/Backend`
   - Frontend built files at `/var/www/rumu/frontend/dist`
5. **Run artisan commands:**
   ```bash
   cd /var/www/rumu/Backend
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   ```

---

## 5. Rumu MCP Gateway & AI Integration (`mcp.rumu.in`)

Rumu 2.0 includes a Model Context Protocol (MCP) server allowing users to interact with their CRM data directly from ChatGPT / OpenAI Codex, Claude Desktop, and Cursor.

### Deployment Packages Ready
The deployment files in `deploy/` are pre-packaged and ready to upload:
- **`frontend-deploy.zip`**: Pre-built SPA assets + `.htaccess`. Extract directly into your frontend document root (`public_html/app`).
- **`backend-deploy.zip`**: Complete Laravel backend with pre-installed `vendor/` dependencies. Extract into your backend root (`public_html/api` or root directory).
- **`database_import.sql`**: Full MySQL database dump with all migrations, including OAuth 2.0 tables (`mcp_oauth_clients`, `mcp_oauth_authorization_codes`).
- **`rumu-mcp-plugin.zip`**: The distributable Codex/ChatGPT plugin.

### How Multi-User Authentication Works in Production
When deployed on production, **you do NOT need to manually set or update tokens for each user**:
1. When a user connects to Rumu via Codex or ChatGPT, the MCP Gateway requests OAuth 2.0 authorization.
2. The user's browser opens `https://api.rumu.in/oauth/authorize`.
3. The user logs in with **their own Rumu account** and clicks **"Authorize"**.
4. Rumu securely exchanges an authorization code for an individual access token. Codex automatically stores and uses that user's token.
5. Logging in or out of different accounts is completely isolated per user.

### Deploying the MCP Gateway Service (`mcp.rumu.in`)
The MCP Gateway (`mcp/dist/http.js`) is a lightweight Node.js service:
1. **Subdomain / Reverse Proxy:** Point `mcp.rumu.in` to port `8787` (e.g. via Nginx reverse proxy).
2. **Environment Variables:**
   ```env
   PORT=8787
   RUMU_API_BASE=https://api.rumu.in/api/v1
   RUMU_MCP_PUBLIC_ORIGIN=https://mcp.rumu.in
   ```
3. **Run with PM2:**
   ```bash
   cd /var/www/rumu/mcp
   pm2 start dist/http.js --name "rumu-mcp"
   pm2 save
   ```

---

## 6. Verification Checklist

- [ ] Visiting `https://api.rumu.in/` returns `{ "status": "online", "app": "Rumu", ... }` or redirects to `https://app.rumu.in`.
- [ ] Visiting `https://api.rumu.in/up` returns HTTP 200 health status.
- [ ] Visiting `https://app.rumu.in/login` loads the React login page without 404 errors.
- [ ] Logging in sends requests to `https://api.rumu.in/api/v1/auth/login` and receives authentication token without CORS issues.
- [ ] Page refreshes on deep routes (e.g. `https://app.rumu.in/realtor/dashboard`) load properly via the included `.htaccess`.
- [ ] Visiting `https://mcp.rumu.in/.well-known/oauth-authorization-server` returns OAuth 2.0 metadata JSON.

