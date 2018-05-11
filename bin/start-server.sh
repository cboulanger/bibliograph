#!/bin/bash

# Compiles and starts the application from the source dir
# This is currently MacOS only and should probably be a node script. 

#set -o errexit

HOST=localhost:9090
SERVER_PATH=src/
TARGET=${1:-source}
APP_PATH=client/bibliograph/$TARGET-compiled/index.html
COMPILE_PATH=$(pwd)/src/client/bibliograph
QX_CMD=$(pwd)/src/vcslib/qooxdoo-compiler/qx

echo " >>> Compiling application..."
pushd $COMPILE_PATH > /dev/null
#$QX_CMD clean
$QX_CMD compile --target=$TARGET
popd > /dev/null  

ps | grep "[p]hp -S $HOST" > /dev/null
if [ $? -eq 0 ]; then
  echo " >>> PHP Server server is already running..."
else
  echo " >>> Starting PHP server..."
  pushd $SERVER_PATH > /dev/null
  php -S $HOST &> /dev/null &
  popd > /dev/null
fi

if [[ "$OSTYPE" == "darwin"* ]]; then
  # assume we have mysql from homebrew
  mysql.server stop &> /dev/null
  killall mysqld &> /dev/null
  killall mysqld_safe &> /dev/null
  mysql.server start
  echo " >>> Opening app in browser"
  open -a "Google Chrome" http://$HOST/$APP_PATH
  # send Alt+Command+I to open Web inspector
  osascript -e 'tell application "System Events" to keystroke "i" using {option down, command down}'
  # continuous compilation
  pushd $COMPILE_PATH > /dev/null
  qx compile --target=$TARGET --watch
  popd > /dev/null
fi