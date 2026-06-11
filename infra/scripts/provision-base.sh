#!/usr/bin/env bash
# Base provisioning: security hardening for Ubuntu 24.04
set -euo pipefail

HOSTNAME="${1:-$(hostname)}"
DEPLOY_USER="deploy"
DEPLOY_PUBKEY="${DEPLOY_PUBKEY:-}"

export DEBIAN_FRONTEND=noninteractive

echo "==> Setting hostname: ${HOSTNAME}"
hostnamectl set-hostname "${HOSTNAME}"

echo "==> System update"
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl wget git ufw fail2ban unattended-upgrades apt-listchanges

echo "==> Timezone Europe/Madrid"
timedatectl set-timezone Europe/Madrid

echo "==> Unattended upgrades"
cat > /etc/apt/apt.conf.d/50unattended-upgrades << 'EOF'
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}";
    "${distro_id}:${distro_codename}-security";
    "${distro_id}ESMApps:${distro_codename}-apps-security";
    "${distro_id}ESM:${distro_codename}-infra-security";
};
Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::Remove-Unused-Kernel-Packages "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
EOF
dpkg-reconfigure -plow unattended-upgrades

echo "==> Create deploy user"
if ! id "${DEPLOY_USER}" &>/dev/null; then
    useradd -m -s /bin/bash -G sudo "${DEPLOY_USER}"
    echo "${DEPLOY_USER} ALL=(ALL) NOPASSWD:ALL" > "/etc/sudoers.d/${DEPLOY_USER}"
    chmod 440 "/etc/sudoers.d/${DEPLOY_USER}"
fi

mkdir -p "/home/${DEPLOY_USER}/.ssh"
chmod 700 "/home/${DEPLOY_USER}/.ssh"

if [[ -n "${DEPLOY_PUBKEY}" ]]; then
    echo "${DEPLOY_PUBKEY}" > "/home/${DEPLOY_USER}/.ssh/authorized_keys"
elif [[ -f /root/.ssh/authorized_keys ]]; then
    cp /root/.ssh/authorized_keys "/home/${DEPLOY_USER}/.ssh/authorized_keys"
fi
chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "/home/${DEPLOY_USER}/.ssh"
chmod 600 "/home/${DEPLOY_USER}/.ssh/authorized_keys"

echo "==> SSH hardening"
cat > /etc/ssh/sshd_config.d/99-tvf-hardening.conf << 'EOF'
PermitRootLogin prohibit-password
PasswordAuthentication no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
UsePAM yes
X11Forwarding no
MaxAuthTries 3
AllowUsers deploy root
EOF
systemctl reload ssh || systemctl reload sshd

echo "==> fail2ban"
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400
EOF
systemctl enable fail2ban
systemctl restart fail2ban

echo "==> UFW base rules"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw allow 22/tcp comment 'SSH'
ufw --force enable

echo "==> Provision base complete for ${HOSTNAME}"
