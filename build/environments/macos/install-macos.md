# Installing the dependencies on MacOS using homebrew

## PHP

- Install homebrew as described here: https://brew.sh
- You need to install the XCode Command line utilities with `xcode-select --install`.
  Check with `xcode-select -p` if they are installed. 

```
brew update
brew install mysql
mysql.server start
brew install yaz autoconf bibutils
brew install php@7.0
echo 'export PATH="/usr/local/opt/php@7.0/bin:$PATH"' >> ~/.bash_profile
echo 'export PATH="/usr/local/opt/php@7.0/sbin:$PATH"' >> ~/.bash_profile
pear install Structures_LinkedList-0.2.2
pear install File_MARC
yes $'\n' | pecl install yaz
```
Check with `php -i | grep yaz` if yaz has been correctly installed.

## Node
- `brew install nvm`
- `nvm install 8` 


