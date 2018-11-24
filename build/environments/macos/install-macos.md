# Installing the dependencies on MacOS

## PHP (7.1) via Homebrew

Homebrew completely changed the way to install PHP, PECL and PEAR in April 2018. Here's how it currently seems to work
(June 2018), with no guarantees that it will work for you. You might want to consider to use https://php-osx.liip.ch/ instead.

https://medium.com/@jjdanek/installing-php-extensions-on-mac-after-homebrew-acfddd6be602
https://rinkovec.com/brew-missing-php-extensions/
  
- Install homebrew as described here: https://brew.sh
- You may need to install the XCode Command line utilities with `xcode-select --install`.
  Check with `xcode-select -p` if they are installed. 
- You might have to re-own /usr/local/Cellar/ in case permission problems occur

```
brew update
brew install mysql
mysql.server start
brew install yaz autoconf bibutils
brew install php@7.1
echo 'export PATH="/usr/local/opt/php@7.1/bin:$PATH"' >> ~/.bash_profile
echo 'export PATH="/usr/local/opt/php@7.1/sbin:$PATH"' >> ~/.bash_profile
source ~/.bash_profile
curl -O http://pear.php.net/go-pear.phar
yes $'\n' | php -d detect_unicode=0 go-pear.phar
pear channel-update pear.php.net
pear config-set temp_dir /tmp
pear config-set download_dir /tmp
pear install Structures_LinkedList-0.2.2
pear install File_MARC
pecl update-channels
yes $'\n' | pecl install yaz
# As of now (November 2018), you need to manually copy the built extension (look for the line 
# "Installing 'user/local/Cellar/.../yaz.so'" to the php extension dir which you can find out 
# with `php -i | grep extension_dir`. 
cp <install dir> <extension dir>
```
Check with `php -i | grep yaz` if yaz has been correctly installed.

## Node
- `brew install nvm`
- `nvm install 8` 


