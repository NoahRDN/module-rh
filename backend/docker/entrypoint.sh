#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

run_as_app() {
  if [[ "$(id -u)" -eq 0 ]] && command -v gosu >/dev/null 2>&1; then
    gosu app "$@"
    return
  fi
  "$@"
}

# 1) .env
if [[ ! -f .env && -f .env.example ]]; then
  cp .env.example .env
fi

# 2) Ensure dirs exist (storage is a named volume in docker-compose)
mkdir -p \
  bootstrap/cache \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  vendor

# 3) Fix permissions for mounted volumes (IMPORTANT)
if [[ "$(id -u)" -eq 0 ]]; then
  chown -R app:app storage bootstrap/cache vendor || true
fi

# 4) Run composer as app
if [[ ! -f vendor/autoload.php ]]; then
  set +e
  run_as_app composer install --no-interaction --prefer-dist
  status=$?
  set -e

  if [[ $status -ne 0 ]]; then
    echo "composer install failed (exit $status); clearing temp zips/cache and retrying once..." >&2
    rm -f vendor/composer/tmp-*.zip 2>/dev/null || true
    run_as_app composer clear-cache || true
    run_as_app composer install --no-interaction --prefer-dist
  fi
fi

# 5) APP_KEY
if [[ -f .env ]] && ! grep -qE '^APP_KEY=base64:' .env; then
  run_as_app php artisan key:generate
fi

# 6) Run the container command as app
if [[ "$(id -u)" -eq 0 ]] && command -v gosu >/dev/null 2>&1; then
  exec gosu app "$@"
fi
exec "$@"
