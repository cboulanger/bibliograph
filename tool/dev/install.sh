#!/bin/bash
# Install compile-time dependencies

# read environment variables
source ./.env || (echo "Script must be called from root dir" && exit 1)

# check for docker
if ! type docker > /dev/null; then
  echo "ERROR: You need docker for the custom PHP environment"
  exit 1
fi

# install multi-version php docker image
echo " >>> Downloading Docker PHPFarm image"
docker pull cboulanger/docker-phpfarm

# install composer locally
echo " >>> Installing composer locally..."
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --install-dir=./src/lib #--quiet
RESULT=$?
rm composer-setup.php
if [ $RESULT -eq 1 ]; then
  >&2 echo "ERROR: Failed to setup composer ..."
  exit 1
fi

## list of git clone targets
echo " >>> Installing github repositories..."
declare -a arr=(
  "cboulanger/yii2-json-rpc-2.0"
  "cboulanger/raptor-client"
  "cboulanger/dsn"
  "cboulanger/worldcat-linkeddata-php"
  "serratus/quaggaJS"
)
for repo in "${arr[@]}"
do
  dir=$(basename $repo)
  if [ -d "$dir" ]; then
    echo "Updating $repo..."
    cd $dir
    git pull
    [[ -f package.json ]] && npm install
    cd ..
  else
    echo "Checking out $repo..."
    uri="https://github.com/$repo.git"
    git clone $uri --depth 1
    cd $dir
    [[ -f package.json ]] && npm install --only=prod && npm audit fix
    cd ..
  fi
done
