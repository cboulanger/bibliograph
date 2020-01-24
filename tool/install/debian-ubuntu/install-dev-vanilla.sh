#!/usr/bin/env bash

# Bibliograph - Online Bibliographic Data Manager
# Build script to set up a development environment on a vanilla Debian/Ubuntu

set -o errexit # Exit on error
PHPVERSION=7.3
MYSQLVERSION=5.*

# Colorize output, see https://linuxtidbits.wordpress.com/2008/08/11/output-color-on-bash-scripts/
txtbld=$(tput bold)             # Bold
bldred=${txtbld}$(tput setaf 1) #  red
bldblu=${txtbld}$(tput setaf 4) #  blue
txtrst=$(tput sgr0)             # Reset
function section {
  echo $bldblu
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $txtrst
}

section "Installing LAMP server..."

# General packages
apt-get update && apt-get install -y \
  wget curl zip jq \
  build-essential

# Apache / PHP / MySQL
if [ "$(apt list php | grep ${PHPVERSION})" == "" ]; then
    apt -y install software-properties-common dirmngr apt-transport-https lsb-release ca-certificates
    add-apt-repository ppa:ondrej/php
    apt update
fi
apt-get install -y  apache2 mysql-server=${MYSQLVERSION} php${PHPVERSION} php-pear
apt-get install -y php${PHPVERSION}-{dev,ldap,curl,gd,intl,mbstring,mcrypt,xml,xsl,zip}

section "Installing bibliographic tools..."
sudo apt-get install -y yaz libyaz4-dev bibutils
pear channel-update pear.php.net && yes $'\n' | pecl install yaz && pear install Structures_LinkedList-0.2.2 && pear install File_MARC
[ -f /etc/php/${PHPVERSION}/mods-available/yaz.ini ] || echo "extension=yaz.so" > /etc/php/${PHPVERSION}/mods-available/yaz.ini
[ -f /etc/php/${PHPVERSION}/cli/conf.d/yaz.ini ] || ln -s /etc/php/${PHPVERSION}/mods-available/yaz.ini /etc/php/${PHPVERSION}/cli/conf.d/
[ -f /etc/php/${PHPVERSION}/cli/conf.d/yaz.ini ] || ln -s /etc/php/${PHPVERSION}/mods-available/yaz.ini /etc/php/${PHPVERSION}/apache2/conf.d/
service apache2 restart
sudo service apache2 restart
if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Installing composer..."
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version

section "Installing node and npm ..."
curl -sL https://deb.nodesource.com/setup_8.x | bash -
apt-get install -y nodejs

section "Installing qooxdoo and qxcompiler..."

section "Building Bibliograph..."
npm install
npm link mocha
pushd src/client/bibliograph
qx contrib update
qx contrib install
qx compile ../../../install/debian-ubuntu/compile.json --all-targets
popd
cp install/debian-ubuntu/app.conf.toml.dist src/server/config/app.conf.toml

section "Setting up Yii2 backend..."
pushd src/server/vendor
[[ -d bower ]] || ln -s bower-asset/ bower
popd

section "Starting MySql Server"
service mysql start
mysql -e 'CREATE DATABASE IF NOT EXISTS tests;'
mysql -e 'CREATE DATABASE IF NOT EXISTS bibliograph;'
mysql -e "DROP USER IF EXISTS 'bibliograph'@'localhost';"
mysql -e "CREATE USER 'bibliograph'@'localhost' IDENTIFIED BY 'bibliograph';"
mysql -e "GRANT ALL PRIVILEGES ON bibliograph.* TO 'bibliograph'@'localhost';"

section "Installation finished."
echo "Please review and adapt the 'src/server/config/app.conf.toml' config file:"
echo "- Enter the email address of the administrator in the [email] section (The application"
echo "  won't start otherwise) "
echo "- If you use an LDAP server for authentication, adapt the settings in the [ldap] section."
echo
echo "You can now execute:"
echo "- 'npm test': run unit, functional and api tests"
echo "- 'npm run test-dev': run unit, functional and api tests in development mode"
echo "- 'npm run server source': start a development server on localhost:9090"
