# Bibliograph - Online Bibliographic Data Manager
# Build script for Debian/Ubuntu 

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

section "Installing required  packages..."

# General packages
apt-get update && apt-get install -y \
  apache2 \
  mysql-server \
  php7.0 php7.0-dev php-pear \
  wget curl zip \
  build-essential \
  bibutils 

# PHP extensions
apt-get install -y \
  php7.0-cli php7.0-ldap php7.0-curl php7.0-gd \
  php7.0-intl php7.0-json php7.0-mbstring \
  php7.0-mcrypt php7.0-mysql php7.0-opcache \
  php7.0-readline php7.0-xml php7.0-xsl php7.0-zip

# sudo add-apt-repository -y ppa:ondrej/php && sudo apt-get update && sudo apt-get install -y php7.0-xsl php7.0-intl; fi  
# sudo apt-get install -y yaz libyaz4-dev
# pear channel-update pear.php.net && yes $'\n' | pecl install yaz && pear install Structures_LinkedList-0.2.2 && pear install File_MARC 
# sudo service apache2 restart
# if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Installing composer..."
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version

section "Installing node and npm ..."
curl -sL https://deb.nodesource.com/setup_8.x | bash -
apt-get install -y nodejs

section "Installing qooxdoo..."
rm -rf qooxdoo-compiler
git clone --depth 1 https://github.com/qooxdoo/qooxdoo-compiler.git
pushd qooxdoo-compiler
npm link
popd
rm -rf qooxdoo
git clone --depth 1 https://github.com/qooxdoo/qooxdoo.git 

section "Building Bibliograph..."
pushd src/client/bibliograph
qx contrib update
qx contrib install
qx compile ../../../build-env/travis/compile.json --all-targets 
popd

section "Setting up Yii2 backend..."
pushd src/server
composer install
ln -s vendor/bower-assets vendor/bower
popd

section "Starting MySql Server"
service mysql start
mysql -e 'CREATE DATABASE IF NOT EXISTS tests;'

section "Installation finished."
echo "- Please complete the post-installation steps as per doc/install.md"
echo "- You can run tests with `npm test`"