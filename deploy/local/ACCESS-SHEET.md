# MPTVI Training System — Access Sheet

Quick reference for signing in. **Keep this sheet private** — it lists the starter passwords, which must be changed on first login.

## How to open the system
- On any office PC: open a web browser and go to **http://mptvi.com/**
- On the server PC itself: **http://mptvi.com/** or **http://localhost/**
- A PC can only reach `mptvi.com` after `client-hostname.bat` has been run on it once (see the Monday install checklist).

## Starter accounts
Every starter account uses the password **password**. Change them immediately — see "First login" below.

| Role | Name | Email (username) | Password |
| --- | --- | --- | --- |
| Administrator | Eleonil Epracse | admin@mptvi.com | password |
| Secretary | Jane Doe | secretary@mptvi.com | password |
| Registrar | Juan dela Cruz | registrar@mptvi.com | password |
| Cashier | Jane Smith | cashier@mptvi.com | password |
| Training Coordinator | Jhon Doe | coordinator@mptvi.com | password |

## What each role can do
- **Administrator** — full access to everything, plus Users, Settings, Activity log and approving download requests.
- **Secretary** — Applicants, Screening, Programs & batches, Training & attendance, Assessment & certificates, ID system, Reports, Downloads.
- **Registrar** — Applicants, Screening, Assessment & certificates, ID system, exports/Downloads.
- **Cashier** — Cashier (record / void payments), finance totals; report exports need admin approval.
- **Training Coordinator** — Programs & batches, Training & attendance, Assessment & certificates.

Everyone signs in at the **same address**; the system automatically shows each person only the menus their role allows.

## First login — do this right away (Administrator)
1. Sign in as **admin@mptvi.com** / **password**.
2. Go to **Settings → Users** and create the real staff accounts with strong passwords.
3. **Change the admin password** (or create a new admin and delete this starter one).
4. **Delete** any starter demo accounts you are not using (secretary@ / registrar@ / cashier@ / coordinator@mptvi.com).
5. **Settings → Institution Profile / Branding / Signatories** — set the real names, logo and assessor.

## Forgot a password?
On the **server PC**, open the Laragon Terminal in the app folder and run:
```
php artisan user:password
```

## Keep in mind
- The starter password **password** is public knowledge — it only exists to get the administrator in the first time. Replace it before real use.
- A login password is **not** the BACKUP_PASSWORD (that one only decrypts backup files).
- Lost access entirely? The administrator can reset any account in **Settings → Users**, or use the `php artisan user:password` command above on the server.
