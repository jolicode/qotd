#!/bin/bash
set -e

groupadd -g $USER_ID app
useradd -M -u $USER_ID -g $USER_ID -s /bin/bash app

crontab -u app /etc/cron.d/crontab

# Wrapper for logs
FIFO=/tmp/cron-stdout
rm -f $FIFO
mkfifo $FIFO
chmod 0666 $FIFO
while true; do
  cat /tmp/cron-stdout
done &

exec "$@"
