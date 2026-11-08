#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-platform-local.sh
docker cp scripts/test-booking-logistics.php vpm-local-wp:/tmp/vpm-test-booking-logistics.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-logistics.php
docker cp vpm-local-wp:/tmp/vpm-booking-logistics-report.json docs/BOOKING-LOGISTICS-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-logistics-browser.py
