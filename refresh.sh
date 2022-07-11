#!/bin/sh

# Run this in the background otherwise the script will stop after the first run
nohup docker-php-entrypoint apache2-foreground &

# Loop forever until the time is on the hour
while true; do
	DATE=`date +%Y%m%d`
	HOUR=`date +%H`

	while [ $HOUR -ne "00" ]; do
		sleep 10
        echo "Checking for expired roles...";
		wget -O - https://kofi.karna.ge/webhook.php >/dev/null 2>&1
		sleep 60
		HOUR=`date +%H`
	done
done