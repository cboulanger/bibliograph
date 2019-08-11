#!/bin/bash

# This test runs a temporary server on localhost:8080 and
# then runs the codeception API test suite.

YII_CMD="php yii-test"
CPT_CMD="php vendor/bin/codecept"
CPT_ENV=${1:-setup}
SERVER_PATH=src/server
SERVER_CMD="${YII_CMD} serve 127.0.0.1:8080 -t=@app/tests"

# Start a PHP server and finish it when the script ends
pushd $SERVER_PATH > /dev/null
$SERVER_CMD &> server.out &
bg_pid=$!
trap "kill -2 $bg_pid" 2
# ps ax | grep "[p]hp $SERVER_CMD" > /dev/null
# if [ $? -eq 1 ]; then
#   echo "Failed to start test server..."
#  exit 1
# fi
echo "Started Bibliograph test server..."
echo

echo "Creating empty database ..."
mysql -uroot -e "DROP DATABASE tests; CREATE DATABASE tests;"
echo "Deleting log and output data files..."
[[ -f runtime/logs/app.log ]] && rm runtime/logs/app.log
[[ -f runtime/logs/error.log ]] && rm runtime/logs/error.log
rm tests/_output/*fail*

echo
echo "Running API tests..."
$CPT_CMD run api --env $CPT_ENV || exit $?
echo
if [[ "$USER" == "travis" ]]; then
  echo "travis_fold:start:server_log"
  echo "Server log:"
  cat server.out
  echo "travis_fold:end:server_log"
fi
rm server.out
popd > /dev/null

# echo "Running Mocha tests..."
# mocha -- ./test/**/*.test.js || exit $?
# echo
# echo "Cleaning up database ..."
# pushd $SERVER_PATH > /dev/null
# $YII_CMD migrate/down all $MIGRATE_ARGS > /dev/null
#popd > /dev/null
exit 0
