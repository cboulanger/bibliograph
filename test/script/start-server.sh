#!/usr/bin/env bash

# This creates an empty database and runs a temporary server on localhost:8080
TARGET=${1:-}
APP=${2:-bibliograph}
HOST=${3:-127.0.0.1:9090}
QX_CMD=$(which qx)
COMPILE_PATH=$(pwd)/src/client/bibliograph
DOCUMENT_ROOT=src/

if ! [[ -d $COMPILE_PATH ]]; then
  echo "Cannot find client application - are you in the repo root?";
  exit 1;
fi

if [[ "$TARGET" == "" ]]; then
  echo "You need to provide a compile target as first parameter.";
  exit 1;
fi
APP_PATH=client/bibliograph/compiled/$TARGET/$APP/index.html

mysql -uroot -e "drop database if exists tests;" || (echo "MySQL server does not seem to be running" && exit 1)
mysql -uroot -e "create database tests;"
echo " >>> Created empty database ..."

echo " >>> Compiling application..."
pushd $COMPILE_PATH > /dev/null
$QX_CMD compile --target=$TARGET
popd > /dev/null

# Start a PHP server and finish it when the script ends
pushd $DOCUMENT_ROOT > /dev/null
php -S $HOST &> /dev/null &
PID=$!
echo " >>> Started Bibliograph test server with PID $PID."

if [[ "$OSTYPE" == "darwin"* ]]; then
  echo " >>> Opening app in browser at http://$HOST/$APP_PATH"
  open -a "Google Chrome" http://$HOST/$APP_PATH
  # send Alt+Command+I to open Web inspector
  osascript -e 'tell application "System Events" to keystroke "i" using {option down, command down}'
  popd > /dev/null
fi

echo "Done. Quit with CTRL-C or COMMAND-C."

# trap ctrl-c and call ctrl_c()
trap ctrl_c INT

function ctrl_c() {
  kill $PID;
  echo
  echo "Terminated test server."
  exit 0
}

# idle waiting for abort from user
read -r -d '' _ </dev/tty
