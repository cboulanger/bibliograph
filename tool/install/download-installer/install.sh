#!/usr/bin/env bash

# Downloads the latest release of bibliograph from GitHub and installs it or updates an
# existing installation (migrating existing data)

set -e # break on errors

# ------- DEFAULT VALUES --------

# The directory in which to install the package
INSTALL_DIR=/var/www

# The last part of the version suffix, replaces alpha/beta plus number
DEV_VERSION_SUFFIX=dev

# Database(s) to clone
DB_LIST=""

# Name of configuration file
CONFIG_FILE=app.conf.toml

# Name of the PHP script that is the entry point to the server
SERVER_SCRIPT=server.php

# Mysql credentials
USERNAME=bibliograph
PASSWORD=bibliograph

# Web host
HOST=https://bibliograph.domain.org

# Logo
LOGO_FILE=

# Path to dir where datasource backup should be created. If left empty, the system temp dir is used
BACKUP_PATH=

# Url from which to download the latest release
DOWNLOAD_URL=$(curl -s https://api.github.com/repos/cboulanger/bibliograph/releases | grep -m 1 browser_download_url | cut -d '"' -f 4 )


echo
echo "Installing latest (development) version of Bibliograph"
echo "======================================================"

# ------ CLI ARGUMENTS -------
# https://gist.github.com/cosimo/3760587

# TODO --stable-only
# TODO --skip-download
# TODO --keep-download
INTERACTIVE=1
OPTS=`getopt -o I --long non-interactive -n 'parse-options' -- "$@"`
while true; do
  case "$1" in
    -I | --non-interactive ) INTERACTIVE=0; shift ;;
    -- ) ;;
    * ) if [ -z "$1" ]; then break; else echo "$1 is not a valid option"; exit 1; fi;;
  esac
done


# ------- DOWNLOAD RELEASE --------

TMP_NAME=bibliograph_tmp

echo
if [ -d "$TMP_NAME" ] ; then
  echo " >>> Latest release already downloaded ..."
else
  echo " >>> Downloading latest release from GitHub ..."
  wget -q -O $TMP_NAME.zip $DOWNLOAD_URL
  echo " >>> Unpacking ..."
  unzip -qq -u $TMP_NAME.zip -d $TMP_NAME
fi

# check version
FULL_VERSION=$(cat $TMP_NAME/version.txt)
INST_VER_FILE=./installed_version.txt
[[ -f $INST_VER_FILE ]] || touch $INST_VER_FILE
#[[ $(cat $INST_VER_FILE) == "$FULL_VERSION" ]] || ( echo "Version $FULL_VERSION already installed" && exit 0)

echo " >>> Latest version is $FULL_VERSION."
echo

# ------- INTERACTIVE --------

if [[ "$INTERACTIVE" == 1 ]] ; then

  default=$INSTALL_DIR
  read -p "Enter installation directory [$default]: " INSTALL_DIR
  INSTALL_DIR=${INSTALL_DIR:-$default}
  [[ -d $INSTALL_DIR ]] || ( echo "Error: Directory $INSTALL_DIR does not exist." && exit 1 )

  default=$DEV_VERSION_SUFFIX
  read -p "Enter version suffix for database and file path [$default]: " DEV_VERSION_SUFFIX
  DEV_VERSION_SUFFIX=${DEV_VERSION_SUFFIX:-$default}

  default=$DB_LIST
  read -p "Enter list of databases from which to migrate data [$default]: " DB_LIST
  DB_LIST=${DB_LIST:-$default}

  default=$USERNAME
  read -p "Enter MySQL username [$default]: " USERNAME
  USERNAME=${USERNAME:-$default}

  default=$PASSWORD
  read -s -p "Enter MySQL password [********]: " PASSWORD
  PASSWORD=${PASSWORD:-$default}
  echo

  default=$BACKUP_PATH
  read -s -p "Enter the path to a directory in which backups can be stored. If left empty, the system temporary folder will be used [$default]: " BACKUP_PATH
  BACKUP_PATH=${BACKUP_PATH:-$default}
  echo
fi

