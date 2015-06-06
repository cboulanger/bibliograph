#!/bin/bash
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
  ./source/class/bibliograph/Main.js \
  ./services/class/bibliograph/Application.php
  
# build the application for inclusion into the zip file
./generate.py build

echo 
echo "----------------------------------------------------------------------------"
echo "    Creating deployable ZIP-File"
echo "----------------------------------------------------------------------------"

echo ">>> Creating local copy..."

# remove a preexisting zip file and create a temporary folder
rm -f ../$filename.zip
mkdir -p bibliograph

# assemble the contents
cp -a ./build ./services ../readme.md ../release-notes.md \
  bibliograph/
mkdir bibliograph/plugins
for name in $(ls -d -- ./plugins/*/); do
  mkdir -p bibliograph/$name/services
  cp -a $name/services/* bibliograph/$name/services
done

# add a version file
echo "$pretty" > ./bibliograph/version.txt

echo ">>> Preparing for deployment..."
  
# remove what shouldn't go in there  
rm -rf \
  ./bibliograph/services/config/bibliograph.ini.php \
  ./bibliograph/services/config/server.conf.php \
  ./bibliograph/services/class/qcl/test/ \
  ./bibliograph/services/api \
  ./bibliograph/services/class/bibliograph/plugin/csl/citeproc-php/tests/ \
  ./bibliograph/plugins/template 

# create the zip file
zip -qr ../$filename.zip ./bibliograph/

# remove the temporary folder
rm -rf ./bibliograph

# remove version information from source code
$sedcmd -i -r "s|(/\*begin-version\*/)(.+)(/\*end-version\*/)|\1\"Development version\"\3|g" \
  ./source/class/bibliograph/Main.js \
  ./services/class/bibliograph/Application.php

FILESIZE=$(ls -lah "../$filename.zip" | awk -F " " {'print $5'})
echo ">>> Created $filename.zip ($FILESIZE)."