#!/bin/bash

npm install
pushd src/server
composer update
popd
pushd src/vcslib
bash install.sh
popd
