@echo off
setlocal enabledelayedexpansion
title MPTVI - One-click setup

echo ============================================================
echo   MPTVI Training Management System - Setup / Update
echo ============================================================
echo.
echo Run this once to INSTALL, or again any time to UPDATE
echo (after replacing the app files with a newer copy).
echo Tip: easiest is to run it from the Laragon Terminal
echo (Laragon menu - Terminal). Double-clicking also works.
echo.

REM --- Move to the app root (this script lives in deploy\local) ---
cd /d "%~dp0..\.."
set "APPROOT=%CD%"
echo App folder: %APPROOT%
echo.

REM --- Make sure php / composer / npm are usable ---
call :ensure php       || goto :need_laragon
call :ensure composer  || goto :need_laragon
call :ensure npm       || goto :need_laragon

echo [1/8] Installing PHP dependencies (composer)...
call composer install --no-dev --optimize-autoloader --no-interaction || goto :err

echo [2/8] Installing front-end packages (npm)...
if exist "node_modules" (
    echo       node_modules present - skipping. ^(delete it to force a reinstall^)
) else (
    call npm ci || goto :err
)

echo [3/8] Building the front-end...
call npm run build || goto :err

echo [4/8] Environment file...
if not exist ".env" (
    copy "deploy\local\env.local.example" ".env" >nul
    echo       Created .env from the local template.
) else (
    echo       .env already present - keeping it.
)

echo [5/8] Application key...
findstr /r /c:"^APP_KEY=." ".env" >nul 2>&1
if errorlevel 1 (
    call php artisan key:generate --force || goto :err
) else (
    echo       key already set - skipping.
)

echo [6/8] Backup password...
findstr /r /c:"^BACKUP_PASSWORD=." ".env" >nul 2>&1
if errorlevel 1 (
    REM Auto-generate a strong random backup password and save a copy the
    REM office can store OFF this PC (an unknowable password = no restores).
    for /f "usebackq delims=" %%K in (`powershell -NoProfile -Command "$a=[char[]](48..57)+[char[]](65..90)+[char[]](97..122); -join (Get-Random -InputObject $a -Count 40)"`) do set "BPW=%%K"
    powershell -NoProfile -Command "(Get-Content '.env') -replace '^BACKUP_PASSWORD=.*', 'BACKUP_PASSWORD=!BPW!' | Set-Content -Encoding ascii '.env'" >nul 2>&1
    > "BACKUP-PASSWORD-KEEP-SAFE.txt" echo MPTVI encrypted-backup password ^(generated %DATE% %TIME%^):
    >> "BACKUP-PASSWORD-KEEP-SAFE.txt" echo !BPW!
    >> "BACKUP-PASSWORD-KEEP-SAFE.txt" echo.
    >> "BACKUP-PASSWORD-KEEP-SAFE.txt" echo Store this somewhere OTHER than this PC. Without it, encrypted backups cannot be restored.
    echo       Generated a backup password - saved to BACKUP-PASSWORD-KEEP-SAFE.txt
    echo       ^>^>^> Move that file to a safe place ^(USB / cloud^), then delete it here. ^<^<^<
) else (
    echo       backup password already set - skipping.
)

echo [7/8] Database + migrations...
set "NEWDB="
if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    set "NEWDB=1"
)
call php artisan migrate --force || goto :err

echo       Syncing roles + permissions (RbacSeeder - safe to re-run)...
call php artisan db:seed --class="Database\Seeders\RbacSeeder" --force || goto :err
if defined NEWDB (
    echo       Fresh database - seeding the program catalog...
    call php artisan db:seed --class="Database\Seeders\ProgramSeeder" --force || goto :err
) else (
    echo       Existing database - programs/data left untouched.
)

echo [8/8] Finalize ^(storage link + optimize^)...
call php artisan storage:link >nul 2>&1
call php artisan optimize || goto :err

REM ---- Best-effort extras (won't fail the setup) ----
echo.
echo Extras:
set "VHOST=C:\laragon\etc\apache2\sites-enabled"
if exist "%VHOST%\" (
    copy /y "deploy\local\apache-vhost.conf" "%VHOST%\mptvi.conf" >nul 2>&1 && echo   - Apache vhost installed. Reload Apache in Laragon ^(Menu - Apache - Reload^).
) else (
    echo   - Laragon vhost folder not found; add deploy\local\apache-vhost.conf manually.
)

