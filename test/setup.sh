#!/bin/bash

#set -o errexit # Exit on error
SERVER_PATH=src/server

# Test server
ps | grep "[p]hp yii serve 127.0.0.1:8080" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph test server is running..."
else
  echo "Starting Bibliograph test server..."
  pushd $SERVER_PATH > /dev/null
  php yii serve 127.0.0.1:8080 -t=@app/tests &> /dev/null &
  popd > /dev/null
fi

