#!/bin/bash
set -euo pipefail

PORT="${PORT:-8080}"
DATA_DIR="${HANKO_DATA_PATH:-/var/www/html/data}"

mkdir -p "$DATA_DIR/mail"
chown -R www-data:www-data "$DATA_DIR" || true

cat >/etc/apache2/ports.conf <<EOF
Listen ${PORT}
ServerName localhost
EOF

cat >/etc/apache2/sites-available/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
    SetEnvIf X-Forwarded-Proto "https" HTTPS=on
</VirtualHost>
EOF

exec apache2-foreground
