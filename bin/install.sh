#!/usr/bin/env bash

set -e # break on errors

echo
echo "Installing latest (development) version of Bibliograph"
echo "======================================================"

# ------- VARIABLES --------

# The directory in which to install the package
INSTALL_DIR=/path/to/install/dir

# Database(s) to clone
DB_LIST="bibliograph"

# Mysql credentials
USERNAME=bibliograph
PASSWORD=bibliograph

# Web host
HOST=https://bibliograph.domain.org

# ------- INSTALLATION --------

[[ -d $INSTALL_DIR ]] || ( echo "Error: Directory $INSTALL_DIR does not exist." && exit 1 )

DOWNLOAD_URL=$(curl -s https://api.github.com/repos/cboulanger/bibliograph/releases | grep -m 1 browser_download_url | cut -d '"' -f 4 )

echo "  >>> Downloading latest release from GitHub ..."
TMP_NAME=bibliograph_tmp
wget -q -O $TMP_NAME.zip $DOWNLOAD_URL
echo "  >>> Unpacking ..."
unzip -qq -u $TMP_NAME.zip -d $TMP_NAME
# remove git folders in composer dependencies
( find $TMP_NAME -type d -name ".git" ) | xargs rm -rf

# check version
FULL_VERSION=$(cat $TMP_NAME/version.txt)
INST_VER_FILE=./installed_version.txt
[[ -f $INST_VER_FILE ]] || touch $INST_VER_FILE
[[ $(cat $INST_VER_FILE) == "$FULL_VERSION" ]] || ( echo "Version $FULL_VERSION already installed" && exit 0)

# this strips alpha/beta number OR patch version
VERSION=$(echo $FULL_VERSION | sed -r s/\(alpha\|beta\)\.[0-9]\+/\\1/ | sed -r s/\([0-9]+\.[0-9]+\)\.[0-9]+$/\\1/)
TARGET_DIR=$INSTALL_DIR/bibliograph.$VERSION

echo "  >>> Installing version $FULL_VERSION ..."
[[ -d $TARGET_DIR ]] || mkdir $TARGET_DIR
cp -a $TMP_NAME/* $TARGET_DIR
chmod 0777 $TARGET_DIR/server/runtime/{cache,logs}

echo "  >>> Removing temporary files..."
rm -rf $TMP_NAME*

echo "  >>> Saving installed version ..."
echo $FULL_VERSION > $INST_VER_FILE

# ------- DATABASE --------

TARGET_DB="bibliograph_$VERSION"
export MYSQL_PWD=$PASSWORD
echo "  >>> Removing database '$TARGET_DB' if it exists..."
mysqladmin \
  --user=$USERNAME -f \
  drop $TARGET_DB || true
echo "  >>> Creating database '$TARGET_DB' ..."
mysqladmin \
  --user=$USERNAME \
  create $TARGET_DB || true
for db in $DB_LIST; do
  echo "  >>> Cloning database '$db' into '$TARGET_DB'..."
  mysqldump \
    --user=$USERNAME \
    $db \
  | sed s/bibliograph\.schema\.huBerlinRewi/bibliograph_extended/g | \
  mysql \
    --user=$USERNAME \
    $TARGET_DB
done

# ------- CONFIG --------
echo "  >>> Adding configuration data ..."
cat bibliograph.ini.php \
  | sed -r s/\\{\\{database\\}\\}/$TARGET_DB/g \
  > $TARGET_DIR/server/config/bibliograph.ini.php

echo "Done. Open application at $HOST/bibliograph.$VERSION"