#!/usr/bin/env bash

BUILD_TARGET=${1:-source}
TOP_DIR=$(pwd)
DIST_DIR=$TOP_DIR/dist
CLIENT_SRC_DIR=$TOP_DIR/src/client/bibliograph
SERVER_SRC_DIR=$TOP_DIR/src/server
VERSION=$(node -p -e "require('$TOP_DIR/package.json').version")
QX_CMD=$TOP_DIR/src/vcslib/qooxdoo-compiler/qx

if [[ ! -d "$DIST_DIR" ]]; then
    echo "Cannot find 'dist' subdirectory - are you in the top folder?"
fi

echo
echo "Building distributable package of Bibliograph from '$BUILD_TARGET' build target"

echo " >>> Building client ..."
cd $CLIENT_SRC_DIR
$QX_CMD compile --target=$BUILD_TARGET --clean --feedback=false

cp -a $BUILD_TARGET-compiled/bibliograph $DIST_DIR
cp -a $BUILD_TARGET-compiled/resource $DIST_DIR
if ! [[ $BUILD_TARGET == *"build"* ]]; then
  cp -a $BUILD_TARGET-compiled/transpiled $DIST_DIR
fi
cp $BUILD_TARGET-compiled/index.html $DIST_DIR

echo " >>> Building server ..."
cd $DIST_DIR/server
cp -a $SERVER_SRC_DIR/{config,controllers,lib,messages,migrations,models,modules,schema,runtime} .
if ! [[ $BUILD_TARGET == *"build"* ]]; then
  cp -a $SERVER_SRC_DIR/web .
fi
rm -f runtime/{cache,logs}/*
rm -f config/{app.conf.toml,message.php,test.php}

cp -a $SERVER_SRC_DIR/composer.* .
if [[ $BUILD_TARGET == *"source"* ]]; then
  composer install > /dev/null
else 
  composer install --no-dev  &> /dev/null
fi
rm -f ./composer.* &> /dev/null

echo " >>> Adding documentation ..."
cp $TOP_DIR/{readme.md,release-notes.md} $DIST_DIR
echo $VERSION > $DIST_DIR/version.txt

echo " >>> Creating ZIP file ..."
cd $DIST_DIR
zip -q -r bibliograph-$VERSION.zip *

echo "Done."

