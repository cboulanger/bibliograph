# Installing the dependencies on MacOS using homebrew

- Install homebrew as described here: https://brew.sh
- You might have to install the XCode Command line utilities `xcode-select --install`
```
brew install php70 --with-pear
brew install php70-intl yaz
pear install Structures_LinkedList-0.2.2
pear install File_MARC
yes $'\n' | pecl install yaz
```
if `pecl install yaz` aborts with an error during the last "installing..." step, try
```
printf "[yaz]\nextension='/usr/local/Cellar/php70/7.0.26_18/lib/php/extensions/no-debug-non-zts-20151012/yaz.so'" > /usr/local/etc/php/7.0/conf.d/ext-yaz.ini
```
You will have to adapt the path to `yaz.so` to whatever was shown in the result output. 

