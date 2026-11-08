#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-compat-local.sh
docker cp scripts/test-booking-operations.php vpm-local-wp:/tmp/vpm-test-booking-operations.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-operations.php
docker cp vpm-local-wp:/tmp/vpm-booking-operations-report.json docs/BOOKING-OPERATIONS-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-operations-browser.py
