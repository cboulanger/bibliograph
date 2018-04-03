#!/usr/bin/env bash

TOP_DIR=$(pwd)
DIST_DIR=$(pwd)/dist
SERVER_SRC_DIR=$(pwd)/src/server

# copy config file
cp -a $SERVER_SRC_DIR/config/bibliograph.ini.php $DIST_DIR/server/config

# start mysql server
mysql.server stop &> /dev/null
killall mysqld &> /dev/null
killall mysqld_safe &> /dev/null
mysql.server start

# open browser and start webserver
cd $DIST_DIR
open -a Safari http://localhost:8080
php -S localhost:8080