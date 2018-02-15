# Bibliograph - Online Bibliographic Data Manager
# Build script for Debian/Ubuntu 

set -o errexit # Exit on error

# Colorize output, see https://linuxtidbits.wordpress.com/2008/08/11/output-color-on-bash-scripts/
txtbld=$(tput bold)             # Bold
bldred=${txtbld}$(tput setaf 1) #  red
bldblu=${txtbld}$(tput setaf 4) #  blue
txtrst=$(tput sgr0)             # Reset
function section {
  echo $bldblue
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $txtrst
}

section "Installing required  packages..."

# Packages
apt-get update && apt-get install -y \
  apache2 libapache2-mod-php5 php5-cli
  mysql-server php5-mysql \
  bibutils \
  php5-dev php-pear \
  wget \
  php5-xsl php5-intl\
  yaz libyaz4-dev \
  curl \
  zip

# Install php-yaz
yes $'\n' | pecl install yaz
pear install Structures_LinkedList-0.2.2
pear install File_MARC
echo "extension=yaz.so" >> /etc/php5/apache2/php.ini
echo "extension=yaz.so" >> /etc/php5/cli/php.ini
  
# configure PHP with yaz/xsl/intl extensions
#  - if [[ ${TRAVIS_PHP_VERSION:0:1} == "5" ]]; then sudo apt-get install -y php5-xsl php5-intl; fi
#  - if [[ ${TRAVIS_PHP_VERSION:0:1} == "7" ]]; then sudo add-apt-repository -y ppa:ondrej/php && sudo apt-get update && sudo apt-get install -y php7.0-xsl php7.0-intl; fi  
#  - sudo apt-get install -y yaz libyaz4-dev
#  - pear channel-update pear.php.net && yes $'\n' | pecl install yaz && pear install Structures_LinkedList-0.2.2 && pear install File_MARC 
#  - sudo service apache2 restart
#  - if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Installing composer..."
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version

section "Installing node and npm ..."
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get install -y nodejs

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
qx compile ../../../build-env/travis/compile.json --all-targets 
popd

section "Setting up backend..."
pushd src/server
composer install
ln -s vendor/bower-assets vendor/bower
popd

section "Installation finished. Please complete the post-installation steps as per doc/install.md"