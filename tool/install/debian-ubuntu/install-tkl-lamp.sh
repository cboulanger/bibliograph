#!/usr/bin/env bash

# Bibliograph - Online Bibliographic Data Manager
# Build script to set up a development environment on a TurnkeyLinux LAMP appliance
# with MySQL < 8.0

PHPVERSION=7.3

set -o errexit # Exit on error

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

section "Installing prerequisites and PHP extensions..."
sudo apt update -y && sudo apt upgrade -y
if [ "$(apt list php | grep ${PHPVERSION})" == "" ]; then
    apt -y install software-properties-common dirmngr apt-transport-https lsb-release ca-certificates
    add-apt-repository ppa:ondrej/php
    apt update
fi
apt-get install -y zip build-essential php${PHPVERSION} php-pear
apt-get install -y php${PHPVERSION}-{dev,ldap,curl,gd,intl,mbstring,xml,xsl,zip}

section "Installing bibliographic tools..."
apt-get install -y yaz libyaz4-dev bibutils jq
pear channel-update pear.php.net && yes $'\n' | pecl install yaz && \
  pear install Structures_LinkedList-0.2.2 && pear install File_MARC
[ -f /etc/php/${PHPVERSION}/mods-available/yaz.ini ] || echo "extension=yaz.so" > /etc/php/${PHPVERSION}/mods-available/yaz.ini
[ -f /etc/php/${PHPVERSION}/cli/conf.d/yaz.ini ] || ln -s /etc/php/${PHPVERSION}/mods-available/yaz.ini /etc/php/${PHPVERSION}/cli/conf.d/
[ -f /etc/php/${PHPVERSION}/cli/conf.d/yaz.ini ] || ln -s /etc/php/${PHPVERSION}/mods-available/yaz.ini /etc/php/${PHPVERSION}/apache2/conf.d/
systemctl restart apache2.service
if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Configuring MySql Server"
systemctl restart mariadb.service
mysql -e 'CREATE DATABASE IF NOT EXISTS bibliograph;'
mysql -e "DROP USER IF EXISTS 'bibliograph'@'localhost';"
mysql -e "CREATE USER 'bibliograph'@'localhost' IDENTIFIED BY 'bibliograph';"
mysql -e "GRANT ALL PRIVILEGES ON bibliograph.* TO 'bibliograph'@'localhost';"

section "Installation finished."
echo "Please review and adapt the 'src/server/config/app.conf.toml' config file:"
echo "- Enter the email address of the administrator in the [email] section (The application"
echo "  won't start otherwise) "
echo "- If you use an LDAP server for authentication, adapt the settings in the [ldap] section."
