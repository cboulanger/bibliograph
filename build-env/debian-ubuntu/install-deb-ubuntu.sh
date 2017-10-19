# Bibliograph - Online Bibliographic Data Manager
# Build script for Debian/Ubuntu 
# Hasn't been tested yet
# run build-env/debian-ubuntu/install-deb-ubuntu.sh

# Packages
apt-get update && apt-get install -y \
  apache2 libapache2-mod-php5 php5-cli
  mysql-server php5-mysql \
  bibutils \
  php5-dev php-pear \
  wget \
  php5-xsl php5-intl\
  yaz libyaz4-dev \
  zip \
  git

# Install php-yaz
pecl install yaz
pear install Structures_LinkedList-0.2.2
pear install File_MARC
echo "extension=yaz.so" >> /etc/php5/apache2/php.ini
echo "extension=yaz.so" >> /etc/php5/cli/php.ini
  
# Constants
THIS_DIR=dirname "$(readlink -f "$0")"
BIB_HTML_DIR=/var/www/html
BIB_VAR_DIR=/var/lib/bibliograph
BIB_CONF_DIR=/var/www/html/bibliograph/services/config/

# checkout the latest qooxdoo master and build qooxdoo app
rm -rf $BIB_HTML_DIR/*
cd ../../bibliograph
git clone https://github.com/qooxdoo/qooxdoo.git
cd bibliograph
./generate.py -I build
cd ..

# Place files in target location
mkdir $BIB_HTML_DIR/bibliograph
mkdir -p $BIB_VAR_DIR && chmod 0777 $BIB_VAR_DIR 
ln -s bibliograph/build $BIB_HTML_DIR/bibliograph/build
ln -s bibliograph/services $BIB_HTML_DIR/bibliograph/services
echo "<?php header('location: /bibliograph/build');" > $BIB_HTML_DIR/index.php 

# add configuration files
cp $THIS_DIR/bibliograph.ini.php $BIB_CONF_DIR/bibliograph.ini.php
cp $THIS_DIR/server.conf.php $BIB_CONF_DIR/server.conf.php
cp $THIS_DIR/plugins.txt $BIB_HTML_DIR/plugins.txt