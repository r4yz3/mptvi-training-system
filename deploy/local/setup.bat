@echo off
setlocal enabledelayedexpansion
title MPTVI - One-click setup

echo ============================================================
echo   MPTVI Training Management System - Setup
echo ============================================================
echo.
echo Tip: easiest is to run this from the Laragon Terminal
echo (Laragon menu - Terminal). Double-clicking also works -
echo this script will try to locate Laragon's PHP/Composer/Node.
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
    echo.
    echo   ^>^>^> Created .env. APP_URL is already set to http://peso.com -
    echo       just set a BACKUP_PASSWORD. Then run setup.bat again. ^<^<^<
    echo.
    start "" notepad ".env"
    pause
    exit /b 0
)

echo [5/8] Application key...
findstr /r /c:"^APP_KEY=." ".env" >nul 2>&1
if errorlevel 1 (
    call php artisan key:generate --force || goto :err
) else (
    echo       key already set - skipping.
)

echo [6/8] Database...
set "NEWDB="
if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    set "NEWDB=1"
)
call php artisan migrate --force || goto :err

echo [7/8] Seed roles + programs...
if defined NEWDB (
    call php artisan db:seed --class=Database\Seeders\RbacSeeder --force || goto :err
    call php artisan db:seed --class=Database\Seeders\ProgramSeeder --force || goto :err
) else (
    echo       existing database - skipping seed.
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
echo   Test on this PC:   http://peso.com/   ^(or http://localhost/^)
echo.
echo   Still to do by hand ^(see deploy\local\INSTALL-LOCAL.md^):
echo     1. Reload Apache in Laragon (if the vhost was just installed)
echo     2. Give this PC a STATIC IP (so peso.com always points the same place)
echo     3. On every OTHER office PC, run deploy\local\client-hostname.bat
echo        (as administrator) so http://peso.com/ reaches this server
echo     4. Right-click deploy\local\install-backup-task.bat - Run as administrator
echo     5. Open the site, log in (admin@peso.com / password),
echo        create real staff and DELETE the demo accounts
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
