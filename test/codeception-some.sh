#!/bin/bash

#set -o errexit # Exit on error

pushd ./src/server > /dev/null
echo "Setting up database ..."
echo "travis_fold:start:migrate_up"
php yii migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema
php yii migrate/up    --interactive=0 --db=testdb --migrationNamespaces=app\\tests\\migrations
echo "travis_fold:end:migrate_up"

echo "Running tests..."
#php vendor/bin/codecept run unit
php vendor/bin/codecept run functional SetupControllerCest --debug


echo "Cleanup database ..."
echo "travis_fold:start:migrate_down"
php yii migrate/down all --interactive=0 --db=testdb 
echo "travis_fold:end:migrate_down"

popd > /dev/null

