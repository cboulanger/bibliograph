#!/bin/bash

# This test runs unit and functional tests that do not need
# a webserver.

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
SERVER_PATH=src/server
MIGRATE_ARGS="--interactive=0"

pushd $SERVER_PATH > /dev/null

echo "Setting up database ..."
mysql -uroot -e "DROP DATABASE tests;" || true
mysql -uroot -e "CREATE DATABASE tests;"

$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\schema $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\data $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\tests\\migrations $MIGRATE_ARGS &> /dev/null

echo "Running unit tests ..."
$CPT_CMD run unit || exit $?

echo "Restoring emtpy database ..."
mysql -uroot -e "DROP DATABASE tests;" || true
mysql -uroot -e "CREATE DATABASE tests;"

$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\schema $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\data $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\tests\\migrations $MIGRATE_ARGS &> /dev/null

echo "Running functional tests ..."
$CPT_CMD run functional -v || exit $?

echo "Tests finished."
popd > /dev/null
exit 0
