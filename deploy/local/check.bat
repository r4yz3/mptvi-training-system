@echo off
setlocal enabledelayedexpansion
title MPTVI - Health check

REM Runs the installation health check. Double-click, or run from the
REM Laragon Terminal. Reports PHP/extensions/.env/database/migrations/
REM storage/assets/backups as OK / WARN / FAIL.

cd /d "%~dp0..\.."

where php >nul 2>&1
if errorlevel 1 (
    for /d %%D in ("C:\laragon\bin\php\*") do if exist "%%D\php.exe" set "PATH=%%D;!PATH!"
)
where php >nul 2>&1
if errorlevel 1 (
    echo Could not find PHP. Run this from the Laragon Terminal, or install Laragon Full.
    echo.
    pause
    exit /b 1
)

php artisan app:check
echo.
pause
