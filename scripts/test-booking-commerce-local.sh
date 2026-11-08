#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-logistics-local.sh
docker cp scripts/test-booking-commerce.php vpm-local-wp:/tmp/vpm-test-booking-commerce.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-commerce.php
docker cp vpm-local-wp:/tmp/vpm-booking-commerce-report.json docs/BOOKING-COMMERCE-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-commerce-browser.py
