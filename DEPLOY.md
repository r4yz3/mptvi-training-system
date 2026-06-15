# MPTVI Training Management System — Deployment Guide

Target: **https://projec06092026.adfirm.net** on the existing **CloudPanel VPS (161.97.78.97)**.
Stack: Laravel 13 + Inertia/React + MySQL, PHP 8.3.

> ⚠️ The static demo currently lives at this domain. Deploying the real app **replaces** it.
> Back up the demo first (`index.html`) and only proceed when Justin authorizes.

---

## 1. One-time server setup (CloudPanel)

1. **Create the site** — CloudPanel → Sites → Add Site → **PHP site**, PHP **8.3**, domain `projec06092026.adfirm.net`.
   (This may replace the existing static site, or use a new site user and repoint the domain.)
2. **Point the web root at `public/`** — CloudPanel → Site → Vhost: the document root must be the app's
   `public/` directory (Laravel serves from there). Confirm the nginx root is `…/projec06092026.adfirm.net/public`.
3. **Create the database** — CloudPanel → Databases → Add → MySQL DB `mptvi` + user `mptvi` (note the password).
4. **Toolchain** — ensure `php 8.3`, `composer`, and `node`/`npm` are available for the site user
   (CloudPanel ships Node; otherwise `nvm install --lts`).

## 2. First deploy

```bash
# as the site user, in the site root (the dir that contains public/)
git clone <repo> .            # or scp the project up (exclude vendor/, node_modules/, .env)

cp .env.production.example .env
# edit .env → set DB_PASSWORD, MAIL_*, and confirm APP_URL

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force

# Seed ONLY production-safe data (roles/permissions/staff + the 7 real programs):
php artisan db:seed --class=Database\\Seeders\\RbacSeeder --force
php artisan db:seed --class=Database\\Seeders\\ProgramSeeder --force
#  ↑ do NOT run the full DatabaseSeeder in prod — it inserts demo applicants/payments/events.

npm ci && npm run build
php artisan storage:link
php artisan config:cache route:cache view:cache

# permissions
chmod -R ug+rw storage bootstrap/cache
```

## 3. First login & lock-down

`RbacSeeder` creates five bootstrap accounts (`admin@peso.com` … password `password`).
**Immediately after first login as admin:**
1. Go to **Users** → create the real staff accounts with strong passwords.
2. Change the admin account's email/password (or create a new admin and delete the seeded one).
3. Delete the remaining seeded demo accounts (`secretary@/registrar@/cashier@/coordinator@peso.com`).

## 4. Cloudflare

Domain is already orange-proxied. Ensure SSL mode is **Full (strict)** and that the origin has a valid
Let's Encrypt cert (CloudPanel → Site → SSL/TLS → issue). The app forces HTTPS + trusts the proxy in production.

## 5. Subsequent updates

```bash
git pull            # or re-upload changed files
bash deploy/deploy.sh
```

`deploy/deploy.sh` runs maintenance-mode → composer → npm build → migrate → cache rebuild → up.

## 6. Notes / future

- **Queue**: nothing requires a worker yet. When background jobs are added, run
  `php artisan queue:work` under supervisor.
- **Real-time Messages**: currently 5-second polling. Laravel Reverb (websockets) is the planned upgrade —
  needs a Reverb process + nginx WS proxy.
- **Backups**: schedule `mysqldump mptvi` + a copy of `storage/app/private` (learner documents, PII).
- **DPA**: document files are on the private disk and only served via the authenticated, audited
  `/document-files/{id}/download` route — never web-served. Keep `storage/app/private` outside the web root.
