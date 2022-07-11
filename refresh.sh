#!/bin/sh

# Run this in the background otherwise the script will stop after the first run
nohup docker-php-entrypoint apache2-foreground &

# Loop forever until the time is on the hour
while true; do
	MINUTE=`date +%M`

	# If the minute of the hour is 0, run the script
	if [ "$MINUTE" = "00" ]; then
		sleep 5
        echo "Checking for expired roles...";
		wget -O - https://kofi.karna.ge/webhook.php >/dev/null 2>&1
		sleep 60
	fi
done