#!/bin/bash

pushd src/server
echo "Updating composer packages..."
composer update
pushd vendor
[[ -d bower ]] || ln -s bower-asset/ bower
popd
popd
pushd src/vcslib
bash install.sh
popd
