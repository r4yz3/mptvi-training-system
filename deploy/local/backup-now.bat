@echo off
REM ============================================================
REM  MPTVI — run an encrypted backup immediately.
REM  Double-click any time, OR schedule daily at 5:00 PM in Task
REM  Scheduler as a simpler alternative to schedule-run.bat.
REM
REM  EDIT the two paths below to match this PC.
REM ============================================================

set "PHP=C:\laragon\bin\php\php-8.3\php.exe"
set "APP=D:\mptvi"

cd /d "%APP%"
"%PHP%" artisan backup:run
echo.
echo Done. Backups are in: %APP%\storage\backups
pause
