#!/bin/bash

## list of git clone targets
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
#    if [[ "$OSTYPE" == "darwin"* ]]; then
#      # if on mac, assume dev workstation with git credentials
#      uri="git@github.com:$repo.git"
#      git clone $uri
#    else
      # otherwise, just clone a shallow read-only copy
      uri="https://github.com/$repo.git"
      git clone $uri --depth 1
#    fi
    cd $dir
    [[ -f package.json ]] && npm install --only=prod && npm audit fix
    cd ..
  fi
done
