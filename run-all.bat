@echo off
title MotoCar Custom - Start All Services
echo ============================================================
echo Starting MotoCar Backend and Frontend...
echo Backend API : http://127.0.0.1:8000
echo Frontend Web: http://localhost:5173
echo ============================================================
start "MotoCar - Backend API" "%~dp0run-backend.bat"
start "MotoCar - Frontend" "%~dp0run-frontend.bat"
