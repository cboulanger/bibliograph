#!/bin/bash

# This test runs a temporary server on localhost:8080 and
# then runs the codeception API test suite. 

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
CPT_ENV=${1:-testing}
SERVER_PATH=src/server
SERVER_CMD="yii serve 127.0.0.1:8080 -t=@app/tests &> /dev/null"

pushd $SERVER_PATH > /dev/null

echo "Starting Bibliograph test server..."
# Start a PHP server and finish it when the script ends
nohup php $SERVER_CMD &
bg_pid=$!
trap "kill -2 $bg_pid" 2
ps | grep "[p]hp $SERVER_CMD" > /dev/null
if [ $? -eq 1 ]; then
  echo "Failed to start test server..."
  exit 1
fi

echo "Setting up database ..."
MIGRATE_ARGS="--interactive=0 --db=testdb"
$YII_CMD migrate/fresh --migrationNamespaces=app\\migrations\\schema $MIGRATE_ARGS &> /dev/null
$YII_CMD migrate/up --migrationNamespaces=app\\migrations\\data $MIGRATE_ARGS &> /dev/null
#$YII_CMD migrate/up --migrationNamespaces=app\\tests\\migrations $MIGRATE_ARGS &> /dev/null

echo "Running Codeception tests..."
$CPT_CMD run api --env $CPT_ENV || exit $?
popd > /dev/null

echo "Running Mocha tests..."
mocha -- ./test/**/*.test.js || exit $?

echo "Cleaning up database ..."
pushd $SERVER_PATH > /dev/null
$YII_CMD migrate/down all $MIGRATE_ARGS > /dev/null
popd > /dev/null
exit 0