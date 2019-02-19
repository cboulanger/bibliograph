#!/usr/bin/env bash

# dependencies: apt-get install jq (linux) / brew install jq (MacOS with homebrew)
command -v jq >/dev/null 2>&1 || { echo >&2 "You need to install the jq command"; exit 1; }

BUILD_TARGET=${1:-dist-build}
TOP_DIR=$(pwd)
DIST_DIR=$TOP_DIR/dist
CLIENT_SRC_DIR=$TOP_DIR/src/client/bibliograph
SERVER_SRC_DIR=$TOP_DIR/src/server
VERSION=$(node -p -e "require('$TOP_DIR/package.json').version")
if [[ "$TRAVIS_BRANCH" != "" ]]; then
  VERSION="$VERSION-${TRAVIS_BRANCH}-travis-${TRAVIS_BUILD_NUMBER})"
fi
PHPVERSION=$(php -r "echo substr(phpversion(),0,3);")
QX_CMD=$(which qx)

if [[ ! -d "$DIST_DIR" ]]; then
    echo "Cannot find 'dist' subdirectory - are you in the top folder?"
fi

echo
echo "Building distributable package of Bibliograph from '$BUILD_TARGET' build target"
echo "using qx executable at $QX_CMD"

echo " >>> Building client ..."
cd $CLIENT_SRC_DIR
mv compile.json compile.old
jq ".environment[\"app.version\"]=\"$VERSION\"" compile.old > compile.json
$QX_CMD compile --target=$BUILD_TARGET --clean
rm compile.old

cp -a compiled/$BUILD_TARGET/bibliograph $DIST_DIR
cp -a compiled/$BUILD_TARGET/resource $DIST_DIR
if ! [[ $BUILD_TARGET == *"build"* ]]; then
  cp -a compiled/$BUILD_TARGET/transpiled $DIST_DIR
fi
cp compiled/$BUILD_TARGET/index.html $DIST_DIR

# cd $TOP_DIR
# bash build/script/modules-compile.sh $BUILD_TARGET

echo " >>> Building server ..."
cd $DIST_DIR/server
cp -a $SERVER_SRC_DIR/{config,controllers,lib,messages,migrations,models,modules,schema,views} .
if ! [[ $BUILD_TARGET == *"build"* ]]; then
  cp -a $SERVER_SRC_DIR/web .
fi
mkdir -p runtime/{cache,logs}
rm -f config/{app.conf.toml,message.php,test.php}

cp -a $SERVER_SRC_DIR/composer.* .
if [[ $BUILD_TARGET == *"source"* ]]; then
  composer install #> /dev/null
else
  composer install --no-dev  #&> /dev/null
fi
if ! [ -d ./vendor ] || ! [ -f ./vendor/autoload.php ]; then
 echo " !!! Composer install failed!"
 exit 1
fi
rm -f ./composer.* &> /dev/null

echo " >>> Adding documentation ..."
cp $TOP_DIR/{readme.md,release-notes.md} $DIST_DIR
echo $VERSION > $DIST_DIR/version.txt

echo " >>> Creating ZIP file ..."
cd $DIST_DIR
# remove git folders
( find . -type d -name ".git" ) | xargs rm -rf
zip -q -r bibliograph-$VERSION-php${PHPVERSION}.zip *

echo "Done."
exit 0
