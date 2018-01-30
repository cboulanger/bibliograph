#!/bin/bash

#set -o errexit # Exit on error
SERVER_PATH=src/server

echo "Setting up database ..."
echo "travis_fold:start:migrate_up"
pushd $SERVER_PATH > /dev/null
php yii migrate/up --interactive=0 --db=testdb -p=@app/migrations/data
echo "travis_fold:end:migrate_up"
popd > /dev/null

echo "Running Mocha tests..."
mocha -- ./test/**/*.test.js

echo "Clearing database ..."
echo "travis_fold:start:migrate_down"
pushd $SERVER_PATH > /dev/null
php yii migrate/down --interactive=0 --db=testdb -p=@app/migrations/data
popd > /dev/null
echo "travis_fold:end:migrate_down"