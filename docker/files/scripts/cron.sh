#!/bin/bash

export CONFIG_DIRECTORY="/usr/local/aspen-discovery/sites/${SITE_NAME}"

# Check if site configuration exists
confSiteFile="$CONFIG_DIRECTORY/conf/config.ini"
if [ ! -f "$confSiteFile" ] ; then
    echo "ERROR: Site configuration not initialized. Skipping cron startup and waiting"
    sleep 5
	exit 1
fi

# Adjust permissions if required
if [[ ! -z "${LOCAL_USER_ID}" && "${LOCAL_USER_ID}" != "33" ]]; then
	echo "%   Setting www-data to UID=${LOCAL_USER_ID}"
    usermod -o -u ${LOCAL_USER_ID} "www-data"
fi

# Move and create temporarily sym-links to etc/cron directory
sanitizedSitename=$(echo "$SITE_NAME" | tr -dc '[:alnum:]_')
cp "$CONFIG_DIRECTORY/conf/crontab_settings.txt" "/etc/cron.d/$sanitizedSitename"

php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php "${SITE_NAME}" &
cron -f -L 2
