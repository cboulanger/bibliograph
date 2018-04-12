# Bibliograph - Online Bibliographic Data Manager
# Build script for c9.io (Ubuntu) - currently needs a VM with at least 1GB RAM
# Call with bash build-env/c9.io/install-c9.sh

# Paths
ROOT_DIR=/home/ubuntu/workspace
THIS_DIR=$ROOT_DIR/build-env/c9.io
BIB_CONF_DIR=$ROOT_DIR/src/server/config/

# Packages
sudo apt-get update 
sudo apt-get install -y \
  bibutils yaz libyaz4-dev \
  libssl-dev libcurl4-openssl-dev libmcrypt-dev

# Install PHP 7
# see https://community.c9.io/t/how-to-upgrade-to-php7/1379
curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew
chmod +x phpbrew
sudo mv phpbrew /usr/local/bin/
phpbrew init
echo "[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc" >> ~/.bashrc
phpbrew lookup-prefix ubuntu
phpbrew install 7.0 +default +ldap +xml +zip +intl +mbstring +json
phpbrew use 7

# Install php-yaz
# sudo yes $'\n' | pecl install yaz
# sudo pear install Structures_LinkedList-0.2.2
# sudo pear install File_MARC
# sudo echo "extension=yaz.so" >> /etc/php5/apache2/php.ini
# sudo echo "extension=yaz.so" >> /etc/php5/cli/php.ini

# Install other dependencies
bash $ROOT_DIR/bin/update-dependencies.sh

# add configuration files
cp $THIS_DIR/bibliograph.ini.php $BIB_CONF_DIR
cp $THIS_DIR/server.conf.php $BIB_CONF_DIR/server.conf.php
sed -e "s/\$C9_USER/$C9_USER/g" --in-place $BIB_CONF_DIR/bibliograph.ini.php

# start mysql server
mysql-ctl start
echo
echo
echo "Installation complete. You can now start the application."