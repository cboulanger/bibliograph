#!/bin/bash

## list of git clone targets
declare -a arr=(
  "git@github.com:qooxdoo/qooxdoo.git" 
  "git@github.com:qooxdoo/qooxdoo-cli.git" 
  "git@github.com:qooxdoo/qooxdoo-compiler.git"
)

for url in "${arr[@]}"
do
  repo=$(echo $url | sed -e 's/\.git$//' | sed -e 's|https://github\.com/||')
  dir=$(basename $repo )
  if [ -d "$dir" ]; then
    cd $dir
    git pull
    npm install
    cd ..
  else
    git clone $url --depth 10
    npm install
  fi
done

# link development versions
cd qooxdoo-cli
npm link
npm link ../qooxdoo-compiler
cd ..