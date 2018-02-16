#!/bin/bash

set -o errexit # Exit on error
SERVER_PATH=src/server
YIICMD="php yii-test"

echo "Setting up database ..."
pushd $SERVER_PATH > /dev/null
$YIICMD migrate/fresh --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema > /dev/null
$YIICMD migrate/up    --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\data > /dev/null
popd > /dev/null

echo "Running Mocha tests..."
mocha -- ./test/**/*.test.js

echo "Cleaning up database ..."
pushd $SERVER_PATH > /dev/null
$YIICMD migrate/down all --interactive=0 --db=testdb --migrationNamespaces=app\\migrations\\schema > /dev/null
popd > /dev/null