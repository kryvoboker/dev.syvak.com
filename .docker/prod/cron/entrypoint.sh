#!/bin/sh
set -e

# Copy and fix crontab if needed
if [ -f /etc/crontabs/crontab ]; then
    echo "Processing crontab file..."
    cp /etc/crontabs/crontab /tmp/crontab.tmp
    dos2unix /tmp/crontab.tmp
    chmod 0600 /tmp/crontab.tmp
    echo "Crontab file prepared successfully"
    # Start supercronic with processed file
    exec /usr/local/bin/supercronic /tmp/crontab.tmp
fi

# Apply umask for runtime
umask "${UMASK:-0002}"

echo "ERROR: Crontab file not found!"
exit 1