@echo off
title MotoCar Custom - Laravel Backend Server
echo ============================================================
echo Starting Laravel Backend (PHP 8.3) on http://127.0.0.1:8000
echo ============================================================
set "PHP_CMD=php"
if exist "C:\Program Files\php-8.3.14\php.exe" (
    set "PHP_CMD=C:\Program Files\php-8.3.14\php.exe"
) else if exist "C:\php-8.3.14\php-8.3.14\php.exe" (
    set "PHP_CMD=C:\php-8.3.14\php-8.3.14\php.exe"
)

"%PHP_CMD%" "%~dp0backend\artisan" serve --host=127.0.0.1 --port=8000
pause
