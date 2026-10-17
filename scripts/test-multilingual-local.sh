#!/usr/bin/env bash
# Explicit fixture lifecycle. Only the guarded local Docker installation is touched.
set -euo pipefail
cd "$(dirname "$0")/.."
docker cp scripts/test-multilingual-local.php vpm-local-wp:/tmp/vpm-language-test.php
cleanup() { docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-language-test.php restore; }
trap cleanup EXIT
# Requires current VPM packages plus setup-polylang-local.sh in the disposable installation.
docker exec vpm-local-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")throw new Exception("Local only");wp_set_current_user(1);foreach(["vpm_prepare_languages","vpm_import_english"]as $fn){$r=$fn();if(is_wp_error($r))throw new Exception($r->get_error_message());echo $r."\n";}'
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-language-test.php
python3 scripts/test-multilingual-browser.py
python3 scripts/test-multilingual-seo-editor.py
python3 scripts/test-multilingual-guide.py
cleanup
python3 scripts/test-multilingual-draft-preview.py
