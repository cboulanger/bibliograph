#!/usr/bin/env bash

# This test runs a temporary server on localhost:8080 and
# then runs the codeception API test suite.
SERVER_PATH=src/server
SERVER_CMD="yii serve 127.0.0.1:8080 -t=@app/tests"

# Start a PHP server and finish it when the script ends
pushd $SERVER_PATH > /dev/null
nohup php $SERVER_CMD &> /dev/null &
bg_pid=$!
trap "kill -2 $bg_pid" 2
ps | grep "[p]hp $SERVER_CMD" #> /dev/null
if [ $? -eq 1 ]; then
  echo "Failed to start test server..."
  exit 1
fi
echo "Started Bibliograph test server..."
echo