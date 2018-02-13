#!/bin/bash

set -o errexit # Exit on error

pushd ./src/server > /dev/null
echo "Setting up database ..."
php yii migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema  &> /dev/null
php yii migrate/up    --interactive=0 --db=testdb --migrationNamespaces=app\\tests\\migrations  &> /dev/null

echo "Running tests..."
#php vendor/bin/codecept run unit
#php vendor/bin/codecept run functional SetupControllerCest --debug
php vendor/bin/codecept run api --debug

echo "Cleanup database ..."
php yii migrate/down all --interactive=0 --db=testdb  &> /dev/null

popd > /dev/null

