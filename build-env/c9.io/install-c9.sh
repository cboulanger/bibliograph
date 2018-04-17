# Bibliograph - Online Bibliographic Data Manager
# Build script for c9.io (Ubuntu) - currently needs a VM with at least 1GB RAM
# Call with bash build-env/c9.io/install-c9.sh

set -o errexit # Exit on error

# Paths
ROOT_DIR=/home/ubuntu/workspace
THIS_DIR=$ROOT_DIR/build-env/c9.io
BIB_CONF_DIR=$ROOT_DIR/src/server/config/
BIB_CONF_FILE=app.conf.toml
PHP_VERSION=7.0.29

echo " >>> Updating package list ..."
#sudo apt-get update > /dev/null

# Install phpbrew
if ! [ -x "$(command -v phpbrew)" ]; then
    echo " >>> Installing phpbrew ..."
    curl -L -O https://github.com/phpbrew/phpbrew/raw/master/phpbrew > /dev/null
    chmod +x phpbrew
    sudo mv phpbrew /usr/local/bin/
    (phpbrew init) > /dev/null
    echo "[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc" >> ~/.bashrc
    source ~/.phpbrew/bashrc
    phpbrew lookup-prefix ubuntu
fi

# Install PHP 7
# see https://community.c9.io/t/how-to-upgrade-to-php7/1379
if ! [ "$(php --version | head -n 1 | cut -d " " -f 2 | cut -c 1,3)" -eq "70" ]; then 
    echo " >>> Installing PHP version $PHP_VERSION via phpbrew. This might take a while ..."
    sudo apt-get install -y libssl-dev libcurl4-openssl-dev libmcrypt-dev > /dev/null
    phpbrew install $PHP_VERSION +default +ldap +xml +zip +intl +mbstring +json > /dev/null
    phpbrew switch $PHP_VERSION
else 
    echo " >>> PHP $PHP_VERSION already installed."
fi

# Install php-yaz
if ! [ "$(php -i | grep yaz)" ]; then 
    if ! [ "$(pecl list | grep yaz)" ]; then
        echo " >>> Installing PHP-YAZ via PECL..."
        sudo apt-get install -y bibutils yaz libyaz4-dev  > /dev/null
        pear channel-update pear.php.net > /dev/null
        (yes $'\n' | pecl install yaz) 
        echo " >>> Installing other needed PEAR libraries ..."
        pear install Structures_LinkedList-0.2.2 > /dev/null
        pear install File_MARC > /dev/null        
    else 
        echo " >>> PHP-YAZ already installed."
    fi
    INI_FILE=$(php -i | grep php.ini | tail -n 1 | sed "s/Loaded Configuration File => //")
    if ! [ "$(cat $INI_FILE | grep extension=yaz.so)" ] ; then 
        echo " >>> Configuring PHP and Apache..."
        echo "extension=yaz.so" >> $INI_FILE
        sudo service apache2 restart > /dev/null
    else
        echo " >>> PHP and Apache configured..."
    fi
else
    echo " >>> PHP-YAZ is already installed."
fi


echo " >>> Installing node v8.x."
source ~/.nvm/nvm.sh
nvm install 8 > /dev/null
nvm use 8

echo " >>> Installing project dependencies ..."
rm package-lock.json
npm install

echo " >>> Installing configuration file ..."
cp $THIS_DIR/$BIB_CONF_FILE $BIB_CONF_DIR
sed -e "s/\$C9_USER/$C9_USER/g" --in-place $BIB_CONF_DIR/$BIB_CONF_FILE

# start mysql server
mysql-ctl start
echo
echo "Installation complete. You can now start the application."