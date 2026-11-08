#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-operations-local.sh
docker cp scripts/test-booking-products.php vpm-local-wp:/tmp/vpm-test-booking-products.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-products.php
docker cp vpm-local-wp:/tmp/vpm-booking-products-report.json docs/BOOKING-PRODUCTS-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-products-browser.py
