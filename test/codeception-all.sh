#!/bin/bash

set -o errexit # Exit on error

echo "Setting up database ..."
echo "travis_fold:start:migrate_up"
pushd ./src/server > /dev/null
php yii migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema  > /dev/null
php yii migrate/up    --interactive=0 --db=testdb --migrationNamespaces=app\\tests\\migrations  > /dev/null
popd > /dev/null
echo "travis_fold:end:migrate_up"

echo "Running Unit tests..."
bash test/codeception run unit

echo "Running functional tests.."
bash test/codeception run functional  

echo "Cleanup database ..."
echo "travis_fold:start:migrate_down"
pushd ./src/server > /dev/null
php yii migrate/down all --interactive=0 --db=testdb > /dev/null
popd > /dev/null
echo "travis_fold:end:migrate_down"

echo "Running apit tests.."
bash test/api.sh