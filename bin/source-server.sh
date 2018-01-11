#!/bin/bash

# Starts a server in "source" mode, i.e. running the uniminified sources
# This is currently MacOS only and should probably be a node script. 

#set -o errexit # Exit on error
HOST="localhost:9090"
SERVER_PATH="src/"
APP_PATH="client/bibliograph/source-compiled/bibliograph/index.html"

# 'Production' server
ps | grep "[p]hp -S $HOST" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph 'production' server is already running..."
else
  echo "Starting Bibliograph 'production' server..."
  pushd $SERVER_PATH > /dev/null
  php -s $HOST &> /dev/null &
  popd > /dev/null
fi

# Better: https://www.npmjs.com/package/webpack-browser-plugin
open -a Safari http://$HOST/$APP_PATH