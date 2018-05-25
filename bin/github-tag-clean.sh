#!/usr/bin/env bash
for tagName in $(git tag | grep 'alpha\|beta')
do
  git push --delete origin $tagName
  git tag -d $tagName
done