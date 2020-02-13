#!/bin/bash

if [ ! $1 ]; then
    echo "Site not specified" >&2
    exit 1
fi

echo "MYSQL_DATABASE=$1" > ./.env

drush status bootstrap | grep Successful
INSTALLED=$?

if [ $INSTALLED -gt 0 ]; then
    mysql -e "CREATE DATABASE IF NOT EXISTS \`$1\`"
    # Site install owerwrites the database, backup existing database to project folder
    mysqldump -h $MYSQL_HOST $1 | gzip > $1-$(date +%Y-%m-%d-%H:%M:%S).sql.gz
    # Unknown bug installing site with --existing-config
    # Workaround is to install without config and import config afterwards
    drush si -y --verbose --site-name=$1 --account-name=admin --account-pass=admin minimal
    # Clean install sets different UUID so we need to sync it before importing config
    drush config-set "system.site" uuid "d6aa6641-0d31-45a6-83d5-5d3ee3296808"
    drush config:import -y
fi

drush state:set system.maintenance_mode 1 -y
drush cache:rebuild
drush updatedb -y
drush config:import -y
drush core:cron -y
drush state:set system.maintenance_mode 0 -y
drush cache:rebuild
