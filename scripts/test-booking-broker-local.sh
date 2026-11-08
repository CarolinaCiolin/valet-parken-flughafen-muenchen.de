#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-policies-local.sh
docker cp scripts/test-booking-broker.php vpm-local-wp:/tmp/vpm-test-booking-broker.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-broker.php
docker cp vpm-local-wp:/tmp/vpm-booking-broker-report.json docs/BOOKING-BROKER-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-broker-browser.py
