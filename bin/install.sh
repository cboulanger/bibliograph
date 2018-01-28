#!/bin/bash

# Use this script if you install Bibliograph for the first time

echo "Installing Bibliograph"
npm install
cd src/client/bibliograph
qx contrib install
cd ../../server
composer install
ln -s vendor/bower-asset/ vendor/bower

echo "Setup and populate database"
php yii migrate/up -p=./migrations/schema --interactive=0
php yii migrate/up -p=./migrations/data --interactive=0