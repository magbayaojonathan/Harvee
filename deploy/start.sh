#!/bin/sh
set -eu
PORT="${PORT:-10000}"
case "$PORT" in *[!0-9]*|'') echo 'Invalid PORT' >&2; exit 1;; esac
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
mkdir -p /var/data/uploads/delivery_proofs /var/data/uploads/reviews /var/data/sessions
chown -R www-data:www-data /var/data
if [ ! -L /var/www/html/assets/uploads ]; then
    if [ -d /var/www/html/assets/uploads ]; then
        cp -an /var/www/html/assets/uploads/. /var/data/uploads/
        rm -rf /var/www/html/assets/uploads
    fi
    ln -s /var/data/uploads /var/www/html/assets/uploads
fi
exec apache2-foreground
