#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-vouchers-local.sh
for suite in platform adapters; do
  docker cp "scripts/test-booking-$suite.php" "vpm-local-wp:/tmp/vpm-test-booking-$suite.php"
  docker exec vpm-local-wp php "/tmp/vpm-test-booking-$suite.php"
  docker cp "vpm-local-wp:/tmp/vpm-booking-$suite-report.json" "docs/BOOKING-${suite^^}-PRUEFUNG.json"
done
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-stock-concurrency.py
"${VPM_TEST_PYTHON:-python3}" scripts/test-booking-portal-browser.py
