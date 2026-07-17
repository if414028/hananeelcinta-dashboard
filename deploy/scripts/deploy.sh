#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/hananeel-cinta/current}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
NPM_BIN="${NPM_BIN:-/usr/bin/npm}"

cd "$APP_DIR"

"$PHP_BIN" artisan down --retry=60 --refresh=15
trap '"$PHP_BIN" artisan up' EXIT

"$COMPOSER_BIN" install --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$NPM_BIN" ci
"$NPM_BIN" run build

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart

"$PHP_BIN" artisan up
trap - EXIT

echo "Deployment completed: $APP_DIR"
