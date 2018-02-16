#!/bin/bash

## list of git clone targets
declare -a arr=(
  "qooxdoo/qooxdoo" 
  "qooxdoo/qooxdoo-compiler"
)
for repo in "${arr[@]}"
do
  dir=$(basename $repo)
  if [ -d "$dir" ]; then
    cd $dir
    git pull
    npm install
    cd ..
  else
    if [[ "$OSTYPE" == "darwin"* ]]; then
      # if on mac, assume dev workstation with git credentials
      uri="git@github.com:$repo.git"
      git clone $uri
    else
      # otherwise, just clone a shallow read-only copy 
      uri="https://github.com/$repo.git"
      git clone $uri --depth 1
    fi
    npm install
  fi
done

# link qooxdoo-compiler development version
cd qooxdoo-compiler
npm link
cd ..