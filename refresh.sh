#!/bin/sh

# Run this in the background otherwise the script will stop after the first run
nohup docker-php-entrypoint apache2-foreground &

# Loop forever until the time is on the hour
while true; do
	MINUTE=`date +%M`

	# If the minute of the hour is 0, run the script
	while [ $MINUTE -eq "00" ]; do
		echo "Checking for expired roles...";
		wget -O - https://pay.karna.ge/webhook.php >/dev/null 2>&1
		# Run a second less to keep it a perfect loop
		sleep 3599
		MINUTE=`date +%M`
	done
done
