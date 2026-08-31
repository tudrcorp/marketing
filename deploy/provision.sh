#!/usr/bin/env bash
# Aprovisiona Debian 13 (Trixie) para tdg-marketing (10 CPU / 10 GB / HDD).
# PHP 8.4 y Nginx 1.26 vienen en los repos oficiales: no se usa Sury.
# Ejecutar como root, una sola vez:
#   TDG_DOMAIN=marketing.tudominio.com bash deploy/provision.sh

set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Este script debe correr como root." >&2
    exit 1
fi

TDG_DOMAIN="${TDG_DOMAIN:-}"
TDG_APP_DIR="${TDG_APP_DIR:-/var/www/tdg-marketing}"
TDG_DEPLOY_USER="${TDG_DEPLOY_USER:-deploy}"
PHP_VERSION="${PHP_VERSION:-8.4}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ -z "${TDG_DOMAIN}" ]]; then
    echo "Define TDG_DOMAIN (ejemplo: TDG_DOMAIN=marketing.tdg.com bash deploy/provision.sh)" >&2
    exit 1
fi

if [[ -f /etc/os-release ]]; then
    # shellcheck source=/dev/null
    . /etc/os-release
    if [[ "${ID:-}" != "debian" || "${VERSION_ID:-}" != "13" ]]; then
        echo "Aviso: este script está afinado para Debian 13. Detecté ${PRETTY_NAME:-desconocido}." >&2
    fi
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Usuario de despliegue"
if ! id -u "${TDG_DEPLOY_USER}" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" "${TDG_DEPLOY_USER}"
fi
usermod -aG www-data "${TDG_DEPLOY_USER}"

echo "==> Paquetes base"
apt-get update
apt-get install -y --no-install-recommends \
    apt-transport-https \
    ca-certificates \
    curl \
    git \
    unzip \
    gnupg \
    ufw \
    fail2ban \
    supervisor \
    redis-server \
    nginx \
    certbot \
    python3-certbot-nginx \
    logrotate \
    acl

if apt-get install -y --no-install-recommends libnginx-mod-http-brotli-filter libnginx-mod-http-brotli-static; then
    echo "==> Módulo Brotli instalado"
else
    echo "==> Brotli no disponible; se usará solo gzip"
fi

echo "==> PHP ${PHP_VERSION} (repos oficiales de Debian 13)"
if ! apt-cache show "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    echo "No encuentro php${PHP_VERSION}-fpm en los repos. ¿Es Debian 13 (trixie)?" >&2
    exit 1
fi

apt-get install -y --no-install-recommends \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-redis" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-opcache"

echo "==> Composer"
if ! command -v composer >/dev/null 2>&1; then
    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> Node 22 (solo para el build de Vite en cada deploy)"
if ! command -v node >/dev/null 2>&1; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y --no-install-recommends nodejs
fi

echo "==> Kernel"
install -m 0644 "${SCRIPT_DIR}/sysctl/99-tdg-marketing.conf" /etc/sysctl.d/99-tdg-marketing.conf
sysctl --system >/dev/null

echo "==> Nginx"
sed -i 's/^worker_processes .*/worker_processes auto;/' /etc/nginx/nginx.conf
if ! grep -q 'worker_rlimit_nofile' /etc/nginx/nginx.conf; then
    sed -i '/^worker_processes /a worker_rlimit_nofile 65535;' /etc/nginx/nginx.conf
fi
if grep -q 'worker_connections' /etc/nginx/nginx.conf; then
    sed -i 's/worker_connections .*/worker_connections 4096;/' /etc/nginx/nginx.conf
fi
# Debian 13 ya declara estas en nginx.conf; no se pueden repetir en conf.d
sed -i 's/^[[:space:]]*keepalive_timeout .*/    keepalive_timeout 15s;/' /etc/nginx/nginx.conf
sed -i 's/^[[:space:]]*#\?[[:space:]]*gzip on;/    gzip on;/' /etc/nginx/nginx.conf
sed -i 's/^[[:space:]]*#\?[[:space:]]*server_tokens .*/    server_tokens off;/' /etc/nginx/nginx.conf

