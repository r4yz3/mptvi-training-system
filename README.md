# MPTVI Training Management System

TESDA training-center management for **Maximino Pellerin Sr. Technical and Vocational
Institute** (under PESO Magsaysay, Davao del Sur).

Laravel 13 + Inertia + React/TypeScript + Tailwind, **SQLite** (self-contained),
locally-built assets. Designed to run on a single office PC and be used by the whole
office over the local network (LAN).

> **Private repository.** This is a client system. Never make it public, and never
> commit client data — the live database, uploaded documents and backups are all
> git-ignored on purpose (they contain personal data protected by R.A. 10173).

**What's inside:** applicant registration & screening, cashier/payments, programs &
batches, training attendance, assessment & **printable national certificates**, **ID-card**
system, internal **messaging** (attachments + reactions), calendar/events, reports &
CSV exports, activity log, role-based users with a PII firewall, a full **Settings** suite
(institution profile, signatories, branding/logos, academic defaults, document
requirements, reference lists, roles, **encrypted backups**), and automatic image
optimization on every upload.

---

## Requirements

- **Windows** PC that stays on (the "server"), plus other PCs with just a browser.
- **Laragon (Full)** — bundles **PHP 8.3+**, Composer and Node. The PHP **GD** and
  **SQLite** extensions must be enabled (they are by default in Laragon).

> Recommended: install the app on a **separate drive** (e.g. `D:\mptvi`) and keep
> Laragon on `C:`, so the app + data survive a Windows/C: reinstall. Ideally D: is a
> physically separate disk. See `deploy/local/INSTALL-LOCAL.md`.

---

## Install on the admin / server PC

```bat
:: Get the code onto a separate drive
D:
git clone https://github.com/r4yz3/mptvi-training-system.git D:\mptvi
cd /d D:\mptvi
```

Then choose **one** of the two options below.

### Option A — One-click (recommended)

Run **`deploy\local\setup.bat`** (double-click, or from the Laragon Terminal). On the
**first** run it creates `.env` and opens it — set `APP_URL` (the server's IP) and
`BACKUP_PASSWORD`, save, then **run `setup.bat` again** to finish. It does composer
install, npm build, key generate, database create + migrate + seed (roles + programs),
storage link, optimize, installs the Apache vhost and adds the firewall rule.

For daily backups, **right-click `deploy\local\install-backup-task.bat` → Run as
administrator**.

### Option B — Manual

```bat
:: 1. Install dependencies and build the front-end (one time)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

:: 2. Configure for the office network
copy deploy\local\env.local.example .env
::    -> edit .env: set APP_URL to the server's IP, and set BACKUP_PASSWORD
php artisan key:generate

:: 3. Create the database and seed the real roles + programs
type nul > database\database.sqlite
php artisan migrate --force
php artisan db:seed --class=Database\Seeders\RbacSeeder --force
php artisan db:seed --class=Database\Seeders\ProgramSeeder --force

:: 4. Finalize
php artisan storage:link
php artisan optimize
```

### LAN specifics (both options)

Follow **[deploy/local/INSTALL-LOCAL.md](deploy/local/INSTALL-LOCAL.md)** for the Apache
virtual host, Windows firewall rule, static IP, the office URL, automatic backups
(Task Scheduler), and the first-login lock-down.

### First login & lock-down

`RbacSeeder` creates a bootstrap admin: **admin@mptvi.test** / **password**.
**Immediately** create the real staff accounts, change the admin password, and delete
the seeded demo accounts (Settings → Users).

---

## Health check (troubleshooting)

If the app isn't working or you're not sure the setup is complete, run the checker — it
verifies PHP, extensions, `.env`, the database, migrations, storage permissions, built
assets, logos and backups, reporting **OK / WARN / FAIL** with a fix for each problem:

```bat
:: from the app folder (or the Laragon Terminal)
php artisan app:check

:: or double-click:
deploy\local\check.bat
```

Common fixes it points to:

| Symptom | Fix |
|---|---|
| `FAIL .env file` | `copy deploy\local\env.local.example .env` |
| `FAIL APP_KEY empty` | `php artisan key:generate` |
| `FAIL Migrations not run` | `php artisan migrate --force` |
| `FAIL Front-end build missing` | `npm ci && npm run build` |
| `WARN storage symlink missing` | `php artisan storage:link` |
| Redirects to https and fails | set `APP_ENV=local` in `.env`, then `php artisan optimize` |
| "419 Page Expired" on login | make `APP_URL` match the address staff type, then `php artisan optimize` |
| `WARN BACKUP_PASSWORD not set` | set `BACKUP_PASSWORD` in `.env` (encrypts backups — they contain PII) |

Other PCs can't connect → check the firewall rule, the server's static IP, and that all
PCs are on the same network. Test `http://localhost/` on the server first.

---

## Updating an installed copy

```bat
deploy\local\backup-now.bat        :: back up first
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan app:check               :: confirm it's healthy
```

`git pull` will not touch `.env`, `database\database.sqlite`, or `storage\` — those are
ignored and hold the live config + data.

---

## Useful commands

```bat
php artisan app:check        :: installation health check
php artisan user:password    :: reset a login password (lists accounts, then prompts)
php artisan backup:run       :: create an encrypted backup now
php artisan images:optimize  :: re-compress previously uploaded images
php artisan test             :: run the test suite
```

**Forgot the admin password?** On the server, run `php artisan user:password` — it lists
the accounts and prompts for a new password (hidden input). Or non-interactively:
`php artisan user:password admin@example.com --password="NewStrongPass123!"`. This is
unrelated to `BACKUP_PASSWORD` (which only encrypts backup files).

---

## Development

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed       # full demo data (dev only)
npm run dev                      # Vite dev server
php artisan serve
```

---

## Credits

Designed and developed by **Justin Paelden** / **[Adfirm.net](https://adfirm.net)**.
© Adfirm.net — proprietary software. See [AUTHORS.md](AUTHORS.md).
