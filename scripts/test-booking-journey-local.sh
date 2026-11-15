#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# Start/activate the existing loopback fixture first; no live-host provisioning.
docker start vpm-local-db >/dev/null
docker start vpm-local-wp >/dev/null
docker cp wordpress/plugins/vpm-booking-core/. vpm-local-wp:/var/www/html/wp-content/plugins/vpm-booking-core/
docker exec vpm-local-wp sh -c 'find /var/www/html/wp-content/plugins/vpm-booking-core -type d -exec chmod 755 {} +; find /var/www/html/wp-content/plugins/vpm-booking-core -type f -exec chmod 644 {} +'
docker exec vpm-local-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);require_once ABSPATH."wp-admin/includes/plugin.php";$r=activate_plugin("vpm-booking-core/vpm-booking-core.php");if(is_wp_error($r))throw new Exception($r->get_error_message());VPM\Booking\DB::install();'
docker cp scripts/test-booking-journey.php vpm-local-wp:/tmp/vpm-test-booking-journey.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-journey.php
docker cp vpm-local-wp:/tmp/vpm-booking-journey-report.json docs/BOOKING-JOURNEY-PRUEFUNG.json
task_python="$(printenv VPM_TEST_PYTHON || true)"
if [ -z "$task_python" ]; then task_python=python3; fi
"$task_python" scripts/test-booking-journey-browser.py
