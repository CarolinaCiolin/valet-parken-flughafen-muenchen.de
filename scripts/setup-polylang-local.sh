#!/usr/bin/env bash
# Optional isolated compatibility test; never deploy manufacturer source snapshots.
set -euo pipefail
cd "$(dirname "$0")/.."
source_dir=.runtime/compatibility-plugins/polylang
source_commit=8a308ebe034672d41fd5dc711107a123806db02f
if [ ! -d "$source_dir/.git" ]; then
    git clone --depth 1 --branch 3.8.9 https://github.com/polylang/polylang.git "$source_dir"
fi
test "$(git -C "$source_dir" rev-parse HEAD)" = "$source_commit"
test -z "$(git -C "$source_dir" status --porcelain)"
docker start vpm-local-db vpm-local-wp >/dev/null
docker cp "$source_dir" vpm-local-wp:/var/www/html/wp-content/plugins/polylang
# Official build step generates classmap and loaders; no runtime Composer dependencies.
docker cp .runtime/composer-2.8.12.phar vpm-local-wp:/tmp/vpm-composer.phar
printf '%s\n' 'f446ea719708bb85fcbf4ef18def5d0515f1f9b4d703f6d820c9c1656e10a2f2  .runtime/composer-2.8.12.phar' | sha256sum --check --status
docker exec -w /var/www/html/wp-content/plugins/polylang vpm-local-wp php /tmp/vpm-composer.phar dump-autoload --no-dev --no-scripts --no-plugins --optimize
docker exec -w /var/www/html/wp-content/plugins/polylang vpm-local-wp php /tmp/vpm-composer.phar run-script post-autoload-dump
docker exec vpm-local-wp sh -c 'chown -R www-data:www-data wp-content/plugins/polylang; find wp-content/plugins/polylang -type d -exec chmod 755 {} +; find wp-content/plugins/polylang -type f -exec chmod 644 {} +'
docker exec vpm-local-wp php -r 'require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1"){throw new Exception("Local only");}require_once ABSPATH."wp-admin/includes/plugin.php";$r=activate_plugin("polylang/polylang.php");if(is_wp_error($r)){throw new Exception($r->get_error_message());}echo "Polylang ".POLYLANG_VERSION." local source fixture active\n";'
