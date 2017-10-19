# Bibliograph - Online Bibliographic Data Manager
# Build script for c9.io (Ubuntu) - currently needs a VM with at least 1GB RAM
# Call with bash build-env/c9.io/install-c9.sh

# Packages
sudo apt-get update && apt-get install -y \
  bibutils \
  php5-dev \
  yaz libyaz4-dev 

# Install php-yaz
sudo yes $'\n' | pecl install yaz
sudo pear install Structures_LinkedList-0.2.2
sudo pear install File_MARC
sudo echo "extension=yaz.so" >> /etc/php5/apache2/php.ini
sudo echo "extension=yaz.so" >> /etc/php5/cli/php.ini
  
# Paths
ROOT_DIR=/home/ubuntu/workspace
THIS_DIR=$ROOT_DIR/build-env/c9.io
BIB_CONF_DIR=$ROOT_DIR/bibliograph/services/config/

# checkout the latest qooxdoo master and build qooxdoo app
cd $ROOT_DIR
git clone https://github.com/qooxdoo/qooxdoo.git
cd bibliograph
./generate.py -I source-hybrid

# add configuration files
cp $THIS_DIR/bibliograph.ini.php $BIB_CONF_DIR/bibliograph.ini.php
cp $THIS_DIR/server.conf.php $BIB_CONF_DIR/server.conf.php
sed -e "s/\$C9_USER/$C9_USER/g" --in-place $BIB_CONF_DIR/bibliograph.ini.php

# start mysql server
mysql-ctl start
echo
echo
echo "Installation complete. You can now start the server."