#!/usr/bin/env bash

BUILD_TARGET=${1:-build}
TOP_DIR=$(pwd)
DIST_DIR=$TOP_DIR/dist
CLIENT_SRC_DIR=$TOP_DIR/src/client/bibliograph
SERVER_SRC_DIR=$TOP_DIR/src/server
VERSION=$(node -p -e "require('$TOP_DIR/package.json').version")

if [[ ! -d "$DIST_DIR" ]]; then
    echo "Cannot find 'dist' subdirectory - are you in the top folder?"
fi

echo
echo "Building distributable package of Bibliograph in '$BUILD_TARGET' mode"

echo " >>> Building client ..."
cd $CLIENT_SRC_DIR
qx compile --target=$BUILD_TARGET > /dev/null
cp -a $BUILD_TARGET-compiled/bibliograph $DIST_DIR
cp -a $BUILD_TARGET-compiled/resource $DIST_DIR
[[ -d $BUILD_TARGET-compiled/transpiled ]] && cp -a $BUILD_TARGET-compiled/transpiled $DIST_DIR
cp $BUILD_TARGET-compiled/index.html $DIST_DIR

echo " >>> Building server ..."
cd $DIST_DIR/server
cp -a $SERVER_SRC_DIR/{config,controllers,lib,messages,migrations,models,modules,schema,runtime} .
if [[ $BUILD_TARGET == "source" ]]; then
  cp -a $SERVER_SRC_DIR/web .
fi
rm runtime/{cache,logs}/*
rm config/{app.conf.toml,message.php,test.php}

cp -a $SERVER_SRC_DIR/composer.* .
if [[ $BUILD_TARGET == "source" ]]; then
  composer install > /deve/null
else 
  composer install --no-dev  > /dev/null
fi
rm ./composer.*

echo " >>> Adding documentation ..."
cp $TOP_DIR/{readme.md,release-notes.md} $DIST_DIR
echo $VERSION > $DIST_DIR/version.txt

echo " >>> Creating ZIP file ..."
cd $DIST_DIR
zip -q -r bibliograph-$VERSION.zip *

echo "Done."

