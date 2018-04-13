#!/usr/bin/env bash

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
qx compile --target=build
cp -a build-compiled/bibliograph $DIST_DIR
cp -a build-compiled/resource $DIST_DIR
cp build-compiled/index.html $DIST_DIR

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


