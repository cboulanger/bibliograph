#!/bin/bash

# This test runs a temporary server on localhost:8080 and
# then tests different application setup scenarios
# requires a running mysql server

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
CPT_ARGS=""
SERVER_PATH=src/server
SERVER_CMD="yii serve 127.0.0.1:8080 -t=@app/tests"
BIBLIOGRAPH2_SQL_DUMP=test/data/bibliograph2.local.sql
#BIBLIOGRAPH2_SQL_DUMP=test/data/bibliograph-hu.local.sql

set -o errexit # Exit on error

# Colorize output, see https://linuxtidbits.wordpress.com/2008/08/11/output-color-on-bash-scripts/
txtbld=$(tput bold)             # Bold
bldred=${txtbld}$(tput setaf 1) #  red
bldblu=${txtbld}$(tput setaf 4) #  blue
txtrst=$(tput sgr0)             # Reset
function section {
  echo $bldred
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $txtrst
}

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

section "Testing new installation"

echo "Creating empty database ..."
mysql -uroot -e "DROP DATABASE tests;" || true
mysql -uroot -e "CREATE DATABASE tests;"
echo "Calling application setup service ..."
${CPT_CMD} run api AASetupControllerCest --env setup $CPT_ARGS || exit $?
${CPT_CMD} run api AASetupControllerCest --env testing $CPT_ARGS || exit $?
echo

section "Testing upgrade from 3.0.0-alpha to 3.0.0..."

${YII_CMD} migrate/create app\\migrations\\schema\\create_post_table --interactive=0
${CPT_CMD} run api AASetupControllerCest --env upgradev3 $CPT_ARGS
exitcode=$?
popd > /dev/null
rm src/server/migrations/schema/*Create_post_table.php
if [ "$exitcode" -ne "0" ]; then
   exit $exitcode;
fi
echo

section "Test upgrade from v2 version"

echo "Clearing log file and database..."
[[ -f runtime/logs/app.log ]] && rm runtime/logs/app.log
mysql -uroot -e "DROP DATABASE tests;"
mysql -uroot -e "CREATE DATABASE tests;"
echo "Importing Bibliograph v2 data..."
mysql -uroot tests < $BIBLIOGRAPH2_SQL_DUMP
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