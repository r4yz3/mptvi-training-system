# MPTVI Training Management System — local dev launcher
# Boots the full stack on this machine (Laragon-bundled PHP/MySQL, no system PATH needed).
# Usage:  powershell -ExecutionPolicy Bypass -File .\dev-start.ps1
# Then open http://127.0.0.1:8000  (login)

$ErrorActionPreference = 'Stop'

# --- Laragon bundled toolchain ---
$PHP      = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64'
$COMPOSER = 'C:\laragon\bin\composer'
$NODE     = 'C:\laragon\bin\nodejs\node-v22'
$MYSQLDIR = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64'
$env:PATH = "$PHP;$COMPOSER;$NODE;$MYSQLDIR\bin;$env:PATH"

$APP = $PSScriptRoot
Set-Location $APP

# --- 1. Ensure MySQL is running ---
$mysqlUp = (Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue).TcpTestSucceeded
if (-not $mysqlUp) {
    Write-Host '[mysql] starting mysqld...' -ForegroundColor Cyan
    Start-Process -FilePath "$MYSQLDIR\bin\mysqld.exe" `
        -ArgumentList "--datadir=`"$MYSQLDIR\data`"","--basedir=`"$MYSQLDIR`"" -WindowStyle Hidden
    do { Start-Sleep -Seconds 1 } until ((Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue).TcpTestSucceeded)
    Write-Host '[mysql] up on 3306' -ForegroundColor Green
} else {
    Write-Host '[mysql] already running on 3306' -ForegroundColor Green
}

# --- 2. Vite dev server (HMR) in a background window ---
Write-Host '[vite] starting dev server (HMR)...' -ForegroundColor Cyan
Start-Process -FilePath 'powershell' -ArgumentList '-NoExit','-Command',"`$env:PATH='$NODE;'+`$env:PATH; Set-Location '$APP'; npm run dev"

# --- 3. Laravel app server (foreground) ---
Write-Host '[laravel] serving on http://127.0.0.1:8000' -ForegroundColor Green
php artisan serve --host=127.0.0.1 --port=8000
