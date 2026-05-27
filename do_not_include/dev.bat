@echo off
cd /d "%~dp0.."

echo Installing dependencies (with dev)...
composer install
if %ERRORLEVEL% neq 0 (
    echo ERROR: composer install failed.
    exit /b 1
)

echo Done. Dev environment ready.
