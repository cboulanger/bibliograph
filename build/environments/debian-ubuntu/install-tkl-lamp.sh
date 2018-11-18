#!/usr/bin/env bash

# Bibliograph - Online Bibliographic Data Manager
# Build script to set up a development environment on a TurnkeyLinux LAMP appliance ($PHPVERSION)

PHPVERSION=7.0

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
apt-get install -y zip build-essential php-pear
apt-get install -y php$PHPVERSION-{dev,ldap,curl,gd,intl,mbstring,mcrypt,xml,xsl,zip}

section "Installing bibliographic tools..."
apt-get install -y yaz libyaz4-dev bibutils
pear channel-update pear.php.net && yes $'\n' | pecl install yaz && \
  pear install Structures_LinkedList-0.2.2 && pear install File_MARC
echo "extension=yaz.so" > /etc/php/$PHPVERSION/mods-available/yaz.ini
[ -f /etc/php/7.0/cli/conf.d/yaz.ini ] || ln -s /etc/php/7.0/mods-available/yaz.ini /etc/php/7.0/cli/conf.d/
[ -f /etc/php/7.0/cli/conf.d/yaz.ini ] || ln -s /etc/php/7.0/mods-available/yaz.ini /etc/php/7.0/apache2/conf.d/
service apache2 restart
if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Configuring MySql Server"
service mysql start
mysql -e 'CREATE DATABASE IF NOT EXISTS bibliograph;'
mysql -e "DROP USER IF EXISTS 'bibliograph'@'localhost';"
mysql -e "CREATE USER 'bibliograph'@'localhost' IDENTIFIED BY 'bibliograph';"
mysql -e "GRANT ALL PRIVILEGES ON bibliograph.* TO 'bibliograph'@'localhost';"

section "Installation finished."
echo "Please review and adapt the 'src/server/config/app.conf.toml' config file:"
echo "- Enter the email address of the administrator in the [email] section (The application"
echo "  won't start otherwise) "
echo "- If you use an LDAP server for authentication, adapt the settings in the [ldap] section."
