#!/usr/bin/env bash
# Fresh disposable WordPress with ONLY the own plugin. Never seed the local editorial database.
set -euo pipefail
umask 077
cd "$(dirname "$0")/.."
for item in vpm-ci-wp vpm-ci-db; do
  if docker container inspect "$item" >/dev/null 2>&1; then echo "Refusing existing CI container: $item"; exit 2; fi
done
if docker network inspect vpm-ci-network >/dev/null 2>&1; then echo 'Refusing existing CI network'; exit 2; fi
node --check wordpress/plugins/vpm-booking-core/assets/front.js
node --check wordpress/plugins/vpm-booking-core/assets/block-editor.js
mkdir -p .runtime/ci .runtime/ci-reports
chmod 700 .runtime/ci
python3 - <<'PY'
from pathlib import Path
import secrets
p=Path('.runtime/ci');password=secrets.token_urlsafe(32)
(p/'db.env').write_text('MARIADB_DATABASE=vpm\nMARIADB_USER=vpm\nMARIADB_PASSWORD='+password+'\nMARIADB_ROOT_PASSWORD='+secrets.token_urlsafe(32)+'\n')
(p/'wp.env').write_text('WORDPRESS_DB_HOST=vpm-ci-db\nWORDPRESS_DB_NAME=vpm\nWORDPRESS_DB_USER=vpm\nWORDPRESS_DB_PASSWORD='+password+'\nVPM_CI_ADMIN_PASSWORD='+secrets.token_urlsafe(32)+'\n')
for f in p.iterdir():f.chmod(0o600)
PY
cleanup() {
  docker rm -f vpm-ci-wp vpm-ci-db >/dev/null 2>&1 || true
  docker network rm vpm-ci-network >/dev/null 2>&1 || true
  rm -f .runtime/ci/db.env .runtime/ci/wp.env .runtime/ci/backup.sql .runtime/ci/before.sha
}
trap cleanup EXIT
docker network create vpm-ci-network >/dev/null
docker run -d --name vpm-ci-db --network vpm-ci-network --env-file .runtime/ci/db.env mariadb@sha256:1292844148b311e4ed4300022a996d39083f415a963e970cf47cad1b3b18e3a6 >/dev/null
docker run -d --name vpm-ci-wp --network vpm-ci-network --env-file .runtime/ci/wp.env -p 127.0.0.1:8090:80 wordpress@sha256:f32ffa85064d9c951b7b69c929fa2a9f682aa4443e0c0a56ac2000499cb64383 >/dev/null
ready=0
for attempt in $(seq 1 120); do
  if docker exec vpm-ci-wp php -r 'mysqli_report(MYSQLI_REPORT_OFF);$db=@new mysqli(getenv("WORDPRESS_DB_HOST"),getenv("WORDPRESS_DB_USER"),getenv("WORDPRESS_DB_PASSWORD"),getenv("WORDPRESS_DB_NAME"));exit($db->connect_errno?1:0);' >/dev/null 2>&1 && docker exec vpm-ci-wp test -f /var/www/html/wp-config.php; then ready=1; break; fi
  sleep 1