# this strips alpha/beta number or patch version
VERSION=$(echo $FULL_VERSION | sed -r s/\(alpha\|beta\)\.[0-9]\+/$DEV_VERSION_SUFFIX/ | sed -r s/\([0-9]+\.[0-9]+\)\.[0-9]+$/\\1/)

TARGET_BASE_DIR=bibliograph.$VERSION

if [[ "$INTERACTIVE" == 1 ]] ; then
  default=$TARGET_BASE_DIR
  read -p "Enter target directory [$default]: " TARGET_BASE_DIR
  TARGET_BASE_DIR=${TARGET_BASE_DIR:-$default}
fi
TARGET_DIR=$INSTALL_DIR/bibliograph.$VERSION

TARGET_DB="bibliograph_$VERSION"
if [[ "$INTERACTIVE" == 1 ]] ; then
  default=$TARGET_DB
  read -p "Enter target database into which to migrate data [$default]: " TARGET_DB
  TARGET_DB=${TARGET_DB:-$default}
fi

# ------- VALIDATION --------

if [[ "$DB_LIST" != "$TARGET_DB" && "$DB_LIST" == *"$TARGET_DB"* ]]; then
        echo "You cannot have the target database be in the list of databases from which to migrate data." 1>&2
        exit 1
fi

# ------- CONFIRM OR ABORT--------
echo
echo "This will installl version $FULL_VERSION with the following settings:"
echo -n " - Installation directory: $TARGET_DIR"
[[ -d $TARGET_DIR ]] && echo -n " (exists and will be overwritten)"
echo
echo " - Database(s) from which data will be migrated: $DB_LIST"
echo " - Database into which data will be migrated: $TARGET_DB"
echo " - Backups are stored in: $BACKUP_PATH"
echo
if [[ "$INTERACTIVE" == 1 ]] ; then
  read -r -p "Are you sure? (y/n) [no] " response
  case "$response" in
      [yY][eE][sS]|[yY])
          # pass
          ;;
      *)
          exit 0;
          ;;
  esac
else
  # wait 5 seconds so user can abort script
  echo "Press Control-C now if this is not what you want..."
  sleep 5
fi

 # ------- CODE --------

[[ -d $TARGET_DIR ]] && ( echo "  >>> Deleting existing installation ..." && rm -rf $TARGET_DIR ) || true

echo "  >>> Copying files ..."
mkdir -p $TARGET_DIR
cp -a $TMP_NAME/* $TARGET_DIR
mkdir -p $TARGET_DIR/server/runtime/{cache,logs}
chmod 0777 $TARGET_DIR/server/runtime/{cache,logs}

echo "  >>> Removing temporary files..."
rm -rf $TMP_NAME*

echo "  >>> Saving installed version ..."
echo $FULL_VERSION > $INST_VER_FILE

# ------- DATABASE --------

if [ "$DB_LIST" == "$TARGET_DB" ]; then
  echo "  >>> Source and target database are identical => no database migration necessary."
else
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
           --default-character-set=utf8 \
           --set-charset \
           $db \
         | sed s/bibliograph\.schema\.huBerlinRewi/bibliograph_extended/g \
         | sed s/$db/$TARGET_DB/g \
         | sed s/\'0000-00-00\'/NULL/g \
         | iconv -f utf-8 -t utf-8 -c \
         | mysql \
           --user=$USERNAME \
           --default-character-set=utf8 \
           $TARGET_DB
        done
fi

# ------- CONFIG --------
echo "  >>> Adding server script and configuration data ..."

cat $SERVER_SCRIPT \
  | sed -r "s|\\{\\{BACKUP_PATH\\}\\}|$BACKUP_PATH|g" \
  > $TARGET_DIR/server.php

cat $CONFIG_FILE \
  | sed -r "s|\\{\\{database\\}\\}|$TARGET_DB|g" \
  > $TARGET_DIR/server/config/app.conf.toml


# ------- Logo --------
if [[ "$LOGO_FILE" != "" ]]; then
    echo "  >>> Copying logo file ..."
    cp $LOGO_FILE $TARGET_DIR/resource/bibliograph/icon/bibliograph-logo.png
fi