set "ADMIN=0"
net session >nul 2>&1 && set "ADMIN=1"

REM --- Make http://peso.com resolve on THIS (server) PC ---
set "HOSTS=%SystemRoot%\System32\drivers\etc\hosts"
findstr /i /c:"peso.com" "%HOSTS%" >nul 2>&1
if "%errorlevel%"=="0" (
    echo   - Hosts: peso.com already mapped.
) else if "%ADMIN%"=="1" (
    >>"%HOSTS%" echo 127.0.0.1    peso.com
    ipconfig /flushdns >nul 2>&1
    echo   - Hosts: mapped peso.com -^> 127.0.0.1 on this PC.
) else (
    echo   - Hosts NOT set ^(needs admin^). To use http://peso.com here, run once in an
    echo     ADMIN terminal:  echo 127.0.0.1    peso.com ^>^> "%HOSTS%"
)

REM --- Start Laragon (Apache + the app) automatically when the PC boots ---
if exist "C:\laragon\laragon.exe" (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$ws=New-Object -ComObject WScript.Shell; $lnk=$ws.CreateShortcut((Join-Path ([Environment]::GetFolderPath('Startup')) 'Laragon.lnk')); $lnk.TargetPath='C:\laragon\laragon.exe'; $lnk.Arguments='start'; $lnk.WorkingDirectory='C:\laragon'; $lnk.Save()" >nul 2>&1 && echo   - Auto-start: Laragon will launch and start Apache when this PC boots. || echo   - Auto-start NOT set; enable it in Laragon - Preferences ^(Run Laragon + start services on startup^).
) else (
    echo   - Laragon not found at C:\laragon; enable auto-start in Laragon - Preferences.
)

if "%ADMIN%"=="1" (
    netsh advfirewall firewall add rule name="MPTVI" dir=in action=allow protocol=TCP localport=80 >nul 2>&1 && echo   - Firewall: allowed inbound TCP 80.
) else (
    echo   - Firewall NOT set ^(needs admin^). To let other PCs connect, run once in an
    echo     ADMIN terminal:  netsh advfirewall firewall add rule name="MPTVI" dir=in action=allow protocol=TCP localport=80
)

echo.
echo ============================================================
echo   SETUP COMPLETE
echo.
echo   Open on this PC:   http://peso.com/   ^(or http://localhost/^)
echo   Log in:            admin@peso.com  /  password
echo.
if defined NEWDB (
    echo   FIRST-TIME to-do ^(see deploy\local\INSTALL-LOCAL.md^):
    echo     1. Reload Apache in Laragon if the vhost was just installed
    echo     2. Give this PC a STATIC IP so peso.com stays put
    echo     3. On every OTHER office PC, run deploy\local\client-hostname.bat as admin
    echo     4. Right-click deploy\local\install-backup-task.bat - Run as administrator
    echo     5. Log in, create real staff, then DELETE the demo accounts
    echo     6. Move BACKUP-PASSWORD-KEEP-SAFE.txt off this PC, then delete it
) else (
    echo   Update applied: files rebuilt, database migrated, permissions re-synced.
)
echo ============================================================
echo.
pause
exit /b 0

:err
echo.
echo *** Setup FAILED at the step above. Fix the error shown, then re-run setup.bat. ***
echo.
pause
exit /b 1

:need_laragon
echo.
echo Could not find php / composer / npm.
echo Install "Laragon Full", or run this script from the Laragon Terminal
echo (Laragon menu - Terminal), then try again.
echo.
pause
exit /b 1

REM ---- subroutine: ensure a tool is on PATH, adding Laragon bins if needed ----
:ensure
where %1 >nul 2>&1 && exit /b 0
if /i "%1"=="php" (
    for /d %%D in ("C:\laragon\bin\php\*") do if exist "%%D\php.exe" set "PATH=%%D;!PATH!"
)
if /i "%1"=="composer" (
    if exist "C:\laragon\bin\composer" set "PATH=C:\laragon\bin\composer;!PATH!"
)
if /i "%1"=="npm" (
    if exist "C:\laragon\bin\nodejs\node.exe" set "PATH=C:\laragon\bin\nodejs;!PATH!"
    for /d %%D in ("C:\laragon\bin\nodejs\*") do if exist "%%D\npm.cmd" set "PATH=%%D;!PATH!"
)
where %1 >nul 2>&1 && exit /b 0
exit /b 1
