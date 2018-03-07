#!/bin/bash

## list of git clone targets
declare -a arr=(
  "qooxdoo/qooxdoo" 
  "qooxdoo/qooxdoo-compiler"
  "cboulanger/yii2-json-rpc-2.0"
  "cboulanger/raptor-client"
  "cboulanger/qx-contrib-Dialog"
  "cboulanger/qx-contrib-TokenField"
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
    if [[ "$OSTYPE" == "darwin"* ]]; then
      # if on mac, assume dev workstation with git credentials
      uri="git@github.com:$repo.git"
      git clone $uri
    else
      # otherwise, just clone a shallow read-only copy 
      uri="https://github.com/$repo.git"
      git clone $uri --depth 1
    fi
    cd $dir
    [[ -f package.json ]] && npm install
    cd ..
  fi
done

# link qooxdoo-compiler development version
cd qooxdoo-compiler
npm link
cd ..

# use specific branches
cd yii2-json-rpc-2.0
git checkout cboulanger-empty-request-object
cd ..