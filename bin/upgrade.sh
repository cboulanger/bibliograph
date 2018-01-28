#!/bin/bash

# Use this script if you upgrade your installation from Bibliograph 2.x
# to Bibliograph 3.0

echo "Installing Bibliograph"
npm install
cd src/client/bibliograph
qx contrib install
cd ../../server
composer install
ln -s vendor/bower-asset/ vendor/bower

echo "Upgrade database"
php yii migrate/mark m171219_230855_create_table_join_User_Role -p=./migrations/schema
php yii migrate/up -p=./migrations/schema --interactive=0
