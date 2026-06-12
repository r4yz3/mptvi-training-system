# MPTVI Training Management System

TESDA training-center management for **Maximino Pellerin Sr. Technical and Vocational
Institute** (under PESO Magsaysay, Davao del Sur).

Laravel 13 + Inertia + React/TypeScript + Tailwind, **SQLite** (self-contained),
locally-built assets. Designed to run on a single office PC and be used by the whole
office over the local network (LAN).

> **Private repository.** This is a client system. Never make it public, and never
> commit client data — the live database, uploaded documents and backups are all
> git-ignored on purpose (they contain personal data protected by R.A. 10173).

---

## Install on the admin / server PC (from GitHub)

The PC needs **Laragon (Full)** — it bundles PHP 8.3+, Composer and Node. (The PHP
**GD** and **SQLite** extensions must be enabled; they are by default in Laragon.)

```bat
:: 1. Get the code into Laragon's www folder
cd C:\laragon\www
git clone <YOUR-PRIVATE-REPO-URL> mptvi
cd mptvi

:: 2. Install dependencies and build the front-end (one time)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

:: 3. Configure for the office network
copy deploy\local\env.local.example .env
::    -> edit .env: set APP_URL to the server's IP, and set BACKUP_PASSWORD
php artisan key:generate

:: 4. Create the database and seed the real roles + programs
type nul > database\database.sqlite
php artisan migrate --force
php artisan db:seed --class=Database\Seeders\RbacSeeder --force
php artisan db:seed --class=Database\Seeders\ProgramSeeder --force

:: 5. Finalize
php artisan storage:link
php artisan optimize
```

Then follow **[deploy/local/INSTALL-LOCAL.md](deploy/local/INSTALL-LOCAL.md)** for the
LAN specifics: the Apache virtual host, Windows firewall rule, static IP, the office
URL, automatic backups (Task Scheduler), and the first-login lock-down.

### First login

`RbacSeeder` creates a bootstrap admin: **admin@mptvi.test** / **password**.
**Immediately** create the real staff accounts, change the admin password, and delete
the seeded demo accounts (Settings → Users). See the lock-down section in the install guide.

---

## Updating an installed copy

```bat
deploy\local\backup-now.bat        :: back up first
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
```

`git pull` will not touch `.env`, `database\database.sqlite`, or `storage\` — those are
ignored and hold the live config + data.

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

Tests: `php artisan test`. Optimize stored images: `php artisan images:optimize`.

---

## Credits

Designed and developed by **Justin Paelden** / **[Adfirm.net](https://adfirm.net)**.
© Adfirm.net — proprietary software. See [AUTHORS.md](AUTHORS.md).