install -m 0644 "${SCRIPT_DIR}/nginx/tdg-performance.conf" /etc/nginx/conf.d/tdg-performance.conf
if [[ -f /usr/share/nginx/modules-available/mod-http-brotli.conf || -f /usr/lib/nginx/modules/ngx_http_brotli_filter_module.so ]]; then
    install -m 0644 "${SCRIPT_DIR}/nginx/tdg-brotli.conf" /etc/nginx/conf.d/tdg-brotli.conf
fi

install -d /etc/nginx/snippets
install -m 0644 "${SCRIPT_DIR}/nginx/tdg-ssl-extras.conf" /etc/nginx/snippets/tdg-ssl-extras.conf

sed -e "s|__DOMAIN__|${TDG_DOMAIN}|g" -e "s|__APP_DIR__|${TDG_APP_DIR}|g" \
    "${SCRIPT_DIR}/nginx/tdg-marketing.conf.template" \
    > /etc/nginx/sites-available/tdg-marketing
ln -sfn /etc/nginx/sites-available/tdg-marketing /etc/nginx/sites-enabled/tdg-marketing
rm -f /etc/nginx/sites-enabled/default

echo "==> PHP-FPM"
install -m 0644 "${SCRIPT_DIR}/php/tdg-fpm-pool.conf" "/etc/php/${PHP_VERSION}/fpm/pool.d/tdg.conf"
# Evita que el pool www por defecto coma RAM
if [[ -f "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf" ]]; then
    sed -i 's/^pm.max_children = .*/pm.max_children = 4/' "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    sed -i 's/^pm.start_servers = .*/pm.start_servers = 1/' "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 1/' "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 2/' "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
fi
install -m 0644 "${SCRIPT_DIR}/php/zz-tdg-production.ini" "/etc/php/${PHP_VERSION}/mods-available/tdg-production.ini"
phpenmod -v "${PHP_VERSION}" tdg-production

echo "==> Redis"
REDIS_CONF=/etc/redis/redis.conf
if [[ -f "${REDIS_CONF}" ]]; then
    sed -i 's/^#\? bind .*/bind 127.0.0.1/' "${REDIS_CONF}"
    sed -i 's/^#\? protected-mode .*/protected-mode yes/' "${REDIS_CONF}"
    sed -i 's/^#\? maxmemory .*/maxmemory 1gb/' "${REDIS_CONF}"
    if grep -q '^#\? maxmemory-policy' "${REDIS_CONF}"; then
        sed -i 's/^#\? maxmemory-policy .*/maxmemory-policy volatile-lru/' "${REDIS_CONF}"
    else
        echo 'maxmemory-policy volatile-lru' >> "${REDIS_CONF}"
    fi
    sed -i 's/^#\? appendonly .*/appendonly yes/' "${REDIS_CONF}"
    sed -i 's/^#\? appendfsync .*/appendfsync everysec/' "${REDIS_CONF}"
fi
mkdir -p /etc/systemd/system/redis-server.service.d
cat > /etc/systemd/system/redis-server.service.d/override.conf <<'EOF'
[Service]
LimitNOFILE=65535
EOF

echo "==> Directorio de la app"
mkdir -p "${TDG_APP_DIR}"
chown -R "${TDG_DEPLOY_USER}:www-data" "${TDG_APP_DIR}"

