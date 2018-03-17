#!/bin/bash

# Starts a server in "source" mode, i.e. running the uniminified sources
# This is currently MacOS only and should probably be a node script. 

#set -o errexit # Exit on error
HOST="localhost:9090"
SERVER_PATH="src/"
APP_PATH="client/bibliograph/source-compiled/index.html"
COMPILE_PATH="src/client/bibliograph"

# first compiler pass
echo "Compiling application..."
pushd $COMPILE_PATH > /dev/null
qx compile
popd > /dev/null  

# 'Production' server
ps | grep "[p]hp -S $HOST" > /dev/null
if [ $? -eq 0 ]; then
  echo "Bibliograph 'production' server is already running..."
else
  echo "Starting Bibliograph 'production' server..."
  pushd $SERVER_PATH > /dev/null
  php -S $HOST &> /dev/null &
  popd > /dev/null
fi

if [[ "$OSTYPE" == "darwin"* ]]; then
  # assume we have mysql from homebrew
  mysql.server status 2>&1 | grep SUCCESS || mysql.server start
  echo "Opening Safari browser"
  # Open Safari, better: https://www.npmjs.com/package/webpack-browser-plugin
  open -a Safari http://$HOST/$APP_PATH
  # send Alt+Command+I to open Web inspector
  osascript -e 'tell application "System Events" to keystroke "i" using {option down, command down}'
  # continuous compilation
  pushd $COMPILE_PATH > /dev/null
  qx compile --watch
  popd > /dev/null
fi