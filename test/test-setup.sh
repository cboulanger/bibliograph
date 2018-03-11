#!/bin/bash

# This test runs a temporary server on localhost:8080 and
# then tests the application setup

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
CPT_ARGS=""
SERVER_PATH=src/server
SERVER_CMD="yii serve 127.0.0.1:8080 -t=@app/tests"


# Start a PHP server and finish it when the script ends
pushd $SERVER_PATH > /dev/null
nohup php $SERVER_CMD &> /dev/null & 
bg_pid=$!
trap "kill -2 $bg_pid" 2
ps | grep "[p]hp $SERVER_CMD" > /dev/null
if [ $? -eq 1 ]; then
  echo "Failed to start test server..."
  exit 1
fi
echo "Started Bibliograph test server..."
echo

# Test new installation
echo "Creating empty database ..."
mysql -uroot -e "DROP DATABASE tests; CREATE DATABASE tests;"
echo "Calling application setup service ..."
${CPT_CMD} run api AASetupControllerCest --env setup $CPT_ARGS || exit $?
${CPT_CMD} run api AASetupControllerCest --env testing $CPT_ARGS || exit $?
echo

# Test for production upgrade
echo "Upgrading from 3.0.0-alpha to 3.0.0..."
${YII_CMD} migrate/create app\\migrations\\schema\\create_post_table --interactive=0
${CPT_CMD} run api AASetupControllerCest --env upgradev3 $CPT_ARGS
exitcode=$?
popd > /dev/null
rm src/server/migrations/schema/*Create_post_table.php
if [ "$exitcode" -ne "0" ]; then
   exit $exitcode;
fi
echo

# Test upgrade from v2 version
echo "Deleting log file..."
[[ -f runtime/logs/app.log ]] && rm runtime/logs/app.log
echo "Recreating empty database and importing Bibliograph v2 data..."
mysql -uroot -e "DROP DATABASE tests;"
mysql -uroot -e "CREATE DATABASE tests;"
mysql -uroot < test/data/bibliograph2.sql
echo "Testing upgrade from v2..."
pushd $SERVER_PATH > /dev/null
migration_path="app\\migrations\\schema\\bibliograph_datasource"
${YII_CMD} migrate/create ${migration_path}\\new_datasource_migration --migrationNamespaces=${migration_path} --interactive=0
${CPT_CMD} run api AASetupControllerCest --env upgradev2 $CPT_ARGS || exit $?
exitcode=$?
rm migrations/schema/bibliograph_datasource/*New_datasource_migration.php
if [ "$exitcode" -ne "0" ]; then
   exit $exitcode;
fi
${CPT_CMD} run api AASetupControllerCest --env testing $CPT_ARGS || exit $?
echo
echo "Cleaning up database ..."
#mysql -uroot -e "DROP DATABASE tests;"
#mysql -uroot -e "CREATE DATABASE tests;"

exit 0