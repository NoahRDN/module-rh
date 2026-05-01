#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Autorise plusieurs requêtes simultanées avec le serveur PHP intégré
# Utile sur Render quand le dashboard lance beaucoup d'appels API en parallèle
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

run_as_app() {
  if [[ "$(id -u)" -eq 0 ]] && command -v gosu >/dev/null 2>&1; then
    gosu app "$@"
    return
  fi

  "$@"
}

# 1) .env uniquement pour développement local
if [[ "${APP_ENV:-local}" != "production" && ! -f .env && -f .env.example ]]; then
  cp .env.example .env
fi

# 2) Créer les dossiers nécessaires
mkdir -p \
  bootstrap/cache \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  vendor

# 3) Corriger les permissions
if [[ "$(id -u)" -eq 0 ]]; then
  chown -R app:app storage bootstrap/cache vendor || true
fi

# 4) Installer Composer si vendor/autoload.php n'existe pas
if [[ ! -f vendor/autoload.php ]]; then
  set +e
  run_as_app composer install --no-interaction --prefer-dist --optimize-autoloader
  status=$?
  set -e

  if [[ $status -ne 0 ]]; then
    echo "composer install failed (exit $status); clearing temp zips/cache and retrying once..." >&2
    rm -f vendor/composer/tmp-*.zip 2>/dev/null || true
    run_as_app composer clear-cache || true
    run_as_app composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

# 5) Générer APP_KEY seulement hors production
if [[ "${APP_ENV:-local}" != "production" && -f .env ]] && ! grep -qE '^APP_KEY=base64:' .env; then
  run_as_app php artisan key:generate
fi

# 6) Nettoyer les caches Laravel pour bien prendre les variables Render
run_as_app php artisan config:clear || true
CACHE_STORE=array run_as_app php artisan cache:clear || true
run_as_app php artisan route:clear || true
run_as_app php artisan view:clear || true

# 7) Optionnel : migrations / seeders via variables Render
fresh_db=0

if [[ "${RUN_SEEDERS_ON_FRESH_DB:-false}" == "true" ]]; then
  if ! run_as_app php artisan migrate:status >/dev/null 2>&1; then
    fresh_db=1
  fi
fi

if [[ "${RUN_MIGRATIONS:-false}" == "true" ]]; then
  run_as_app php artisan migrate --force
fi

if [[ "${RUN_SEEDERS:-false}" == "true" ]]; then
  run_as_app php artisan db:seed --force
elif [[ "${RUN_SEEDERS_ON_FRESH_DB:-false}" == "true" && "${fresh_db}" == "1" ]]; then
  run_as_app php artisan db:seed --force
fi

# 8) Si aucune commande n'est fournie au container, lancer Laravel avec le serveur PHP intégré
if [[ "$#" -eq 0 ]]; then
  set -- php -S "0.0.0.0:${PORT:-8000}" -t public
fi

# 9) Lancer la commande finale
if [[ "$(id -u)" -eq 0 ]] && command -v gosu >/dev/null 2>&1; then
  exec gosu app "$@"
fi

exec "$@"
