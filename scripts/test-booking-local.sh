#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
# Existing loopback WordPress only; never install this test harness on Hostinger.
docker start vpm-local-db >/dev/null
docker start vpm-local-wp >/dev/null
docker cp wordpress/plugins/vpm-booking-core/. vpm-local-wp:/var/www/html/wp-content/plugins/vpm-booking-core/
docker exec vpm-local-wp sh -c 'find /var/www/html/wp-content/plugins/vpm-booking-core -type d -exec chmod 755 {} +; find /var/www/html/wp-content/plugins/vpm-booking-core -type f -exec chmod 644 {} +'
docker exec vpm-local-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1")exit(2);require_once ABSPATH."wp-admin/includes/plugin.php";$r=activate_plugin("vpm-booking-core/vpm-booking-core.php");if(is_wp_error($r))throw new Exception($r->get_error_message());VPM\Booking\DB::install();'
docker cp scripts/test-booking-core.php vpm-local-wp:/tmp/vpm-test-booking-core.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-core.php
VPM_TEST_PYTHON="${VPM_TEST_PYTHON:-python3}"
"$VPM_TEST_PYTHON" scripts/test-booking-browser.py
"$VPM_TEST_PYTHON" scripts/test-booking-concurrency.py
docker cp vpm-local-wp:/tmp/vpm-booking-core-report.json docs/BOOKING-CORE-PRUEFUNG.json
docker cp scripts/test-booking-live-guard.php vpm-local-wp:/tmp/vpm-test-booking-live-guard.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-live-guard.php
"$VPM_TEST_PYTHON" - <<'PY'
from pathlib import Path
import json
p=Path('docs/BOOKING-CORE-PRUEFUNG.json');d=json.loads(p.read_text())
d['additional_checks']=['Browser GET never confirms or revokes; explicit POST buttons do','No external tracking assets on consent landing; noindex/no-referrer headers','Unauthenticated real HTTP cannot read bookings','Two independent MySQL transactions cannot redeem single-use coupon twice','Live-shaped key rejected before any Stripe request']
p.write_text(json.dumps(d,ensure_ascii=False,indent=2)+'\n')
PY
