@echo off
setlocal
title MPTVI - Install backup task

REM ============================================================
REM  Registers the Windows Task Scheduler job that drives the
REM  daily backup. It ticks the Laravel scheduler every minute, so
REM  the backup runs at the time set in Settings -> Backups.
REM
REM  >>> RIGHT-CLICK this file -> "Run as administrator" <<<
REM ============================================================

net session >nul 2>&1
if not "%errorlevel%"=="0" (
    echo This must be run as Administrator.
    echo Right-click install-backup-task.bat  ^->  Run as administrator.
    echo.
    pause
    exit /b 1
)

set "TASK=MPTVI Backup Scheduler"
set "RUNNER=%~dp0schedule-run.bat"

if not exist "%RUNNER%" (
    echo Could not find schedule-run.bat next to this script:
    echo   %RUNNER%
    pause
    exit /b 1
)

echo Task name : %TASK%
echo Runs      : %RUNNER%
echo Frequency : every 1 minute ^(fires the backup at the time set in the app^)
echo Account   : SYSTEM ^(runs even when nobody is logged in^)
echo.

schtasks /create /tn "%TASK%" /tr "\"%RUNNER%\"" /sc minute /mo 1 /ru SYSTEM /rl HIGHEST /f
if errorlevel 1 (
    echo.
    echo *** Failed to create the task ^(see the error above^). ***
    pause
    exit /b 1
)

echo.
echo ============================================================
echo   Backup task installed. It starts within a minute.
echo.
echo   Verify a run wrote to:   storage\logs\schedule.log
echo   See / run backups in:    the app -> Settings -> Backups
echo   Remove the task with:    schtasks /delete /tn "%TASK%" /f
echo ============================================================
echo.
echo NOTE: a same-PC backup won't survive the PC being lost.
echo Copy storage\backups\*.enc to a USB drive or another PC regularly.
echo.
pause
exit /b 0
