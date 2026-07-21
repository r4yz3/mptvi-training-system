@echo off
setlocal enabledelayedexpansion
title MPTVI - Point mptvi.com at the server (run on each office PC)

REM ============================================================
REM  Run this ONCE on every OTHER office PC (not the server) so
REM  that http://mptvi.com/ opens the MPTVI app on the server PC.
REM  It adds one line to the Windows "hosts" file mapping
REM  mptvi.com -> the server's IP address.
REM
REM  >>> RIGHT-CLICK this file -> "Run as administrator" <<<
REM ============================================================

net session >nul 2>&1
if not "%errorlevel%"=="0" (
    echo This must be run as Administrator.
    echo Right-click client-hostname.bat  ^->  Run as administrator.
    echo.
    pause
    exit /b 1
)

set "HOSTS=%SystemRoot%\System32\drivers\etc\hosts"

echo This points  http://mptvi.com/  at the MPTVI server PC.
echo.
set "SERVERIP=%~1"
if "%SERVERIP%"=="" set /p "SERVERIP=Enter the server PC's IP address (e.g. 192.168.1.50): "

if "%SERVERIP%"=="" (
    echo No IP entered. Nothing changed.
    echo.
    pause
    exit /b 1
)

REM --- Remove any existing mptvi.com line, then add the fresh one ---
findstr /i /v /c:"mptvi.com" "%HOSTS%" > "%TEMP%\hosts.mptvi" 2>nul
copy /y "%TEMP%\hosts.mptvi" "%HOSTS%" >nul 2>&1
del "%TEMP%\hosts.mptvi" >nul 2>&1

>>"%HOSTS%" echo %SERVERIP%    mptvi.com
ipconfig /flushdns >nul 2>&1

echo.
echo ============================================================
echo   Done. mptvi.com now points at %SERVERIP% on this PC.
echo.
echo   Open:  http://mptvi.com/   and bookmark it.
echo   (If the server's IP ever changes, run this again.)
echo ============================================================
echo.
pause
exit /b 0
