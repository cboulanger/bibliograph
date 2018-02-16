#!/bin/bash

set -o errexit # Exit on error

echo "Migrate Bibliograph v2 data..."
mysql -uroot -e 'DROP DATABASE tests;'
mysql -uroot -e 'CREATE DATABASE tests;'
mysql -uroot < test/data/bibliograph2.sql

echo "Run tests..."
pushd ./src/server > /dev/null
php vendor/bin/codecept run api SetupControllerCest --debug
popd > /dev/null

echo "Cleanup database ..."
mysql -uroot -e 'DROP DATABASE tests;'
mysql -uroot -e 'CREATE DATABASE tests;'

