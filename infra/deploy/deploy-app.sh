#!/usr/bin/env bash
set -euo pipefail

APP_SERVER="${APP_SERVER:-deploy@167.233.46.114}"
REMOTE_PATH="/var/www/tvf"

echo "==> Deploy Laravel app to ${APP_SERVER}"

rsync -avz --delete \
    --exclude '.env' \
    --exclude 'node_modules' \
    --exclude 'storage/logs/*' \
    --exclude '.git' \
    ./app/ "${APP_SERVER}:${REMOTE_PATH}/"

ssh "${APP_SERVER}" "cd ${REMOTE_PATH} && \
    composer install --no-dev --optimize-autoloader && \
    npm ci && npm run build && \
    php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan tvf:sync-tracker-config"

echo "==> Deploy complete"
