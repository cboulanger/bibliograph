#!/usr/bin/env bash
TOP_DIR=$(pwd)
VERSION=$(node -p -e "require('$TOP_DIR/package.json').version")
for tagName in $(git tag | grep 'alpha\|beta')
do
  if [[ "$tagName" != "$VERSION" ]]
  then
    git push --delete origin $tagName
    git tag -d $tagName
  fi
done