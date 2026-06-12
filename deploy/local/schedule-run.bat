@echo off
REM ============================================================
REM  MPTVI — Laravel scheduler tick (drives the daily backup).
REM  Schedule this in Windows Task Scheduler to run EVERY 1 MINUTE.
REM  This honors the backup time set in Settings -> Backups.
REM
REM  EDIT the two paths below to match this PC:
REM   - PHP  : the php.exe inside your Laragon install
REM   - APP  : the app folder (the one containing "artisan")
REM ============================================================

set "PHP=C:\laragon\bin\php\php-8.3\php.exe"
set "APP=D:\mptvi"

cd /d "%APP%"
"%PHP%" artisan schedule:run >> "%APP%\storage\logs\schedule.log" 2>&1
