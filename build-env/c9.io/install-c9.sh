# Bibliograph - Online Bibliographic Data Manager
# Build script for c9.io (Ubuntu)

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
BIB_CONF_DIR=bibliograph/services/config/

# checkout the latest qooxdoo master and build qooxdoo app
rm -rf $BIB_HTML_DIR/*
cd ../../bibliograph
git clone https://github.com/qooxdoo/qooxdoo.git
cd bibliograph
./generate.py -I build
cd ..

# add configuration files
cp $THIS_DIR/bibliograph.ini.php $BIB_CONF_DIR/bibliograph.ini.php
cp $THIS_DIR/server.conf.php $BIB_CONF_DIR/server.conf.php