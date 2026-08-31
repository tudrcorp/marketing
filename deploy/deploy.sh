#!/usr/bin/env bash
# Despliegue de tdg-marketing. Pensado para correrlo en el servidor,
# como root o con sudo, desde cualquier ruta:
#   bash /var/www/tdg-marketing/deploy/deploy.sh
#
# Recompila assets, cachea Laravel/Filament y recarga PHP-FPM/workers
# sin dejar el panel a medias más de unos segundos.

set -euo pipefail

TDG_APP_DIR="${TDG_APP_DIR:-/var/www/tdg-marketing}"
TDG_DEPLOY_USER="${TDG_DEPLOY_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.4}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"

if [[ ! -f "${TDG_APP_DIR}/artisan" ]]; then
    echo "No encuentro artisan en ${TDG_APP_DIR}. Clona el repo primero." >&2
    exit 1
fi

if [[ ! -f "${TDG_APP_DIR}/.env" ]]; then
    echo "Falta ${TDG_APP_DIR}/.env. Copia deploy/env.production.example y rellénalo." >&2
    exit 1
fi

if grep -Eq '^DB_CONNECTION=sqlite' "${TDG_APP_DIR}/.env"; then
    echo "SQLite está prohibido en este servidor (HDD + concurrencia). Usa MySQL." >&2
    exit 1
fi

if ! grep -Eq '^APP_KEY=base64:' "${TDG_APP_DIR}/.env"; then
    echo "APP_KEY vacío. Genera una con: php artisan key:generate --force" >&2
    exit 1
fi

as_deploy() {
    sudo -u "${TDG_DEPLOY_USER}" -H bash -lc "cd ${TDG_APP_DIR} && $*"
}

echo "==> Mantenimiento"
as_deploy "php artisan down --retry=30 --refresh=5" || true

echo "==> Código"
if [[ -d "${TDG_APP_DIR}/.git" ]]; then
    as_deploy "git pull --ff-only"
fi

echo "==> PHP (sin dev, autoload autoritativo)"
as_deploy "composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist"

if [[ "${SKIP_ASSETS}" != "1" ]]; then
    echo "==> Frontend (Vite producción)"
    as_deploy "npm ci"
    as_deploy "npm run build"
fi

echo "==> Permisos de storage"
chown -R "${TDG_DEPLOY_USER}:www-data" "${TDG_APP_DIR}/storage" "${TDG_APP_DIR}/bootstrap/cache"
chmod -R ug+rwx "${TDG_APP_DIR}/storage" "${TDG_APP_DIR}/bootstrap/cache"
setfacl -R -m u:www-data:rwx "${TDG_APP_DIR}/storage" "${TDG_APP_DIR}/bootstrap/cache" 2>/dev/null || true
setfacl -R -d -m u:www-data:rwx "${TDG_APP_DIR}/storage" "${TDG_APP_DIR}/bootstrap/cache" 2>/dev/null || true

echo "==> Enlace de storage y migraciones"
as_deploy "php artisan storage:link --force"
as_deploy "php artisan migrate --force"

echo "==> Cachés de producción"
as_deploy "php artisan optimize:clear"
as_deploy "php artisan optimize"
as_deploy "php artisan filament:optimize"
as_deploy "php artisan icons:cache"
as_deploy "php artisan view:cache"

echo "==> Recarga de procesos"
systemctl reload "php${PHP_VERSION}-fpm"
if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl reread
    supervisorctl update
    supervisorctl restart tdg-worker-default:* tdg-worker-email:* || true
fi

as_deploy "php artisan up"

APP_URL="$(grep -E '^APP_URL=' "${TDG_APP_DIR}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")"

echo
echo "Despliegue listo. Comprueba:"
echo "  curl -fsS ${APP_URL}/up"
echo "  sudo supervisorctl status"
echo "  redis-cli ping"
