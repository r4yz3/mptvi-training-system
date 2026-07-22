# CLAUDE.md — MPTVI Training Management System

Working-context guide for Claude Code. For client/office install steps see `README.md`
and `deploy/local/INSTALL-LOCAL.md`; for VPS deploy see `DEPLOY.md`.

## What this is
TESDA training-center management for **Maximino Pellerin Sr. Technical and Vocational
Institute (MPTVI)**, under **PESO Magsaysay, Davao del Sur**. A **real client system**,
not a demo. Private repo — **never make it public, never commit client data**. The live
DB, uploaded documents, and backups are git-ignored on purpose; they hold personal data
protected by **R.A. 10173 (PH Data Privacy Act)**.

- **Repo:** `github.com/r4yz3/mptvi-training-system` — branch **`main`**
- **Local path (this account):** `F:\PROJECTS\peso\app`
- **Author:** Justin Paelden / Adfirm.net

## Stack
Laravel 13 + Inertia + **React/TypeScript** + Tailwind. Locally-built Vite assets (no CDN).
Role-based access with a **PII firewall** (capability-gated). Zero-dependency spreadsheet
reader for imports.

## Databases (important nuance)
- **Local dev = MySQL** (Laragon), db **`mptvi`**.
- **VPS deploy (mptvi.adfirm.net) and client office install = SQLite** (self-contained).
- ⇒ **Keep every migration SQLite-compatible** even though you develop on MySQL. Avoid
  MySQL-only DDL (raw `ENUM`, `MODIFY COLUMN`, etc.).

## Run locally (this PC / the other PC)
PHP is **not** on the default PATH. Add Laragon's PHP first, then run both servers
(Inertia needs both):
```bash
# add PHP to PATH (adjust the version folder to what Laragon has installed)
export PATH="/c/laragon/bin/php/php-8.3.30-Win32-vs16-x64:$PATH"
php artisan serve --port=8000     # app  -> http://localhost:8000
npm run dev                       # Vite -> http://localhost:5173
```
First-time setup on a fresh clone: `composer install`, `npm install`,
`cp .env.example .env`, `php artisan key:generate`, create the `mptvi` MySQL db, then
`php artisan migrate --seed`.

## Seeded logins (dev)
All passwords = `password`, defined in `database/seeders/RbacSeeder.php`:
- **admin@mptvi.com** — admin (bootstrap)
- secretary@ / registrar@ / cashier@ / coordinator@ `mptvi.com` — role accounts

`RbacSeeder` is the **source of truth for roles + capabilities**. When you change caps,
re-run it (`php artisan db:seed --class=Database\Seeders\RbacSeeder --force`) — and any
deploy must reseed it too, or capability changes won't sync.

## Domain model — current surface (post 2026-07-21 simplification)
`routes/web.php` is ground truth; the README's feature list is partly stale.
- **Applicant pipeline:** Qualified → Enrolled. Paying auto-enrolls → *In training*.
  **No batches** (the Batches + Training modules were removed).
- **Programs:** two types — **School** and **Community**.
- **Grading:** **Major/Minor numeric GWA, 1.00–5.00** (registrar enters on the trainee
  profile; "Report of Grades" print). Subjects are defined in **Settings → Subjects**.
  This replaced the old TESDA competency grading.
- **Assessment:** manual result **Competent / Not Yet Competent** on the profile.
  **Certificate printing was removed.**
- **Collections / fees:** categories = Misc fee / School uniform / Assessment fee /
  Others (specify). **Per-program, per-school-year fee schedule** in **Settings → Fees**.
  Single-year school year. Receipt = **quarter-page 2-up** (CN- control no., COPY watermark).
- **Import module:** Excel/CSV for **trainees + grades**. Flow = template → validate →
  preview → confirm. Zero-dependency `SpreadsheetReader`.
- **Cashier finance privacy:** the cashier role has **no `finance.view`** — no ₱ analytics
  on the dashboard.
- **Documents:** note-only docs, with an **admin-approved download queue**.

## Testing
`php artisan test` — **188 tests, keep them green** before committing.

## Useful artisan commands
```bash
php artisan app:check        # installation health check (OK/WARN/FAIL + fixes)
php artisan user:password    # reset a login password (lists accounts, prompts)
php artisan backup:run       # encrypted backup now (uses BACKUP_PASSWORD)
php artisan images:optimize  # re-compress uploaded images
```

## Deploy targets
- **VPS:** `mptvi.adfirm.net` (SQLite). See `DEPLOY.md` + `deploy/`.
- **Client office:** LAN install via `deploy\local\setup.bat` (SQLite, one-run installer).
  Updating an installed copy: `git pull` → `composer install --no-dev -o` →
  `npm ci && npm run build` → **`php artisan migrate --force`** → reseed `RbacSeeder` →
  `php artisan optimize`. Client PCs still need `php artisan migrate` after new migrations.

## Gotchas / rules
- Never commit `.env`, `database/database.sqlite`, or `storage/` contents (PII, git-ignored).
- Migrations must run on **SQLite** (prod/client) even though dev is MySQL.
- PHP isn't on PATH — prefix commands with the Laragon PHP path.
- Client defines **Subjects** (Settings → Subjects) before grading / grade-import works.
- README's "What's inside" still mentions batches / certificates / ID cards — **stale**;
  trust `routes/web.php` and this file.
