#!/bin/bash

# This test runs unit and functional tests that do not need
# a webserver.

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
SERVER_PATH=src/server

pushd $SERVER_PATH > /dev/null

echo "Setting up database ..."
MIGRATE_ARGS="--interactive=0 --db=testdb --verbose"
$YII_CMD migrate/fresh --migrationNamespaces=app\\migrations\\schema $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\data $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\tests\\migrations $MIGRATE_ARGS &> /dev/null

echo "Running unit tests ..."
$CPT_CMD run unit || exit $?

$YII_CMD migrate/fresh --migrationNamespaces=app\\migrations\\schema $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\data $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\tests\\migrations $MIGRATE_ARGS &> /dev/null

echo "Running functional tests ..."
$CPT_CMD run functional -v || exit $?

echo "Tests finished. Cleaning up database ..."
$YII_CMD migrate/down all --interactive=0 --db=testdb > /dev/null
popd > /dev/null
exit 0
