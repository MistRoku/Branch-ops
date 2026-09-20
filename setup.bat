@echo off
REM Cross-platform setup for Windows. Linux/macOS users: run ./setup.sh instead.
cd /d "%~dp0"

echo ==^> composer install (Horizon needs pcntl/posix, absent on Windows -- ignored locally)
call composer install --no-interaction --ignore-platform-reqs
if errorlevel 1 exit /b %errorlevel%

echo ==^> .env
if not exist .env copy .env.example .env
call php artisan key:generate --force

echo ==^> database
call php artisan migrate --force
if errorlevel 1 exit /b %errorlevel%

echo ==^> frontend (requires Node.js 20+ on PATH)
call npm install --no-audit --no-fund
if errorlevel 1 exit /b %errorlevel%
call npm run build
if errorlevel 1 exit /b %errorlevel%

echo ==^> done. Start with: php artisan serve
