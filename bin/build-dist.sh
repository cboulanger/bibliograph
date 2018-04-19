#!/usr/bin/env bash

BUILD_TARGET=source
TOP_DIR=$(pwd)
DIST_DIR=$(pwd)/dist
CLIENT_SRC_DIR=$(pwd)/src/client/bibliograph
SERVER_SRC_DIR=$(pwd)/src/server
VERSION=$(node -p -e "require('$TOP_DIR/package.json').version")

if [[ ! -d "$DIST_DIR" ]]; then
    echo "Cannot find 'dist' subdirectory - are you in the top folder?"
fi

# Client files
cd $CLIENT_SRC_DIR
qx compile --target=$BUILD_TARGET
cp -a $BUILD_TARGET-compiled/bibliograph $DIST_DIR
cp -a $BUILD_TARGET-compiled/resource $DIST_DIR
[[ -d $BUILD_TARGET-compiled/transpiled ]] && cp -a $BUILD_TARGET-compiled/transpiled $DIST_DIR
cp $BUILD_TARGET-compiled/index.html $DIST_DIR

# Server files
cd $DIST_DIR/server
cp -a $SERVER_SRC_DIR/{config,controllers,lib,messages,migrations,models,modules,schema} .
rm config/{app.conf.toml,message.php,test.php}
mkdir -p runtime/cache
mkdir -p runtime/logs

cp -a $SERVER_SRC_DIR/composer.* .
composer install --no-dev
rm ./composer.*

# Documentation
cp $TOP_DIR/{readme.md,release-notes.md} $DIST_DIR
echo $VERSION > $DIST_DIR/version.txt

# package as zip
cd $DIST_DIR
zip -q -r bibliograph-$VERSION.zip *