echo "==> Supervisor (se activa cuando exista artisan)"
install -m 0644 "${SCRIPT_DIR}/supervisor/laravel-worker-default.conf" /etc/supervisor/conf.d/tdg-worker-default.conf
install -m 0644 "${SCRIPT_DIR}/supervisor/laravel-worker-email.conf" /etc/supervisor/conf.d/tdg-worker-email.conf
sed -i "s|/var/www/tdg-marketing|${TDG_APP_DIR}|g" /etc/supervisor/conf.d/tdg-worker-default.conf
sed -i "s|/var/www/tdg-marketing|${TDG_APP_DIR}|g" /etc/supervisor/conf.d/tdg-worker-email.conf
sed -i "s|user=deploy|user=${TDG_DEPLOY_USER}|g" /etc/supervisor/conf.d/tdg-worker-default.conf
sed -i "s|user=deploy|user=${TDG_DEPLOY_USER}|g" /etc/supervisor/conf.d/tdg-worker-email.conf

echo "==> Cron del scheduler"
cat > /etc/cron.d/tdg-marketing <<EOF
* * * * * ${TDG_DEPLOY_USER} php ${TDG_APP_DIR}/artisan schedule:run >> /dev/null 2>&1
EOF
chmod 0644 /etc/cron.d/tdg-marketing

echo "==> Logrotate"
install -m 0644 "${SCRIPT_DIR}/logrotate/tdg-marketing" /etc/logrotate.d/tdg-marketing
sed -i "s|/var/www/tdg-marketing|${TDG_APP_DIR}|g" /etc/logrotate.d/tdg-marketing
sed -i "s|deploy|${TDG_DEPLOY_USER}|g" /etc/logrotate.d/tdg-marketing

echo "==> Firewall"
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> fail2ban"
systemctl enable --now fail2ban

echo "==> Servicios"
systemctl daemon-reload
systemctl enable --now "php${PHP_VERSION}-fpm" nginx redis-server supervisor
nginx -t
systemctl reload nginx
systemctl restart "php${PHP_VERSION}-fpm" redis-server

cat <<EOF

============================================================
Servidor listo para tdg-marketing
============================================================

Siguiente:

1) En el servidor MySQL (el que ya usa integracorp-api), crea
   una base SOLO para esta app:

   CREATE DATABASE tdg_marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'tdg_marketing'@'IP_DE_ESTE_SERVIDOR' IDENTIFIED BY 'CLAVE_FUERTE';
   GRANT ALL PRIVILEGES ON tdg_marketing.* TO 'tdg_marketing'@'IP_DE_ESTE_SERVIDOR';
   FLUSH PRIVILEGES;

2) Clona el repo como ${TDG_DEPLOY_USER}:

   sudo -u ${TDG_DEPLOY_USER} git clone URL_DEL_REPO ${TDG_APP_DIR}

3) Copia y edita el entorno:

   sudo -u ${TDG_DEPLOY_USER} cp ${TDG_APP_DIR}/deploy/env.production.example ${TDG_APP_DIR}/.env
   sudo -u ${TDG_DEPLOY_USER} nano ${TDG_APP_DIR}/.env

   Obligatorio: APP_KEY, APP_URL, DB_*, MARKETING_API_URL, MARKETING_API_KEY,
   REDIS_PASSWORD (opcional pero recomendado).

4) Despliega la app:

   bash ${TDG_APP_DIR}/deploy/deploy.sh

5) TLS:

   certbot --nginx -d ${TDG_DOMAIN}

   Después, dentro del server 443 que crea Certbot, añade:
   include /etc/nginx/snippets/tdg-ssl-extras.conf;
   nginx -t && systemctl reload nginx

NO instales MySQL en esta caja: el disco es HDD y ya tienes
servidor de base de datos. Redis y OPcache viven en RAM a propósito.

Dominio:     ${TDG_DOMAIN}
App:         ${TDG_APP_DIR}
PHP-FPM:     unix:/run/php/php${PHP_VERSION}-fpm-tdg.sock (80 workers)
Redis:       127.0.0.1:6379 (1 GB, AOF, volatile-lru)
Colas:       default x4  |  email x2  (timeout correo 420s)
EOF
