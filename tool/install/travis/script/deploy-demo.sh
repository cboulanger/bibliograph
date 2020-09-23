#!/usr/bin/env bash

TARGET_DIR=~/public_html/bibliograph/$TRAVIS_BRANCH/
PARENT_DIR=$(dirname $TARGET_DIR)
CONFIG_FILE=/var/www/bibliograph/server/config/app.conf.toml
PHPVERSION=7.0

echo " >>> Copying files to demo server..."
ssh demoserver mkdir -p $TARGET_DIR
scp ~/uploads/*php${PHPVERSION}.zip demoserver:$PARENT_DIR/bibliograph.zip

echo " >>> Deploying to target directory..."
ssh demoserver rm -rf $TARGET_DIR
ssh demoserver unzip -o -qq -u $PARENT_DIR/bibliograph.zip -d $TARGET_DIR
ssh demoserver cp $CONFIG_FILE $TARGET_DIR/server/config/
ssh demoserver chmod -R 0777 $TARGET_DIR/server/runtime
ssh demoserver rm $PARENT_DIR/bibliograph.zip
echo "Done."

