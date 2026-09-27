#!/bin/sh
set -e

# Railway provides dynamic $PORT environment variable.
# Fallback to port 80 if PORT is not set.
PORT="${PORT:-80}"

echo "Starting Apache on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/*.conf

exec apache2-foreground
