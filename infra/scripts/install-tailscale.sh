#!/usr/bin/env bash
# Install Tailscale and restrict SSH to tailscale interface
set -euo pipefail

AUTH_KEY="${1:-}"

if ! command -v tailscale &>/dev/null; then
    curl -fsSL https://tailscale.com/install.sh | sh
fi

if [[ -n "${AUTH_KEY}" ]]; then
    tailscale up --auth-key="${AUTH_KEY}" --accept-routes
else
    echo "No auth key provided. Run: tailscale up"
    tailscale up --accept-routes || true
fi

# Restrict SSH to Tailscale after tailscale is up
if ip link show tailscale0 &>/dev/null; then
    ufw delete allow 22/tcp 2>/dev/null || true
    ufw allow in on tailscale0 to any port 22 proto tcp comment 'SSH via Tailscale'
    ufw reload
    echo "SSH restricted to Tailscale interface"
fi

tailscale status
