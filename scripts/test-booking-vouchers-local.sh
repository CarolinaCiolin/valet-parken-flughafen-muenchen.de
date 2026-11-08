#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-products-local.sh
docker cp scripts/test-booking-vouchers.php vpm-local-wp:/tmp/vpm-test-booking-vouchers.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-vouchers.php
docker cp vpm-local-wp:/tmp/vpm-booking-vouchers-report.json docs/BOOKING-VOUCHERS-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-vouchers-browser.py
