# Installing the dependencies on MacOS

## PHP (7.1) via Homebrew

Homebrew completely changed the way to install PHP, PECL and PEAR in April 2018. Here's how it currently seems to work
(November 2018), with no guarantees that it will work for you.
  
- Install homebrew as described here: https://brew.sh .You may need to install the XCode Command line utilities 
  with `xcode-select --install`. Check with `xcode-select -p` if they are installed. You might have to re-own 
  `/usr/local/Cellar/` in case permission problems occur.
- Install If you haven't already, `brew install nvm`, follow the instructions to set up nvm, then run `nvm install 8` in
  a new shell.`
- run `bash ./install.sh` in this direcory
- As of now, you need to manually copy the built extension (look for the line "Installing 'user/local/Cellar/.../yaz.so'") 
  to the php extension dir which you can find out with `php -i | grep extension_dir`.
  `cp <install dir> <extension dir>
- Check with `php -i | grep yaz` if yaz has been correctly installed.
- Install all dependencies with `npm install`

If you run into problems, the following links might help:
- https://medium.com/@jjdanek/installing-php-extensions-on-mac-after-homebrew-acfddd6be602
- https://rinkovec.com/brew-missing-php-extensions/
- https://stackoverflow.com/questions/46652968/install-intl-php-extension-osx-high-sierra