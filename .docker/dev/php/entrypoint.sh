#!/bin/sh
set -e

# Apply umask for runtime
umask "${UMASK:-0002}"

# Start supervisord in background (daemon mode)
supervisord -c /etc/supervisord.conf || echo "Failed to start supervisord!"

# Start PHP-FPM as PID 1 (foreground)
exec php-fpm -F || echo "Failed to start PHP-FPM!"