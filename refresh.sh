#!/bin/sh

docker-php-entrypoint apache2-foreground

while true; do
	DATE=`date +%Y%m%d`
	HOUR=`date +%H`

	while [ $HOUR -ne "00" ]; do
        echo "Checking for expired roles...";
		wget -O - https://kofi.karna.ge/webhook.php >/dev/null 2>&1
		sleep 3600
		HOUR=`date +%H`
	done
done