#!/usr/bin/env bash
# Stack for AffGoServer (Go tracker)
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

echo "==> Installing Go, Redis, Caddy"
apt-get update -qq
apt-get install -y -qq redis-server curl

# Go 1.22+
if ! command -v go &>/dev/null; then
    GO_VERSION="1.22.5"
    curl -fsSL "https://go.dev/dl/go${GO_VERSION}.linux-amd64.tar.gz" | tar -C /usr/local -xz
    echo 'export PATH=$PATH:/usr/local/go/bin' >> /etc/profile.d/golang.sh
    export PATH=$PATH:/usr/local/go/bin
fi

# Caddy
if ! command -v caddy &>/dev/null; then
    apt-get install -y -qq debian-keyring debian-archive-keyring apt-transport-https
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
    curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list
    apt-get update -qq
    apt-get install -y -qq caddy
fi

echo "==> Redis local buffer"
sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf
sed -i 's/^# maxmemory .*/maxmemory 2gb/' /etc/redis/redis.conf
sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
systemctl enable redis-server caddy
systemctl restart redis-server

echo "==> Caddy config"
mkdir -p /etc/caddy
cat > /etc/caddy/caddyfile << 'EOF'
{
    on_demand_tls {
        ask http://127.0.0.1:8081/internal/allow-host
    }
}

:443 {
    tls {
        on_demand
    }
    reverse_proxy 127.0.0.1:8080
}

:80 {
    reverse_proxy 127.0.0.1:8080
}
EOF
systemctl reload caddy || systemctl restart caddy

mkdir -p /opt/tvf-tracker
chown -R deploy:deploy /opt/tvf-tracker

echo "==> Tracker stack provision complete"
