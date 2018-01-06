#!/bin/bash

## list of git clone targets
declare -a arr=(
  "https://github.com/qooxdoo/qooxdoo.git" 
  "https://github.com/qooxdoo/qooxdoo-cli.git" 
  "https://github.com/qooxdoo/qooxdoo-compiler.git"
)

for url in "${arr[@]}"
do
  repo=$(echo $url | sed -e 's/\.git$//' | sed -e 's|https://github\.com/||')
  dir=$(basename $repo )
  if [ -d "$dir" ]; then
    cd $dir
    git pull
    cd ..
  else
    git clone $url --depth 10
  fi
done