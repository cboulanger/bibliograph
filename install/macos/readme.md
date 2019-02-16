# Installing the dependencies on MacOS

## PHP (7.3) via Homebrew

Homebrew completely changed the way to install PHP, PECL and PEAR in April 2018. Here's how it currently seems to work
(February 2019), with no guarantees that it will work for you.

Please note: 
- Bibliograph works with MySQL version 5 only (latest is v5.7). Version 8 is backwards-incompatible and 
  currently not supported.
  
Instructions:
- Install homebrew as described here: https://brew.sh .You may need to install the XCode Command line 
  utilities with `xcode-select --install`. Check with `xcode-select -p` if they are installed. 
  You might have to re-own `/usr/local/Cellar/` in case permission problems occur.
- Install If you haven't already, `brew install nvm`, follow the instructions to set up nvm, then run 
  `nvm install 8` in a new shell.
- Run `bash ./install.sh` in this direcory
- If you get the messge "YAZ installation failed", you may need to manually copy the built extension 
  (look for the line "Installing 'user/local/Cellar/.../yaz.so'")  to the php extension dir (which you 
  can find out with `php -i | grep extension_dir`) with `cp <install dir> <extension dir>
- Install all dependencies with `npm install` in the top directory

If you run into problems, the following links might help:
- https://medium.com/@jjdanek/installing-php-extensions-on-mac-after-homebrew-acfddd6be602
- https://rinkovec.com/brew-missing-php-extensions/
- https://stackoverflow.com/questions/46652968/install-intl-php-extension-osx-high-sierra
- https://stackoverflow.com/questions/32893056/installing-pecl-and-pear-on-os-x-10-11-el-capitan-macos-10-12-sierra-macos-10
