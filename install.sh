#!/usr/bin/env bash

INIT_EXECUTED="success"

if [ "$APP_ENV" != "" ]; then
    #cat /var/app/current/environtment/env.$APP_ENV > /var/app/current/.env
    sudo cp -rf environtment/env.$APP_ENV .env
    echo "Init script for $APP_ENV environment is executed successfully."
elif [ $1 != "" ]; then
    sudo cat environtment/.env.$1 > .env
    echo "Init script for $1 environment is executed successfully."
else
    INIT_EXECUTED="failed"
    echo 'Init script is failed to execute.'
fi