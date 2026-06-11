#!/usr/bin/env bash
# Run on each server after installing Tailscale:
#   bash tailscale-auth.sh <AUTH_KEY>
set -euo pipefail
AUTH_KEY="${1:-}"
if [[ -z "${AUTH_KEY}" ]]; then
  echo "Usage: $0 <tailscale-auth-key>"
  exit 1
fi
tailscale up --auth-key="${AUTH_KEY}" --accept-routes
ufw delete allow 22/tcp 2>/dev/null || true
ufw allow in on tailscale0 to any port 22 proto tcp comment 'SSH via Tailscale'
ufw reload
tailscale status
