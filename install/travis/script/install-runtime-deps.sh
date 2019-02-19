#!/usr/bin/env bash

# Install runtime dependecies (YAZ, bibutils)
sudo apt-get install -y yaz libyaz4-dev bibutils
pear channel-update pear.php.net && yes $'\n' | pecl install yaz
if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ;
then echo "YAZ is installed";
else echo "YAZ installation failed"; exit 1; fi;
mysql -e 'CREATE DATABASE IF NOT EXISTS tests;'
cp install/travis/app.conf.toml src/server/config