done
if [ "$ready" -ne 1 ]; then echo 'CI database startup failed'; exit 1; fi
docker exec vpm-ci-wp mkdir -p /var/www/html/wp-content/mu-plugins
cat > .runtime/ci/no-network.php <<'PHP'
<?php
add_filter('pre_http_request',function(){return new WP_Error('ci_network_disabled');},-999);
add_filter('pre_wp_mail','__return_true');
PHP
docker cp .runtime/ci/no-network.php vpm-ci-wp:/var/www/html/wp-content/mu-plugins/no-network.php
docker exec vpm-ci-wp chmod 644 /var/www/html/wp-content/mu-plugins/no-network.php
docker exec vpm-ci-wp php -r '$_SERVER["HTTP_HOST"]="127.0.0.1:8090";define("WP_INSTALLING",true);require "/var/www/html/wp-load.php";require_once ABSPATH."wp-admin/includes/upgrade.php";wp_install("Booking CI","ci_admin","ci@example.invalid",false,"",getenv("VPM_CI_ADMIN_PASSWORD"));update_option("home","http://127.0.0.1:8090");update_option("siteurl","http://127.0.0.1:8090");'
docker cp wordpress/plugins/vpm-booking-core/. vpm-ci-wp:/var/www/html/wp-content/plugins/vpm-booking-core/
docker exec vpm-ci-wp sh -c 'find /var/www/html/wp-content/plugins/vpm-booking-core -type d -exec chmod 755 {} +; find /var/www/html/wp-content/plugins/vpm-booking-core -type f -exec chmod 644 {} +; for f in /var/www/html/wp-content/plugins/vpm-booking-core/includes/*.php; do php -l "$f" >/dev/null || exit 1; done'
docker exec vpm-ci-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";require_once ABSPATH."wp-admin/includes/plugin.php";$r=activate_plugin("vpm-booking-core/vpm-booking-core.php");if(is_wp_error($r))throw new Exception($r->get_error_message());VPM\Booking\DB::install();'
for suite in core products vouchers platform adapters logistics commerce policies; do
  docker cp "scripts/test-booking-$suite.php" "vpm-ci-wp:/tmp/test-$suite.php"
  docker exec vpm-ci-wp php "/tmp/test-$suite.php"
  if [ "$suite" = core ]; then report=vpm-booking-core-report.json; else report="vpm-booking-$suite-report.json"; fi
  docker cp "vpm-ci-wp:/tmp/$report" ".runtime/ci-reports/$report"
done
docker cp scripts/test-booking-live-guard.php vpm-ci-wp:/tmp/live-guard.php
docker exec vpm-ci-wp php /tmp/live-guard.php
VPM_TEST_CONTAINER=vpm-ci-wp python3 scripts/test-booking-stock-concurrency.py
# Disposable CI database only: prove financial snapshot and ledger survive SQL backup/restore.
docker exec vpm-ci-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";$p=VPM\Booking\Core::partner(["airport"=>"FRA","name"=>"Backup fixture","stripe_account"=>"acct_backupfixture","commission_bps"=>2000]);$b=VPM\Booking\Core::create(["partner_id"=>(int)$p["id"],"base_cents"=>10000,"email"=>"backup@example.invalid"],"backup-roundtrip-fixture");VPM\Booking\Finance::journal($b,"backup-test-journal",VPM\Booking\Finance::paid_entries(VPM\Booking\Core::snapshot($b)));global $wpdb;echo hash("sha256",wp_json_encode([$wpdb->get_results("SELECT * FROM ".VPM\Booking\DB::table("bookings")." ORDER BY id",ARRAY_A),$wpdb->get_results("SELECT * FROM ".VPM\Booking\DB::table("ledger")." ORDER BY id",ARRAY_A)]));' > .runtime/ci/before.sha
docker exec vpm-ci-db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb-dump --user=root --single-transaction --skip-comments --skip-dump-date vpm' > .runtime/ci/backup.sql
docker exec vpm-ci-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";global $wpdb;$wpdb->query("DELETE FROM ".VPM\Booking\DB::table("bookings"));$wpdb->query("DELETE FROM ".VPM\Booking\DB::table("ledger"));'
docker exec -i vpm-ci-db sh -c 'MYSQL_PWD="$MARIADB_ROOT_PASSWORD" mariadb --user=root vpm' < .runtime/ci/backup.sql
docker exec vpm-ci-wp php -r 'define("WP_ADMIN",true);require "/var/www/html/wp-load.php";global $wpdb;echo hash("sha256",wp_json_encode([$wpdb->get_results("SELECT * FROM ".VPM\Booking\DB::table("bookings")." ORDER BY id",ARRAY_A),$wpdb->get_results("SELECT * FROM ".VPM\Booking\DB::table("ledger")." ORDER BY id",ARRAY_A)]));' > .runtime/ci/after.sha
cmp .runtime/ci/before.sha .runtime/ci/after.sha
printf '%s\n' '{"scope":"disposable CI database only","snapshot_and_ledger_roundtrip":true,"hostinger_restore_tested":false}' > .runtime/ci-reports/backup-restore.json
rm -f .runtime/ci/after.sha
echo 'Fresh WordPress CI checks and isolated database backup/restore passed without third-party plugin fixtures.'
