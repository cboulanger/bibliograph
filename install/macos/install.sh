#!/usr/bin/env bash

PHPVERSION="7.1"
brew update
brew install jq
brew install mysql
mysql.server start
mysql -u root -e 'CREATE DATABASE IF NOT EXISTS bibliograph;'
mysql -u root -e "CREATE USER 'bibliograph'@'localhost' IDENTIFIED BY 'bibliograph';"
mysql -u root -e "GRANT ALL PRIVILEGES ON bibliograph.* TO 'bibliograph'@'localhost';"
brew install yaz autoconf bibutils
brew install php@${PHPVERSION}
echo 'export PATH="/usr/local/opt/php@'${PHPVERSION}/bin:$PATH'"' >> ~/.bash_profile
echo 'export PATH="/usr/local/opt/php@'${PHPVERSION}/sbin:$PATH'"' >> ~/.bash_profile
source ~/.bash_profile
curl -O http://pear.php.net/go-pear.phar
yes $'\n' | php -d detect_unicode=0 go-pear.phar
rm go-pear.phar
rm -f /usr/local/opt/php@${PHPVERSION}/bin/pear
pear channel-update pear.php.net
pear config-set temp_dir /tmp
pear config-set download_dir /tmp
pear install Structures_LinkedList-0.2.2
pear install File_MARC
pecl update-channels
yes $'\n' | pecl install yaz