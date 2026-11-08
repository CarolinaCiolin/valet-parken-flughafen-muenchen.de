#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-local.sh
docker cp scripts/test-booking-compat-domain.php vpm-local-wp:/tmp/vpm-test-booking-compat-domain.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-compat-domain.php
docker cp vpm-local-wp:/tmp/vpm-booking-compat-domain-report.json docs/BOOKING-COMPAT-DOMAIN-PRUEFUNG.json
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-compat-browser.py
