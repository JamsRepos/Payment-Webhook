#!/bin/sh

docker-php-entrypoint apache2-foreground

while true; do wget -O - https://kofi.karna.ge/webhook.php >/dev/null 2>&1; sleep 3600; done