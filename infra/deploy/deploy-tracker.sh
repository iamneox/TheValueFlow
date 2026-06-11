#!/usr/bin/env bash
set -euo pipefail

TRACKER_SERVER="${TRACKER_SERVER:-deploy@167.233.41.29}"
REMOTE_PATH="/opt/tvf-tracker"

echo "==> Build tracker"
cd tracker
GOOS=linux GOARCH=amd64 go build -o tvf-tracker ./cmd/tracker
cd ..

echo "==> Deploy to ${TRACKER_SERVER}"
rsync -avz tracker/tvf-tracker "${TRACKER_SERVER}:${REMOTE_PATH}/tvf-tracker"
ssh "${TRACKER_SERVER}" "sudo systemctl restart tvf-tracker || true"

echo "==> Tracker deploy complete"
