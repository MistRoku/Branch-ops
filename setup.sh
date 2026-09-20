#!/usr/bin/env bash
# Cross-platform setup for Linux/macOS. Windows users: run setup.bat instead.
set -euo pipefail
cd "$(dirname "$0")"

echo "==> composer install"
composer install --no-interaction

echo "==> .env"
[ -f .env ] || cp .env.example .env
php artisan key:generate --force

echo "==> database"
if [ "${DB_CONNECTION:-}" = "sqlite" ] || grep -q "^DB_CONNECTION=sqlite" .env; then
  mkdir -p database
  touch database/database.sqlite
fi
php artisan migrate --force

echo "==> frontend"
if ! command -v node >/dev/null 2>&1; then
  echo "Node.js >= 20 is required: https://nodejs.org" >&2
  exit 1
fi
npm install --no-audit --no-fund
npm run build

echo "==> done. Start with: php artisan serve"
