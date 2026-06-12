# MPTVI Training System — Monday Install Checklist

For setting up the app on the **admin / server PC** (Windows). Other office PCs will
just open it in a browser — nothing to install on them.

## Before you start — have these ready
- The **server PC** (Windows) that will stay switched on.
- A **static IP** for it, e.g. `192.168.1.50` (ask whoever manages the office router, or pick an unused address).
- A **BACKUP_PASSWORD** — a long random secret. Write it down and keep it **off this PC**.
- **GitHub access** to the private repo (be signed in), or the downloaded ZIP.

---

## Step 1 — Install Laragon
Download and install **Laragon (Full)**. Make sure PHP is **8.3+** (Laragon menu → PHP → Version).

## Step 2 — Get the code onto the D: drive
Open the Laragon Terminal (Laragon → Menu → Terminal), then:
```
D:
git clone https://github.com/r4yz3/mptvi-training-system.git D:\mptvi
cd /d D:\mptvi
```
(Or download the ZIP from GitHub and extract it to `D:\mptvi`.)

## Step 3 — Run the setup
Run `deploy\local\setup.bat` (double-click, or type it in the terminal).
It creates `.env` and opens it in Notepad — set these two values:
```
APP_URL=http://192.168.1.50      (your static IP)
BACKUP_PASSWORD=your-long-secret
```
Save and close Notepad, then **run `setup.bat` again** to finish.

## Step 4 — Reload Apache and test
Laragon → Menu → **Apache → Reload**. On this PC, open **http://localhost/** —
you should see the login page.

## Step 5 — Give the PC a static IP
Windows Settings → Network → (Ethernet/Wi-Fi) → IP assignment → **Manual** → set the
IPv4 you chose (or add a DHCP reservation in the router). Make sure `.env` `APP_URL`
matches it, then run:
```
php artisan optimize
```

## Step 6 — Allow other PCs through the firewall
`setup.bat` already added this **if** you ran it as administrator. If not, open an
**Administrator** terminal and run:
```
netsh advfirewall firewall add rule name="MPTVI" dir=in action=allow protocol=TCP localport=80
```

## Step 7 — Turn on automatic daily backups
Right-click `deploy\local\install-backup-task.bat` → **Run as administrator**.

## Step 8 — Verify everything
Run `deploy\local\check.bat` (or `php artisan app:check`). Everything should read **OK**.

## Step 9 — First login and lock-down (in the browser)
Open **http://localhost/** and log in: **admin@mptvi.test** / **password**. Then:
- Settings → **Users**: create the real staff accounts (strong passwords).
- **Change the admin password** (or make a new admin and delete the seeded one).
- **Delete** the demo accounts: secretary@, registrar@, cashier@, coordinator@mptvi.test.
- Settings → **Institution Profile / Branding / Signatories**: set the real names, logos, assessor.

## Step 10 — Connect the office
On every other PC, open **http://192.168.1.50/** (your static IP) and bookmark it.

---

## After go-live — good to know
- **Customize the form:** Settings → **Form Builder**. Hide a field you don't need
  (pencil → untick "Show this field on the form"), or hide a whole section with its
  Shown/Hidden toggle. The 5 padlocked fields (Last name, First name, Barangay, Contact
  no., Sex) can be renamed but not hidden.
- **Certificate assessor:** set the default in Settings → **Signatories**. You can
  override it per student in **Assessment & certs** (pencil in the *Assessor* column) —
  Admin, Secretary, Registrar and Training Coordinator can edit it.
- **Backups:** view, run and download them in Settings → **Backups**; the daily time is
  set there too. Run `deploy\local\install-backup-task.bat` (as admin) once so they run
  automatically.
- **Health check anytime:** `php artisan app:check` (or `deploy\local\check.bat`)
  diagnoses the install and points to any fix.
- **Forgot a login password?** On the server: `php artisan user:password`

---

## Keep in mind
- The **server PC must be ON** whenever staff use the system. Enable Laragon auto-start (Laragon → Preferences).
- **Copy backups off this PC** regularly: `storage\backups\*.enc` → a USB drive or another computer.
- **Forgot a login password?** On the server: `php artisan user:password`
- **BACKUP_PASSWORD** only decrypts backup files — keep it safe and off this PC. It is **not** the login password.
