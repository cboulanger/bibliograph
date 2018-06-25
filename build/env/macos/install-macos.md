# Installing the development environment on MacOS with Homebrew

## PHP 

Homebrew completely changed the way to install PHP, PECL and PEAR in April 2018. Here's how it currently seems to work (June 2018), with no guarantees that it will work for you. You might want to consider to use https://php-osx.liip.ch/ instead.

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
pear_old channel-update pear.php.net
pear_old config-set temp_dir /tmp
pear_old config-set download_dir /tmp
pear_old install Structures_LinkedList-0.2.2
pear_old install File_MARC
pecl update-channels
yes $'\n' | pecl install yaz
# As of now (June 2018), you need to manually copy the built extension to the php extension dir
# Find out with `php -i | grep extension_dir`. 
cp /usr/local/Cellar/php@7.1/7.1.18_1/pecl/20160303/yaz.so /usr/local/Cellar/php@7.1/7.1.18_1/lib/php/20160303
```
Check with `php -i | grep yaz` if yaz has been correctly installed.

## Linters
- `brew install shellcheck`
- Travis (see below)

## Node
- `brew install nvm`
- `nvm install 8` 

## Travis
The [travis command line client](https://github.com/travis-ci/travis.rb/blob/master/README.md) is used to
lint the .travis.yaml file. `gem install travis` should work on any recent Mac OS version.

## Install git hooks
`echo "export GIT_HOOKS_DIR=./build/hooks" >> ~/.bash_profile`