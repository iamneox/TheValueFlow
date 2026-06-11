#!/usr/bin/env bash
# Stack for AffTheValueFlow (Laravel platform)
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

echo "==> Installing Nginx, PHP 8.3, MySQL 8, Redis, Node"
apt-get update -qq
apt-get install -y -qq software-properties-common

# PHP
add-apt-repository -y universe
apt-get install -y -qq nginx redis-server supervisor \
    php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-xml \
    php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-intl \
    php8.3-redis php8.3-gd php8.3-readline unzip

# MySQL 8
apt-get install -y -qq mysql-server

# Node.js 20 LTS
if ! command -v node &>/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y -qq nodejs
fi

# Composer
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> MySQL: bind to private network + localhost"
PRIVATE_IP=$(ip -4 addr show enp7s0 2>/dev/null | awk '/inet / {print $2}' | cut -d/ -f1 || echo "127.0.0.1")
cat > /etc/mysql/mysql.conf.d/99-tvf.cnf << EOF
[mysqld]
bind-address = 127.0.0.1,${PRIVATE_IP}
max_connections = 300
innodb_buffer_pool_size = 4G
innodb_log_file_size = 512M
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
EOF
systemctl restart mysql

echo "==> Create database and tracker user"
MYSQL_TRACKER_PASS="${MYSQL_TRACKER_PASS:-$(openssl rand -hex 24)}"
MYSQL_APP_PASS="${MYSQL_APP_PASS:-$(openssl rand -hex 24)}"

mysql -e "CREATE DATABASE IF NOT EXISTS tvf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'tvf_app'@'localhost' IDENTIFIED BY '${MYSQL_APP_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS 'tvf_app'@'${PRIVATE_IP}' IDENTIFIED BY '${MYSQL_APP_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS 'tvf_tracker'@'10.5.0.2' IDENTIFIED BY '${MYSQL_TRACKER_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON tvf.* TO 'tvf_app'@'localhost';"
mysql -e "GRANT ALL PRIVILEGES ON tvf.* TO 'tvf_app'@'${PRIVATE_IP}';"
mysql -e "GRANT SELECT, INSERT, UPDATE ON tvf.* TO 'tvf_tracker'@'10.5.0.2';"
mysql -e "FLUSH PRIVILEGES;"

echo "MYSQL_APP_PASS=${MYSQL_APP_PASS}" > /root/.tvf-db-credentials
echo "MYSQL_TRACKER_PASS=${MYSQL_TRACKER_PASS}" >> /root/.tvf-db-credentials
chmod 600 /root/.tvf-db-credentials

echo "==> Redis: bind localhost only"
sed -i 's/^# supervised auto/supervised systemd/' /etc/redis/redis.conf
sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf
sed -i 's/^bind .*/bind 127.0.0.1 -::1/' /etc/redis/redis.conf
systemctl enable redis-server nginx php8.3-fpm mysql
systemctl restart redis-server nginx php8.3-fpm

echo "==> Nginx site"
cat > /etc/nginx/sites-available/tvf << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/tvf/public;
    index index.php;
    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
ln -sf /etc/nginx/sites-available/tvf /etc/nginx/sites-enabled/tvf
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

mkdir -p /var/www/tvf
chown -R deploy:www-data /var/www/tvf

echo "==> App stack provision complete"
echo "DB credentials saved to /root/.tvf-db-credentials"
