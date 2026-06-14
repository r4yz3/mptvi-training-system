#!/usr/bin/env bash
# MPTVI Training Management System — production deploy/update script.
# Run on the CloudPanel box from the site root after pulling/uploading new code.
# Usage:  bash deploy/deploy.sh
set -euo pipefail

# --- Safety checks: never deploy with debug ON or a non-production env ---
echo "==> Pre-flight (production env safety)"
if [ ! -f .env ]; then
    echo "ERROR: .env not found in $(pwd). Create it from .env.production.example first." >&2
    exit 1
fi
if grep -qiE '^[[:space:]]*APP_DEBUG[[:space:]]*=[[:space:]]*("?)true\1' .env; then
    echo "ERROR: APP_DEBUG=true in .env — refusing to deploy with debug ON (it can leak" >&2
    echo "       stack traces, env values and queries on error pages). Set APP_DEBUG=false" >&2
    echo "       in the server .env, then re-run this script." >&2
    exit 1
fi
if ! grep -qiE '^[[:space:]]*APP_ENV[[:space:]]*=[[:space:]]*("?)production\1' .env; then
    echo "WARNING: APP_ENV is not 'production' — HTTPS enforcement and secure cookies may be off." >&2
fi

echo "==> Maintenance mode"
php artisan down --render="errors::503" || true

echo "==> Composer (no-dev, optimized)"
composer install --no-dev --optimize-autoloader --no-interaction

# NOTE: this server has no Node/npm. Front-end assets are built LOCALLY
# (npm run build) and the compiled public/build directory is uploaded with
# the code. Do NOT run npm here.

echo "==> Database migrations"
php artisan migrate --force

echo "==> Storage symlink"
php artisan storage:link || true

echo "==> Cache config / routes / views / events"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Up"
php artisan up
echo "==> Deploy complete."
