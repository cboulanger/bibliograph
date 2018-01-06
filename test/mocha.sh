#!/bin/bash

#set -o errexit # Exit on error
SERVER_PATH=src/server

echo "Loading data ..."
echo "travis_fold:start:data"
pushd $SERVER_PATH > /dev/null
php yii migrate/up --interactive=0 --db=testdb -p=@app/migrations/data
echo "travis_fold:end:data"
popd > /dev/null

echo "Running Mocha tests..."
mocha -- ./test/**/*.test.js