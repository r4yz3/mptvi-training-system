# MPTVI Training Management System — Local (LAN) Install Guide

Run the app on **one office PC** (the "server"); every other PC opens it in a
browser over the office network. No internet required.

- **Stack on the server:** Windows + Laragon (Apache + PHP 8.3) + SQLite
- **Other PCs need:** just a web browser — nothing installed
- **Address staff use:** `http://<server-ip>/` (e.g. `http://192.168.1.50/`)

---

## 0. Where to install — use a separate drive (D:)

Install the **app + data on a non-system drive** (e.g. `D:\mptvi`), keeping
**Laragon on C:**. Why:

- If **Windows / C: ever corrupts or is reinstalled**, the app code, the database
  (`database\database.sqlite`), all uploaded documents and the backups on **D:
  survive**. Recovery = reinstall Laragon, re-point the vhost to `D:\mptvi\public`,
  done — no data loss.
- **Best case:** D: is a *physically separate disk/SSD*, not just a partition — then
  it also survives a C: drive failure. A second partition on the *same* physical disk
  does **not** survive that disk dying — so still copy backups off the machine (USB /
  another PC). See step 8.

Get the code onto the PC and build it once (Laragon Full includes Composer + Node) —
the full commands are in the project **README** ("Install on the admin / server PC").
In short:

```bat
D:
git clone <YOUR-PRIVATE-REPO-URL> D:\mptvi
cd D:\mptvi
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

> Never copy a previous `.env` or `database\database.sqlite` between installs — each
> install gets fresh ones below.

---

## 1. Install Laragon (one time)

1. Install **Laragon Full** (ships Apache + PHP). Ensure **PHP 8.3+**
   (Laragon → Menu → PHP → Version).
2. Make sure the **SQLite** and **GD** PHP extensions are enabled (they are in
   Laragon's default PHP). GD powers the image optimization.

## 2. Configure the site

1. Copy `deploy\local\apache-vhost.conf` to
   `C:\laragon\etc\apache2\sites-enabled\mptvi.conf`
   (its `DocumentRoot` already points at `D:\mptvi\public` — adjust if you used a
   different drive/folder).
2. Laragon → Menu → **Apache → Reload**.

## 3. Configure the app

### One-click (recommended)

In the app folder, run **`deploy\local\setup.bat`** (double-click, or from the Laragon
Terminal). On the **first** run it creates `.env` from the template and opens it — set
`APP_URL` + `BACKUP_PASSWORD`, save, then **run `setup.bat` again** to finish. It does
composer install, npm build, key generate, database create + migrate + seed (RBAC +
Programs), storage link, optimize, installs the Apache vhost, and adds the firewall rule.
Then skip to **step 4**. (Steps 2 and 5 are handled by the script; static IP and the
backup task are still manual — steps 6 and 8.)

### Or do it manually

Open a terminal in `D:\mptvi` (Laragon → Menu → Terminal, then `cd /d D:\mptvi`), then:

```bat
copy deploy\local\env.local.example .env
```

Edit `.env`:
- Set **`APP_URL`** to the server PC's LAN address (you'll set a static IP in step 6) — e.g. `http://192.168.1.50`
- Set **`BACKUP_PASSWORD`** to a long random secret (and store it somewhere off this PC)

Then:

```bat
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate --force
php artisan db:seed --class=Database\Seeders\RbacSeeder --force
php artisan db:seed --class=Database\Seeders\ProgramSeeder --force
php artisan storage:link
php artisan optimize
```

> Seed only **RbacSeeder** (roles + bootstrap staff) and **ProgramSeeder**
> (the real qualifications). Do **not** run the full `DatabaseSeeder` — it adds
> demo applicants/payments.

Confirm it works on the server itself: open `http://localhost/` → you should see the login page.

## 4. First login & lock-down (do this immediately)

Log in with `admin@mptvi.test` / `password`, then:
1. **Users** → create the real staff accounts with strong passwords.
2. Change the admin account's email + password (or make a new admin and delete the seeded one).
3. Delete the other seeded demo accounts (`secretary@/registrar@/cashier@/coordinator@mptvi.test`).
4. **Settings → Institution Profile / Branding / Signatories** → set the real names, logos and assessor.

## 5. Make it reachable from other PCs (firewall)

On the server PC, allow the web port through Windows Firewall (run as Administrator):

```bat
netsh advfirewall firewall add rule name="MPTVI" dir=in action=allow protocol=TCP localport=80
```

(Use the matching port if you changed it to 8080 in the vhost.)

## 6. Give the server PC a fixed address (static IP)

So the URL never changes:
- **Easiest:** in your router's DHCP settings, add a **reservation** for this PC's MAC address.
- **Or:** Windows → Settings → Network → Ethernet/Wi-Fi → IP assignment → **Manual**,
  set a fixed IPv4 (e.g. `192.168.1.50`), matching subnet/gateway/DNS.

Find the current IP with: `ipconfig` (look at *IPv4 Address*).

Make sure `.env` `APP_URL` matches this IP, then re-run `php artisan optimize`.

## 7. Tell the office the URL

From any other PC on the same network, open:

```
http://192.168.1.50/
```

(substitute the server's static IP). Bookmark it on every staff PC.

## 8. Automatic daily backups

The app's **Settings → Backups** page lets the admin set the daily time (default 5:00 PM)
and run a backup on demand. To make the daily run fire on Windows:

**One-click (recommended):**
1. First edit the two paths inside `deploy\local\schedule-run.bat` (PHP + APP) if they
   differ from the defaults.
2. **Right-click `deploy\local\install-backup-task.bat` → Run as administrator.**
   It registers a Task Scheduler job that ticks the scheduler every minute, so the
   backup fires at the time set in the app. (Remove it later with
   `schtasks /delete /tn "MPTVI Backup Scheduler" /f`.)

**Simpler alternative (fixed 5 PM, ignores the UI time):**
- Schedule `deploy\local\backup-now.bat` to run **Daily at 5:00 PM** in Task Scheduler.

Backups land in `storage\backups\` (encrypted if `BACKUP_PASSWORD` is set) and are
visible/downloadable in **Settings → Backups**. Old ones auto-prune (14 daily + 8 weekly).

> **Copy backups off the PC regularly.** A backup on the same machine won't help if the
> PC dies. Periodically copy `storage\backups\*.enc` to a USB drive or another computer.

## 9. Keep it running

- The **server PC must be on** whenever staff need the system.
- Laragon's Apache should **auto-start** — Laragon → Menu → Preferences → "Run Laragon when Windows starts" and "Start all services automatically".
- After a Windows restart, confirm `http://localhost/` loads.

---

## Updating to a newer version later

1. Build the new version locally (`composer install --no-dev`, `npm run build`).
2. On the server: **back up first** (run `backup-now.bat`).
3. Copy the new files over the old folder **except** `.env`, `database\database.sqlite`,
   and `storage\` (these hold the live config + data).
4. In the app folder: `php artisan migrate --force` then `php artisan optimize`.

## Quick troubleshooting

- **Other PCs can't connect:** firewall rule (step 5), server static IP (step 6), and all PCs on the same network/router. Test `http://localhost/` on the server first.
- **"419 Page Expired" on login:** make sure `APP_URL` matches the address staff actually type, then `php artisan optimize`.
- **Redirects to https and fails:** `APP_ENV` must be `local` (not `production`) in `.env`.
- **Blank page / 500:** check `storage\logs\laravel.log`; ensure `storage\` and `bootstrap\cache\` are writable.
