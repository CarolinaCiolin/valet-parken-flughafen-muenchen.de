#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/test-booking-broker-local.sh
docker cp scripts/test-booking-branding.php vpm-local-wp:/tmp/vpm-test-booking-branding.php
docker exec vpm-local-wp php /tmp/vpm-test-booking-branding.php
docker cp vpm-local-wp:/tmp/vpm-booking-branding-report.json docs/BOOKING-BRANDING-PRUEFUNG.json
task_python="$(printenv VPM_TEST_PYTHON || true)"
if [ -z "$task_python" ]; then task_python=python3; fi
"$task_python" scripts/test-booking-branding-browser.py
