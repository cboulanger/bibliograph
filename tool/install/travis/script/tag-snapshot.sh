#!/usr/bin/env bash
# only ever have one snapshot release of each branch
git tag -f $TRAVIS_BRANCH-snapshot
git remote add gh https://${TRAVIS_REPO_SLUG%/*}:${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG}.git
git push -f gh $TRAVIS_BRANCH-snapshot
git remote remove gh
