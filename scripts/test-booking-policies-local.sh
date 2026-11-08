#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-commerce-local.sh
docker cp scripts/test-booking-policies.php vpm-local-wp:/tmp/vpm-test-booking-policies.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-policies.php
docker cp vpm-local-wp:/tmp/vpm-booking-policies-report.json docs/BOOKING-POLICIES-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-policies-browser.py
