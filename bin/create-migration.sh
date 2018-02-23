#!/usr/bin/env bash

cd src/server
read -p 'Please enter a short descriptive name of the migration: ' name
php yii migrate/create --migration-namespaces=app\\migrations\\schema _$(echo $name | sed -e 's/[^a-zA-Z0-9\-]/_/g')