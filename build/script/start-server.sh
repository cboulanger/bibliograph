#!/bin/bash

# Compiles and starts the application from the source dir
# This is currently MacOS only and should probably be a node script.

#set -o errexit

TARGET=${1:-source}
APP=${2:-bibliograph}
HOST=${3:-localhost:9090}
APP_PATH=client/bibliograph/compiled/$TARGET/$APP/index.html
COMPILE_PATH=$(pwd)/src/client/bibliograph
QX_CMD=$(which qx)
DOCUMENT_ROOT=src/

if ! [[ -d $COMPILE_PATH ]]; then
  echo "Cannot find client application - are you in the repo root?";
  exit 1;
fi

if [[ "$TARGET" != "only" ]]; then
    echo " >>> Compiling application..."
    pushd $COMPILE_PATH > /dev/null
    $QX_CMD compile --target=$TARGET
    popd > /dev/null
fi

ps | grep "[p]hp -S $HOST" > /dev/null
if [ $? -eq 0 ]; then
  echo " >>> PHP Server server is already running..."
else
  echo " >>> Starting PHP server..."
  pushd $DOCUMENT_ROOT > /dev/null
  php -S $HOST &> /dev/null &
  popd > /dev/null
fi

if [[ "$TARGET" == "only" ]]; then
  exit 0
fi

if [[ "$OSTYPE" == "darwin"* ]]; then
  # assume we have mysql from homebrew
  mysql.server stop &> /dev/null
  killall mysqld &> /dev/null
  killall mysqld_safe &> /dev/null
  mysql.server start
  echo " >>> Opening app in browser at http://$HOST/$APP_PATH"
  open -a "Google Chrome" http://$HOST/$APP_PATH
  # send Alt+Command+I to open Web inspector
  osascript -e 'tell application "System Events" to keystroke "i" using {option down, command down}'
  echo " >>> Waiting 2 minutes until page is loaded..."
  sleep 120s
  echo " >>> Start continuous compilation..."
  pushd $COMPILE_PATH > /dev/null
  qx compile --target=$TARGET --watch --clean=false
  popd > /dev/null
fi
