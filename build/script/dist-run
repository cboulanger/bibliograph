#!/usr/bin/env bash

TOP_DIR=$(pwd)
DIST_DIR=$(pwd)/dist
SERVER_SRC_DIR=$(pwd)/src/server
HOST=localhost:9091

# copy config file
cp -a $SERVER_SRC_DIR/config/app.conf.toml $DIST_DIR/server/config

# start mysql server
mysql.server stop &> /dev/null
killall mysqld &> /dev/null
killall mysqld_safe &> /dev/null
mysql.server start

# open browser
cd $DIST_DIR
open -a "Google Chrome" http://$HOST

# start webserver (stops when script ends)
php -S $HOST