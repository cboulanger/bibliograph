#!/bin/bash
# Creates a deployable ZIP file

BIBLIOGRAPH_DIR=../bibliograph

# the variables we'll need
version=$(cat version.txt)
today=$(date +"%Y-%m-%d")
hash=$(git log --pretty=format:'%h' -n 1)
pretty="$version ($today, $hash)"
filename="bibliograph-$version"

# this requires the GNU-sed
if [ -f '/usr/local/opt/gnu-sed/bin/sed' ]
then
    sedcmd='/usr/local/opt/gnu-sed/bin/sed' # we're on a mac with GNU sed installe via HomeBrew
else
    sedcmd='sed' # this is normal linux
fi

# update version information in files
$sedcmd -i -r "s|(/\*begin-version\*/)(.+)(/\*end-version\*/)|\1\"$pretty\"\3|g" \
  $BIBLIOGRAPH_DIR/source/class/bibliograph/Main.js \
  $BIBLIOGRAPH_DIR/services/class/bibliograph/Application.php
  
# build the application for inclusion into the zip file
cd $BIBLIOGRAPH_DIR
./generate.py build

echo 
echo "----------------------------------------------------------------------------"
echo "    Creating deployable ZIP-File"
echo "----------------------------------------------------------------------------"

echo ">>> Creating local copy..."

# remove a preexisting zip file and create a temporary folder
rm -f ../$filename.zip
mkdir -p dist

# assemble the contents
cp -a ./build ./services ../readme.md ../release-notes.md \
  dist/
mkdir dist/plugins
for name in $(ls -d -- ./plugins/*/); do
  mkdir -p dist/$name/services
  cp -a $name/services/* dist/$name/services
done

# add a version file
echo "$pretty" > ./dist/version.txt

echo ">>> Preparing for deployment..."
  
# remove what shouldn't go in there  
rm -rf \
  ./dist/services/config/bibliograph.ini.php \
  ./dist/services/config/server.conf.php \
  ./dist/services/class/qcl/test/ \
  ./dist/services/api \
  ./dist/services/class/bibliograph/plugin/csl/citeproc-php/tests/ \
  ./dist/plugins/template

# remove plugins that are not ready to ship or are deprecated
# rm -rf  ./dist/plugins/isbnscanner
rm -rf  ./dist/plugins/bookends
rm -rf  ./dist/plugins/mdbackup
rm -rf ./dist/plugins/nnforum/services/www/Forum/*

# create the zip file
mv dist bibliograph
zip -qr ../$filename.zip ./bibliograph/
rm -rf ./bibliograph

# remove version information from source code
$sedcmd -i -r "s|(/\*begin-version\*/)(.+)(/\*end-version\*/)|\1\"Development version\"\3|g" \
  $BIBLIOGRAPH_DIR/source/class/bibliograph/Main.js \
  $BIBLIOGRAPH_DIR/services/class/bibliograph/Application.php

FILESIZE=$(ls -lah "../$filename.zip" | awk -F " " {'print $5'})
echo ">>> Created $filename.zip ($FILESIZE)."