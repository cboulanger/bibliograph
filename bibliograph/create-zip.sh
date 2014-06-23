# the variables we'll need
version=$(cat version.txt)
today=$(date +"%Y-%m-%d")
hash=$(git log --pretty=format:'%h' -n 1)
pretty="$version ($today)"
filename="bibliograph-$version"

# update version information in files
sed -i -r "s|(/\*begin-version\*/)(.+)(/\*end-version\*/)|\1\"$pretty\"\3|g" \
  ./source/class/bibliograph/Main.js \
  ./services/class/bibliograph/Application.php
  
# build the application for inclusion into the zip file
./generate.py build

echo ">>> Creating deployable ZIP-File $filename.zip ..."

# remove a preexisting zip file and create a temporary folder
rm -f ../$filename.zip
mkdir -p bibliograph

# assemble the contents
cp -a ./build ./services ../readme.md ../release-notes.md \
  bibliograph/
  
# add a version file
echo "$pretty" > ./bibliograph/version.txt
  
# remove what shouldn't go in there  
rm -rf \
  ./bibliograph/services/config/bibliograph.ini.php \
  ./bibliograph/services/config/server.conf.php \
  ./bibliograph/services/class/qcl/test/

# create the zip file
zip -qr ../$filename.zip ./bibliograph/

# remove the temporary folder
rm -rf ./bibliograph

# remove version information from source code
sed -i -r "s|(/\*begin-version\*/)(.+)(/\*end-version\*/)|\1\"Development version\"\3|g" \
  ./source/class/bibliograph/Main.js \
  ./services/class/bibliograph/Application.php