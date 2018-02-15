# Installing the dependencies on MacOS using homebrew

- Install homebrew as described here: https://brew.sh
- You might have to install the XCode Command line utilities with `xcode-select --install`, 
  for example if you get an error similar to "configure: error: Cannot find libz"
  Check with `xcode-select -p` if they are installed. 
- If you have already installed PHP 7 with homebrew, it might have been compiled without 
  PEAR/PECL support. If either´ `pear` or `pecl` is not an executable that can be called in 
  the shell, replace `install` with `reinstall` in the following script

```
brew update
brew install mysql
mysql.server start
brew install yaz autoconf bibutils
brew install php70 --with-pear
brew install php70-intl
pear install Structures_LinkedList-0.2.2
pear install File_MARC
yes $'\n' | pecl install yaz
```
If `pecl install yaz` aborts with "Call to a member function getFilelist()" after the
"Build process completed successfully", execute
```
printf "[yaz]\nextension='/usr/local/Cellar/php70/7.0.26_18/lib/php/extensions/no-debug-non-zts-20151012/yaz.so'" > /usr/local/etc/php/7.0/conf.d/ext-yaz.ini
```
You will have to adapt the path to `yaz.so` to whatever was shown in the result output. 

Check with `php -i | grep yaz` whether yaz has been correctly installed.

# Deployment

Install deployer:
```bash
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```


