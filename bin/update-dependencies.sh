#!/bin/bash

npm install
pushd src/server
composer update
pushd vendor
[[ -d bower ]] || ln -s bower-asset/ bower
popd
popd
pushd src/vcslib
bash install.sh
popd
