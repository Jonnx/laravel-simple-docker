#!/bin/bash

LOG_FILE="/var/www/html/storage/logs/laravel.log"
touch $LOG_FILE
chown www-data. $LOG_FILE

echo ""
echo "-----------------------------------------------"
echo "Route Cache: Clear & Re-Cache"
php artisan route:clear

if [ $APP_TYPE == "worker" ]
then

    # run migrations
    echo ""
    echo "-----------------------------------------------"
    echo "WORKER: MySQL Migrations"
    php artisan migrate --force

    # supervisor
    echo ""
    echo "-----------------------------------------------"
    echo "WORKER: Supervisor Queue Workers"
    echo "Starting supervisor"
    supervisord
    echo "Supervisor started" >> $LOG_FILE
    echo "Done"

fi

echo ""
php artisan view:clear

echo ""
php artisan up


echo ""
echo "Starting apache"
apache2-foreground &
apachepid=$!
wait "$apachepid"