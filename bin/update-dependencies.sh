#!/bin/bash
echo " >>> Updating contribs..."
pushd src/client/bibliograph >/dev/null
qx contrib update
qx contrib install
popd >/dev/null

echo " >>> Updating composer packages..."
pushd src/server >/dev/null
composer update
pushd vendor >/dev/null
[[ -d bower ]] || ln -s bower-asset/ bower
popd >/dev/null
popd >/dev/null

echo " >>> Updating cloned GitHub repos..."
pushd src/vcslib >/dev/null
bash install.sh
popd >/dev/null
