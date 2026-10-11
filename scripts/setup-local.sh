#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p .runtime dist
chmod 700 .runtime
python3 - <<'PY'
from pathlib import Path
import secrets
r = Path('.runtime')
if not (r / 'db.env').exists():
    password = secrets.token_urlsafe(32)
    (r / 'db.env').write_text('MARIADB_DATABASE=vpm\nMARIADB_USER=vpm\nMARIADB_PASSWORD=' + password + '\nMARIADB_ROOT_PASSWORD=' + secrets.token_urlsafe(32) + '\n')
    (r / 'wp.env').write_text('WORDPRESS_DB_HOST=vpm-local-db\nWORDPRESS_DB_NAME=vpm\nWORDPRESS_DB_USER=vpm\nWORDPRESS_DB_PASSWORD=' + password + '\n')
    (r / 'admin-password').write_text(secrets.token_urlsafe(32))
for name in ['db.env', 'wp.env', 'admin-password']:
    (r / name).chmod(0o600)
PY
WP_IMAGE='wordpress@sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383'
DB_IMAGE='mariadb@sha256:1292844148b311e4ed4300022a996d39083f415a963e970cf47cad1b3b18e3a6'
docker image inspect "$WP_IMAGE" >/dev/null 2>&1 || docker pull "$WP_IMAGE"
docker image inspect "$DB_IMAGE" >/dev/null 2>&1 || docker pull "$DB_IMAGE"
docker network inspect vpm-local-network >/dev/null 2>&1 || docker network create vpm-local-network
if docker container inspect vpm-local-db >/dev/null 2>&1; then
    docker start vpm-local-db >/dev/null
else
    docker run -d --name vpm-local-db --network vpm-local-network --env-file .runtime/db.env "$DB_IMAGE"
fi
if docker container inspect vpm-local-wp >/dev/null 2>&1; then
    docker start vpm-local-wp >/dev/null
else
    docker run -d --name vpm-local-wp --network vpm-local-network --env-file .runtime/wp.env -p 127.0.0.1:8088:80 "$WP_IMAGE"
fi
for attempt in $(seq 1 30); do
    if docker exec vpm-local-db healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then break; fi
    sleep 1
done
docker exec vpm-local-db healthcheck.sh --connect --innodb_initialized >/dev/null
for attempt in $(seq 1 30); do
    if docker exec vpm-local-wp test -f /var/www/html/wp-load.php; then break; fi
    sleep 1
done
docker exec vpm-local-wp test -f /var/www/html/wp-load.php
if [ ! -f .runtime/elementor-3.33.0.zip ]; then
    curl --fail --location --max-time 90 --output .runtime/elementor-3.33.0.zip https://github.com/elementor/elementor/releases/download/3.33.0/elementor-3.33.0.zip
fi
printf '%s\n' '2a2fe5db9e8f5a2238b0524758b2cff425d91a61cce41093b503c47950e999a5  .runtime/elementor-3.33.0.zip' | sha256sum --check --status
mkdir -p .runtime/elementor
unzip -oq .runtime/elementor-3.33.0.zip -d .runtime/elementor
docker cp wordpress/themes/vpm-hybrid/. vpm-local-wp:/var/www/html/wp-content/themes/vpm-hybrid/
docker cp wordpress/plugins/vpm-providers/. vpm-local-wp:/var/www/html/wp-content/plugins/vpm-providers/
docker cp wordpress/plugins/vpm-content-drafts/. vpm-local-wp:/var/www/html/wp-content/plugins/vpm-content-drafts/
docker cp .runtime/elementor/elementor/. vpm-local-wp:/var/www/html/wp-content/plugins/elementor/
docker exec vpm-local-wp sh -c 'chown -R www-data:www-data wp-content/themes/vpm-hybrid wp-content/plugins/vpm-providers wp-content/plugins/vpm-content-drafts wp-content/plugins/elementor; find wp-content/themes/vpm-hybrid wp-content/plugins/vpm-providers wp-content/plugins/vpm-content-drafts -type d -exec chmod 755 {} +; find wp-content/themes/vpm-hybrid wp-content/plugins/vpm-providers wp-content/plugins/vpm-content-drafts -type f -exec chmod 644 {} +'
docker cp .runtime/admin-password vpm-local-wp:/tmp/vpm-local-admin-password
docker cp scripts/fixture.json vpm-local-wp:/tmp/vpm-fixture.json
for file in install-local hero-media-local seed-local seed-elementor-local seed-content-local prepare-handover-local test-integration; do
    docker cp "scripts/$file.php" "vpm-local-wp:/tmp/vpm-$file.php"
done
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-install-local.php
docker exec vpm-local-wp php -r 'require "/var/www/html/wp-load.php"; require_once ABSPATH."wp-admin/includes/plugin.php"; $r=activate_plugin("elementor/elementor.php"); if(is_wp_error($r)){fwrite(STDERR,$r->get_error_message());exit(1);}'
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-seed-local.php
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-seed-elementor-local.php
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-seed-content-local.php
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-prepare-handover-local.php
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-test-integration.php
curl --fail --silent --max-time 15 http://127.0.0.1:8088/ | python3 -c 'import sys; html=sys.stdin.read(); assert "vpm-provider-card" in html; assert "noindex" in html; print("HTTP-Ausgabe mit Anbietern und noindex geprüft.")'
