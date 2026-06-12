#!/usr/bin/env bash
# MPTVI Training Management System — production deploy/update script.
# Run on the CloudPanel box from the site root after pulling/uploading new code.
# Usage:  bash deploy/deploy.sh
set -euo pipefail

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
