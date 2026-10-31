@echo off
title MotoCar Custom - Laravel Backend Server
echo ============================================================
echo Starting Laravel Backend (PHP 8.3) on http://127.0.0.1:8000
echo ============================================================
php "%~dp0backend\artisan" serve --host=127.0.0.1 --port=8000
pause
