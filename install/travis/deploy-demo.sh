#!/usr/bin/env bash
TARGET_DIR=~/public_html/travis/$TRAVIS_BRANCH/
PARENT_DIR=$(dirname $TARGET_DIR)
CONFIG_FILE=/var/www/bibliograph/server/config/app.conf.toml
echo " >>> Copying files to demo server..."
ssh demoserver mkdir -p $TARGET_DIR
scp ~/uploads/*php7.0.zip demoserver:$PARENT_DIR/bibliograph.zip
echo " >>> Deploying to target directory..."
ssh demoserver unzip -o -qq -u $PARENT_DIR/bibliograph.zip -d $TARGET_DIR
ssh demoserver cp $CONFIG_FILE $TARGET_DIR/server/config/
ssh demoserver chmod -R 0777 $TARGET_DIR/server/runtime
ssh demoserver rm $PARENT_DIR/bibliograph.zip
echo "Done."
